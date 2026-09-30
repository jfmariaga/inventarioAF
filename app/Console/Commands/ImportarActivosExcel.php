<?php

namespace App\Console\Commands;

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportarActivosExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:importar-activos {--archivo= : Ruta del Excel a importar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa (de forma idempotente) los activos fijos desde el Excel corporativo a la base de datos';

    public function handle(): int
    {
        $ruta = $this->option('archivo') ?: base_path('documentacion/DATA AF APP VF.xlsx');

        if (! is_file($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");

            return self::FAILURE;
        }

        $this->info("Leyendo {$ruta}...");

        $spreadsheet = IOFactory::createReaderForFile($ruta)
            ->setReadDataOnly(true)
            ->load($ruta);

        $hoja = $spreadsheet->getSheetByName('APP VF') ?? $spreadsheet->getActiveSheet();

        $empresasCreadas = 0;
        $centrosCreados = 0;
        $activosCreados = 0;
        $activosActualizados = 0;

        $filaMaxima = $hoja->getHighestDataRow();

        for ($fila = 2; $fila <= $filaMaxima; $fila++) {
            $numeroActivo = trim((string) $hoja->getCell("A{$fila}")->getValue());

            if ($numeroActivo === '') {
                continue;
            }

            $denominacion = trim((string) $hoja->getCell("B{$fila}")->getValue());
            $fechaCapitalizacion = $this->leerFecha($hoja->getCell("C{$fila}")->getValue());
            $placa = $this->valorONulo($hoja->getCell("D{$fila}")->getValue());
            $ubicacionOriginal = $this->valorONulo($hoja->getCell("E{$fila}")->getValue());
            $codigoCeco = trim((string) $hoja->getCell("F{$fila}")->getValue());
            $descripcionCeco = trim((string) $hoja->getCell("G{$fila}")->getValue());
            $nombreEmpresa = trim((string) $hoja->getCell("H{$fila}")->getValue());

            $empresa = Empresa::firstOrCreate(['nombre' => $nombreEmpresa]);
            if ($empresa->wasRecentlyCreated) {
                $empresasCreadas++;
            }

            $centroCostos = CentroCostos::firstOrNew(['codigo' => $codigoCeco]);
            $centroCostosNuevo = ! $centroCostos->exists;
            $centroCostos->fill([
                'descripcion' => $descripcionCeco,
                'empresa_id' => $empresa->id,
            ])->save();
            if ($centroCostosNuevo) {
                $centrosCreados++;
            }

            $activoExistente = Activo::where('numero_activo', $numeroActivo)->first();

            $activo = Activo::updateOrCreate(
                ['numero_activo' => $numeroActivo],
                [
                    'denominacion' => $denominacion,
                    'fecha_capitalizacion' => $fechaCapitalizacion,
                    'placa' => $placa,
                    'ubicacion_original' => $ubicacionOriginal,
                    'centro_costos_id' => $centroCostos->id,
                    'origen' => 'excel',
                ]
            );

            if ($activoExistente === null) {
                $activosCreados++;
            } else {
                $activosActualizados++;
            }
        }

        $this->info("Empresas creadas: {$empresasCreadas}");
        $this->info("Centros de costos creados: {$centrosCreados}");
        $this->info("Activos creados: {$activosCreados}");
        $this->info("Activos actualizados: {$activosActualizados}");
        $this->info('Totales en BD -> Empresas: '.Empresa::count().', Centros de costos: '.CentroCostos::count().', Activos: '.Activo::count());

        return self::SUCCESS;
    }

    private function valorONulo(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function leerFecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            return Date::excelToDateTimeObject($valor)->format('Y-m-d');
        }

        try {
            return (new \DateTime((string) $valor))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
