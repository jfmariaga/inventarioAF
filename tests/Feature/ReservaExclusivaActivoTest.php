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

class ReservaExclusivaActivoTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioInventariador(string $nombre = 'Inventariador'): User
    {
        Permission::firstOrCreate(['name' => 'inventario.ver', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventario.capturar', 'guard_name' => 'web']);
        $rol = Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web']);
        $rol->syncPermissions(['inventario.ver', 'inventario.capturar']);

        $usuario = User::factory()->create(['name' => $nombre]);
        $usuario->assignRole('Inventariador');

        return $usuario;
    }

    private function crearActivo(): Activo
    {
        $empresa = Empresa::create(['nombre' => 'LEVAPAN']);
        $centroCostos = CentroCostos::create([
            'codigo' => '101706081',
            'descripcion' => 'LEVALIQUIDA',
            'empresa_id' => $empresa->id,
        ]);

        return Activo::create([
            'numero_activo' => '22022-0',
            'denominacion' => 'Equipo de prueba',
            'centro_costos_id' => $centroCostos->id,
            'origen' => 'excel',
        ]);
    }

    private function crearInventarioAbierto(User $usuario): Inventario
    {
        return Inventario::create([
            'nombre' => 'Periodo de prueba',
            'fecha_apertura' => now(),
            'estado' => 'abierto',
            'abierto_por' => $usuario->id,
        ]);
    }

    public function test_segundo_usuario_no_puede_abrir_un_activo_ya_reservado(): void
    {
        $primerUsuario = $this->crearUsuarioInventariador('Primer Usuario');
        $segundoUsuario = $this->crearUsuarioInventariador('Segundo Usuario');
        $activo = $this->crearActivo();
        $this->crearInventarioAbierto($primerUsuario);

        $this->actingAs($primerUsuario);
        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->assertSet('reservaAdquirida', true);

        $this->actingAs($segundoUsuario);
        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->assertSet('reservaAdquirida', false)
            ->assertSet('reservadoPorNombre', 'Primer Usuario')
            ->assertSee('Primer Usuario');
    }

    public function test_cancelar_libera_la_reserva_para_otro_usuario(): void
    {
        $primerUsuario = $this->crearUsuarioInventariador('Primer Usuario');
        $segundoUsuario = $this->crearUsuarioInventariador('Segundo Usuario');
        $activo = $this->crearActivo();
        $this->crearInventarioAbierto($primerUsuario);

        $this->actingAs($primerUsuario);
        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->call('cancelar');

        $this->actingAs($segundoUsuario);
        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->assertSet('reservaAdquirida', true);
    }

    public function test_reserva_expirada_permite_que_otro_usuario_la_adquiera(): void
    {
        $primerUsuario = $this->crearUsuarioInventariador('Primer Usuario');
        $segundoUsuario = $this->crearUsuarioInventariador('Segundo Usuario');
        $activo = $this->crearActivo();
        $inventario = $this->crearInventarioAbierto($primerUsuario);

        $detalle = InventarioDetalle::create([
            'inventario_id' => $inventario->id,
            'activo_id' => $activo->id,
            'estado' => 'pendiente',
            'reservado_por' => $primerUsuario->id,
            'reservado_en' => now()->subMinutes(20),
        ]);

        $this->actingAs($segundoUsuario);
        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->assertSet('reservaAdquirida', true);

        $detalle->refresh();
        $this->assertSame($segundoUsuario->id, $detalle->reservado_por);
    }
}
