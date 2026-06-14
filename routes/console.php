<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:endfield-data')
    ->weeklyOn(1, '02:00')
    ->withoutOverlapping()
    ->description('Sync Endfield game data from warfarin.wiki');
