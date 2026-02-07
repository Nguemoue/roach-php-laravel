# Guide complet : Comment scrapper une page web avec Roach PHP Laravel

## Table des matières
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Concepts de base](#concepts-de-base)
4. [Créer votre premier Spider](#créer-votre-premier-spider)
5. [Exemples pratiques](#exemples-pratiques)
6. [Configuration avancée](#configuration-avancée)
7. [Meilleures pratiques](#meilleures-pratiques)

## Introduction

Roach PHP Laravel est un adaptateur Laravel pour [Roach PHP](https://roach-php.dev), une boîte à outils complète de web scraping pour PHP. Ce package vous permet de scrapper facilement des pages web directement depuis votre application Laravel.

## Installation

### Étape 1 : Installer le package

```bash
composer require roach-php/laravel
```

### Étape 2 : Publier la configuration (optionnel)

```bash
php artisan vendor:publish --provider="RoachPHP\Laravel\RoachServiceProvider"
```

Cela créera un fichier de configuration `config/roach.php` que vous pouvez personnaliser.

## Concepts de base

### Les composants principaux

1. **Spider** : La classe principale qui définit comment scrapper une page
   - Définit les URLs de départ (`startUrls`)
   - Contient la logique de parsing dans la méthode `parse()`
   - Configure les middlewares et les processeurs

2. **Response** : L'objet qui contient la réponse HTTP
   - Permet d'accéder au contenu HTML
   - Fournit des méthodes pour extraire des données

3. **ParseResult** : Les données extraites de la page
   - Peut être un Item (données à sauvegarder)
   - Peut être une Request (pour suivre des liens)

4. **Middleware** : Intercepte et modifie les requêtes/réponses
   - `RequestDeduplicationMiddleware` : Évite les doublons
   - Personnalisable pour ajouter des headers, gérer l'authentification, etc.

5. **ItemProcessors** : Traite les données extraites
   - Validation, transformation, sauvegarde en base de données, export en fichier, etc.

6. **Extensions** : Ajoute des fonctionnalités supplémentaires
   - `LoggerExtension` : Journalisation
   - `StatsCollectorExtension` : Collecte de statistiques

## Créer votre premier Spider

### Étape 1 : Générer un Spider

Utilisez la commande Artisan pour créer un nouveau Spider :

```bash
php artisan roach:spider ExampleSpider
```

Cela créera un fichier dans `app/Spiders/ExampleSpider.php`.

### Étape 2 : Configurer votre Spider

Ouvrez le fichier généré et configurez-le :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\BasicSpider;

class ExampleSpider extends BasicSpider
{
    // URLs de départ pour le scraping
    public array $startUrls = [
        'https://example.com'
    ];

    // Middlewares pour le téléchargement
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    // Middlewares pour le Spider
    public array $spiderMiddleware = [
        //
    ];

    // Processeurs pour traiter les données extraites
    public array $itemProcessors = [
        //
    ];

    // Extensions à utiliser
    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    // Nombre de requêtes simultanées
    public int $concurrency = 2;

    // Délai entre les requêtes (en secondes)
    public int $requestDelay = 1;

    /**
     * Méthode principale de parsing
     * 
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        // Extraire le titre de la page
        $title = $response->filter('title')->text();
        
        // Retourner les données sous forme d'Item
        yield $this->item([
            'title' => $title,
            'url' => $response->getUri(),
        ]);
    }
}
```

### Étape 3 : Exécuter votre Spider

```bash
php artisan roach:run ExampleSpider
```

## Exemples pratiques

### Exemple 1 : Scrapper les titres d'articles d'un blog

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;

class BlogSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example-blog.com/articles'
    ];

    public int $concurrency = 3;
    public int $requestDelay = 2;

    public function parse(Response $response): Generator
    {
        // Extraire tous les articles
        $articles = $response->filter('.article');

        foreach ($articles as $article) {
            yield $this->item([
                'title' => $article->filter('.article-title')->text(),
                'author' => $article->filter('.article-author')->text(),
                'date' => $article->filter('.article-date')->text(),
                'excerpt' => $article->filter('.article-excerpt')->text(),
            ]);
        }

        // Suivre le lien "Page suivante" pour paginer
        $nextPage = $response->filter('.pagination .next')->link();
        if ($nextPage) {
            yield $this->request('GET', $nextPage);
        }
    }
}
```

### Exemple 2 : Scrapper des produits avec leurs détails

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class ProductSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example-shop.com/products'
    ];

    public function parse(Response $response): Generator
    {
        // Extraire les liens de produits
        $productLinks = $response->filter('.product-item a')->links();

        // Créer une requête pour chaque produit
        foreach ($productLinks as $link) {
            yield $this->request('GET', $link, 'parseProduct');
        }
    }

    public function parseProduct(Response $response): Generator
    {
        // Extraire les détails du produit
        yield $this->item([
            'name' => $response->filter('h1.product-name')->text(),
            'price' => $response->filter('.product-price')->text(),
            'description' => $response->filter('.product-description')->text(),
            'image' => $response->filter('.product-image img')->attr('src'),
            'stock' => $response->filter('.product-stock')->text(),
            'url' => $response->getUri(),
        ]);
    }
}
```

### Exemple 3 : Scrapper avec authentification

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use App\Middleware\AuthMiddleware;

class ProtectedSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example.com/protected-page'
    ];

    // Ajouter un middleware d'authentification
    public array $downloaderMiddleware = [
        AuthMiddleware::class,
    ];

    public function parse(Response $response): Generator
    {
        yield $this->item([
            'content' => $response->filter('.content')->text(),
        ]);
    }
}
```

### Exemple 4 : Sauvegarder les données dans la base de données

Créez d'abord un ItemProcessor :

```php
<?php

namespace App\ItemProcessors;

use App\Models\Article;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

class SaveToDatabase implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        // Sauvegarder dans la base de données
        Article::updateOrCreate(
            ['url' => $item->get('url')],
            [
                'title' => $item->get('title'),
                'content' => $item->get('content'),
                'author' => $item->get('author'),
            ]
        );

        return $item;
    }
}
```

Puis utilisez-le dans votre Spider :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use App\ItemProcessors\SaveToDatabase;

class ArticleSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example-blog.com/articles'
    ];

    // Ajouter le processeur
    public array $itemProcessors = [
        SaveToDatabase::class,
    ];

    public function parse(Response $response): Generator
    {
        yield $this->item([
            'title' => $response->filter('h1')->text(),
            'content' => $response->filter('.content')->text(),
            'author' => $response->filter('.author')->text(),
            'url' => $response->getUri(),
        ]);
    }
}
```

## Configuration avancée

### Configurer le fichier `config/roach.php`

```php
<?php

return [
    // File d'attente des requêtes
    'request_queue' => \RoachPHP\Scheduling\ArrayRequestScheduler::class,
    
    // Client HTTP
    'client' => \RoachPHP\Http\Client::class,
    
    // Namespace par défaut pour les Spiders
    'default_spider_namespace' => 'App\Spiders',
];
```

### Créer un Middleware personnalisé

```php
<?php

namespace App\Middleware;

use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

class CustomHeaderMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    public function handleRequest(Request $request): Request
    {
        // Ajouter des headers personnalisés
        return $request->withHeader('User-Agent', 'Mozilla/5.0 Custom Bot');
    }

    public function handleResponse(Response $response): Response
    {
        // Traiter la réponse si nécessaire
        return $response;
    }
}
```

### Gérer les erreurs et les retry

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Downloader\Middleware\RetryMiddleware;

class RobustSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example.com'
    ];

    // Ajouter le middleware de retry
    public array $downloaderMiddleware = [
        [
            RetryMiddleware::class,
            [
                'max_retries' => 3,
                'retry_delay' => 5, // secondes
            ]
        ],
    ];

    public function parse(Response $response): Generator
    {
        try {
            yield $this->item([
                'title' => $response->filter('h1')->text(),
            ]);
        } catch (\Exception $e) {
            // Logger l'erreur
            logger()->error('Erreur de parsing', [
                'url' => $response->getUri(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

## Meilleures pratiques

### 1. Respectez les robots.txt

Vérifiez toujours le fichier `robots.txt` du site avant de scrapper.

### 2. Limitez la concurrence et ajoutez des délais

```php
public int $concurrency = 2;
public int $requestDelay = 1; // Au moins 1 seconde entre les requêtes
```

### 3. Utilisez un User-Agent approprié

Identifiez clairement votre bot avec un User-Agent personnalisé.

### 4. Gérez les erreurs correctement

Utilisez des try-catch et des middlewares de retry pour gérer les erreurs.

### 5. Validez les données extraites

Vérifiez que les sélecteurs CSS sont corrects avant de scrapper en masse.

### 6. Évitez de surcharger les serveurs

Ajoutez des délais entre les requêtes et limitez la concurrence.

### 7. Utilisez un cache si nécessaire

Pour éviter de re-télécharger les mêmes pages lors du développement.

### 8. Loggez vos activités

Utilisez `LoggerExtension` pour suivre vos scraping sessions.

## Commandes utiles

```bash
# Créer un nouveau Spider
php artisan roach:spider MonSpider

# Exécuter un Spider
php artisan roach:run MonSpider

# Exécuter en mode REPL (interactif)
php artisan roach:repl

# Publier la configuration
php artisan vendor:publish --provider="RoachPHP\Laravel\RoachServiceProvider"
```

## Ressources supplémentaires

- [Documentation officielle Roach PHP](https://roach-php.dev)
- [Documentation Laravel](https://laravel.com/docs)
- [Repository GitHub](https://github.com/roach-php/laravel)

## Conclusion

Avec Roach PHP Laravel, vous disposez d'un outil puissant et flexible pour scrapper des pages web. Ce guide couvre les bases, mais le package offre de nombreuses autres fonctionnalités avancées que vous pouvez explorer dans la documentation officielle.

N'oubliez pas de toujours respecter les conditions d'utilisation des sites web que vous scrappez et d'être un "bon citoyen du web" en limitant l'impact de vos requêtes sur les serveurs cibles.
