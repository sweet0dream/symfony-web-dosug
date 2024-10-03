<?php

namespace App\Controller;

use App\Helper\AdvertHelper;
use App\Helper\ItemHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends AbstractController
{
    public function __construct(
        private readonly ItemHelper $itemHelper,
        private readonly AdvertHelper $advertHelper
    )
    {
    }

    #[Route('/', name: 'page_main')]
    public function mainPage(): Response
    {
        return $this->render('section/index.html.twig', [
            'key' => 'main',
            'items' => $this->itemHelper->getActiveItems(),
            'advert' => $this->advertHelper->getItems('main')
        ]);
    }

    #[Route('/{type}', name: 'page_type')]
    public function typePage(string $type): Response
    {
        if (!isset(ItemHelper::TYPE[$type])) {
            return $this->pageNotFound();
        }

        return $this->render('section/index.html.twig', [
            'key' => ItemHelper::TYPE[$type],
            'items' => $this->itemHelper->getActiveItems($type),
            'advert' => $this->advertHelper->getItems(ItemHelper::TYPE[$type])
        ]);
    }

    #[Route('/{type}/id{id}', name: 'page_item')]
    public function viewFull(
        string $type,
        string $id
    ): Response
    {
        $item = $this->itemHelper->getOneItem((int)$id, $type);

        if (is_null($item)) {
            return $this->pageNotFound();
        }

        return $this->render('section/page/full.html.twig', [
            'item' => $item
        ]);
    }

    private function pageNotFound(): Response
    {
        return $this->render('error/404.html.twig', [], (new Response())->setStatusCode(Response::HTTP_NOT_FOUND));
    }
}
