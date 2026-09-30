<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodosInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventario.ver', 'admin.periodos'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web'])
            ->syncPermissions(['inventario.ver', 'admin.periodos']);

        Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web'])
            ->syncPermissions(['inventario.ver']);
    }

    public function test_administrador_puede_abrir_un_periodo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin);

        Volt::test('admin.periodos')
            ->set('nombre', 'Inventario 2026')
            ->set('fechaApertura', now()->toDateString())
            ->call('abrir')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventarios', [
            'nombre' => 'Inventario 2026',
            'estado' => 'abierto',
            'abierto_por' => $admin->id,
        ]);
    }

    public function test_no_se_puede_abrir_un_segundo_periodo_mientras_hay_uno_abierto(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        Inventario::create([
            'nombre' => 'Periodo existente',
            'fecha_apertura' => now(),
            'estado' => 'abierto',
            'abierto_por' => $admin->id,
        ]);

        $this->actingAs($admin);

        Volt::test('admin.periodos')
            ->set('nombre', 'Otro periodo')
            ->set('fechaApertura', now()->toDateString())
            ->call('abrir')
            ->assertHasErrors('nombre');

        $this->assertSame(1, Inventario::count());
    }

    public function test_cerrar_y_reabrir_un_periodo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $inventario = Inventario::create([
            'nombre' => 'Periodo', 'fecha_apertura' => now(), 'estado' => 'abierto', 'abierto_por' => $admin->id,
        ]);

        $this->actingAs($admin);

        $componente = Volt::test('admin.periodos')
            ->call('cerrar', $inventario->id);

        $this->assertSame('cerrado', $inventario->fresh()->estado);
        $this->assertSame($admin->id, $inventario->fresh()->cerrado_por);

        $componente->call('reabrir', $inventario->id);

        $this->assertSame('abierto', $inventario->fresh()->estado);
        $this->assertSame($admin->id, $inventario->fresh()->reabierto_por);
    }

    public function test_inventariador_no_accede_a_la_ruta_de_periodos(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Inventariador');

        $this->actingAs($usuario)
            ->get('/admin/periodos')
            ->assertForbidden();
    }
}
