# Laravel + Roach PHP Usage Examples

This directory contains complete examples showing how to use the `roach-php/laravel` package for web scraping with Laravel, specifically for scraping job listings from https://jobs.doopinet.com/search/?dref=welcome-msg.

## Installation

1. Install the package via Composer:

```bash
composer require roach-php/laravel
```

2. Publish the configuration (optional):

```bash
php artisan vendor:publish --provider="RoachPHP\Laravel\RoachServiceProvider"
```

## Example Files

This directory contains the following example files:

- **JobScraperSpider.php** - Spider for scraping job listings
- **SaveJobToDatabase.php** - Item processor for saving data to database
- **JobScraperController.php** - Controller for running spiders from routes
- **ScrapeJobsCommand.php** - Artisan command for running spiders

## Quick Start Guide

### 1. Create a Spider

Use the Artisan command to generate a new spider:

```bash
php artisan roach:spider JobScraperSpider
```

This will create a file at `app/Spiders/JobScraperSpider.php`. Copy the content from the example `JobScraperSpider.php` into this file.

### 2. Spider Configuration

The basic spider contains:

```php
<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Http\Response;

class JobScraperSpider extends BasicSpider
{
    // Starting URLs
    public array $startUrls = [
        'https://jobs.doopinet.com/search/?dref=welcome-msg',
    ];

    // Number of concurrent requests
    public int $concurrency = 2;

    // Delay between requests (in seconds)
    public int $requestDelay = 1;

    public function parse(Response $response): Generator
    {
        // Extract data from the page
        $crawler = $response->filter('body');
        
        // Scraping logic here...
    }
}
```

### 3. Run the Spider

There are several ways to run your spider:

#### Option A: Via Command Line

```bash
php artisan roach:run JobScraperSpider
```

#### Option B: Programmatically in a Controller

```php
use RoachPHP\Roach;
use App\Spiders\JobScraperSpider;

$items = Roach::collectSpider(JobScraperSpider::class);
```

#### Option C: Via a Custom Artisan Command

Create a command:

```bash
php artisan make:command ScrapeJobsCommand
```

Then use the code from the example `ScrapeJobsCommand.php` and run:

```bash
php artisan scrape:jobs
```

### 4. Extract Data

In the `parse()` method, use Symfony DomCrawler to extract data:

```php
public function parse(Response $response): Generator
{
    $crawler = $response->filter('body');
    
    // Find all job listing elements
    $jobListings = $crawler->filter('.job-listing');
    
    foreach ($jobListings as $jobElement) {
        $job = new Crawler($jobElement);
        
        // Extract data
        yield $this->item([
            'title' => $job->filter('.job-title')->text(),
            'company' => $job->filter('.company-name')->text(),
            'location' => $job->filter('.location')->text(),
            'url' => $job->filter('a')->attr('href'),
        ]);
    }
}
```

### 5. Save Data

#### Option A: Create an Item Processor

Create a processor to save to database:

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

Then add it to your spider:

```php
public array $itemProcessors = [
    SaveJobToDatabase::class,
];
```

#### Option B: Process Results After Execution

```php
$items = Roach::collectSpider(JobScraperSpider::class);

foreach ($items as $item) {
    DB::table('jobs')->insert($item);
}
```

### 6. Create Database Table

Create a migration to store job listings:

```bash
php artisan make:migration create_jobs_table
```

In the migration:

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

Run the migration:

```bash
php artisan migrate
```

## Advanced Features

### Handle Pagination

To follow pagination links:

```php
public function parse(Response $response): Generator
{
    // Extract data...
    
    // Follow "Next page" link
    $nextPage = $response->filter('.pagination .next')->attr('href');
    if ($nextPage) {
        yield $this->request('GET', $nextPage);
    }
}
```

### Custom Middleware

Add middleware to modify requests/responses:

```php
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Downloader\Middleware\UserAgentMiddleware;

public array $downloaderMiddleware = [
    RequestDeduplicationMiddleware::class,
    [UserAgentMiddleware::class, ['userAgent' => 'My Bot/1.0']],
];
```

### Background Execution

For large scraping jobs, use Laravel queues:

```bash
php artisan make:job ScrapeJobsJob
```

In the job:

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

Then dispatch the job:

```php
ScrapeJobsJob::dispatch();
```

### Error Handling

Add error handling in your spider:

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

## Complete Example for jobs.doopinet.com

Here's a complete example adapted for jobs.doopinet.com:

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
        
        // Adjust selectors based on actual HTML structure of the site
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

## Tips and Best Practices

1. **Respect websites**: Use a reasonable delay between requests (`requestDelay`)
2. **Handle errors**: Add try-catch blocks to prevent crashes
3. **Test progressively**: Start with a single page before scraping the entire site
4. **Inspect HTML**: Use browser developer tools to identify the correct CSS selectors
5. **Logs**: Use `logger()` to track progress and debug
6. **User-Agent**: Use an appropriate User-Agent to identify your bot

## Resources

- [Official Roach PHP Documentation](https://roach-php.dev)
- [Roach Laravel Documentation](https://roach-php.dev/docs/laravel)
- [Symfony DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html)

## License

These examples are provided under the MIT license, like the main package.
