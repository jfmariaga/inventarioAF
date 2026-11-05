# SSO con Microsoft (Entra ID)

Login con un solo botón "Continuar con Microsoft" para usuarios de los directorios de **Levapan** y **Panal**. Convive con el login tradicional de correo/contraseña (se puede desactivar con `SSO_SOLO`).

## Cómo funciona

1. El usuario hace clic en "Continuar con Microsoft" → `GET /sso/redirect`.
2. Microsoft lo autentica y redirige a `GET /sso/levapan/callback` con un código.
3. La app intercambia el código por el perfil del usuario y por el `id_token` (JWT), del cual lee el claim `tid` (tenant id).
4. Se valida que:
   - el `tid` corresponda a uno de los tenants permitidos (Levapan o Panal), y
   - el dominio del correo pertenezca a ese mismo tenant.

   Si algo no calza, se rechaza el login — el tenant **no se elige en pantalla**, siempre sale del token.
5. Si el usuario ya existe (por `azure_oid` o por `email`), se actualiza y se inicia sesión.
6. Si no existe, se crea (aprovisionamiento JIT) y se dispara el evento `Verified`, que activa el listener ya existente `AsignarRolInventariadorPorDefecto` para asignarle el rol **Inventariador** automáticamente — el mismo flujo que ya usaba el registro manual, sin duplicar lógica de roles.

## Archivos involucrados

| Archivo | Rol |
|---|---|
| `app/Http/Controllers/Auth/SsoController.php` | `redirect()` y `callback()` — el flujo completo de OAuth |
| `app/Support/SsoTenants.php` | Helper: valida configuración, resuelve tenant por `tid`, valida dominio de correo |
| `config/services.php` | Entrada `azure` (relleno exigido por Socialite) + entrada `sso` (tenants, dominios, client id/secret) |
| `routes/auth.php` | Rutas `sso.redirect` y `sso.callback`, dentro del grupo `guest` |
| `app/Providers/AppServiceProvider.php` | Registra el listener de `socialiteproviders/microsoft-azure` (Laravel 12 no trae `EventServiceProvider` por defecto) |
| `resources/views/livewire/pages/auth/login.blade.php` | Botón de Microsoft, mensaje de error de SSO, oculta el form de contraseña si `SSO_SOLO=true` |
| `resources/css/app.css` | Clase `.btn-microsoft` |
| `database/migrations/2026_10_08_161639_add_sso_fields_to_users_table.php` | Agrega `azure_oid`, `sso_tenant`, `last_login_at` a `users` |

## Variables de entorno

```env
SSO_ACTIVO=true          # apaga/prende el SSO completo (sin esto, /sso/redirect da 404)
SSO_SOLO=false            # true = oculta el login por correo/contraseña, solo queda el botón
SSO_CLIENT_ID=            # Client ID de la app "Levapan Activos Fijos" en Entra ID
SSO_CLIENT_SECRET=        # secreto de esa app — está en el .env real de cada ambiente, nunca en este repo
SSO_LEVAPAN_TENANT=       # tenant id del directorio de Levapan
SSO_PANAL_TENANT=         # tenant id del directorio de Panal
```

Los valores reales están en el `.env` de cada ambiente (local y Hostinger) y en `.env.example` como plantilla vacía. No se documentan aquí a propósito, para no dejar credenciales en el historial de git.

Los dominios de correo permitidos por tenant están en código, en `config/services.php` (`services.sso.tenants.*.dominios`), no en `.env`:

- **Panal**: `panalsas.com`
- **Levapan**: `levapan.com`, `levacolsas.com`, `levapan.com.ec`, `levapan.com.do`

## Configuración en Azure / Entra ID

- App registrada: **Levapan Activos Fijos** (una sola app, no hay app separada para Panal).
- Tipo de cuenta: **Varios inquilinos de Entra ID** (multi-tenant) — así una sola app sirve a los dos directorios.
- Redirect URI registrada: `https://inventario.panalapp.com/sso/levapan/callback` (debe coincidir exacto con el esquema/dominio/ruta — sin barra final).
- El administrador del directorio de Panal ya dio el consentimiento de admin para esta app (una sola vez, no hay que repetirlo).
- Client ID / secret: ver variables de entorno arriba.

## Desplegar un cambio (Hostinger)

El hosting es compartido y **no tiene Node/npm por SSH**, así que el build de frontend se hace en local y se sube a mano.

```bash
# en el servidor por SSH, para cambios de PHP/config
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Si el cambio toca CSS/JS (por ejemplo `resources/css/app.css` o cualquier vista):

```bash
# en local
npm run build
```

Y luego subir `public/build/` completo (reemplazando lo que haya) por el Administrador de Archivos / FTP de Hostinger, porque `public/build` está en `.gitignore` y nunca viaja con git.

## Troubleshooting

- **`AADSTS50011: redirect URI mismatch`**: el `redirect_uri` que Laravel generó no es exactamente el registrado en Azure. Pasa típicamente al probar en `localhost`/`127.0.0.1` en vez del dominio real — Azure solo tiene registrado `https://inventario.panalapp.com/sso/levapan/callback`.
- **`Class "Laravel\Socialite\Facades\Socialite" not found`**: falta `composer install` en el servidor (el `vendor/` no tiene el paquete).
- **Botón sin estilos**: `public/build/` está desactualizado respecto al CSS fuente. Recompilar con `npm run build` y subir la carpeta (ver sección de despliegue).
- **Cuenta rechazada con "no está autorizada para ingresar"**: el `tid` del token no es Levapan ni Panal, o el dominio del correo no está en la lista de `config/services.php` para ese tenant. Los detalles quedan en `storage/logs/laravel.log` con el prefijo `SSO:`.
- **Cuenta inactiva**: el campo `activo` del usuario está en `false` — hay que activarlo manualmente, el SSO no lo hace.

## Qué falta / decisiones pendientes

- Confirmar con pruebas reales una cuenta de cada tenant (Levapan y Panal), nueva y existente.
- Decidir si se activa `SSO_SOLO=true` para retirar del todo el login por contraseña, o si conviven ambos indefinidamente.
