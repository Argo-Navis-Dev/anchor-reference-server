<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Used to manually dispatch jobs from the console. E.g.
 * php artisan job:dispatch Sep6WithdrawalsWatcher
 */
class DispatchJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:dispatch {job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $class = '\\App\\Jobs\\' . $this->argument('job');
        dispatch(new $class());
    }
}

