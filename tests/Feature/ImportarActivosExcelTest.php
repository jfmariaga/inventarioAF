<?php

namespace Tests\Feature;

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use App\Models\Inventario;
use App\Models\InventarioDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportarActivosExcelTest extends TestCase
{
    use RefreshDatabase;

    private function crearExcelDePrueba(string $ruta, bool $conFilaSinPlaca = true): void
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('APP VF');

        $hoja->fromArray([
            'ACTIVO FIJO', 'DENOMINACIÓN ACTIVO FIJO', 'FECHA DE CAPITALIZACIÓN',
            'PLACA', 'UBICACIÓN', 'CECO', 'DESCRIPCIÓN CECO', 'EMPRESA',
        ], null, 'A1');

        $hoja->fromArray([
            '22022-0', 'CONSTRUCCION SISTEMA ENFRIAMIENTO', '2015-01-01',
            'PV-01278', 'TULUA', 101706081, 'LEVALIQUIDA', 'LEVAPAN',
        ], null, 'A2');

        $hoja->fromArray([
            '22023-0', 'OTRO ACTIVO DE PRUEBA', '2016-01-01',
            $conFilaSinPlaca ? null : 'PV-99999', 'TULUA', 101706081, 'LEVALIQUIDA', 'LEVAPAN',
        ], null, 'A3');

        (new Xlsx($spreadsheet))->save($ruta);
    }

    public function test_importa_activos_empresas_y_centros_de_costos(): void
    {
        $ruta = storage_path('app/test-activos.xlsx');
        $this->crearExcelDePrueba($ruta);

        $this->artisan('inventario:importar-activos', ['--archivo' => $ruta])
            ->assertExitCode(0);

        $this->assertSame(1, Empresa::where('nombre', 'LEVAPAN')->count());
        $this->assertSame(1, CentroCostos::where('codigo', '101706081')->count());
        $this->assertSame(2, Activo::count());

        $activoSinPlaca = Activo::where('numero_activo', '22023-0')->first();
        $this->assertNotNull($activoSinPlaca);
        $this->assertNull($activoSinPlaca->placa);

        unlink($ruta);
    }

    public function test_reimportar_el_mismo_archivo_es_idempotente(): void
    {
        $ruta = storage_path('app/test-activos-idempotente.xlsx');
        $this->crearExcelDePrueba($ruta);

        $this->artisan('inventario:importar-activos', ['--archivo' => $ruta])->assertExitCode(0);
        $conteoInicial = Activo::count();

        $this->artisan('inventario:importar-activos', ['--archivo' => $ruta])->assertExitCode(0);

        $this->assertSame($conteoInicial, Activo::count());
        $this->assertSame(1, Empresa::count());
        $this->assertSame(1, CentroCostos::count());

        unlink($ruta);
    }

    public function test_reimportar_no_afecta_registros_de_inventario_ya_capturados(): void
    {
        $ruta = storage_path('app/test-activos-preserva.xlsx');
        $this->crearExcelDePrueba($ruta);

        $this->artisan('inventario:importar-activos', ['--archivo' => $ruta])->assertExitCode(0);

        $usuario = User::factory()->create();
        $inventario = Inventario::create([
            'nombre' => 'Periodo de prueba',
            'fecha_apertura' => now(),
            'abierto_por' => $usuario->id,
        ]);
        $activo = Activo::first();

        $detalle = InventarioDetalle::create([
            'inventario_id' => $inventario->id,
            'activo_id' => $activo->id,
            'estado' => 'verificado',
            'ubicacion' => 'Bodega 1',
            'actualizado_por' => $usuario->id,
        ]);

        $this->artisan('inventario:importar-activos', ['--archivo' => $ruta])->assertExitCode(0);

        $detalle->refresh();
        $this->assertSame('verificado', $detalle->estado);
        $this->assertSame('Bodega 1', $detalle->ubicacion);

        unlink($ruta);
    }
}
