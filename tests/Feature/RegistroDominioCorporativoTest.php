<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistroDominioCorporativoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'inventario.ver', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventario.capturar', 'guard_name' => 'web']);
        $rol = Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web']);
        $rol->syncPermissions(['inventario.ver', 'inventario.capturar']);
    }

    public function test_rechaza_registro_con_dominio_no_permitido(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Usuario Externo')
            ->set('email', 'usuario@gmail.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['email' => 'usuario@gmail.com']);
    }

    #[DataProvider('dominiosPermitidos')]
    public function test_acepta_registro_con_dominios_corporativos_permitidos(string $dominio): void
    {
        $correo = "empleado@{$dominio}";

        Volt::test('pages.auth.register')
            ->set('name', 'Empleado Corporativo')
            ->set('email', $correo)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => $correo]);
    }

    public static function dominiosPermitidos(): array
    {
        return [
            ['levapan.com'],
            ['panalsas.com'],
            ['levacolsas.com'],
        ];
    }

    public function test_verificar_correo_asigna_rol_inventariador_automaticamente(): void
    {
        $usuario = User::factory()->unverified()->create(['email' => 'nuevo@levapan.com']);

        event(new Verified($usuario));

        $this->assertTrue($usuario->fresh()->hasRole('Inventariador'));
    }
}
