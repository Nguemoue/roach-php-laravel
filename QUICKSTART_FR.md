# Guide de démarrage rapide - Roach PHP Laravel

## 🚀 Démarrage en 5 minutes

Ce guide vous permet de faire votre premier scraping en quelques minutes.

### Étape 1 : Installation (2 minutes)

```bash
# Dans votre projet Laravel
composer require roach-php/laravel
```

### Étape 2 : Créer votre premier Spider (1 minute)

```bash
php artisan roach:spider MonPremierSpider
```

Cela crée le fichier `app/Spiders/MonPremierSpider.php`

### Étape 3 : Configurer le Spider (2 minutes)

Ouvrez `app/Spiders/MonPremierSpider.php` et modifiez :

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class MonPremierSpider extends BasicSpider
{
    // Changez cette URL vers le site que vous voulez scrapper
    public array $startUrls = [
        'https://example.com',
    ];

    public function parse(Response $response): Generator
    {
        // Extraire le titre de la page
        $title = $response->filter('title')->text();
        
        // Afficher le résultat
        yield $this->item([
            'title' => $title,
            'url' => $response->getUri(),
        ]);
    }
}
```

### Étape 4 : Exécuter ! (30 secondes)

```bash
php artisan roach:run MonPremierSpider
```

**Félicitations ! 🎉** Vous venez de faire votre premier scraping avec Roach PHP Laravel !

## 📖 Prochaines étapes

### Scrapper plusieurs pages

Pour scrapper plusieurs URLs :

```php
public array $startUrls = [
    'https://example.com/page1',
    'https://example.com/page2',
    'https://example.com/page3',
];
```

### Extraire plus de données

Utilisez les sélecteurs CSS pour cibler précisément les éléments :

```php
public function parse(Response $response): Generator
{
    yield $this->item([
        'title' => $response->filter('h1.main-title')->text(),
        'description' => $response->filter('.description')->text(),
        'price' => $response->filter('.price')->text(),
        'image' => $response->filter('img.product')->attr('src'),
    ]);
}
```

### Sauvegarder en base de données

1. Copiez l'exemple de processeur :
```bash
cp examples/DatabaseItemProcessor.php app/ItemProcessors/
```

2. Créez une migration :
```bash
php artisan make:migration create_scraped_data_table
```

3. Dans votre Spider, ajoutez :
```php
use App\ItemProcessors\DatabaseItemProcessor;

class MonPremierSpider extends BasicSpider
{
    public array $itemProcessors = [
        DatabaseItemProcessor::class,
    ];
    
    // ... reste du code
}
```

### Exporter en JSON

1. Copiez l'exemple :
```bash
cp examples/JsonExportProcessor.php app/ItemProcessors/
```

2. Utilisez-le dans votre Spider :
```php
use App\ItemProcessors\JsonExportProcessor;

class MonPremierSpider extends BasicSpider
{
    public array $itemProcessors = [
        [
            JsonExportProcessor::class,
            ['output_path' => 'mes_donnees.json'],
        ],
    ];
}
```

## 💡 Astuces pour débuter

### 1. Tester vos sélecteurs CSS

Avant de scrapper, testez vos sélecteurs dans la console du navigateur :

```javascript
// Dans la console du navigateur (F12)
document.querySelector('h1.main-title').textContent
document.querySelectorAll('.product-item')
```

### 2. Gérer les erreurs

Utilisez des valeurs par défaut pour éviter les erreurs :

```php
$title = $response->filter('h1')->text('Pas de titre');
$price = $response->filter('.price')->text('N/A');
```

### 3. Limiter la vitesse

Pour ne pas surcharger les serveurs :

```php
public int $concurrency = 1;      // Une requête à la fois
public int $requestDelay = 2;     // 2 secondes entre chaque requête
```

### 4. Voir les logs

Pour débugger, vérifiez les logs Laravel :

```bash
tail -f storage/logs/laravel.log
```

## 🔍 Exemples de sélecteurs CSS courants

| Ce que vous voulez | Sélecteur CSS |
|-------------------|---------------|
| Titre de la page | `h1` ou `title` |
| Tous les liens | `a` |
| Éléments avec une classe | `.ma-classe` |
| Élément avec un ID | `#mon-id` |
| Paragraphes dans un div | `div p` |
| Premier élément | `.liste > :first-child` |
| Attribut data | `[data-product-id]` |

## ⚠️ Points importants

1. **Vérifiez robots.txt** : `https://example.com/robots.txt`
2. **Soyez respectueux** : Ne surchargez pas les serveurs
3. **Vérifiez les conditions d'utilisation** du site
4. **Testez sur une seule page** avant de tout scrapper

## 📚 Documentation complète

Pour aller plus loin, consultez :

- **[Guide complet en français](GUIDE_FR.md)** - Tous les détails et explications
- **[Exemples pratiques](examples/)** - Code d'exemple prêt à utiliser
- **[Documentation officielle](https://roach-php.dev)** - Documentation Roach PHP

## 🆘 Problèmes courants

### "Class not found"
Vérifiez que vous avez bien le bon namespace dans votre Spider :
```php
namespace App\Spiders;
```

### "Call to a member function text() on null"
L'élément n'existe pas. Utilisez une valeur par défaut :
```php
$title = $response->filter('h1')->text('Titre par défaut');
```

### Le scraping est trop lent
Augmentez la concurrence :
```php
public int $concurrency = 5;  // 5 requêtes simultanées
```

### Je ne trouve pas le bon sélecteur
Utilisez l'inspecteur du navigateur (F12) pour trouver les sélecteurs CSS corrects.

## ✨ Prêt à aller plus loin ?

Consultez les exemples dans le dossier `examples/` pour des cas d'usage plus avancés :
- BlogSpider : Scrapping avec pagination
- DatabaseItemProcessor : Sauvegarde en BDD
- JsonExportProcessor : Export JSON
- CustomHeadersMiddleware : Headers personnalisés

**Bon scraping ! 🕷️**
