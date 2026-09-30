<?php

namespace Tests\Feature;

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use App\Models\Inventario;
use App\Models\InventarioDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CumplimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_porcentaje_de_cumplimiento_por_centro_de_costos_y_empresa(): void
    {
        Permission::firstOrCreate(['name' => 'cumplimiento.ver', 'guard_name' => 'web']);
        $rol = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);
        $rol->syncPermissions(['cumplimiento.ver']);

        $usuario = User::factory()->create();
        $usuario->assignRole('Consulta');

        $empresa = Empresa::create(['nombre' => 'LEVAPAN']);
        $centroCostos = CentroCostos::create([
            'codigo' => '101706081',
            'descripcion' => 'LEVALIQUIDA',
            'empresa_id' => $empresa->id,
        ]);

        $inventario = Inventario::create([
            'nombre' => 'Periodo de prueba',
            'fecha_apertura' => now(),
            'estado' => 'abierto',
            'abierto_por' => $usuario->id,
        ]);

        // 4 activos: 2 verificados, 1 no_encontrado, 1 pendiente (sin registro) => 75% inventariado
        $activos = collect(range(1, 4))->map(fn ($i) => Activo::create([
            'numero_activo' => "ACT-{$i}",
            'denominacion' => "Activo {$i}",
            'centro_costos_id' => $centroCostos->id,
            'origen' => 'excel',
        ]));

        InventarioDetalle::create([
            'inventario_id' => $inventario->id,
            'activo_id' => $activos[0]->id,
            'estado' => 'verificado',
            'ubicacion' => 'Bodega',
            'actualizado_por' => $usuario->id,
        ]);

        InventarioDetalle::create([
            'inventario_id' => $inventario->id,
            'activo_id' => $activos[1]->id,
            'estado' => 'verificado',
            'ubicacion' => 'Bodega',
            'actualizado_por' => $usuario->id,
        ]);

        InventarioDetalle::create([
            'inventario_id' => $inventario->id,
            'activo_id' => $activos[2]->id,
            'estado' => 'no_encontrado',
            'observacion' => 'No estaba',
            'actualizado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario);

        Volt::test('cumplimiento.panel')
            ->assertSee('75')
            ->assertSee($centroCostos->codigo)
            ->assertSee('LEVAPAN');
    }
}
