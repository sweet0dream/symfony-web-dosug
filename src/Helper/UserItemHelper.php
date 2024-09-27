<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemPhoto;
use App\Entity\ItemStatus;
use App\Entity\User;
use App\Entity\UserHash;
use CodeBuds\WebPConverter\WebPConverter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;

readonly class UserItemHelper
{
    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/media')]
        private string                 $mediaDir
    )
    {
    }

    //import module

    /**
     * @throws Exception
     */
    public function importData(array $data): ?array
    {
        $now = new DateTimeImmutable('now');

        if (isset($data['user'])) {
            $user = (new User())
                ->setLogin($data['user']['login'])
                ->setPassword($data['user']['password'])
                ->setPasswordView($data['user']['password'])
                ->setCreatedAt(isset($data['item']['created_at'])
                    ? (new DateTimeImmutable())->setTimestamp(strtotime($data['item']['created_at']))
                    : $now
                )
                ->setUpdatedAt(isset($data['item']['updated_at'])
                    ? (new DateTimeImmutable())->setTimestamp(strtotime($data['item']['created_at']))
                    : $now)
            ;
            try {
                $this->em->persist($user);
                $this->em->flush();
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }

        }

        if (isset($data['item']) && isset($user) && $user instanceof User) {
            $item = (new Item())
                ->setUser($user)
                ->setPhone($data['item']['phone'])
                ->setName($data['item']['name'])
                ->setType($data['item']['type'])
                ->setInfo($data['item']['info'])
                ->setService($data['item']['service'])
                ->setPrice($data['item']['price'])
                ->setCreatedAt((new DateTimeImmutable())->setTimestamp(strtotime($data['item']['created_at'])))
                ->setUpdatedAt((new DateTimeImmutable())->setTimestamp(strtotime($data['item']['updated_at'])))
                ->setTopedAt((new DateTimeImmutable())->setTimestamp(strtotime($data['item']['toped_at'])))
            ;
            try {
                $this->em->persist($item);
                $this->em->flush();
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if (isset($item) && $item instanceof Item) {
            foreach ($data['item']['photo'] as $photo) {
                $pathItemPhoto = $this->mediaDir . '/'. $item->getId() .'/src/';
                if (!is_dir($pathItemPhoto)) {
                    mkdir($pathItemPhoto, 0755, true);
                }
                $newFileName = (new DateTimeImmutable('now'))->format('Ymd') . '_' . base64_encode(rand(111,999));
                $srcPath = $this->mediaDir . '/'. $item->getId() .'/src';

                copy($photo['file_name'], $srcPath . '/' . $newFileName . '.jpg');

                WebPConverter::createWebpImage(
                    new File($srcPath . '/' . $newFileName . '.jpg'),
                    [
                        'saveFile' => true,
                        'force' => true,
                        'filename' => $newFileName,
                        'quality' => 80,
                        'savePath' => $srcPath
                    ]
                );

                $itemPhoto = (new ItemPhoto())
                    ->setFileName($newFileName . '.webp')
                    ->setCreatedAt($now)
                    ->setHasMain($photo['has_main'] ?? false);
                ;
                $item->addItemPhoto($itemPhoto);
            }
            try {
                $this->em->persist($item);
                $this->em->flush();
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if (isset($data['item']['status']) && isset($item) && $item instanceof Item) {
            $status = (new ItemStatus())
                ->setActive($data['item']['status']['active'])
                ->setPremium($data['item']['status']['premium'])
                ->setPremiumPriority($data['item']['status']['premium_priority'])
                ->setRealy(false)
            ;
            $item->setItemStatus($status);
            try {
                $this->em->persist($item);
                $this->em->flush();
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if (isset($user) && $user instanceof User) {
            return [
                'login' => $user->getLogin(),
                'password' => $user->getPasswordView(),
                'redirect' => 'rewrite ^' . $data['link'] . '$ /' . array_flip(ItemHelper::TYPE)[$item->getType()] . '/id' . $item->getId() . ' permanent;'
            ];
        }

        return null;
    }
    //!import module

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

    public function getItem(
        int $itemId
    ): ?Item
    {
        return $this->em->getRepository(Item::class)->find($itemId);
    }

    public function isAdmin(?string $hashUser): bool
    {
        $user = $this->em->getRepository(UserHash::class)->findOneBy(['value' => $hashUser]);
        return $user && $user->getUser()->getId() == AdminHelper::ADMIN_USER_ID;
    }
}