<?php

namespace App\Controller;

use App\Helper\ItemHelper;
use App\Helper\UserHelper;
use App\Helper\UserItemHelper;
use Sweet0dream\IntimAnketaContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserItemController extends AbstractController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly ItemHelper $itemHelper,
        private readonly UserItemHelper $userItemHelper
    )
    {
    }

    #[Route('/user/item/add/{type}', name: 'user_item_add', methods: ['GET', 'POST'])]
    public function addUserItem(
        string $type,
        Request $request
    ): Response
    {
        $auth = $request->cookies->get('auth_hash') ?? null;

        if (!in_array($type, IntimAnketaContract::TYPE) || !$auth) {
            return $this->redirectToRoute('user_lk');
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->request->all();

            return $this->redirectToRoute('user_item_photo', [
                'id' => $this->itemHelper->addItem(
                    array_merge($data[key($data)], ['type' => $type]),
                    $this->userHelper->validateAuth($auth)
                )
            ]);

        }

        $formFields = $this->getField($type);
        unset($formFields['info']['name']);

        return $this->render('user/lk-item-add.html.twig', [
            'type' => $type,
            'form' => $formFields
        ]);
    }

    #[Route('/user/item/photo/{id}', name: 'user_item_photo', methods: ['GET', 'POST', 'DELETE'])]
    public function photoUserItem(
        int $id,
        Request $request
    ): Response
    {
        if (!$this->userItemHelper->isItemHasUser(
            $id,
            $request->cookies->get('auth_hash')
        )) {
            return $this->redirectToRoute('user_lk');
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->request->all()['action'];
            $itemId = $data['item']; unset($data['item']);
            $action = key($data) ?? 'upload';
            $files = $data[$action]['file'] ?? $request->files->all()['upload']['files'];
            if ($this->mappedAction($action, $itemId, $files)) {
                return $this->redirectToRoute('user_item_photo', ['id' => $id]);
            }
        }

        return $this->render('user/lk-item-photo.html.twig', [
            'id' => $id,
            'photos' => $this->itemHelper->getPhoto($id)
        ]);
    }

    private function mappedAction(
        string $action,
        int $itemId,
        array|string $files
    ): bool
    {
        return match ($action) {
            'upload' => $this->itemHelper->uploadPhoto($itemId, $files),
            'delete' => $this->itemHelper->deletePhoto($itemId, $files),
            'main' => $this->itemHelper->mainPhoto($itemId, $files),
        };
    }

    private function getField(string $type): array
    {
        return (new IntimAnketaContract($type))->getField();
    }
}
