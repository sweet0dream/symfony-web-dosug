<?php

namespace App\Controller;

use App\Helper\AdminHelper;
use App\Helper\UserHelper;
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
        private readonly AdminHelper $adminHelper
    )
    {
    }

    #[Route('/user/lk', name: 'user_lk')]
    public function viewAuthArea(Request $request): Response
    {
        $user = $this->userHelper->validateAuth($request->cookies->get('auth_hash'));

        if (is_null($user)) {
            $this->authLogout($request);
            return $this->redirectToRoute('user_auth', ['action' => 'login']);
        }

        if ($user->getId() == 1) {
            return $this->render('user/admin/index.html.twig', [
                'users' => $this->adminHelper->getAllUsers(),
                'priority' => $this->adminHelper->getPremiumPriorityItems()
            ]);
        }

        return $this->render('user/reg/lk.html.twig', [
            'user' => $user,
            'available_types' => IntimAnketaContract::TYPE
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
