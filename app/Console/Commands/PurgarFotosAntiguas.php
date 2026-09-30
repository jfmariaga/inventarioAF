<?php

namespace App\Console\Commands;

use App\Models\InventarioDetalle;
use App\Services\ProcesadorFotoActivo;
use Illuminate\Console\Command;

class PurgarFotosAntiguas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:purgar-fotos-antiguas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina definitivamente las fotos reemplazadas que superaron el periodo de retención (FR-013a)';

    public function handle(ProcesadorFotoActivo $procesador): int
    {
        $diasRetencion = (int) config('inventario.retencion_fotos_dias');

        $detalles = InventarioDetalle::query()
            ->whereNotNull('fotos_reemplazadas_en')
            ->where(function ($query) {
                $query->whereNotNull('foto_equipo_path_anterior')
                    ->orWhereNotNull('foto_placa_path_anterior');
            })
            ->get();

        $procesados = 0;

        foreach ($detalles as $detalle) {
            if (! $procesador->fechaLimiteRetencionSuperada($detalle->fotos_reemplazadas_en, $diasRetencion)) {
                continue;
            }

            $procesador->purgarAnteriores($detalle->foto_equipo_path_anterior, $detalle->foto_placa_path_anterior);

            $detalle->update([
                'foto_equipo_path_anterior' => null,
                'foto_placa_path_anterior' => null,
            ]);

            $procesados++;
        }

        $this->info("Registros purgados: {$procesados}");

        return self::SUCCESS;
    }
}
