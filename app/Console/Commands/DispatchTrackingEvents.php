<?php

namespace App\Console\Commands;

use App\Services\TrackingService;
use Illuminate\Console\Command;

class DispatchTrackingEvents extends Command
{
    protected $signature = 'tracking:dispatch {--limit=500}';

    protected $description = 'Send queued server-side events to Meta CAPI, TikTok Events API, Snapchat CAPI and GA4';

    public function handle(TrackingService $service): int
    {
        $result = $service->dispatchPending((int) $this->option('limit'));
        $this->info("Events: {$result['events']}, sent: {$result['sent']}, failed: {$result['failed']}");

        return self::SUCCESS;
    }
}
