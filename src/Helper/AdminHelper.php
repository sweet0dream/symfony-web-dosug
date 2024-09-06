<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class AdminHelper {

    private const array ITEM_ACTION = [
        'active',
        'premium',
        'realy',
        'top'
    ];

    public function __construct(
        private EntityManagerInterface $em
    )
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

    private function mapAction(string $itemAction): ?string
    {
        return array_combine(
            self::ITEM_ACTION,
            array_map(
                fn($value) => 'action' . ucfirst($value),
                self::ITEM_ACTION
            )
        )[$itemAction] ?? null;
    }

    public function makeAction(Request $request): ?array
    {
        $keyForm = array_key_first($request->request->all());
        $data = $request->request->all()[$keyForm];
        $itemId = array_shift($data);

        $item = $this->em->getRepository(Item::class)->find($itemId);

        switch (array_key_first($request->request->all())) {
            case 'item':
                $itemAction = array_key_first($data);
                $actionValue = $data[$itemAction];
                if ($item && in_array($itemAction, self::ITEM_ACTION)) {
                    $action = $this->mapAction($itemAction);
                    return $this->$action($item, $actionValue);
                }
                break;
            case 'priority':
                return $this->actionPriority($item, $data['value']);
            default:
                return null;
        }

        return null;
    }

    private function actionPriority(Item $item, int $value): array
    {
        $itemStatus = $item->getItemStatus();
        $itemStatus->setPremiumPriority($value != 0 ? $value : null);
        $this->saveChanges($item);

        return [
            'change_priority' => [
                'id' => $item->getId(),
                'action' => $value != 0 ? 'on' : 'off',
                'value' => $item->getItemStatus()->getPremiumPriority()
            ]
        ];
    }

    private function actionActive(Item $item, bool $value): array
    {
        $itemStatus = $item->getItemStatus();
        $itemStatus->setActive($value);
        if (!$value) {
            $itemStatus
                ->setPremiumPriority(null)
                ->setPremium(false)
                ->setRealy(false)
            ;
        }
        $this->saveChanges($item);

        return [
            'change_status' => [
                'id' => $item->getId(),
                'action' => 'active',
                'value' => $item->getItemStatus()->isActive()
            ]
        ];
    }

    private function actionPremium(Item $item, bool $value): array
    {
        $itemStatus = $item->getItemStatus();
        $itemStatus->setPremium($value);
        if (!$value) {
            $itemStatus->setPremiumPriority(null);
        }
        $this->saveChanges($item);

        return [
            'change_status' => [
                'id' => $item->getId(),
                'action' => 'premium',
                'value' => $item->getItemStatus()->isPremium()
            ]
        ];
    }

    private function actionRealy(Item $item, bool $value): array
    {
        $itemStatus = $item->getItemStatus();
        $itemStatus->setRealy($value);
        $this->saveChanges($item);

        return [
            'change_status' => [
                'id' => $item->getId(),
                'action' => 'realy',
                'value' => $item->getItemStatus()->isRealy()
            ]
        ];
    }

    private function actionTop(Item $item, bool $value): array
    {
        $item->setTopedAt((new DateTimeImmutable('now')));
        $this->saveChanges($item);

        return [
            'change_status' => [
                'id' => $item->getId(),
                'action' => 'top',
                'value' => true
            ]
        ];
    }

    private function saveChanges(Item $item): void
    {
        $this->em->persist($item);
        $this->em->flush();
    }
}