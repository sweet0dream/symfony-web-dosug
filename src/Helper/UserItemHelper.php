<?php

namespace App\Helper;

use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\UserHashRepository;

readonly class UserItemHelper
{
    public function __construct(
        private UserHashRepository $userHashRepository,
        private ItemRepository $itemRepository
    )
    {
    }

    public function isItemHasUser(
        int $itemId,
        ?string $hashUser
    ): bool
    {
        $user = !is_null($hashUser)
            ? $this->userHashRepository->findOneBy(['value' => $hashUser])?->getUser()
            : false;
        $item = $this->itemRepository->find($itemId);

        return $user && $item && $item->getUser()->getId() === $user->getId();
    }

    public function getItem(
        int $itemId
    ): ?Item
    {
        return $this->itemRepository->find($itemId);
    }

    public function isAdmin(?string $hashUser): bool
    {
        $user = $this->userHashRepository->findOneBy(['value' => $hashUser]);
        return $user && $user->getUser()->getId() == AdminHelper::ADMIN_USER_ID;
    }
}