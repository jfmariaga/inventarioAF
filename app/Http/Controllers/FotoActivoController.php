<?php

namespace App\Http\Controllers;

use App\Models\InventarioDetalle;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoActivoController extends Controller
{
    public function mostrar(InventarioDetalle $inventarioDetalle, string $tipo): StreamedResponse
    {
        abort_unless(in_array($tipo, ['equipo', 'placa'], true), 404);

        $columna = $tipo === 'equipo' ? 'foto_equipo_path' : 'foto_placa_path';
        $ruta = $inventarioDetalle->{$columna};

        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta);
    }
}
