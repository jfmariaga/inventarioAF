<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inventario:purgar-fotos-antiguas')->daily();

// En hosting compartido no hay un proceso `queue:work` corriendo de forma
// permanente, así que procesamos la cola cada minuto vía el scheduler
// (que a su vez depende del cron `php artisan schedule:run` cada minuto).
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
