<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Example Spider for scraping job listings from https://jobs.doopinet.com/search/?dref=welcome-msg
 * 
 * This spider demonstrates how to:
 * - Configure start URLs
 * - Parse HTML responses
 * - Extract data from job listings
 * - Follow pagination links
 * - Save scraped data
 * 
 * Usage:
 * 1. Create this spider in your Laravel app:
 *    php artisan roach:spider JobScraperSpider
 * 
 * 2. Run the spider:
 *    php artisan roach:run JobScraperSpider
 * 
 * 3. Or run it programmatically in a controller or command:
 *    use RoachPHP\Roach;
 *    $items = Roach::collectSpider(JobScraperSpider::class);
 */
class JobScraperSpider extends BasicSpider
{
    /**
     * The starting URLs for the spider
     */
    public array $startUrls = [
        'https://jobs.doopinet.com/search/?dref=welcome-msg',
    ];

    /**
     * Downloader middleware to use for this spider
     */
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    /**
     * Spider middleware to use for this spider
     */
    public array $spiderMiddleware = [
        //
    ];

    /**
     * Item processors to use for this spider
     * You can add custom processors here to save data to database, export to CSV, etc.
     */
    public array $itemProcessors = [
        // Example: SaveToDatabase::class,
        // Example: ExportToCSV::class,
    ];

    /**
     * Extensions to use for this spider
     */
    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    /**
     * Maximum number of concurrent requests
     */
    public int $concurrency = 2;

    /**
     * Delay between requests in seconds (be respectful to the server)
     */
    public int $requestDelay = 1;

    /**
     * Parse the response and extract job listings
     *
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        // Get the HTML crawler from the response
        $crawler = $response->filter('body');

        // Example: Extract job listings from the page
        // Adjust the selectors based on the actual HTML structure of jobs.doopinet.com
        
        // Find all job cards/listings on the page
        $jobListings = $crawler->filter('.job-listing, .job-card, article.job, div[data-job-id]');

        foreach ($jobListings as $jobElement) {
            $job = new Crawler($jobElement);

            // Extract job details
            // Note: You'll need to adjust these selectors based on the actual website structure
            $title = $this->extractText($job, '.job-title, h2.title, .job-heading');
            $company = $this->extractText($job, '.company-name, .employer, .company');
            $location = $this->extractText($job, '.job-location, .location, .city');
            $description = $this->extractText($job, '.job-description, .description, .summary');
            $salary = $this->extractText($job, '.salary, .compensation, .pay');
            $jobType = $this->extractText($job, '.job-type, .employment-type, .type');
            $postedDate = $this->extractText($job, '.posted-date, .date-posted, time');
            
            // Extract the job URL
            $jobUrl = $this->extractAttribute($job, 'a', 'href');
            if ($jobUrl && !str_starts_with($jobUrl, 'http')) {
                $jobUrl = $response->getUri() . $jobUrl;
            }

            // Yield the extracted data as an item
            if ($title) {
                yield $this->item([
                    'title' => $title,
                    'company' => $company,
                    'location' => $location,
                    'description' => $description,
                    'salary' => $salary,
                    'job_type' => $jobType,
                    'posted_date' => $postedDate,
                    'url' => $jobUrl,
                    'scraped_at' => now()->toIso8601String(),
                ]);
            }
        }

        // Follow pagination links to scrape all pages
        // Adjust the selector based on the actual pagination structure
        $nextPageLink = $crawler->filter('.pagination .next, .next-page, a[rel="next"]')->first();
        
        if ($nextPageLink->count() > 0) {
            $nextUrl = $nextPageLink->attr('href');
            
            // Make sure we have an absolute URL
            if ($nextUrl && !str_starts_with($nextUrl, 'http')) {
                // Parse the base URL properly to handle paths
                $baseUrl = parse_url($response->getUri());
                $scheme = $baseUrl['scheme'] ?? 'https';
                $host = $baseUrl['host'] ?? '';
                
                // If nextUrl starts with /, it's absolute path
                if (str_starts_with($nextUrl, '/')) {
                    $nextUrl = $scheme . '://' . $host . $nextUrl;
                } else {
                    // Relative URL - append to the directory of current URL
                    $path = $baseUrl['path'] ?? '/';
                    $directory = dirname($path);
                    $nextUrl = $scheme . '://' . $host . $directory . '/' . $nextUrl;
                }
            }

            if ($nextUrl) {
                // Follow the pagination link
                yield $this->request('GET', $nextUrl);
            }
        }
    }

    /**
     * Helper method to safely extract text from an element
     */
    private function extractText(Crawler $crawler, string $selector): ?string
    {
        try {
            $element = $crawler->filter($selector)->first();
            return $element->count() > 0 ? trim($element->text()) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper method to safely extract an attribute from an element
     */
    private function extractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
    {
        try {
            $element = $crawler->filter($selector)->first();
            return $element->count() > 0 ? $element->attr($attribute) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
