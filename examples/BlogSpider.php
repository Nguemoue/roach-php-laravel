<?php

/**
 * Exemple de Spider pour scrapper un blog avec pagination
 * 
 * Ce Spider extrait les articles d'un blog et suit automatiquement
 * les liens de pagination pour scrapper toutes les pages.
 * 
 * Pour l'utiliser :
 * 1. Copiez ce fichier dans app/Spiders/
 * 2. Modifiez les URLs et sélecteurs CSS selon votre cible
 * 3. Exécutez : php artisan roach:run BlogSpider
 */

namespace App\Spiders;

use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class BlogSpider extends BasicSpider
{
    /**
     * URL de départ (page d'accueil du blog)
     */
    public array $startUrls = [
        'https://example-blog.com/articles',
    ];

    /**
     * Middlewares
     */
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    /**
     * Extensions
     */
    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    /**
     * Configuration de performance
     */
    public int $concurrency = 3;
    public int $requestDelay = 2;

    /**
     * Parse la page de liste d'articles
     */
    public function parse(Response $response): Generator
    {
        // Extraire les liens vers les articles
        $articleLinks = $response->filter('.article-item a.article-link')->links();

        // Créer une requête pour chaque article
        foreach ($articleLinks as $link) {
            yield $this->request('GET', $link, 'parseArticle');
        }

        // Suivre le lien "Page suivante" pour la pagination
        $nextPageLink = $response->filter('.pagination .next-page')->link();
        
        if ($nextPageLink) {
            // Créer une requête pour la page suivante
            yield $this->request('GET', $nextPageLink, 'parse');
        }
    }

    /**
     * Parse une page d'article individuelle
     */
    public function parseArticle(Response $response): Generator
    {
        // Extraire les données de l'article
        $title = $response->filter('h1.article-title')->text('');
        $author = $response->filter('.article-author')->text('Auteur inconnu');
        $date = $response->filter('.article-date')->text('');
        $content = $response->filter('.article-content')->html('');
        
        // Extraire les catégories/tags
        $categories = $response->filter('.article-categories .category')->each(function ($node) {
            return $node->text();
        });

        // Extraire l'image principale si elle existe
        $image = null;
        if ($response->filter('.article-image img')->count() > 0) {
            $image = $response->filter('.article-image img')->attr('src');
        }

        // Retourner les données extraites
        yield $this->item([
            'url' => $response->getUri(),
            'title' => trim($title),
            'author' => trim($author),
            'date' => trim($date),
            'content' => $content,
            'categories' => $categories,
            'image' => $image,
            'scraped_at' => now()->toIso8601String(),
        ]);
    }
}
