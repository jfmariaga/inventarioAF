<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventario.ver', 'inventario.capturar', 'inventario.exportar', 'cumplimiento.ver', 'admin.usuarios', 'admin.roles'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web'])
            ->syncPermissions(['inventario.ver', 'inventario.capturar', 'inventario.exportar', 'cumplimiento.ver', 'admin.usuarios', 'admin.roles']);

        Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web'])
            ->syncPermissions(['inventario.ver', 'inventario.capturar']);

        Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web'])
            ->syncPermissions(['cumplimiento.ver']);
    }

    public function test_administrador_puede_cambiar_el_rol_de_un_usuario(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $usuario = User::factory()->create();
        $usuario->assignRole('Consulta');

        $this->actingAs($admin);

        Volt::test('admin.gestion-usuarios')
            ->call('cambiarRol', $usuario->id, 'Inventariador')
            ->assertHasNoErrors();

        $this->assertTrue($usuario->fresh()->hasRole('Inventariador'));
        $this->assertFalse($usuario->fresh()->hasRole('Consulta'));
    }

    public function test_usuario_consulta_no_puede_capturar_ni_exportar(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Consulta');

        $this->assertFalse($usuario->can('inventario.capturar'));
        $this->assertFalse($usuario->can('inventario.exportar'));
        $this->assertFalse($usuario->can('inventario.ver'));
        $this->assertTrue($usuario->can('cumplimiento.ver'));
    }

    public function test_usuario_sin_permiso_admin_usuarios_no_accede_a_la_ruta(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Inventariador');

        $this->actingAs($usuario)
            ->get('/admin/usuarios')
            ->assertForbidden();
    }
}
