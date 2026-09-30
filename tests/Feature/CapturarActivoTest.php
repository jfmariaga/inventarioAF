<?php

namespace Tests\Feature;

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CapturarActivoTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioInventariador(): User
    {
        Permission::firstOrCreate(['name' => 'inventario.ver', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventario.capturar', 'guard_name' => 'web']);
        $rol = Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web']);
        $rol->syncPermissions(['inventario.ver', 'inventario.capturar']);

        $usuario = User::factory()->create();
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

    public function test_captura_verificado_exige_ambas_fotos_y_ubicacion(): void
    {
        Storage::fake('local');

        $usuario = $this->crearUsuarioInventariador();
        $activo = $this->crearActivo();
        $this->crearInventarioAbierto($usuario);

        $this->actingAs($usuario);

        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->set('form.estado', 'verificado')
            ->call('guardar')
            ->assertHasErrors(['form.ubicacion', 'form.fotoEquipo', 'form.fotoPlaca']);
    }

    public function test_captura_completa_marca_el_activo_como_inventariado(): void
    {
        Storage::fake('local');

        $usuario = $this->crearUsuarioInventariador();
        $activo = $this->crearActivo();
        $this->crearInventarioAbierto($usuario);

        $this->actingAs($usuario);

        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->set('form.estado', 'verificado')
            ->set('form.ubicacion', 'Bodega principal')
            ->set('form.fotoEquipo', UploadedFile::fake()->image('equipo.jpg'))
            ->set('form.fotoPlaca', UploadedFile::fake()->image('placa.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $detalle = $activo->inventarioDetalles()->first();
        $this->assertSame('verificado', $detalle->estado);
        $this->assertSame('Bodega principal', $detalle->ubicacion);
        $this->assertSame($usuario->id, $detalle->actualizado_por);
        $this->assertNotNull($detalle->foto_equipo_path);
        $this->assertNotNull($detalle->foto_placa_path);
        $this->assertNull($detalle->reservado_por);
    }

    public function test_no_encontrado_exige_observacion_pero_no_fotos(): void
    {
        Storage::fake('local');

        $usuario = $this->crearUsuarioInventariador();
        $activo = $this->crearActivo();
        $this->crearInventarioAbierto($usuario);

        $this->actingAs($usuario);

        Volt::test('inventario.capturar-activo', ['activo' => $activo])
            ->set('form.estado', 'no_encontrado')
            ->call('guardar')
            ->assertHasErrors(['form.observacion'])
            ->set('form.observacion', 'No se encontró en el área asignada')
            ->call('guardar')
            ->assertHasNoErrors();

        $detalle = $activo->inventarioDetalles()->first();
        $this->assertSame('no_encontrado', $detalle->estado);
        $this->assertNull($detalle->foto_equipo_path);
    }
}
