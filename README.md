# Web Scraping for Laravel

This is the Laravel adapter for [Roach](https://roach-php.dev), the complete web scraping toolkit for PHP.

## Installation

Install the package via composer

```bash
composer require roach-php/laravel
```

## Documentation

Check out the [full documentation](https://roach-php.dev/docs/laravel) to get up and running.

## Examples

Want to see Roach PHP in action? Check out the [examples directory](./examples) which contains practical examples including:

- **Job Scraper Spider** - A complete example of scraping job listings from a website (e.g., jobs.doopinet.com)
- **Database Integration** - How to save scraped data to your Laravel database
- **Controller Integration** - Using spiders in your Laravel controllers
- **Artisan Commands** - Creating custom commands to run your spiders
- **Detailed README** - Step-by-step guides in both [French](./examples/README.md) and [English](./examples/README_EN.md)

### Quick Example

```php
// Create a spider
php artisan roach:spider JobScraper

// Run the spider
php artisan roach:run JobScraper

// Or use it programmatically
use RoachPHP\Roach;
$items = Roach::collectSpider(JobScraper::class);
```

See the [examples directory](./examples) for complete, working examples.

## Credits

- [Kai Sassnowski](https://github.com/ksassnowski)
- [All contributors](https://github.com/roach-php/laravel/contributors)


## License

MIT
