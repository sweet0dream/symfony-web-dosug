<?php

namespace App\Helper;

use App\Entity\ItemStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AdminHelper {

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getAdmin(): array
    {
        return $this->em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC']);
    }

    public function getPremiumPriorityItems(): array
    {
        foreach ($this->em->getRepository(ItemStatus::class)->findBy(['premium' => true], ['premium_priority' => 'ASC']) as $item) {
            $result[is_null($item->getPremiumPriority()) ? 'unsort' : 'sort'][] = $item;
        }

        return $result ?? [];
    }

}