<?php

namespace App\Controller;

use App\Helper\ItemHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends AbstractController
{
    public function __construct(private readonly ItemHelper $itemHelper)
    {
    }

    #[Route('/', name: 'page_main')]
    public function mainPage(): Response
    {
        return $this->render('section.html.twig', [
            'key' => 'main',
            'items' => $this->itemHelper->getActiveItems()
        ]);
    }

    #[Route('/{type}', name: 'page_type')]
    public function typePage(string $type): Response
    {
        if (!isset(ItemHelper::TYPE[$type])) {
            return $this->pageNotFound();
        }

        return $this->render('section.html.twig', [
            'key' => ItemHelper::TYPE[$type],
            'items' => $this->itemHelper->getActiveItems($type)
        ]);
    }

    private function pageNotFound(): Response
    {
        return $this->render('error/404.html.twig', [], (new Response())->setStatusCode(Response::HTTP_NOT_FOUND));
    }
}
