<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class MonitorSlaNotifications extends Command
{
    protected $signature = 'notifications:monitor-sla';

    protected $description = 'Create due-soon and breached SLA notifications without duplicates.';

    public function handle(NotificationService $notifications): int
    {
        $result = $notifications->monitorSla();
        $this->info("SLA notifications checked. Due soon: {$result['due_soon']}; breached: {$result['breached']}.");

        return self::SUCCESS;
    }
}
