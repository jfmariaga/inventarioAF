<?php

namespace App\Jobs;

use App\Models\Exportacion;
use App\Models\InventarioDetalle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerarExportacionInventario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $exportacionId) {}

    public function handle(): void
    {
        $exportacion = Exportacion::findOrFail($this->exportacionId);

        try {
            $ruta = $this->generar($exportacion);

            $exportacion->update([
                'estado' => 'lista',
                'archivo_path' => $ruta,
                'generado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            $exportacion->update(['estado' => 'fallida']);

            throw $e;
        }
    }

    private function generar(Exportacion $exportacion): string
    {
        $detalles = InventarioDetalle::query()
            ->with(['activo.centroCostos.empresa', 'actualizadoPor'])
            ->whereHas('activo.centroCostos', function ($query) use ($exportacion) {
                if ($exportacion->empresa_id) {
                    $query->where('empresa_id', $exportacion->empresa_id);
                }

                if ($exportacion->centro_costos_id) {
                    $query->where('id', $exportacion->centro_costos_id);
                }
            })
            ->get();

        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Inventario');

        $encabezados = [
            'A' => 'Activo', 'B' => 'Denominación', 'C' => 'Placa', 'D' => 'Empresa',
            'E' => 'Centro de costos', 'F' => 'Estado', 'G' => 'Ubicación',
            'H' => 'Observación', 'I' => 'Autor', 'J' => 'Fecha', 'K' => 'Foto equipo', 'L' => 'Foto placa',
        ];
        foreach ($encabezados as $columna => $titulo) {
            $hoja->setCellValue("{$columna}1", $titulo);
        }

        $fila = 2;
        foreach ($detalles as $detalle) {
            $activo = $detalle->activo;
            $hoja->setCellValue("A{$fila}", $activo->numero_activo);
            $hoja->setCellValue("B{$fila}", $activo->denominacion);
            $hoja->setCellValue("C{$fila}", $activo->placa);
            $hoja->setCellValue("D{$fila}", $activo->centroCostos->empresa->nombre);
            $hoja->setCellValue("E{$fila}", $activo->centroCostos->codigo.' - '.$activo->centroCostos->descripcion);
            $hoja->setCellValue("F{$fila}", $detalle->estado);
            $hoja->setCellValue("G{$fila}", $detalle->ubicacion);
            $hoja->setCellValue("H{$fila}", $detalle->observacion);
            $hoja->setCellValue("I{$fila}", $detalle->actualizadoPor?->name);
            $hoja->setCellValue("J{$fila}", optional($detalle->updated_at)->format('Y-m-d H:i'));

            $hoja->getRowDimension($fila)->setRowHeight(60);

            $this->incrustarFoto($hoja, $detalle->foto_equipo_path, "K{$fila}");
            $this->incrustarFoto($hoja, $detalle->foto_placa_path, "L{$fila}");

            $fila++;
        }

        foreach (array_keys($encabezados) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }
        $hoja->getColumnDimension('K')->setWidth(20);
        $hoja->getColumnDimension('L')->setWidth(20);

        $this->agregarHojaResumen($spreadsheet, $exportacion);

        $nombreArchivo = 'exportaciones/inventario-'.now()->timestamp.'.xlsx';
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'inv').'.xlsx';
        (new Xlsx($spreadsheet))->save($rutaTemporal);

        Storage::disk('local')->put($nombreArchivo, file_get_contents($rutaTemporal));
        unlink($rutaTemporal);

        return $nombreArchivo;
    }

    private function incrustarFoto($hoja, ?string $rutaRelativa, string $celda): void
    {
        if (! $rutaRelativa || ! Storage::disk('local')->exists($rutaRelativa)) {
            return;
        }

        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $dibujo = new Drawing;
        $dibujo->setPath($rutaAbsoluta);
        $dibujo->setHeight(80);
        $dibujo->setCoordinates($celda);
        $dibujo->setWorksheet($hoja);
    }

    private function agregarHojaResumen(Spreadsheet $spreadsheet, Exportacion $exportacion): void
    {
        $resumen = $spreadsheet->createSheet();
        $resumen->setTitle('Cumplimiento');
        $resumen->setCellValue('A1', 'Centro de costos');
        $resumen->setCellValue('B1', 'Empresa');
        $resumen->setCellValue('C1', 'Total activos');
        $resumen->setCellValue('D1', 'Inventariados');
        $resumen->setCellValue('E1', '% Cumplimiento');

        $query = DB::table('centros_costos')
            ->join('empresas', 'empresas.id', '=', 'centros_costos.empresa_id')
            ->leftJoin('activos', 'activos.centro_costos_id', '=', 'centros_costos.id')
            ->leftJoin('inventario_detalles', function ($join) {
                $join->on('inventario_detalles.activo_id', '=', 'activos.id');
            })
            ->select('centros_costos.codigo', 'centros_costos.descripcion', 'empresas.nombre as empresa_nombre')
            ->selectRaw('COUNT(DISTINCT activos.id) as total_activos')
            ->selectRaw("COUNT(DISTINCT CASE WHEN inventario_detalles.estado IN ('verificado','no_encontrado') THEN activos.id END) as activos_inventariados");

        if ($exportacion->empresa_id) {
            $query->where('empresas.id', $exportacion->empresa_id);
        }
        if ($exportacion->centro_costos_id) {
            $query->where('centros_costos.id', $exportacion->centro_costos_id);
        }

        $filas = $query
            ->groupBy('centros_costos.id', 'centros_costos.codigo', 'centros_costos.descripcion', 'empresas.nombre')
            ->orderBy('empresas.nombre')
            ->orderBy('centros_costos.codigo')
            ->get();

        $fila = 2;
        foreach ($filas as $datos) {
            $porcentaje = $datos->total_activos > 0
                ? round(($datos->activos_inventariados / $datos->total_activos) * 100)
                : 0;

            $resumen->setCellValue("A{$fila}", $datos->codigo.' - '.$datos->descripcion);
            $resumen->setCellValue("B{$fila}", $datos->empresa_nombre);
            $resumen->setCellValue("C{$fila}", $datos->total_activos);
            $resumen->setCellValue("D{$fila}", $datos->activos_inventariados);
            $resumen->setCellValue("E{$fila}", $porcentaje.'%');
            $fila++;
        }
    }
}
