<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

$run = function (string $command, string $name, int $expiresAt = 5) {
    return Schedule::call(function () use ($command) {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            Artisan::call($command);
        } catch (Throwable $e) {
            report($e);
        }
    })->name($name)->withoutOverlapping($expiresAt);
};

$run('workflow:run-scheduled', 'tameshk-workflow-runner', 5)
    ->everyMinute();

$run('queue:work --stop-when-empty --tries=3 --max-time=50', 'tameshk-queue-worker', 5)
    ->everyMinute();

$run('git-backup:run --periodic', 'tameshk-git-periodic-backup', 10)
    ->everyThirtyMinutes();
