<?php

namespace App\Http\Controllers;

use App\Models\Exportacion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescargaExportacionController extends Controller
{
    public function descargar(Exportacion $exportacion): StreamedResponse
    {
        if (! $exportacion->estaLista()) {
            abort(409, 'La exportación aún no está lista. Intenta de nuevo en un momento.');
        }

        abort_unless(
            $exportacion->archivo_path && Storage::disk('local')->exists($exportacion->archivo_path),
            404
        );

        return Storage::disk('local')->download($exportacion->archivo_path, 'inventario.xlsx');
    }
}
