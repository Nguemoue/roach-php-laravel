# Guide de démarrage rapide / Quick Start Guide

[English version below](#english-version)

## Version Française

### Exemple complet pour jobs.doopinet.com

Ce guide vous montre comment utiliser Roach PHP avec Laravel pour scraper le site https://jobs.doopinet.com/search/?dref=welcome-msg

#### Installation

```bash
composer require roach-php/laravel
```

#### Étape 1 : Créer le Spider

```bash
php artisan roach:spider JobScraperSpider
```

#### Étape 2 : Copier le code du spider

Copiez le contenu de `examples/JobScraperSpider.php` dans le fichier généré `app/Spiders/JobScraperSpider.php`.

#### Étape 3 : Exécuter le spider

```bash
php artisan roach:run JobScraperSpider
```

#### Étape 4 (Optionnel) : Sauvegarder en base de données

Créez une migration :

```bash
php artisan make:migration create_jobs_table
```

Ajoutez dans la migration :

```php
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
```

Exécutez la migration :

```bash
php artisan migrate
```

Créez le processeur d'items :

```bash
# Créez le fichier app/ItemProcessors/SaveJobToDatabase.php
```

Copiez le contenu de `examples/SaveJobToDatabase.php` et ajoutez-le à votre spider :

```php
public array $itemProcessors = [
    \App\ItemProcessors\SaveJobToDatabase::class,
];
```

#### Autres méthodes d'exécution

**Dans un contrôleur :**

```php
use RoachPHP\Roach;
use App\Spiders\JobScraperSpider;

public function scrapeJobs()
{
    $items = Roach::collectSpider(JobScraperSpider::class);
    return response()->json($items);
}
```

**Via une commande Artisan personnalisée :**

Voir `examples/ScrapeJobsCommand.php` pour un exemple complet.

### Adapter aux sélecteurs réels

⚠️ **Important** : Les sélecteurs CSS dans l'exemple (`job-listing`, `.job-title`, etc.) sont génériques. Vous devez les adapter à la structure HTML réelle du site jobs.doopinet.com :

1. Visitez https://jobs.doopinet.com/search/?dref=welcome-msg
2. Ouvrez les outils de développement (F12)
3. Inspectez la structure HTML des offres d'emploi
4. Modifiez les sélecteurs dans votre spider en conséquence

### Documentation complète

Consultez les fichiers suivants pour plus de détails :
- `examples/README.md` - Guide complet en français
- `examples/README_EN.md` - Guide complet en anglais

---

## English Version

### Complete example for jobs.doopinet.com

This guide shows you how to use Roach PHP with Laravel to scrape https://jobs.doopinet.com/search/?dref=welcome-msg

#### Installation

```bash
composer require roach-php/laravel
```

#### Step 1: Create the Spider

```bash
php artisan roach:spider JobScraperSpider
```

#### Step 2: Copy the spider code

Copy the content from `examples/JobScraperSpider.php` to the generated file `app/Spiders/JobScraperSpider.php`.

#### Step 3: Run the spider

```bash
php artisan roach:run JobScraperSpider
```

#### Step 4 (Optional): Save to database

Create a migration:

```bash
php artisan make:migration create_jobs_table
```

Add to the migration:

```php
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
```

Run the migration:

```bash
php artisan migrate
```

Create the item processor:

```bash
# Create the file app/ItemProcessors/SaveJobToDatabase.php
```

Copy the content from `examples/SaveJobToDatabase.php` and add it to your spider:

```php
public array $itemProcessors = [
    \App\ItemProcessors\SaveJobToDatabase::class,
];
```

#### Other execution methods

**In a controller:**

```php
use RoachPHP\Roach;
use App\Spiders\JobScraperSpider;

public function scrapeJobs()
{
    $items = Roach::collectSpider(JobScraperSpider::class);
    return response()->json($items);
}
```

**Via a custom Artisan command:**

See `examples/ScrapeJobsCommand.php` for a complete example.

### Adapting to actual selectors

⚠️ **Important**: The CSS selectors in the example (`.job-listing`, `.job-title`, etc.) are generic. You must adapt them to the actual HTML structure of jobs.doopinet.com:

1. Visit https://jobs.doopinet.com/search/?dref=welcome-msg
2. Open developer tools (F12)
3. Inspect the HTML structure of job listings
4. Modify the selectors in your spider accordingly

### Full documentation

Check out these files for more details:
- `examples/README.md` - Complete guide in French
- `examples/README_EN.md` - Complete guide in English
