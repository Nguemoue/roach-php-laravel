# Exemples d'utilisation de Roach PHP avec Laravel

Ce dossier contient des exemples complets montrant comment utiliser le package `roach-php/laravel` pour faire du web scraping avec Laravel, en particulier pour scraper des offres d'emploi depuis https://jobs.doopinet.com/search/?dref=welcome-msg.

## Installation

1. Installez le package via Composer :

```bash
composer require roach-php/laravel
```

2. Publiez la configuration (optionnel) :

```bash
php artisan vendor:publish --provider="RoachPHP\Laravel\RoachServiceProvider"
```

## Structure des exemples

Ce dossier contient les fichiers d'exemple suivants :

- **JobScraperSpider.php** - Spider pour scraper des offres d'emploi
- **SaveJobToDatabase.php** - Processeur d'items pour sauvegarder les données en base
- **JobScraperController.php** - Contrôleur pour exécuter les spiders depuis une route
- **ScrapeJobsCommand.php** - Commande Artisan pour exécuter les spiders

## Guide d'utilisation rapide

### 1. Créer un Spider

Utilisez la commande Artisan pour générer un nouveau spider :

```bash
php artisan roach:spider JobScraperSpider
```

Cela créera un fichier dans `app/Spiders/JobScraperSpider.php`. Copiez le contenu de l'exemple `JobScraperSpider.php` dans ce fichier.

### 2. Configuration du Spider

Le spider de base contient :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Http\Response;

class JobScraperSpider extends BasicSpider
{
    // URLs de départ
    public array $startUrls = [
        'https://jobs.doopinet.com/search/?dref=welcome-msg',
    ];

    // Nombre de requêtes concurrentes
    public int $concurrency = 2;

    // Délai entre les requêtes (en secondes)
    public int $requestDelay = 1;

    public function parse(Response $response): Generator
    {
        // Extraire les données de la page
        $crawler = $response->filter('body');
        
        // Logique de scraping ici...
    }
}
```

### 3. Exécuter le Spider

Il existe plusieurs façons d'exécuter votre spider :

#### Option A : Via la ligne de commande

```bash
php artisan roach:run JobScraperSpider
```

#### Option B : Programmatiquement dans un contrôleur

```php
use RoachPHP\Roach;
use App\Spiders\JobScraperSpider;

$items = Roach::collectSpider(JobScraperSpider::class);
```

#### Option C : Via une commande Artisan personnalisée

Créez une commande :

```bash
php artisan make:command ScrapeJobsCommand
```

Puis utilisez le code de l'exemple `ScrapeJobsCommand.php` et exécutez :

```bash
php artisan scrape:jobs
```

### 4. Extraire les données

Dans la méthode `parse()`, utilisez le Symfony DomCrawler pour extraire les données :

```php
public function parse(Response $response): Generator
{
    $crawler = $response->filter('body');
    
    // Trouver tous les éléments d'offres d'emploi
    $jobListings = $crawler->filter('.job-listing');
    
    foreach ($jobListings as $jobElement) {
        $job = new Crawler($jobElement);
        
        // Extraire les données
        yield $this->item([
            'title' => $job->filter('.job-title')->text(),
            'company' => $job->filter('.company-name')->text(),
            'location' => $job->filter('.location')->text(),
            'url' => $job->filter('a')->attr('href'),
        ]);
    }
}
```

### 5. Sauvegarder les données

#### Option A : Créer un Item Processor

Créez un processeur pour sauvegarder en base de données :

```php
namespace App\ItemProcessors;

use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use Illuminate\Support\Facades\DB;

class SaveJobToDatabase implements ItemProcessorInterface
{
    public function processItem(ItemInterface $item): ItemInterface
    {
        DB::table('jobs')->insert($item->all());
        return $item;
    }
}
```

Puis ajoutez-le à votre spider :

```php
public array $itemProcessors = [
    SaveJobToDatabase::class,
];
```

#### Option B : Traiter les résultats après l'exécution

```php
$items = Roach::collectSpider(JobScraperSpider::class);

foreach ($items as $item) {
    DB::table('jobs')->insert($item);
}
```

### 6. Créer la table de base de données

Créez une migration pour stocker les offres d'emploi :

```bash
php artisan make:migration create_jobs_table
```

Dans la migration :

```php
public function up()
{
    Schema::create('jobs', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('company')->nullable();
        $table->string('location')->nullable();
        $table->text('description')->nullable();
        $table->string('salary')->nullable();
        $table->string('job_type')->nullable();
        $table->string('posted_date')->nullable();
        $table->string('url')->unique();
        $table->timestamp('scraped_at')->nullable();
        $table->timestamps();
    });
}
```

Exécutez la migration :

```bash
php artisan migrate
```

## Fonctionnalités avancées

### Gérer la pagination

Pour suivre les liens de pagination :

```php
public function parse(Response $response): Generator
{
    // Extraire les données...
    
    // Suivre le lien "Page suivante"
    $nextPage = $response->filter('.pagination .next')->attr('href');
    if ($nextPage) {
        yield $this->request('GET', $nextPage);
    }
}
```

### Middleware personnalisé

Ajoutez des middleware pour modifier les requêtes/réponses :

```php
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Downloader\Middleware\UserAgentMiddleware;

public array $downloaderMiddleware = [
    RequestDeduplicationMiddleware::class,
    [UserAgentMiddleware::class, ['userAgent' => 'Mon Bot/1.0']],
];
```

### Exécution en arrière-plan

Pour les gros jobs de scraping, utilisez les queues Laravel :

```bash
php artisan make:job ScrapeJobsJob
```

Dans le job :

```php
namespace App\Jobs;

use App\Spiders\JobScraperSpider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use RoachPHP\Roach;

class ScrapeJobsJob implements ShouldQueue
{
    use Queueable;

    public function handle()
    {
        Roach::startSpider(JobScraperSpider::class);
    }
}
```

Puis dispatchez le job :

```php
ScrapeJobsJob::dispatch();
```

### Gestion des erreurs

Ajoutez la gestion d'erreurs dans votre spider :

```php
private function extractText(Crawler $crawler, string $selector): ?string
{
    try {
        $element = $crawler->filter($selector)->first();
        return $element->count() > 0 ? trim($element->text()) : null;
    } catch (\Exception $e) {
        logger()->warning("Failed to extract: $selector");
        return null;
    }
}
```

## Exemple complet pour jobs.doopinet.com

Voici un exemple complet adapté au site jobs.doopinet.com :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class JobScraperSpider extends BasicSpider
{
    public array $startUrls = [
        'https://jobs.doopinet.com/search/?dref=welcome-msg',
    ];

    public int $concurrency = 2;
    public int $requestDelay = 1;

    public function parse(Response $response): Generator
    {
        $crawler = $response->filter('body');
        
        // Adapter les sélecteurs selon la structure HTML réelle du site
        $jobListings = $crawler->filter('.job-listing, .job-card');
        
        foreach ($jobListings as $jobElement) {
            $job = new Crawler($jobElement);
            
            yield $this->item([
                'title' => $this->extractText($job, '.job-title'),
                'company' => $this->extractText($job, '.company'),
                'location' => $this->extractText($job, '.location'),
                'description' => $this->extractText($job, '.description'),
                'url' => $job->filter('a')->attr('href'),
                'scraped_at' => now()->toIso8601String(),
            ]);
        }
        
        // Pagination
        $nextPage = $crawler->filter('.next-page')->first();
        if ($nextPage->count() > 0) {
            yield $this->request('GET', $nextPage->attr('href'));
        }
    }
    
    private function extractText(Crawler $crawler, string $selector): ?string
    {
        try {
            $element = $crawler->filter($selector)->first();
            return $element->count() > 0 ? trim($element->text()) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

## Conseils et bonnes pratiques

1. **Respectez les sites web** : Utilisez un délai raisonnable entre les requêtes (`requestDelay`)
2. **Gérez les erreurs** : Ajoutez des blocs try-catch pour éviter les crashs
3. **Testez progressivement** : Commencez avec une seule page avant de scraper tout le site
4. **Inspectez le HTML** : Utilisez les outils de développement du navigateur pour identifier les bons sélecteurs CSS
5. **Logs** : Utilisez `logger()` pour suivre l'avancement et déboguer
6. **User-Agent** : Utilisez un User-Agent approprié pour identifier votre bot

## Ressources

- [Documentation officielle de Roach PHP](https://roach-php.dev)
- [Documentation Laravel de Roach](https://roach-php.dev/docs/laravel)
- [Symfony DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html)

## Licence

Ces exemples sont fournis sous licence MIT, comme le package principal.
