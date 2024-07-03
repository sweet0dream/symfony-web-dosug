<?php

namespace App\Entity;

use App\Repository\ItemStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemStatusRepository::class)]
class ItemStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'itemStatus', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column]
    private ?bool $premium = null;

    #[ORM\Column(nullable: true)]
    private ?int $premium_priority = null;

    #[ORM\Column]
    private ?bool $realy = null;

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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isPremium(): ?bool
    {
        return $this->premium;
    }

    public function setPremium(bool $premium): static
    {
        $this->premium = $premium;

        return $this;
    }

    public function getPremiumPriority(): ?int
    {
        return $this->premium_priority;
    }

    public function setPremiumPriority(?int $premium_priority): static
    {
        $this->premium_priority = $premium_priority;

        return $this;
    }

    public function isRealy(): ?bool
    {
        return $this->realy;
    }

    public function setRealy(bool $realy): static
    {
        $this->realy = $realy;

        return $this;
    }

    public function setDefaultValue(): static
    {
        $this->setActive(false);
        $this->setPremium(false);
        $this->setPremiumPriority(null);
        $this->setRealy(false);

        return $this;
    }
}
