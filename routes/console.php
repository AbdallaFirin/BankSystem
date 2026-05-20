<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Accrue monthly interest on the last day of each month at 23:00.
 * Run manually anytime: php artisan bank:accrue-interest
 * Dry-run preview:      php artisan bank:accrue-interest --dry-run
 */
Schedule::command('bank:accrue-interest')
    ->monthlyOn(28, '23:00')   // 28th ensures all months are covered
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/interest-accrual.log'));

