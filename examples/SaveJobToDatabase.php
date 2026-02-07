<?php

namespace App\ItemProcessors;

use Illuminate\Support\Facades\DB;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

/**
 * Example Item Processor for saving scraped job data to a database
 * 
 * This processor demonstrates how to:
 * - Process scraped items
 * - Save data to a database
 * - Handle errors gracefully
 * 
 * Usage:
 * 1. Create the jobs table:
 *    php artisan make:migration create_jobs_table
 * 
 * 2. Add this processor to your spider's $itemProcessors array:
 *    public array $itemProcessors = [
 *        SaveJobToDatabase::class,
 *    ];
 * 
 * 3. Run your spider:
 *    php artisan roach:run JobScraperSpider
 */
class SaveJobToDatabase implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        $data = $item->all();

        try {
            // Save the job to the database
            // Make sure you have a 'jobs' table with these columns
            DB::table('jobs')->updateOrInsert(
                ['url' => $data['url']], // Use URL as unique identifier
                [
                    'title' => $data['title'] ?? null,
                    'company' => $data['company'] ?? null,
                    'location' => $data['location'] ?? null,
                    'description' => $data['description'] ?? null,
                    'salary' => $data['salary'] ?? null,
                    'job_type' => $data['job_type'] ?? null,
                    'posted_date' => $data['posted_date'] ?? null,
                    'scraped_at' => now(),
                    'updated_at' => now(),
                ]
            );

            logger()->info('Saved job to database: ' . $data['title']);
        } catch (\Exception $e) {
            logger()->error('Failed to save job to database: ' . $e->getMessage());
        }

        return $item;
    }
}
