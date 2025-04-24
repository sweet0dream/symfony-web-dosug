<?php

namespace App\Entity;

use App\Repository\ItemWorkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemWorkRepository::class)]
class ItemWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'itemWork', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\Column]
    private ?int $fromTime = null;

    #[ORM\Column]
    private ?int $toTime = null;

    #[ORM\Column]
    private ?bool $isForce = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getFromTime(): ?int
    {
        return $this->fromTime;
    }

    public function setFromTime(int $fromTime): static
    {
        $this->fromTime = $fromTime;

        return $this;
    }

    public function getToTime(): ?int
    {
        return $this->toTime;
    }

    public function setToTime(int $toTime): static
    {
        $this->toTime = $toTime;

        return $this;
    }

    public function isForce(): ?bool
    {
        return $this->isForce;
    }

    public function setForce(bool $isForce): static
    {
        $this->isForce = $isForce;

        return $this;
    }
}
