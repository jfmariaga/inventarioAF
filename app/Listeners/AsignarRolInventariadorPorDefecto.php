<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;

class AsignarRolInventariadorPorDefecto
{
    /**
     * Al verificar su correo, el usuario obtiene acceso inmediato con el
     * rol Inventariador, sin aprobación manual de un Administrador
     * (spec FR-006, clarificación del 2026-09-30).
     */
    public function handle(Verified $event): void
    {
        $usuario = $event->user;

        if (! $usuario->hasAnyRole(['Administrador', 'Inventariador', 'Consulta'])) {
            $usuario->assignRole('Inventariador');
        }
    }
}
