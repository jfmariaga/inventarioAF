<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcesadorFotoActivo
{
    private const DISCO = 'local';

    private const LADO_MAXIMO = 1600;

    private const CALIDAD_JPEG = 80;

    /**
     * Redimensiona y recomprime la foto subida, la guarda en el disco
     * privado, y devuelve la ruta relativa guardada.
     */
    public function guardar(UploadedFile $archivo, string $carpeta, string $tipo): string
    {
        if (! $this->esImagen($archivo)) {
            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'bin');
            $ruta = "{$carpeta}/{$tipo}-".now()->timestamp.".{$extension}";

            Storage::disk(self::DISCO)->put($ruta, $archivo->get());

            return $ruta;
        }

        $imagen = Image::read($archivo)->scaleDown(width: self::LADO_MAXIMO, height: self::LADO_MAXIMO);
        $codificada = $imagen->toJpeg(quality: self::CALIDAD_JPEG);

        $ruta = "{$carpeta}/{$tipo}-".now()->timestamp.'.jpg';

        Storage::disk(self::DISCO)->put($ruta, (string) $codificada);

        return $ruta;
    }

    private function esImagen(UploadedFile $archivo): bool
    {
        return str_starts_with((string) $archivo->getMimeType(), 'image/');
    }

    /**
     * Reemplaza la foto vigente: mueve la actual a la ruta "_anterior" y
     * guarda la nueva como vigente (FR-013a). Devuelve [rutaNueva,
     * rutaAnterior].
     *
     * @return array{0: string, 1: ?string}
     */
    public function reemplazar(UploadedFile $archivo, string $carpeta, string $tipo, ?string $rutaVigenteActual): array
    {
        $rutaAnterior = null;

        if ($rutaVigenteActual && Storage::disk(self::DISCO)->exists($rutaVigenteActual)) {
            $rutaAnterior = "{$carpeta}/{$tipo}-anterior-".now()->timestamp.'.jpg';
            Storage::disk(self::DISCO)->move($rutaVigenteActual, $rutaAnterior);
        }

        $rutaNueva = $this->guardar($archivo, $carpeta, $tipo);

        return [$rutaNueva, $rutaAnterior];
    }

    /**
     * Elimina físicamente las fotos "_anterior" cuyo reemplazo supere los
     * días de retención configurados. Devuelve cuántos archivos borró.
     */
    public function purgarAnteriores(?string $rutaEquipoAnterior = null, ?string $rutaPlacaAnterior = null): int
    {
        $borrados = 0;

        foreach ([$rutaEquipoAnterior, $rutaPlacaAnterior] as $ruta) {
            if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
                Storage::disk(self::DISCO)->delete($ruta);
                $borrados++;
            }
        }

        return $borrados;
    }

    public function fechaLimiteRetencionSuperada(?Carbon $fotosReemplazadasEn, int $diasRetencion): bool
    {
        return $fotosReemplazadasEn !== null
            && $fotosReemplazadasEn->lt(now()->subDays($diasRetencion));
    }
}
