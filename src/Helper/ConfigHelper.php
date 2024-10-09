<?php

namespace App\Helper;

use App\Entity\Config;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

readonly class ConfigHelper {

    public function __construct(
        private EntityManagerInterface $em,
        private ConfigRepository $configRepository,
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $publicDir,
    )
    {
    }

    public function loadConfig(): ?Config
    {
        if (!file_exists($this->publicDir.'/config.yaml')) {
            file_put_contents($this->publicDir . '/config.yaml', Yaml::dump($this->configRepository->load()));
        }

        $config = (new Serializer([new ObjectNormalizer()]))
            ->denormalize(
                Yaml::parseFile($this->publicDir . '/config.yaml'),
                Config::class,
            );

        return $config instanceof Config ? $config : null;
    }

    public function getSiteName(): string
    {
        return $this->loadConfig()->getSiteName();
    }

    public function getCities(): array
    {
        return explode(',', $this->loadConfig()->getCities());
    }

    public function updateConfig(array $data): void
    {
        $currentConfig = $this->publicDir.'/config.yaml';

        if (file_exists($currentConfig)) {
            unlink($currentConfig);
        }

        $this->em->persist(
            $this
                ->configRepository->find(1)
                ->setSiteName($data['site_name'])
                ->setMaxPhotoUpload($data['max_photo_upload'])
                ->setCities(implode(',', $data['cities']))
        );
        $this->em->flush();
    }

}