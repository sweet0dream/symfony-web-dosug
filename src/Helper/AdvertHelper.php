<?php

namespace App\Helper;

use App\Entity\Advert;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class AdvertHelper {

    public const array SECTION_PUBLISH = [
        'main' => 'Главная',
        'ind' => 'Индивидуалки',
        'sal' => 'Салоны',
        'man' => 'Мужчины',
        'tsl' => 'Трансы',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/advert')]
        private readonly string $advertDir,
    )
    {
    }

    public function getItems(?string $type = null): array
    {
        $items = $this->em->getRepository(Advert::class)->findAll();

        return is_null($type)
            ? $items
            : array_filter(
                array_map(
                    fn($value) => in_array($type, $value->getSection()) ? $value : null,
                    $items
                )
            );
    }

    public function mapAction(array $data): void
    {
        $action = $data['action'];
        unset($data['action']);
        switch($action) {
            case 'upload':
                $this->uploadItems($data);
                break;
            case 'update':
                $this->updateItem($data);
                break;
            case 'remove':
                unset($data['section']);
                $this->removeItem($data);
                break;
        }
    }

    private function generateFilename(): string
    {
        return str_replace(
            '=',
            '',
            md5(
                rand(11111, 99999)
            ) . base64_encode(
                rand(11111, 99999)
            )
        );
    }

    private function uploadItems(array $files): void
    {
        if (!is_dir($this->advertDir)) {
            mkdir($this->advertDir);
        }

        $saveItems = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filename = $this->generateFilename();
                $file->move(
                    $this->advertDir,
                    $filename . '.' . $file->guessExtension()
                );
                $saveItems[] = $filename;
            }
        }

        if (!empty($saveItems)) {
            foreach ($saveItems as $saveItem) {
                $this->em->persist(
                    (new Advert())
                        ->setFilename($saveItem)
                        ->setSection(null)
                        ->setCreatedAt((new DateTimeImmutable('now')))
                );
            }
            $this->em->flush();
        }
    }

    private function updateItem(array $data): void
    {
        $item = $this->em->getRepository(Advert::class)->findOneBy(['id' => $data['id']]);

        $this->em->persist(
            $item->setSection($data['section'] ?? [])
        );
        $this->em->flush();
    }

    private function removeItem(array $data): void
    {
        $item = $this->em->getRepository(Advert::class)->findOneBy(['id' => $data['id']]);

        if (unlink($this->advertDir . '/' . $item->getFilename() . '.webp')) {
            $this->em->remove($item);
            $this->em->flush();
        }
    }
}