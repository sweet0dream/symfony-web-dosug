<?php

namespace App\Helper;

use App\Entity\Item;
use App\Entity\ItemPhoto;
use App\Entity\ItemStatus;
use App\Entity\User;
use App\Entity\UserHash;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\DateTimeImmutable;
use Symfony\Component\Config\Definition\Exception\DuplicateKeyException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserItemHelper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/media')]
        private readonly string $mediaDir
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
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
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
                ->setCreatedAt(new DateTimeImmutable($data['item']['created_at']))
                ->setUpdatedAt(new DateTimeImmutable($data['item']['updated_at']))
                ->setTopedAt(new DateTimeImmutable($data['item']['toped_at']))
            ;
            try {
                $this->em->persist($item);
                $this->em->flush();
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if (isset($data['item']['photo']) && isset($item) && $item instanceof Item) {
            foreach ($data['item']['photo'] as $photo) {
                $pathItemPhoto = $this->mediaDir . '/'. $item->getId() .'/src/';
                if (!is_dir($pathItemPhoto)) {
                    mkdir($pathItemPhoto, 0755, true);
                }
                $newFileName = (new DateTimeImmutable('now'))->format('Ymd') . '_' . base64_encode(rand(111,999)) . '.jpg';
                copy($photo['file_name'], $this->mediaDir . '/'. $item->getId() .'/src/' . $newFileName);
                $itemPhoto = (new ItemPhoto())
                    ->setFileName($newFileName)
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
}