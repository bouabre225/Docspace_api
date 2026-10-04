<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retry des notifications échouées
Schedule::command('notifications:retry-failed')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Nettoyage des jobs en échec (> 30 jours) — prune au lieu de flush destructeur
Schedule::command('queue:prune-failed --hours=720')
    ->monthly();

// Nettoyage des notifications lues (> 90 jours) par chunks
Schedule::call(function () {
    \App\Models\Notification::where('lu', true)
        ->where('created_at', '<', now()->subDays(90))
        ->chunkById(1000, fn($rows) => \App\Models\Notification::whereIn('id', $rows->pluck('id'))->delete());
})->weekly()->name('clean-old-notifications')->withoutOverlapping();
