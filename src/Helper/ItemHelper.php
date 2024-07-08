<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemPhoto;
use App\Entity\ItemStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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

    private const RENDER_TYPE_INTRO = 'intro';
    private const FILTER_FIELDS_INTRO = [
        'name',
        'photo_main',
        'info',
        'price',
        'text'
    ];
    private const RENDER_TYPE_PREMIUM = 'premium';
    private const FILTER_FIELDS_PREMIUM = [
        'name',
        'photo_main',
        'phone',
        'info',
        'service',
        'price'
    ];
    private const RENDER_TYPE_FULL = 'full';

    private const array RENDER_LIST = [
        self::RENDER_TYPE_INTRO => self::FILTER_FIELDS_INTRO,
        self::RENDER_TYPE_PREMIUM => self::FILTER_FIELDS_PREMIUM
    ];

    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/media')]
        private readonly string $mediaDir,
        private ?string $type = null,
        private ?Item $item = null
    )
    {
    }

    public function getActiveItems($type = null, $premium = false): array
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

        return $result ?? [];
    }

    public function getOneItem(
        string $type,
        int $id
    ): array|false
    {
        $item = $this->em->getRepository(Item::class)->find($id);

        return !is_null($item) || $type === $item->getType() ? $item : false;
    }

    private function prepareItem(?string $prepare = null): array
    {
        $data = [
            'name' => $this->item->getName(),
            'phone' => $this->item->getPhone(),
            'info' => $this->getInfoValue(),
            'service' => $this->getServiceValue(),
            'photo_main' => $this->getMainPhoto(),
            'photo' => $this->getPhotoValue(),
            'price' => $this->getPriceValue(),
            'text' => $this->item->getInfo()['text']
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
                $this->em->remove($itemPhoto);
            }
            $this->em->flush();
        }

        return true;
    }

    public function mainPhoto(
        int $id,
        string $file,
    )
    {
        $item = $this->em->getRepository(Item::class)->find($id);

        foreach ($item->getItemPhotos() as $photo) {
            $photo->setHasMain(
                $photo->getFileName() == $file ? !$photo->hasMain() : false
            );
        }

        $this->em->persist($item);
        $this->em->flush();

        return true;
    }

    private function checkAndRemoveEmptyPath(string $path): void
    {
        $isDirEmpty = !(new FilesystemIterator($path . '/src'))->valid();

        if ($isDirEmpty) {
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

        if (!file_exists($filename) || !unlink($filename)) {
            return null;
        }

        $this->checkAndRemoveEmptyPath($path);

        return $file;
    }
}