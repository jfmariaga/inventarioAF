<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SsoTenants;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;

class SsoController extends Controller
{
    public function redirect()
    {
        abort_unless(SsoTenants::configurado(), 404);

        return $this->driver()->redirect();
    }

    public function callback(Request $request)
    {
        abort_unless(SsoTenants::configurado(), 404);

        if ($request->has('error')) {
            Log::warning('SSO: Entra devolvio error', ['error' => $request->input('error')]);

            return $this->fallo('No se pudo completar el inicio de sesion con Microsoft.');
        }

        try {
            $sso = $this->driver()->user();
        } catch (\Throwable $e) {
            Log::warning('SSO: fallo al obtener el usuario', ['mensaje' => $e->getMessage()]);

            return $this->fallo('No se pudo completar el inicio de sesion con Microsoft. Intenta de nuevo.');
        }

        $raw = $sso->getRaw();
        $email = strtolower(trim($raw['mail'] ?? $raw['userPrincipalName'] ?? ''));
        $oid = $sso->getId();

        // el tenant sale del id_token que Microsoft entrega directo en el intercambio del código
        $tenant = SsoTenants::porTid($this->tid($sso->accessTokenResponseBody['id_token'] ?? null));

        // el tenant debe estar permitido y el correo debe ser de un dominio de ESE tenant
        if (! $tenant || ! $email || ! SsoTenants::dominioPermitido($tenant, $email)) {
            Log::warning('SSO: tenant o dominio no permitido', ['tenant' => $tenant, 'email' => $email]);

            return $this->fallo('Tu cuenta de Microsoft no esta autorizada para ingresar.');
        }

        $user = User::where('azure_oid', $oid)->first() ?? User::where('email', $email)->first();

        if ($user && ! $user->activo) {
            return $this->fallo('Tu cuenta esta inactiva. Contacta con el administrador.');
        }

        $esNuevo = ! $user;

        if ($esNuevo) {
            $user = $this->crear($email, $raw);
        }

        $user->forceFill([
            'azure_oid' => $oid,
            'sso_tenant' => $tenant,
            'last_login_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        // dispara el flujo existente de asignación de rol por defecto (ver AsignarRolInventariadorPorDefecto)
        if ($esNuevo) {
            event(new Verified($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function driver()
    {
        return Socialite::driver('azure')
            ->scopes(['openid', 'profile', 'email'])
            ->setConfig(new Config(
                config('services.sso.client_id'),
                config('services.sso.client_secret'),
                route('sso.callback'),
                ['tenant' => 'organizations'] // cuentas de trabajo de cualquier directorio; se filtra por tid
            ));
    }

    /** Lee el tid del id_token (JWT). No hace falta verificar la firma: llega directo de Microsoft por TLS. */
    private function tid(?string $idToken): ?string
    {
        $partes = explode('.', (string) $idToken);

        if (count($partes) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($partes[1], '-_', '+/')), true);

        return $payload['tid'] ?? null;
    }

    /** Aprovisionamiento JIT: crea el usuario si no existe. */
    private function crear(string $email, array $raw): User
    {
        $nombre = trim($raw['givenName'] ?? '') ?: ($raw['displayName'] ?? Str::before($email, '@'));

        return User::create([
            'name' => $nombre,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'activo' => true,
        ]);
    }

    private function fallo(string $mensaje)
    {
        return redirect()->route('login')->withErrors(['sso' => $mensaje]);
    }
}
