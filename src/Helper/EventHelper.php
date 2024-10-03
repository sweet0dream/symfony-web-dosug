<?php

namespace App\Helper;

use App\Entity\Event;
use App\Entity\Item;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class EventHelper {

    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    public function addEvent(
        Item $item,
        array $event
    ): void
    {
        $this->em->persist(
            (new Event())
                ->setItem($item)
                ->setUser($item->getUser())
                ->setEvent(serialize($event))
                ->setCreatedAt(new DateTimeImmutable('now'))
        );
        $this->em->flush();
    }
}
