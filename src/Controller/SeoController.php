<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoController extends AbstractController
{
    #[Route('/robots.txt', name: 'generate_robots', priority: 100)]
    public function index(): Response
    {
        return new Response("User-agent: *\r\n\r\nDisallow: /", Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
