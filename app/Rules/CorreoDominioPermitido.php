<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CorreoDominioPermitido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dominio = strtolower((string) substr((string) strrchr((string) $value, '@'), 1));

        $dominiosPermitidos = config('inventario.dominios_correo_permitidos', []);

        if (! in_array($dominio, $dominiosPermitidos, true)) {
            $lista = implode(', ', $dominiosPermitidos);
            $fail("Solo se permite el registro con un correo corporativo de los siguientes dominios: {$lista}.");
        }
    }
}
