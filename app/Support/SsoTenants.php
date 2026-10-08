<?php

namespace App\Support;

final class SsoTenants
{
    public static function dominio(string $email): string
    {
        return strtolower(substr(strrchr($email, '@') ?: '', 1));
    }

    public static function configurado(): bool
    {
        return config('services.sso.activo')
            && config('services.sso.client_id')
            && config('services.sso.client_secret');
    }

    /** Clave del tenant cuyo tenant_id es el $tid del token, o null. */
    public static function porTid(?string $tid): ?string
    {
        if (! $tid) {
            return null;
        }

        foreach (config('services.sso.tenants') as $clave => $tenant) {
            if ($tenant['tenant_id'] && strcasecmp($tenant['tenant_id'], $tid) === 0) {
                return $clave;
            }
        }

        return null;
    }

    /** True si el dominio del correo pertenece a ese tenant. */
    public static function dominioPermitido(string $clave, string $email): bool
    {
        // los dominios llevan puntos: no usar la notación de config() para entrar a ellos
        return in_array(self::dominio($email), config("services.sso.tenants.$clave.dominios", []), true);
    }
}
