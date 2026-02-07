<?php

namespace App\Http\Controllers;

use App\Spiders\JobScraperSpider;
use Illuminate\Http\JsonResponse;
use RoachPHP\Roach;

/**
 * Example Controller for running spiders and displaying scraped data
 * 
 * This controller demonstrates how to:
 * - Run spiders from a controller
 * - Collect scraped data
 * - Return data as JSON response
 * 
 * Usage in routes/web.php:
 * Route::get('/scrape-jobs', [JobScraperController::class, 'scrapeJobs']);
 * Route::get('/jobs', [JobScraperController::class, 'index']);
 */
class JobScraperController extends Controller
{
    /**
     * Run the job scraper spider and return collected items
     */
    public function scrapeJobs(): JsonResponse
    {
        // Run the spider and collect all items
        $items = Roach::collectSpider(JobScraperSpider::class);

        return response()->json([
            'success' => true,
            'message' => 'Successfully scraped ' . count($items) . ' jobs',
            'data' => $items,
        ]);
    }

    /**
     * Display all scraped jobs from the database
     */
    public function index(): JsonResponse
    {
        // If you're using the SaveJobToDatabase processor
        $jobs = \DB::table('jobs')
            ->orderBy('scraped_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $jobs->count(),
            'data' => $jobs,
        ]);
    }

    /**
     * Run spider in the background (recommended for large scraping jobs)
     */
    public function scrapeJobsAsync(): JsonResponse
    {
        // Dispatch a job to run the spider in the background
        // First create a job: php artisan make:job ScrapeJobsJob
        
        // \App\Jobs\ScrapeJobsJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Scraping job has been queued',
        ]);
    }
}
