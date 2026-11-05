<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarUbicacionesExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:importar-ubicaciones {--archivo= : Ruta del Excel a importar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa (de forma idempotente) el catálogo de ubicaciones desde el Excel corporativo a la base de datos';

    public function handle(): int
    {
        $ruta = $this->option('archivo') ?: base_path('documentacion/Ubicaciones AF Colombia.xlsx');

        if (! is_file($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");

            return self::FAILURE;
        }

        $this->info("Leyendo {$ruta}...");

        $spreadsheet = IOFactory::createReaderForFile($ruta)
            ->setReadDataOnly(true)
            ->load($ruta);

        $hoja = $spreadsheet->getSheetByName('AF') ?? $spreadsheet->getActiveSheet();

        $empresasCreadas = 0;
        $ubicacionesCreadas = 0;

        $filaMaxima = $hoja->getHighestDataRow();

        for ($fila = 2; $fila <= $filaMaxima; $fila++) {
            $ubicacionTexto = trim((string) $hoja->getCell("A{$fila}")->getValue());

            if ($ubicacionTexto === '') {
                continue;
            }

            $detalleTexto = trim((string) $hoja->getCell("B{$fila}")->getValue());
            $nombreEmpresa = trim((string) $hoja->getCell("C{$fila}")->getValue());

            $empresa = Empresa::firstOrCreate(['nombre' => $nombreEmpresa]);
            if ($empresa->wasRecentlyCreated) {
                $empresasCreadas++;
            }

            $ubicacion = Ubicacion::firstOrCreate([
                'empresa_id' => $empresa->id,
                'ubicacion' => $ubicacionTexto,
                'detalle_ubicacion' => $detalleTexto,
            ]);

            if ($ubicacion->wasRecentlyCreated) {
                $ubicacionesCreadas++;
            }
        }

        $this->info("Empresas creadas: {$empresasCreadas}");
        $this->info("Ubicaciones creadas: {$ubicacionesCreadas}");
        $this->info('Totales en BD -> Empresas: '.Empresa::count().', Ubicaciones: '.Ubicacion::count());

        return self::SUCCESS;
    }
}
