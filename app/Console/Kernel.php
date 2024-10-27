<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Console;

use App\Jobs\FundTestAccounts;
use App\Jobs\Sep6DepositPaymentsWatcher;
use App\Jobs\Sep6PendingInfoWatcher;
use App\Jobs\Sep6DepositPendingTrustWatcher;
use App\Jobs\Sep6WithdrawalsWatcher;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        Log::debug(
            'Scheduling the Anchor jobs.',
            ['context' => 'shared'],
        );
        $schedule->job(new Sep6PendingInfoWatcher)->everyMinute();
        $schedule->job(new Sep6DepositPendingTrustWatcher)->everyMinute();
        $schedule->job(new Sep6DepositPaymentsWatcher)->everyMinute();
        $schedule->job(new Sep6WithdrawalsWatcher)->everyMinute();

        $schedule->job(new FundTestAccounts)->everySixHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
