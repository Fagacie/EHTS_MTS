<?php

namespace App\Console\Commands;

use App\Services\Booking\SessionBookingService;
use Illuminate\Console\Command;

class ReconcileSessionSeatsCommand extends Command
{
    protected $signature = 'etms:reconcile-session-seats';

    protected $description = 'Expire elapsed 15-minute physical session holds and reconcile seat capacity counters (D-019, D-033)';

    public function handle(SessionBookingService $bookingService): int
    {
        $summary = $bookingService->reconcileAllActiveSessions();

        $this->info(sprintf(
            'Reconciled %d session(s); expired %d elapsed seat hold(s).',
            $summary['sessions_checked'],
            $summary['holds_expired'],
        ));

        return self::SUCCESS;
    }
}
