<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

$run = function (string $command, string $name) {
    return Schedule::call(function () use ($command) {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        Artisan::call($command);
    })->name($name);
};

$run('queue:work --stop-when-empty --tries=3 --max-time=50', 'tameshk-queue-worker')
    ->everyMinute()
    ->withoutOverlapping();

$run('workflow:run-scheduled', 'tameshk-workflow-runner')
    ->everyMinute()
    ->withoutOverlapping();
