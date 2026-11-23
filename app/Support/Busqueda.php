<?php

namespace App\Support;

class Busqueda
{
    /**
     * Quita los ceros iniciales de un término de búsqueda (p. ej. para que
     * "0245429" encuentre el activo "245429" y viceversa). Si el término
     * son solo ceros, lo deja intacto para no vaciar la búsqueda.
     */
    public static function sinCerosIniciales(string $termino): string
    {
        $sinCeros = ltrim($termino, '0');

        return $sinCeros === '' ? $termino : $sinCeros;
    }
}
