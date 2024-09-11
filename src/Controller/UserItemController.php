<?php

namespace App\Controller;

use App\Helper\ItemHelper;
use App\Helper\UserHelper;
use App\Helper\UserItemHelper;
use Exception;
use Sweet0dream\IntimAnketaContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserItemController extends AbstractController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly ItemHelper $itemHelper,
        private readonly UserItemHelper $userItemHelper,
        private readonly HttpClientInterface $httpClient
    )
    {
    }

    //import module

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws Exception
     */
    #[Route('/import', name: 'import_data', methods: ['GET', 'POST'], priority: 2)]
    public function importData(Request $request): Response
    {
        if ($request->getMethod() === Request::METHOD_POST) {

            $response = $this->httpClient->request(
                'GET',
                'https://export.luxedosug.mom',
                [
                    'query' => [
                        'id' => (int)$request->request->all()['import']['id']
                    ]
                ]
            );

            $responseData = $response->toArray();

            if (isset($responseData['error']) || $response->getStatusCode() !== Response::HTTP_OK) {
                $this->addFlash('error', $responseData['error'] ?? $response->getContent());
                return $this->redirectToRoute('import_data');
            }

            $importData = $this->userItemHelper->importData($responseData);

            if (isset($importData['error'])) {
                $this->addFlash('error', $importData['error']);
                return $this->redirectToRoute('import_data');
            }

            $this->addFlash('info', $importData);
            return $this->redirectToRoute('user_auth', ['action' => 'login']);

        }
        return $this->render('user/import.html.twig');
    }
    //!import module

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

        if ($request->getMethod() === Request::METHOD_POST) {
            $data = $request->request->all();

            return $this->redirectToRoute('user_item_photo', [
                'id' => $this->itemHelper->createItem(
                    array_merge($data[key($data)], ['type' => $type]),
                    $this->userHelper->validateAuth($auth)
                )
            ]);

        }

        $formFields = $this->getField($type);
        unset($formFields['info']['name']);

        return $this->render('user/reg/page/add.html.twig', [
            'type' => $type,
            'form' => $formFields
        ]);
    }

    #[Route('/user/item/edit/{id}', name: 'user_item_edit', methods: ['GET', 'POST'])]
    public function editUserItem(
        int $id,
        Request $request
    ): Response
    {
        $authHash = $request->cookies->get('auth_hash');

        if (
            !$this->userItemHelper->isItemHasUser($id, $authHash)
            && !$this->userItemHelper->isAdmin($authHash)
        ) {
            return $this->redirectToRoute('user_lk');
        }

        $item = $this->userItemHelper->getItem($id);

        if ($request->getMethod() === Request::METHOD_POST) {
            $data = $request->request->all();

            return $this->redirectToRoute('user_item_edit', [
                'id' => $this->itemHelper->updateItem(
                    $item,
                    $data[key($data)]
                )
            ]);

        }

        $formFields = $this->getField($item->getType());
        unset($formFields['info']['name']);

        return $this->render('user/reg/page/edit.html.twig', [
            'form' => $formFields,
            'item' => [
                'id' => $item->getId(),
                'updated' => $item->getUpdatedAt(),
                'name' => $item->getName(),
                'phone' => str_replace('+7', '', $item->getPhone()),
                'info' => $item->getInfo(),
                'service' => $item->getService(),
                'price' => $item->getPrice(),
                'text' => $item->getInfo()['text']
            ]
        ]);
    }

    #[Route('/user/item/photo/{id}', name: 'user_item_photo', methods: ['GET', 'POST', 'DELETE'])]
    public function photoUserItem(
        int $id,
        Request $request
    ): Response
    {
        $authHash = $request->cookies->get('auth_hash');

        if (
            !$this->userItemHelper->isItemHasUser($id, $authHash)
            && !$this->userItemHelper->isAdmin($authHash)
        ) {
            return $this->redirectToRoute('user_lk');
        }

        if ($request->getMethod() === Request::METHOD_POST) {
            $data = $request->request->all()['action'];
            $itemId = $data['item']; unset($data['item']);
            $action = key($data) ?? 'upload';
            $files = $data[$action]['file'] ?? $request->files->all()['upload']['files'] ?? [];
            if ($this->mappedAction($action, $itemId, $files)) {
                return $this->redirectToRoute('user_item_photo', ['id' => $id]);
            }
        }

        return $this->render('user/reg/page/photo.html.twig', [
            'id' => $id,
            'has_main_photo' => $this->itemHelper->hasMainPhoto($id),
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
