<?php

namespace App\Helper;

use App\Entity\ItemStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AdminHelper {

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getAllUsers(): array
    {
        return $this->em->getRepository(User::class)->findBy(
            [],
            ['createdAt' => 'DESC']
        );
    }

    public function getPremiumPriorityItems(): ?array
    {
        $premiumItems = $this->em->getRepository(ItemStatus::class)->findBy(
            ['premium' => true],
            ['premium_priority' => 'ASC']
        );

        foreach ($premiumItems as $item) {
            $result[is_null($item->getPremiumPriority()) ? 'unsort' : 'sort']['items'][] = $item;
        }

        if (!empty($result)) {
            $result['place'] = array_values(
                array_diff(
                    range(1, count($premiumItems)),
                    array_filter(
                        array_map(
                            fn($item) => $item->getPremiumPriority(),
                            $premiumItems
                        )
                    )
                )
            );
        }

        return $result ?? null;
    }
}