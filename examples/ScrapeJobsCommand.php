<?php

namespace App\Console\Commands;

use App\Spiders\JobScraperSpider;
use Illuminate\Console\Command;
use RoachPHP\Roach;

/**
 * Example Artisan Command for running spiders
 * 
 * This command demonstrates how to:
 * - Create a custom artisan command to run spiders
 * - Display progress and results
 * - Handle errors
 * 
 * Usage:
 * 1. Create this command:
 *    php artisan make:command ScrapeJobsCommand
 * 
 * 2. Run the command:
 *    php artisan scrape:jobs
 * 
 * 3. Run with specific URL:
 *    php artisan scrape:jobs --url="https://jobs.doopinet.com/search/?dref=welcome-msg"
 */
class ScrapeJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scrape:jobs 
                            {--url= : The URL to scrape}
                            {--save : Save results to database}';

    /**
     * The console command description.
     */
    protected $description = 'Scrape job listings from jobs.doopinet.com';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting job scraper...');

        try {
            // Get the URL option if provided
            $url = $this->option('url');
            
            // If URL is provided, override the spider's start URLs
            if ($url) {
                // You can create an overridden configuration
                $items = Roach::collectSpider(JobScraperSpider::class, overrides: [
                    'startUrls' => [$url],
                ]);
            } else {
                // Use default configuration
                $items = Roach::collectSpider(JobScraperSpider::class);
            }

            $this->info('Scraping completed!');
            $this->info('Total jobs scraped: ' . count($items));

            // Display a sample of the results
            if (count($items) > 0) {
                $this->newLine();
                $this->info('Sample of scraped jobs:');
                $this->table(
                    ['Title', 'Company', 'Location'],
                    collect($items)->take(5)->map(fn($item) => [
                        $item['title'] ?? 'N/A',
                        $item['company'] ?? 'N/A',
                        $item['location'] ?? 'N/A',
                    ])->toArray()
                );
            }

            // Optionally save to database
            if ($this->option('save')) {
                $this->info('Saving results to database...');
                foreach ($items as $item) {
                    \DB::table('jobs')->updateOrInsert(
                        ['url' => $item['url']],
                        [
                            'title' => $item['title'] ?? null,
                            'company' => $item['company'] ?? null,
                            'location' => $item['location'] ?? null,
                            'description' => $item['description'] ?? null,
                            'salary' => $item['salary'] ?? null,
                            'job_type' => $item['job_type'] ?? null,
                            'posted_date' => $item['posted_date'] ?? null,
                            'scraped_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
                $this->info('Results saved successfully!');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
