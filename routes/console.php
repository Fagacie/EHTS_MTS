<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('etms:reconcile-session-seats')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('etms:send-session-reminders')
    ->hourly()
    ->withoutOverlapping(10);

Schedule::command('etms:backup')
    ->timezone('Asia/Kuala_Lumpur')
    ->dailyAt('02:30')
    ->withoutOverlapping(30);
