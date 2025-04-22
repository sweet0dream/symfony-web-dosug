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
        #[Autowire('%kernel.cache_dir%/config.yaml')]
        private string $configFile,
    )
    {
    }

    public function loadConfig(): ?Config
    {
        if (!file_exists($this->configFile)) {
            file_put_contents($this->configFile, Yaml::dump($this->configRepository->load()));
        }

        $config = (new Serializer([new ObjectNormalizer()]))
            ->denormalize(
                Yaml::parseFile($this->configFile),
                Config::class,
            );

        return $config instanceof Config ? $config : null;
    }

    public function getSiteName(): string
    {
        return $this->loadConfig()->getSiteName();
    }

    public function getMaxPhotoUpload(): int
    {
        return $this->loadConfig()->getMaxPhotoUpload();
    }

    public function getCities(): array
    {
        return explode(',', $this->loadConfig()->getCities());
    }

    public function getDistricts(): array
    {
        return $this->loadConfig()->getDistrictsCity();
    }

    public function updateConfig(array $data): void
    {
        $currentConfig = $this->configFile;

        if (file_exists($currentConfig)) {
            unlink($currentConfig);
        }

        $config = $this->configRepository->find(1);

        $config
            ->setSiteName($data['site_name'])
            ->setMaxPhotoUpload($data['max_photo_upload'])
            ->setCities(implode(',', $data['cities']))
        ;
        if (isset($data['districts'])) {
            $config->setDistrictsCity(array_merge($config->getDistrictsCity(), $data['districts']));
        }

        $this->em->persist($config);
        $this->em->flush();
    }

}