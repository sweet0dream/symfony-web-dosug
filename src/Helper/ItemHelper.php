<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FilesystemIterator;
use Sweet0dream\IntimAnketaContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class ItemHelper {

    public const array TYPE = [
        'individualki' => 'ind',
        'salony' => 'sal',
        'muzhskoi-eskort' => 'man',
        'transseksualki' => 'tsl'
    ];

    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/media')]
        private readonly string $mediaDir
    )
    {
    }

    public function getActiveItems($type = null, $premium = false): array
    {
        $items = $type && isset(self::TYPE[$type]) ?
            $this->em->getRepository(Item::class)->findBy(['type' => self::TYPE[$type]]) :
            $this->em->getRepository(Item::class)->findAll();

        foreach ($items as $item) {
            $check = $premium
                ? $item->getItemStatus()->isActive() && $item->getItemStatus()->isPremium()
                : $item->getItemStatus()->isActive();
            if ($check) {
                $result[] = $this->prepareItem($item, $premium ? 'premium' : 'intro');
            }
        }

        return $result ?? [];
    }

    private function prepareItem(Item $item, string $prepare = 'intro'): array
    {
        return match ($prepare) {
            'premium' => $this->renderPremium($item),
            'intro' => $this->renderIntro($item)
        };
    }

    private function renderPremium(Item $item): array
    {
        return [
            'name' => $item->getName(),
            'phone' => $item->getPhone(),
            'info' => $item->getInfo(),
            'service' => $item->getService(),
            'photo' => $item->getPhotoWithPath(),
            'price' => $item->getPrice()
        ];
    }

    private function renderIntro(Item $item): array
    {
        $type = $item->getType();
        return [
            'name' => $item->getName(),
            'info' => $this->getInfoValue($item->getInfo(), $type),
            'service' => $this->getServiceValue($item->getService(), $type),
            'photo' => $item->getPhotoWithPath(),
            'price' => $this->getPriceValue($item->getPrice(), $type)
        ];
    }

    private function getInfoValue(array $info, string $type): array
    {
        $result = [];
        $term = (new IntimAnketaContract($type))->getField()['info'];
        foreach ($info as $infoKey => $infoValue) {
            if (isset($term[$infoKey]['value'][$infoValue])) {
                $result[$infoKey] = [
                    'name' => $term[$infoKey]['name'],
                    'value' => $term[$infoKey]['value'][$infoValue],
                ];
            }
        }

        return $result;
    }

    private function getServiceValue(array $service, string $type): array
    {
        $result = [];
        $term = (new IntimAnketaContract($type))->getField()['service'];
        foreach ($service as $serviceKey => $serviceValue) {
            if (is_array($serviceValue)) {
                foreach ($serviceValue as $value) {
                    $result[$serviceKey]['name'] = $term[$serviceKey]['name'];
                    $result[$serviceKey]['value'][] = $term[$serviceKey]['value'][$value];
                }
            }
        }

        return $result;
    }

    private function getPriceValue(array $price, string $type): array
    {
        $result = [];
        $term = (new IntimAnketaContract($type))->getField()['price'];
        foreach ($price as $priceKey => $priceValue) {
            $result[$priceKey] = [
                'name' => $term[$priceKey]['name'],
                'value' => $priceValue
            ];
        }

        return $result;
    }

    public function getPhoto(int $id): ?array
    {
        return $this->em->getRepository(Item::class)->find($id)->getPhoto();
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
            ->setPhoto([])
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
            $item->setPhoto(
                $item->getPhoto()
                    ? array_merge($item->getPhoto(), $uploadedPhotos)
                    : $uploadedPhotos
            );
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
            $itemPhotos = $item->getPhoto();

            foreach ($removePhotos as $removePhoto) {
                unset($itemPhotos[array_search($removePhoto, $itemPhotos)]);
            }

            $item->setPhoto($itemPhotos);
            $this->em->persist($item);
            $this->em->flush();
        }

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