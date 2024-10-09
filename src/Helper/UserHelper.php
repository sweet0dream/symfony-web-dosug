<?php

namespace App\Helper;

use App\Entity\User;
use App\Entity\UserHash;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserHelper
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private ItemHelper $itemHelper,
        private EventHelper $eventHelper
    )
    {
    }

    public function actionLogin(array $data): array
    {
        $user = $this->userRepository->findOneBy(['login' => $data['login']]);
        if (!$user) {
            $result['error'] = 'loginNotFound';
        }
        if ($user && !$user->verifyPassword($data['password'])) {
            $result['error'] = 'passwordNotCorrect';
        }

        if (!isset($result['error'])) {
            $hash = md5((new DateTimeImmutable('now'))->format('YmdHis'));

            $userHash = $user->getUserHash();

            if (is_null($userHash)) {
                $userHash = (new UserHash())
                    ->setUser($user)
                    ->setValue($hash)
                    ->setLoginAt(new DateTimeImmutable('now'))
                ;
            } else {
                $userHash
                    ->setValue($hash)
                    ->setLoginAt(new DateTimeImmutable('now'))
                ;
            }

            $this->em->persist($userHash);
            $this->em->flush();

            $result['success']['hash'] = $hash;
        }

        return $result ?? ['error' => 'unknownError'];
    }

    public function actionLogout(?string $hash): true
    {
        $userHash = $this->em->getRepository(UserHash::class)->findOneBy(['value' => $hash]);
        if (!is_null($userHash)) {
            $this->em->remove($userHash);
            $this->em->flush();
        }

        return true;
    }

    public function actionRegin(array $data): array
    {
        if ($data['password'] !== $data['password_verify']) {
            $result['error'] = 'passwordNotEquals';
        }

        if ($this->userRepository->findOneBy(['login' => $data['login']])) {
            $result['error'] = 'loginAlreadyExists';
        }

        if (!isset($result['error'])) {
            $now = new DateTimeImmutable('now');
            $regUser = new User();
            $regUser
                ->setLogin($data['login'])
                ->setPassword($data['password'])
                ->setPasswordView($data['password'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;
            $this->em->persist($regUser);
            $this->em->flush();

            $result = $this->actionLogin([
                'login' => $regUser->getLogin(),
                'password' => $regUser->getPasswordView(),
            ]);
        }

        return $result ?? ['error' => 'unknownError'];
    }

    public function actionItemLk(
        int $itemId,
        array $data
    ): array
    {
        $result = $this->itemHelper->updateItemPartially(
            $item = $this->itemHelper->getItem($itemId),
            key($data),
            $data[key($data)]
        );

        $this->eventHelper->addEvent($item, $result);

        return $result;
    }

    public function validateAuth(?string $hash): ?User
    {
        return $this->em->getRepository(UserHash::class)->findOneBy(['value' => $hash])?->getUser();
    }

    public function getAllItems(User $user): array
    {
        return $this->itemHelper->getAllItemsForUser($user);
    }
}