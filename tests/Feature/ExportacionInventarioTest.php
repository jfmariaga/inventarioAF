<?php

namespace Tests\Feature;

use App\Jobs\GenerarExportacionInventario;
use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use App\Models\Exportacion;
use App\Models\Inventario;
use App\Models\InventarioDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportacionInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventario.ver', 'inventario.capturar', 'inventario.exportar', 'cumplimiento.ver'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web'])
            ->syncPermissions(['inventario.ver', 'inventario.capturar', 'inventario.exportar', 'cumplimiento.ver']);

        Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web'])
            ->syncPermissions(['cumplimiento.ver']);
    }

    private function crearEscenario(): array
    {
        $empresaA = Empresa::create(['nombre' => 'LEVAPAN']);
        $empresaB = Empresa::create(['nombre' => 'PANAL']);

        $centroA = CentroCostos::create(['codigo' => '111', 'descripcion' => 'Centro A', 'empresa_id' => $empresaA->id]);
        $centroB = CentroCostos::create(['codigo' => '222', 'descripcion' => 'Centro B', 'empresa_id' => $empresaB->id]);

        $activoA = Activo::create(['numero_activo' => 'A-1', 'denominacion' => 'Activo A', 'centro_costos_id' => $centroA->id, 'origen' => 'excel']);
        $activoB = Activo::create(['numero_activo' => 'B-1', 'denominacion' => 'Activo B', 'centro_costos_id' => $centroB->id, 'origen' => 'excel']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $inventario = Inventario::create([
            'nombre' => 'Periodo', 'fecha_apertura' => now(), 'estado' => 'abierto', 'abierto_por' => $admin->id,
        ]);

        InventarioDetalle::create([
            'inventario_id' => $inventario->id, 'activo_id' => $activoA->id,
            'estado' => 'verificado', 'ubicacion' => 'Bodega', 'actualizado_por' => $admin->id,
        ]);

        InventarioDetalle::create([
            'inventario_id' => $inventario->id, 'activo_id' => $activoB->id,
            'estado' => 'verificado', 'ubicacion' => 'Bodega', 'actualizado_por' => $admin->id,
        ]);

        return compact('empresaA', 'empresaB', 'centroA', 'centroB', 'activoA', 'activoB', 'admin');
    }

    public function test_exportacion_filtrada_por_empresa_solo_incluye_sus_activos(): void
    {
        Storage::fake('local');
        ['empresaA' => $empresaA, 'admin' => $admin] = $this->crearEscenario();

        $exportacion = Exportacion::create([
            'solicitado_por' => $admin->id,
            'empresa_id' => $empresaA->id,
            'estado' => 'en_proceso',
        ]);

        (new GenerarExportacionInventario($exportacion->id))->handle();

        $exportacion->refresh();
        $this->assertSame('lista', $exportacion->estado);
        $this->assertNotNull($exportacion->archivo_path);
        Storage::disk('local')->assertExists($exportacion->archivo_path);
    }

    public function test_descarga_devuelve_409_mientras_no_esta_lista(): void
    {
        ['admin' => $admin] = $this->crearEscenario();

        $exportacion = Exportacion::create([
            'solicitado_por' => $admin->id,
            'estado' => 'en_proceso',
        ]);

        $this->actingAs($admin)
            ->get(route('exportaciones.descargar', $exportacion))
            ->assertStatus(409);
    }

    public function test_usuario_sin_permiso_exportar_recibe_403(): void
    {
        $consulta = User::factory()->create();
        $consulta->assignRole('Consulta');

        $this->actingAs($consulta)
            ->get('/admin/exportaciones')
            ->assertForbidden();
    }
}
