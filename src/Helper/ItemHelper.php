<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemPhoto;
use App\Entity\ItemStatus;
use App\Entity\User;
use CodeBuds\WebPConverter\WebPConverter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FilesystemIterator;
use Sweet0dream\IntimAnketaContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ItemHelper {

    public const array TYPE = [
        'individualki' => 'ind',
        'salony' => 'sal',
        'muzhskoi-eskort' => 'man',
        'transseksualki' => 'tsl'
    ];

    public const array TERM_TYPE = [
        'ind' => 'Индивидуалка',
        'sal' => 'Салон',
        'man' => 'Мужчина по вызову',
        'tsl' => 'Транссексуалка'
    ];

    private const string RENDER_TYPE_INTRO = 'intro';
    private const array FILTER_FIELDS_INTRO = [
        'name',
        'photo_main',
        'info',
        'price',
        'text',
        'url',
        'realy'
    ];
    private const string RENDER_TYPE_PREMIUM = 'premium';
    private const array FILTER_FIELDS_PREMIUM = [
        'priority',
        'online',
        'type',
        'name',
        'photo_main',
        'phone',
        'info',
        'price',
        'url',
        'realy'
    ];
    private const string RENDER_TYPE_USER_LK = 'lk';
    private const array FILTER_FIELDS_USER_LK = [
        'id',
        'type',
        'name',
        'photo_main',
        'photo_count',
        'phone',
        'info',
        'service',
        'price',
        'text',
        'date',
        'status',
        'url',
        'events',
        'editable',
        'eligible_activation'
    ];

    private const array RENDER_LIST = [
        self::RENDER_TYPE_INTRO => self::FILTER_FIELDS_INTRO,
        self::RENDER_TYPE_PREMIUM => self::FILTER_FIELDS_PREMIUM,
        self::RENDER_TYPE_USER_LK => self::FILTER_FIELDS_USER_LK
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventHelper $eventHelper,
        #[Autowire('%kernel.project_dir%/public/media')]
        private readonly string $mediaDir,
        private ?string $type = null,
        private ?Item $item = null
    )
    {
    }

    public function getActiveItems($type = null): array
    {
        $items = $type && isset(self::TYPE[$type]) ?
            $this->em->getRepository(Item::class)->findBy(['type' => self::TYPE[$type]], ['topedAt' => 'DESC']) :
            $this->em->getRepository(Item::class)->findBy([], ['topedAt' => 'DESC']);

        foreach ($items as $item) {
            if ($item->getItemStatus()->isActive()) {
                $this->item = $item;
                $this->type = $item->getType();
                $render = array_keys(self::RENDER_LIST)[(int)$item->getItemStatus()->isPremium()];
                $result[$render][] = $this->prepareItem($render);
            }
        }

        if (!empty($result[self::RENDER_TYPE_PREMIUM])) {
            $result[self::RENDER_TYPE_PREMIUM] = $this->sortablePremiumPriority($result[self::RENDER_TYPE_PREMIUM]);
        }

        return $result ?? [];
    }

    private function sortablePremiumPriority(array $premiumItems): array
    {
        foreach ($premiumItems as $premiumItem) {
            if (is_null($premiumItem['priority'])) {
                $result['unsort'][] = $premiumItem;
            } else {
                $result['sort'][$premiumItem['priority']] = $premiumItem;
            }
        }

        $count = 0;
        if (isset($result['sort'])) {
            $count += count($result['sort']);
        }
        if (isset($result['unsort'])) {
            $count += count($result['unsort']);
            shuffle($result['unsort']);
        }

        foreach (range(1, $count) as $key) {
            $combine[$key] = $result['sort'][$key] ?? array_shift($result['unsort']);
        }

        return array_filter($combine);
    }

    public function getOneItem(
        string $type,
        int $id
    ): ?array
    {
        $item = $this->em->getRepository(Item::class)->findOneBy([
            'type' => ItemHelper::TYPE[$type],
            'id' => $id
        ]);

        if ($item) {
            $this->item = $item;
            $this->type = $item->getType() ?? $type;
        }

        return $item ? $this->prepareItem() : null;
    }

    public function getAllItemsForUser(User $user): array
    {
        foreach ($user->getItems() as $item) {
            $this->item = $item;
            $this->type = $item->getType();
            $allItems[] = $this->prepareItem(self::RENDER_TYPE_USER_LK);
        }

        return $allItems ?? [];
    }

    private function prepareItem(?string $prepare = null): array
    {
        $data = [
            'id' => $this->item->getId(),
            'eligible_activation' => count($this->item->getItemPhotos()),
            'type' => $this->getTypeValue(),
            'name' => $this->item->getName(),
            'phone' => $this->item->getPhone(),
            'info' => $this->getInfoValue(),
            'service' => $this->getServiceValue(),
            'photo_count' => count($this->item->getItemPhotos()),
            'photo_main' => !is_null($this->getMainPhoto())
                ? $this->getPathPhoto($this->getMainPhoto(), $this->item->getId())
                : null,
            'photo' => $this->getPhotoValue(),
            'price' => $this->getPriceValue(),
            'text' => $this->item->getInfo()['text'] ?? '',
            'priority' => $this->item->getItemStatus()->getPremiumPriority(),
            'online' => (bool)$this->item->getUser()->getUserHash()?->getId(),
            'url' => $this->generateUrl(),
            'date' => [
                'created' => $this->item->getCreatedAt(),
                'updated' => $this->item->getUpdatedAt(),
                'toped' => $this->item->getTopedAt()
            ],
            'status' => $this->getStatuses(),
            'realy' => $this->item->getItemStatus()->isRealy(),
            'events' => $this->item->getEvents(),
            'editable' => $this->getEditableItem()
        ];

        return is_null($prepare) ? $data : array_combine(
            self::RENDER_LIST[$prepare],
            array_map(
                function($filter) use ($data) {
                    return $data[$filter];
                },
                self::RENDER_LIST[$prepare]
            )
        );
    }

    private function getEditableItem(): array
    {
        $fields = (new IntimAnketaContract($this->type))->getField();
        unset($fields['info']['name']);

        $info = [];
        foreach ($this->item->getInfo() as $k => $v) {
            if (isset($fields['info'][$k])) {
                $info[$k] = $v;
            }
        }

        return [
            'contact' => [
                'name' => $this->item->getName(),
                'phone' => $this->item->getPhone()
            ],
            'info' => [
                'fields' => $fields['info'],
                'values' => $info
            ],
            'service' => [
                'fields' => $fields['service'],
                'values' => $this->item->getService()
            ],
            'price' => [
                'fields' => $fields['price'],
                'values' => $this->item->getPrice()
            ],
            'text' => [
                'fields' => $fields['dop'],
                'values' => $this->item->getInfo()['text'] ?? ''
            ]
        ];
    }

    private function getStatuses(): array
    {
        $result['active'] = $this->item->getItemStatus()->isActive();
        if ($result['active']) {
            $result['premium'] = $this->item->getItemStatus()->isPremium();
            if ($result['premium']) {
                $result['premium_priority'] = $this->item->getItemStatus()->getPremiumPriority();
            }
            $result['realy'] = $this->item->getItemStatus()->isRealy();
        }

        return $result;
    }

    private function generateUrl(): string
    {
        return '/' . array_flip(self::TYPE)[$this->type] . '/id' . $this->item->getId();
    }

    private function getTypeValue(): array
    {
        return [
            'key' => $this->item->getType(),
            'value' => self::TERM_TYPE[$this->item->getType()]
        ];
    }

    private function getMainPhoto(): ?string
    {
        $photos = $this->item->getItemPhotos();

        $selectMainPhoto = array_values(
            array_filter(
                array_map(
                    fn(ItemPhoto $photo) => $photo->hasMain() ? $photo->getFileName() : null,
                    $photos->toArray()
                )
            )
        );

        return !empty($selectMainPhoto)
            ? $selectMainPhoto[0]
            : ($photos->last() ? $photos->last()->getFileName() : null);
    }

    private function getPhotoValue(): array
    {
        return array_filter(
            array_map(
                fn($photo) => $this->getMainPhoto() != $photo->getFileName()
                    ? $this->getPathPhoto($photo->getFileName(), $this->item->getId())
                    : null,
                $this->item->getItemPhotos()->toArray()
            )
        );
    }

    private function getPathPhoto(string $filename, int $itemId): string
    {
        return '/media/' . $itemId . '/src/' . $filename;
    }

    private function getInfoValue(): array
    {
        $result = [];
        $term = (new IntimAnketaContract($this->type))->getField()['info'];
        foreach ($this->item->getInfo() as $infoKey => $infoValue) {
            if (isset($term[$infoKey]['value'][$infoValue])) {
                $result[$infoKey] = [
                    'name' => $term[$infoKey]['name'],
                    'value' => $term[$infoKey]['value'][$infoValue],
                ];
            }
        }

        return $result;
    }

    private function getServiceValue(): array
    {
        $result = [];
        $term = (new IntimAnketaContract($this->type))->getField()['service'];
        foreach ($this->item->getService() as $serviceKey => $serviceValue) {
            if (is_array($serviceValue)) {
                foreach ($serviceValue as $value) {
                    $result[$serviceKey]['name'] = $term[$serviceKey]['name'];
                    $result[$serviceKey]['value'][] = $term[$serviceKey]['value'][$value];
                }
            }
        }

        return $result;
    }

    private function getPriceValue(): array
    {
        $result = [];
        $term = (new IntimAnketaContract($this->type))->getField()['price'];
        foreach ($this->item->getPrice() as $priceKey => $priceValue) {
            $result[$priceKey] = [
                'name' => $term[$priceKey]['name'],
                'value' => $priceValue
            ];
        }

        return $result;
    }

    public function getPhoto(int $id): ?array
    {
        return $this->em->getRepository(Item::class)->find($id)->getItemPhotos()->toArray();
    }

    public function hasMainPhoto(int $id): bool
    {
        foreach ($this->getPhoto($id) as $checkMainPhoto) {
            if ($checkMainPhoto->hasMain()) {
                return true;
            }
        }

        return false;
    }

    public function createItem(
        array $data,
        User $user
    ): int
    {
        $now = new DateTimeImmutable('now');
        $newItem = new Item();
        $newItem
            ->setUser($user)
            ->setPhone(CommonHelper::getPhoneFormat('+7' . $data['phone']))
            ->setName($data['name'])
            ->setType($data['type'])
            ->setInfo($data['info'])
            ->setService($data['service'])
            ->setPrice($data['price'])
            ->setItemStatus((new ItemStatus())->setDefaultValue())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setTopedAt($now)
        ;

        $this->em->persist($newItem);
        $this->em->flush();

        return $newItem->getId();
    }

    public function updateItem(
        Item $item,
        array $data
    ): int
    {
        $item
            ->setPhone(CommonHelper::getPhoneFormat('+7' . $data['phone']))
            ->setName($data['name'])
            ->setInfo($data['info'])
            ->setService($data['service'])
            ->setPrice($data['price'])
            ->setUpdatedAt(new DateTimeImmutable('now'))
        ;

        $this->em->persist($item);
        $this->em->flush();

        $this->eventHelper->addEvent($item, [
            'updated_item_by_admin' => [
                'id' => $item->getId()
            ]
        ]);

        return $item->getId();
    }

    public function updateItemPartially(
        Item $item,
        string $key,
        array $data
    ): array
    {
        switch ($key) {
            case 'contact':
                $item
                    ->setName($data['name'])
                    ->setPhone(CommonHelper::getPhoneFormat('+7' . $data['phone']))
                ;
                break;
            case 'info':
                $item->setInfo(isset($data['text'])
                    ? array_merge($item->getInfo(), (array)$data)
                    : array_merge($data, ['text' => $item->getInfo()['text']])
                );
                $action = isset($data['text']) ? 'text' : 'info';
                break;
            case 'service':
                $item->setService($data);
                break;
            case 'price':
                $item->setPrice($data);
                break;
        }

        $item->setUpdatedAt(new DateTimeImmutable('now'));

        $this->em->persist($item);
        $this->em->flush();

        return [
            'updated_item_by_user' => [
                'id' => $item->getId(),
                'action' => $action ?? $key
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function uploadPhoto(
        int $id,
        array $photos
    ): bool
    {
        $dateUploaded = (new DateTimeImmutable('now'))->format('Ymd');
        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {

                $saveFile = $dateUploaded . '_' . base64_encode(rand(111,999));
                $extensionFile = $photo->guessExtension();
                $saveSrcFile = $saveFile . '.' . $extensionFile;

                $photo->move(
                    $this->mediaDir . '/' . $id . '/src',
                    $saveSrcFile
                );

                if ($extensionFile != 'webp') {
                    WebPConverter::createWebpImage(
                        new File($this->mediaDir . '/' . $id . '/src/' . $saveSrcFile),
                        [
                            'saveFile' => true,
                            'force' => true,
                            'filename' => $saveFile,
                            'quality' => 80,
                            'savePath' => $this->mediaDir . '/' . $id . '/src'
                        ]
                    );
                }

                $uploadedPhotos[] = $saveFile . '.webp';
            }
        }

        if (!empty($uploadedPhotos)) {
            $item = $this->em->getRepository(Item::class)->find($id);
            foreach ($uploadedPhotos as $uploadedPhoto) {
                $item->addItemPhoto(
                    (new ItemPhoto())
                        ->setFileName($uploadedPhoto)
                        ->setHasMain(false)
                        ->setCreatedAt(new DateTimeImmutable('now'))
                );

                $this->eventHelper->addEvent($item, [
                    'change_photos' => [
                        'id' => $item->getId(),
                        'action' => 'added',
                        'value' => $uploadedPhoto,
                    ]
                ]);
            }

            $this->em->persist($item);
            $this->em->flush();

            return true;
        }

        return false;
    }

    public function deletePhoto(
        int $id,
        array|string $file
    ): bool
    {
        $removePhotos = is_array($file)
            ? array_map(function($oneFile) use ($id) {
                return $this->removeFilename($id, $oneFile);
            }, $file)
            : [$this->removeFilename($id, $file)];

        if (!empty($removePhotos)) {
            $this->item = $this->em->getRepository(Item::class)->find($id);

            foreach ($removePhotos as $removePhoto) {
                $itemPhoto = $this->em->getRepository(ItemPhoto::class)->findOneBy(['fileName' => $removePhoto]);
                if (!is_null($itemPhoto)) {
                    $this->em->remove($itemPhoto);
                }
            }

            $this->em->flush();

            $this->eventHelper->addEvent($this->item, [
                'change_photos' => [
                    'id' => $this->item->getId(),
                    'action' => 'removed',
                    'value' => count($removePhotos),
                ]
            ]);

            // hide item with empty photos
            if (count($this->item->getItemPhotos()) == 0) {
                $this->resetAllStatuses($this->item->getItemStatus());

                $this->eventHelper->addEvent($this->item, [
                    'change_photos' => [
                        'id' => $this->item->getId(),
                        'action' => 'reset_statuses',
                        'value' => 1
                    ]
                ]);
            }
            // !hide item with empty photos
        }

        return true;
    }

    public function mainPhoto(
        int $id,
        string $file,
    ): true
    {
        $item = $this->em->getRepository(Item::class)->find($id);

        foreach ($item->getItemPhotos() as $photo) {
            $photo->setHasMain(
                $photo->getFileName() == $file && !$photo->hasMain()
            );
        }

        $this->em->persist($item);
        $this->em->flush();

        $this->eventHelper->addEvent($item, [
            'change_photos' => [
                'id' => $item->getId(),
                'action' => 'has_main',
                'value' => $file,
            ]
        ]);

        return true;
    }

    private function checkAndRemoveEmptyPath(string $path): void
    {
        if (is_dir($path . '/src')) {
            $isDirEmpty = !(new FilesystemIterator($path . '/src'))->valid();
        }

        if (isset($isDirEmpty) && $isDirEmpty) {
            rmdir($path . '/src');
            rmdir($path);
        }
    }

    private function removeFilename(
        int $id,
        string $file
    ): ?string
    {
        $path = $this->mediaDir . '/' . $id;

        $removeFiles = array_filter(
            array_map(
                fn($scanFile) => explode('.', $scanFile)[0] == explode('.', $file)[0] ? $scanFile : null,
                array_diff(scandir($path . '/src'), ['..', '.'])
            )
        );

        if (!empty($removeFiles)) {
            foreach ($removeFiles as $removeFile) {
                if (file_exists($path . '/src/' . $removeFile)) {
                    unlink($path . '/src/' . $removeFile);
                }
            }
        }

        $this->checkAndRemoveEmptyPath($path);

        return $file;
    }

    public function resetAllStatuses(ItemStatus $itemStatus): void
    {
        $itemStatus
            ->setActive(false)
            ->setPremium(false)
            ->setRealy(false)
            ->setPremiumPriority(null)
        ;

        $this->em->persist($itemStatus);
        $this->em->flush();
    }
}