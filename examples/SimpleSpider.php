<?php

/**
 * Exemple simple de Spider pour scrapper une page web
 * 
 * Ce Spider extrait le titre et les liens d'une page web.
 * 
 * Pour l'utiliser :
 * 1. Copiez ce fichier dans app/Spiders/
 * 2. Exécutez : php artisan roach:run SimpleSpider
 */

namespace App\Spiders;

use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;

class SimpleSpider extends BasicSpider
{
    /**
     * URLs de départ pour le scraping
     */
    public array $startUrls = [
        'https://example.com',
    ];

    /**
     * Middlewares pour gérer les requêtes
     */
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    /**
     * Extensions pour ajouter des fonctionnalités
     */
    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    /**
     * Nombre de requêtes simultanées
     */
    public int $concurrency = 2;

    /**
     * Délai entre les requêtes (en secondes)
     */
    public int $requestDelay = 1;

    /**
     * Méthode principale de parsing
     * 
     * @param Response $response La réponse HTTP de la page
     * @return Generator<ParseResult> Les données extraites
     */
    public function parse(Response $response): Generator
    {
        // Extraire le titre de la page
        $title = $response->filter('title')->text('Pas de titre');

        // Extraire tous les liens de la page
        $links = $response->filter('a')->each(function ($node) {
            return [
                'text' => $node->text(),
                'href' => $node->attr('href'),
            ];
        });

        // Retourner les données extraites
        yield $this->item([
            'url' => $response->getUri(),
            'title' => $title,
            'links_count' => count($links),
            'links' => $links,
            'scraped_at' => now()->toIso8601String(),
        ]);
    }
}
