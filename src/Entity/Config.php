<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $siteName = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxPhotoUpload = null;

    #[ORM\Column(nullable: true)]
    private ?string $cities = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getMaxPhotoUpload(): ?int
    {
        return $this->maxPhotoUpload;
    }

    public function setMaxPhotoUpload(?int $maxPhotoUpload): static
    {
        $this->maxPhotoUpload = $maxPhotoUpload;

        return $this;
    }

    public function getCities(): ?string
    {
        return $this->cities;
    }

    public function setCities(string $cities): static
    {
        $this->cities = $cities;

        return $this;
    }
}
