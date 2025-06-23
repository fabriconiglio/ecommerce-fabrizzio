<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configurar el scheduler
Schedule::command('sync:products')->everySixHours()
    ->appendOutputTo(storage_path('logs/sync_products.log'));

Schedule::command('sync:categories')->daily()
    ->appendOutputTo(storage_path('logs/sync_categories.log'));

Schedule::command('cache:clear-custom --expired')->hourly()
    ->name('clear-expired-cache');

Schedule::command('health:check --detailed')
    ->daily()
    ->appendOutputTo(storage_path('logs/health_check.log'));

// Comando para verificar salud de APIs
Schedule::call(function () {
    $platziHealthy = app(\App\Services\PlatziApiService::class)->isHealthy();
    $randomUserHealthy = app(\App\Services\RandomUserService::class)->isHealthy();
    
    Log::info('API Health Check', [
        'platzi_api' => $platziHealthy ? 'healthy' : 'down',
        'randomuser_api' => $randomUserHealthy ? 'healthy' : 'down',
    ]);
})->everyFifteenMinutes()->name('api-health-check');
