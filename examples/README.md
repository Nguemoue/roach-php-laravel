# Exemples de Scraping avec Roach PHP Laravel

Ce dossier contient des exemples pratiques pour vous aider à démarrer rapidement avec Roach PHP Laravel.

## 📁 Fichiers d'exemples

### 1. `SimpleSpider.php`
**Utilisation :** Spider basique pour scrapper une page web

**Ce qu'il fait :**
- Extrait le titre d'une page
- Liste tous les liens présents sur la page
- Compte le nombre total de liens

**Comment l'utiliser :**
```bash
# 1. Copiez le fichier dans votre projet
cp examples/SimpleSpider.php app/Spiders/

# 2. Exécutez le Spider
php artisan roach:run SimpleSpider
```

### 2. `BlogSpider.php`
**Utilisation :** Spider avancé avec pagination et parsing de détails

**Ce qu'il fait :**
- Parcourt une liste d'articles de blog
- Suit automatiquement les liens de pagination
- Extrait les détails complets de chaque article (titre, auteur, contenu, catégories, image)

**Comment l'utiliser :**
```bash
# 1. Copiez le fichier dans votre projet
cp examples/BlogSpider.php app/Spiders/

# 2. Modifiez les URLs et sélecteurs CSS selon votre cible
# 3. Exécutez le Spider
php artisan roach:run BlogSpider
```

### 3. `DatabaseItemProcessor.php`
**Utilisation :** Sauvegarde automatique des données en base de données

**Ce qu'il fait :**
- Prend les items scrapés
- Les sauvegarde dans une table MySQL/PostgreSQL
- Évite les doublons en utilisant `updateOrInsert`

**Comment l'utiliser :**
```bash
# 1. Créez une migration pour votre table
php artisan make:migration create_scraped_items_table

# 2. Copiez le processeur dans votre projet
cp examples/DatabaseItemProcessor.php app/ItemProcessors/

# 3. Ajoutez-le à votre Spider
```

Exemple d'utilisation dans un Spider :
```php
use App\ItemProcessors\DatabaseItemProcessor;

class MonSpider extends BasicSpider
{
    public array $itemProcessors = [
        [
            DatabaseItemProcessor::class,
            [
                'table' => 'scraped_articles',
                'unique_fields' => ['url'],
            ]
        ],
    ];
}
```

### 4. `JsonExportProcessor.php`
**Utilisation :** Exporte les données scrapées en fichier JSON

**Ce qu'il fait :**
- Sauvegarde tous les items dans un fichier JSON
- Formate le JSON de manière lisible
- Préserve les caractères UTF-8

**Comment l'utiliser :**
```bash
# 1. Copiez le processeur dans votre projet
cp examples/JsonExportProcessor.php app/ItemProcessors/

# 2. Ajoutez-le à votre Spider
```

Exemple d'utilisation dans un Spider :
```php
use App\ItemProcessors\JsonExportProcessor;

class MonSpider extends BasicSpider
{
    public array $itemProcessors = [
        [
            JsonExportProcessor::class,
            [
                'output_path' => 'articles_scraped.json',
                'disk' => 'local',
            ]
        ],
    ];
}
```

### 5. `CustomHeadersMiddleware.php`
**Utilisation :** Ajoute des headers HTTP personnalisés aux requêtes

**Ce qu'il fait :**
- Ajoute un User-Agent personnalisé
- Simule un navigateur réel
- Permet d'ajouter des headers supplémentaires

**Comment l'utiliser :**
```bash
# 1. Copiez le middleware dans votre projet
cp examples/CustomHeadersMiddleware.php app/Middleware/

# 2. Ajoutez-le à votre Spider
```

Exemple d'utilisation dans un Spider :
```php
use App\Middleware\CustomHeadersMiddleware;

class MonSpider extends BasicSpider
{
    public array $downloaderMiddleware = [
        [
            CustomHeadersMiddleware::class,
            [
                'user_agent' => 'Mon Bot/1.0',
                'headers' => [
                    'Referer' => 'https://example.com',
                    'X-Custom-Header' => 'valeur',
                ],
            ]
        ],
    ];
}
```

## 🚀 Exemple complet

Voici un exemple de Spider complet utilisant plusieurs composants :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use App\Middleware\CustomHeadersMiddleware;
use App\ItemProcessors\DatabaseItemProcessor;
use App\ItemProcessors\JsonExportProcessor;

class CompleteBlogSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example-blog.com/articles'
    ];

    // Configuration des middlewares
    public array $downloaderMiddleware = [
        [
            CustomHeadersMiddleware::class,
            [
                'user_agent' => 'MyBlogScraper/1.0',
            ]
        ],
    ];

    // Configuration des processeurs (exécutés dans l'ordre)
    public array $itemProcessors = [
        // Sauvegarder en base de données
        [
            DatabaseItemProcessor::class,
            [
                'table' => 'blog_articles',
                'unique_fields' => ['url'],
            ]
        ],
        // ET exporter en JSON
        [
            JsonExportProcessor::class,
            [
                'output_path' => 'blog_export.json',
            ]
        ],
    ];

    public int $concurrency = 2;
    public int $requestDelay = 2;

    public function parse(Response $response): Generator
    {
        // Votre logique de scraping ici
        yield $this->item([
            'title' => $response->filter('h1')->text(),
            'url' => $response->getUri(),
        ]);
    }
}
```

## 📝 Migration exemple

Pour sauvegarder en base de données, créez une migration :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_articles', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('author')->nullable();
            $table->string('date')->nullable();
            $table->json('categories')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
            
            $table->index('url');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_articles');
    }
};
```

## 🎯 Cas d'utilisation

### Scrapper un site e-commerce

```php
public function parse(Response $response): Generator
{
    $products = $response->filter('.product-card');

    foreach ($products as $product) {
        yield $this->item([
            'name' => $product->filter('.product-name')->text(),
            'price' => $product->filter('.product-price')->text(),
            'image' => $product->filter('.product-image img')->attr('src'),
            'url' => $product->filter('a')->link(),
        ]);
    }
}
```

### Scrapper des données météo

```php
public function parse(Response $response): Generator
{
    yield $this->item([
        'city' => $response->filter('.city-name')->text(),
        'temperature' => $response->filter('.temperature')->text(),
        'conditions' => $response->filter('.conditions')->text(),
        'humidity' => $response->filter('.humidity')->text(),
        'wind_speed' => $response->filter('.wind-speed')->text(),
    ]);
}
```

### Scrapper des offres d'emploi

```php
public function parse(Response $response): Generator
{
    $jobs = $response->filter('.job-listing');

    foreach ($jobs as $job) {
        yield $this->item([
            'title' => $job->filter('.job-title')->text(),
            'company' => $job->filter('.company-name')->text(),
            'location' => $job->filter('.job-location')->text(),
            'salary' => $job->filter('.salary-range')->text(),
            'description' => $job->filter('.job-description')->text(),
            'posted_date' => $job->filter('.posted-date')->text(),
            'url' => $job->filter('a')->link(),
        ]);
    }
}
```

## ⚠️ Conseils importants

1. **Respectez les robots.txt** : Vérifiez toujours les règles du site
2. **Limitez la charge** : Utilisez `$requestDelay` et `$concurrency` appropriés
3. **Testez d'abord** : Testez vos sélecteurs CSS sur une seule page
4. **Gérez les erreurs** : Utilisez des valeurs par défaut avec `text('')`
5. **Loggez vos actions** : Activez `LoggerExtension` pour le debug
6. **Sauvegardez régulièrement** : Utilisez des ItemProcessors pour sauvegarder

## 🔧 Commandes utiles

```bash
# Créer un nouveau Spider
php artisan roach:spider MonSpider

# Exécuter un Spider
php artisan roach:run MonSpider

# Mode interactif (REPL)
php artisan roach:repl

# Voir les logs
tail -f storage/logs/laravel.log
```

## 📚 Ressources

- [Documentation complète en français](../GUIDE_FR.md)
- [Documentation Roach PHP](https://roach-php.dev)
- [Sélecteurs CSS](https://developer.mozilla.org/fr/docs/Web/CSS/CSS_Selectors)

## 💡 Besoin d'aide ?

Consultez le [GUIDE_FR.md](../GUIDE_FR.md) pour une documentation complète avec plus d'exemples et d'explications détaillées.
