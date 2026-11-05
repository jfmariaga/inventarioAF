<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Carga el catálogo de ubicaciones (empresa -> ubicación -> detalle) desde
 * documentacion/Ubicaciones AF Colombia.xlsx, reutilizando el comando
 * inventario:importar-ubicaciones. Idempotente: se puede correr varias veces
 * sin duplicar registros (ver App\Console\Commands\ImportarUbicacionesExcel).
 *
 * Uso en producción: php artisan db:seed --class=UbicacionesSeeder
 */
class UbicacionesSeeder extends Seeder
{
    public function run(): void
    {
        $exitCode = Artisan::call('inventario:importar-ubicaciones');

        $this->command?->getOutput()->write(Artisan::output());

        if ($exitCode !== 0) {
            throw new \RuntimeException('inventario:importar-ubicaciones terminó con errores (ver salida arriba).');
        }
    }
}
