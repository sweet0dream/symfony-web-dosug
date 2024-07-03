<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 3)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $info = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $service = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $price = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $topedAt = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'item', cascade: ['persist', 'remove'])]
    private ?ItemStatus $itemStatus = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getInfo(): ?array
    {
        return json_decode($this->info, true);
    }

    public function setInfo(array $info): static
    {
        $this->info = json_encode($info);

        return $this;
    }

    public function getService(): ?array
    {
        return json_decode($this->service, true);
    }

    public function setService(array $service): static
    {
        $this->service = json_encode($service);

        return $this;
    }

    public function getPhoto(): ?array
    {
        return $this->photo ? explode(',', $this->photo) : null;
    }

    public function getPhotoWithPath(): array
    {
        return array_map(
            fn($photo) => '/media/' . $this->getId() . '/src/' . $photo,
            $this->getPhoto()
        );
    }

    public function setPhoto(array $photo): static
    {
        $this->photo = implode(',', $photo);

        return $this;
    }

    public function getPrice(): ?array
    {
        return json_decode($this->price, true);
    }

    public function setPrice(array $price): static
    {
        $this->price = json_encode(
            array_filter(
                $price,
                fn($v) => !is_null($v) && $v !== ''
            )
        );

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTopedAt(): ?DateTimeImmutable
    {
        return $this->topedAt;
    }

    public function setTopedAt(DateTimeImmutable $topedAt): static
    {
        $this->topedAt = $topedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getItemStatus(): ?ItemStatus
    {
        return $this->itemStatus;
    }

    public function setItemStatus(ItemStatus $itemStatus): static
    {
        // set the owning side of the relation if necessary
        if ($itemStatus->getItem() !== $this) {
            $itemStatus->setItem($this);
        }

        $this->itemStatus = $itemStatus;

        return $this;
    }
}
