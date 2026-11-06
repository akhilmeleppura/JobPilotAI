<?php

namespace App\Modules\Jobs\app\Console\Commands;

use App\Models\User;
use App\Modules\Jobs\app\Services\JobDiscoveryService;
use Illuminate\Console\Command;

class FetchJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jobpilot:fetch-jobs {--query=PHP Laravel : Search keyword} {--user-id= : Target candidate user ID}';

    /**
     * The console command description.
     */
    protected $description = 'Ingest latest developer job openings across all integrated free job providers';

    public function handle(JobDiscoveryService $discoveryService): int
    {
        $this->info("Initializing JobPilot AI Multi-Source Discovery Pipeline...");

        $userId = $this->option('user-id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $this->error("No active user found in system to associate discovered vacancies.");
            return Command::FAILURE;
        }

        $query = $this->option('query') ?: 'PHP Laravel';

        $this->comment("Fetching vacancies matching query: '{$query}' for User: {$user->name}...");

        $result = $discoveryService->fetchAndIngestAll($user, [
            'query' => $query,
            'limit' => 15,
        ]);

        $this->info("=== Ingestion Summary ===");
        $this->line("Total Listings Evaluated: {$result['fetched']}");
        $this->line("New Vacancies Created:    {$result['new_vacancies']}");
        $this->line("Existing/Deduplicated:    {$result['duplicates']}");

        $rows = [];
        foreach ($result['provider_stats'] as $key => $stat) {
            $rows[] = [$stat['name'], $stat['total'], $stat['new']];
        }
        $this->table(['Provider', 'Fetched', 'New Ingested'], $rows);

        $this->info("Job discovery sync complete!");
        return Command::SUCCESS;
    }
}

