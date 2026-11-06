<?php

namespace App\Console\Commands;

use App\Shared\Services\EmergencyStopService;
use Illuminate\Console\Command;

class EmergencyStopCommand extends Command
{
    protected $signature = 'jobpilot:emergency-stop 
                            {--resume : Disengage the kill switch and resume operations}
                            {--reason= : Reason for engaging emergency stop}';

    protected $description = 'Engage or disengage the JobPilot AI global kill switch';

    public function handle(EmergencyStopService $service): int
    {
        if ($this->option('resume')) {
            $service->resume();
            $this->info('Emergency stop has been DISENGAGED. Normal operations resumed.');
            return self::SUCCESS;
        }

        $reason = $this->option('reason') ?: 'CLI manual emergency stop command invoked';
        $service->trigger($reason);

        $this->warn("EMERGENCY STOP ENGAGED: {$reason}");
        $this->line('All background queues, email outreach, AI workflows, and external publishing are paused.');
        return self::SUCCESS;
    }
}

