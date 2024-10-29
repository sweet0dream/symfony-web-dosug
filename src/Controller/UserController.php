<?php

namespace App\Controller;

use App\Helper\AdminHelper;
use App\Helper\AdvertHelper;
use App\Helper\ConfigHelper;
use App\Helper\UserHelper;
use App\Helper\UserItemHelper;
use Sweet0dream\IntimAnketaContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    private const string LOGIN_ACTION = 'login';
    private const string LOGOUT_ACTION = 'logout';
    private const string REGIN_ACTION = 'regin';

    private const array VALID_ACTION = [
        self::LOGIN_ACTION,
        self::LOGOUT_ACTION,
        self::REGIN_ACTION
    ];

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly UserItemHelper $userItemHelper,
        private readonly AdminHelper $adminHelper,
        private readonly AdvertHelper $advertHelper,
        private readonly ConfigHelper $configHelper
    )
    {
    }

    #[Route('/user/lk', name: 'user_lk', methods: ['GET', 'POST'])]
    public function viewAuthArea(Request $request): Response
    {
        $user = $this->userHelper->validateAuth($request->cookies->get('auth_hash'));

        if (is_null($user)) {
            $this->authLogout($request);
            return $this->redirectToRoute('user_auth', ['action' => 'login']);
        }

        if ($user->getId() == AdminHelper::ADMIN_USER_ID) {
            if ($request->getMethod() === 'POST') {
                $this->addFlash('notify', $this->adminHelper->makeAction($request));

                return $this->redirectToRoute('user_lk');
            }

            return $this->render('user/adm/index.html.twig', [
                'users' => $this->adminHelper->getAllUsers(),
                'priority' => $this->adminHelper->getPremiumPriorityItems()
            ]);
        }

        if ($request->getMethod() === 'POST') {
            $key = key($request->request->all());
            $data = $request->request->all()[$key];
            $id = $data['id'];
            unset($data['id']);

            match ($key) {
                'edit' => $this->userHelper->actionItemLk($id, $data),
                'delete' => $this->userHelper->actionRemoveItem($id)
            };

            return $this->redirectToRoute('user_lk');
        }

        $items = $this->userHelper->getAllItems($user);

        usort($items, fn($a, $b) => $b['id'] - $a['id']);

        return $this->render('user/reg/index.html.twig', [
            'user' => $user,
            'items' => $items,
            'types' => IntimAnketaContract::TYPE
        ]);
    }

    #[Route('/admin/delete/{userId}', name: 'admin_delete_user', methods: ['GET'])]
    public function deleteUser(Request $request, int $userId): Response
    {
        $user = $this->userHelper->getUser($userId);

        if (
            !$this->userItemHelper->isAdmin($request->cookies->get('auth_hash'))
            || is_null($user)
        ) {
            return $this->redirectToRoute('user_lk');
        }

        $this->userHelper->deleteUser($user);

        return $this->redirectToRoute('user_lk');
    }

    #[Route('/user/admin/advert', name: 'user_admin_advert', methods: ['GET', 'POST'])]
    public function viewAdminAdvert(Request $request): Response
    {
        if (!$this->userItemHelper->isAdmin($request->cookies->get('auth_hash'))) {
            return $this->redirectToRoute('user_lk');
        }

        if ($request->getMethod() === 'POST') {
            $this->advertHelper->mapAction(
                isset($request->files->all()['upload']) ? array_merge(
                    $request->files->all()['upload'], ['action' => 'upload']
                ) : $request->request->all()
            );

            return $this->redirectToRoute('user_admin_advert');
        }
        return $this->render('user/adm/page/advert.html.twig', [
            'items' => $this->advertHelper->getItems()
        ]);
    }

    #[Route('/user/admin/config', name: 'user_admin_config', methods: ['GET', 'POST'])]
    public function viewAdminConfig(Request $request): Response
    {
        if (!$this->userItemHelper->isAdmin($request->cookies->get('auth_hash'))) {
            return $this->redirectToRoute('user_lk');
        }

        $config = $this->configHelper->loadConfig();

        if ($request->getMethod() === 'POST') {
            $this->configHelper->updateConfig($request->request->all()['config']);

            return $this->redirectToRoute('user_admin_config');
        }

        return $this->render('user/adm/page/config.html.twig', [
            'data' => $config
        ]);
    }

    #[Route('/user/{action}', name: 'user_auth', methods: ['GET', 'POST'])]
    public function authForm(
        string $action,
        Request $request,
    ): Response
    {
        if (in_array($action, self::VALID_ACTION)) {
            if ($request->getMethod() === 'POST') {
                return match ($action) {
                    self::LOGIN_ACTION => $this->authLogin($request),
                    self::LOGOUT_ACTION => $this->authLogout($request),
                    self::REGIN_ACTION => $this->authRegin($request)
                };
            }

            if ($request->cookies->get('auth_hash') !== null) {
                return $this->redirectToRoute('user_lk');
            }

            return $this->render('user/auth.html.twig', [
                'key' => $action
            ]);
        }

        return $this->redirectToRoute('page_main');
    }

    private function authRegin(Request $request): ?Response
    {
        return $this->hasAuth(
            $this->userHelper->actionRegin(
                $request->request->all()[self::REGIN_ACTION]
            ), self::REGIN_ACTION) ?? null;
    }

    private function authLogin(Request $request): ?Response
    {
        return $this->hasAuth(
            $this->userHelper->actionLogin(
                $request->request->all()[self::LOGIN_ACTION]
            ), self::LOGIN_ACTION) ?? null;
    }

    private function authLogout(Request $request): ?Response
    {
        if ($this->userHelper->actionLogout($request->cookies->get('auth_hash'))) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('auth_hash', 1, time() - 60 * 60 * 24 * 30));;
            $response->sendHeaders();
            $result = $this->redirectToRoute('user_auth', ['action' => 'login']);
        }

        return $result ?? null;
    }

    private function hasAuth(array $responses, string $action): ?RedirectResponse
    {
        if (isset($responses['error'])) {
            $this->addFlash('error', $responses['error']);
            $result = $this->redirectToRoute('user_auth', ['action' => $action]);
        } elseif (isset($responses['success'])) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('auth_hash', $responses['success']['hash'], time() + 60 * 60 * 24 * 30));
            $response->sendHeaders();
            $result = $this->redirectToRoute('user_lk');
        }

        return $result ?? null;
    }
}
