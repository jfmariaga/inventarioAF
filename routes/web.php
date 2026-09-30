<?php

use App\Http\Controllers\DescargaExportacionController;
use App\Http\Controllers\FotoActivoController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('inventario', 'inventario.seleccionar-centro-costos')
        ->middleware('can:inventario.ver')
        ->name('inventario.index');

    Volt::route('inventario/{centroCostos}', 'inventario.lista-activos')
        ->middleware('can:inventario.ver')
        ->name('inventario.centro-costos');

    Volt::route('inventario/activos/{activo}/capturar', 'inventario.capturar-activo')
        ->middleware('can:inventario.capturar')
        ->name('inventario.capturar');

    Route::get('fotos/{inventarioDetalle}/{tipo}', [FotoActivoController::class, 'mostrar'])
        ->middleware('can:inventario.ver')
        ->name('fotos.mostrar');

    Volt::route('cumplimiento', 'cumplimiento.panel')
        ->middleware('can:cumplimiento.ver')
        ->name('cumplimiento.index');

    Volt::route('admin/usuarios', 'admin.gestion-usuarios')
        ->middleware('can:admin.usuarios')
        ->name('admin.usuarios');

    Volt::route('admin/periodos', 'admin.periodos')
        ->middleware('can:admin.periodos')
        ->name('admin.periodos');

    Volt::route('admin/catalogos', 'admin.catalogos')
        ->middleware('can:admin.catalogos')
        ->name('admin.catalogos');

    Volt::route('admin/exportaciones', 'admin.solicitar-exportacion')
        ->middleware('can:inventario.exportar')
        ->name('admin.exportaciones');

    Route::get('admin/exportaciones/{exportacion}/descargar', [DescargaExportacionController::class, 'descargar'])
        ->middleware('can:inventario.exportar')
        ->name('exportaciones.descargar');
});

require __DIR__.'/auth.php';
