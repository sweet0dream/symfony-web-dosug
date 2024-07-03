<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\UserHash;
use Doctrine\ORM\EntityManagerInterface;

class UserItemHelper
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function isItemHasUser(
        int $itemId,
        ?string $hashUser
    ): bool
    {
        $user = !is_null($hashUser)
            ? $this->em->getRepository(UserHash::class)->findOneBy(['value' => $hashUser])?->getUser()
            : false;
        $item = $this->em->getRepository(Item::class)->find($itemId);

        return $user && $item && $item->getUser()->getId() === $user->getId();
    }
}