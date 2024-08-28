<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemPhoto;
use App\Entity\ItemStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FilesystemIterator;
use Sweet0dream\IntimAnketaContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        'url'
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
        'url'
    ];

    private const array RENDER_LIST = [
        self::RENDER_TYPE_INTRO => self::FILTER_FIELDS_INTRO,
        self::RENDER_TYPE_PREMIUM => self::FILTER_FIELDS_PREMIUM
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
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
            $this->em->getRepository(Item::class)->findBy(['type' => self::TYPE[$type]]) :
            $this->em->getRepository(Item::class)->findAll();

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
            'id' => $id]
        );

        if ($item) {
            $this->item = $item;
            $this->type = $item->getType() ?? $type;
        }

        return $item ? $this->prepareItem() : null;
    }

    private function prepareItem(?string $prepare = null): array
    {
        $data = [
            'type' => $this->getTypeValue(),
            'name' => $this->item->getName(),
            'phone' => $this->item->getPhone(),
            'info' => $this->getInfoValue(),
            'service' => $this->getServiceValue(),
            'photo_main' => $this->getMainPhoto(),
            'photo' => $this->getPhotoValue(),
            'price' => $this->getPriceValue(),
            'text' => $this->item->getInfo()['text'],
            'priority' => $this->item->getItemStatus()->getPremiumPriority(),
            'online' => (bool)$this->item->getUser()->getUserHash()?->getId(),
            'url' => $this->generateUrl()
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

        if (!empty($photos)) {
            $filename = array_values(
                array_filter(
                    array_map(
                        fn(ItemPhoto $photo) => $photo->hasMain() ? $photo->getFileName() : null,
                        $photos->toArray()
                    )
                )
            )[0] ?? $photos->last()->getFilename();
        }

        return isset($filename) ? $this->getPathPhoto($filename, $this->item->getId()) : null;
    }

    private function getPhotoValue(): array
    {
        $result = [];
        foreach ($this->item->getItemPhotos() as $photo) {
            $result[] = $this->getPathPhoto($photo->getFileName(), $this->item->getId());
        }

        return $result;
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

    public function addItem(
        array $item,
        User $user
    ): int
    {
        $now = new DateTimeImmutable('now');
        $newItem = new Item();
        $newItem
            ->setUser($user)
            ->setPhone($item['phone'])
            ->setName($item['name'])
            ->setType($item['type'])
            ->setInfo($item['info'])
            ->setService($item['service'])
            ->setPrice($item['price'])
            ->setItemStatus((new ItemStatus())->setDefaultValue())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setTopedAt($now)
        ;
        $this->em->persist($newItem);
        $this->em->flush();

        return $newItem->getId();
    }

    public function uploadPhoto(
        int $id,
        array $photos
    ): bool
    {
        $dateUploaded = (new DateTimeImmutable('now'))->format('Ymd');
        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $saveFile = $dateUploaded . '_' . base64_encode(rand(111,999)) . '.' . $photo->guessExtension();
                $photo->move(
                    $this->mediaDir . '/' . $id . '/src',
                    $saveFile
                );
                $uploadedPhotos[] = $saveFile;
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
        if (is_array($file)) {
            foreach ($file as $oneFile) {
                $removePhotos[] = $this->removeFilename($id, $oneFile);
            }
        } else {
            $removePhotos[] = $this->removeFilename($id, $file);
        }

        if (!empty($removePhotos)) {
            $item = $this->em->getRepository(Item::class)->find($id);
            foreach ($removePhotos as $removePhoto) {
                $itemPhoto = $this->em->getRepository(ItemPhoto::class)->findOneBy(['fileName' => $removePhoto]);
                if (!is_null($itemPhoto)) {
                    $this->em->remove($itemPhoto);
                }
            }
            $this->em->flush();
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
        $filename = $path . '/src/' . $file;

        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->checkAndRemoveEmptyPath($path);

        return $file;
    }
}