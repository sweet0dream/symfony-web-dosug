<?php

namespace App\Controller;

use App\Helper\ItemHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoController extends AbstractController
{
    public function __construct(
        private readonly ItemHelper $itemHelper
    )
    {
    }

    #[Route(
        '/sitemap.{_format}',
        name: 'generate_sitemap',
        requirements: ['_format' => 'html|xml'],
        priority: 100,
        format: 'xml'
    )]
    public function generateSitemap(Request $request): Response
    {
        $host = $request->getSchemeAndHttpHost();

        return new Response(
            '<?xml version="1.0" encoding="UTF-8"?> <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode(
            array_map(
                fn($url) => "<url><loc>" . $url['loc']. "</loc><priority>" . $url['priority'] . "</priority>" . (isset($url['lastmod']) ? "<lastmod>" . $url['lastmod'] . "</lastmod>" : '') . "</url>",
                array_merge(
                    [['loc' => $host, 'priority' => '1.00']],
                    array_map(
                        fn($link) => [
                            'loc' => $host . $this->generateUrl('page_type', ['type' => $link]),
                            'priority' => 1.0
                        ],
                        array_keys(ItemHelper::TYPE)
                    ),
                    array_map(
                        fn($item) => [
                            'loc' => $host . $this->generateUrl('page_item', $item['param']),
                            'priority' => 0.8,
                            'lastmod' => $item['updated']
                        ],
                        $this->itemHelper->getItemsForSitemap()
                    )
                )
            )) . '</urlset>',
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml']
        );
    }
}
