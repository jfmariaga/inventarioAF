# Quickstart: Validación end-to-end

Prerrequisitos: `.env` apuntando a MySQL (`inventario_af`), servidor Laragon
corriendo, `php artisan migrate` aplicado, `npm run build` (o `npm run dev`)
corriendo para Livewire/Volt + Tailwind.

## 1. Base de pruebas MySQL (gate de constitución)

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS inventario_af_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

Esperado: la suite corre contra MySQL (confirmar en la salida que no se usa
SQLite), todos los tests en verde.

## 2. Carga inicial del Excel (US1)

```bash
php artisan inventario:importar-activos
php artisan inventario:importar-activos   # segunda vez, debe ser idempotente
```

Esperado: `activos`, `empresas` (3), `centros_costos` (~395) poblados en la
primera corrida; la segunda corrida no cambia los conteos.

## 3. Roles y periodo inicial

```bash
php artisan db:seed --class=RolesYPermisosSeeder
```

Crear (manual o vía tinker/seeder de desarrollo) un periodo `inventarios`
con `estado = 'abierto'` y un usuario Administrador de prueba.

## 4. Registro y verificación (US3)

1. Ir a `/register`, registrarse con un correo `@levapan.com` → debe
   permitir el registro y enviar correo de verificación (revisar
   `storage/logs/laravel.log` si `MAIL_MAILER=log`).
2. Intentar registrarse con un correo `@gmail.com` → debe rechazarse con
   mensaje claro.
3. Verificar el correo del primer registro → debe iniciar sesión con rol
   `Inventariador` ya asignado (confirmar en `/admin/usuarios` con un
   Administrador, o vía `php artisan tinker`).

## 5. Captura de inventario y reserva exclusiva (US2)

1. Iniciar sesión como Inventariador, ir a `/inventario`, seleccionar un
   centro de costos con activos.
2. Abrir un activo, subir ambas fotos, ubicación, estado `verificado` →
   guardar. Confirmar que queda marcado inventariado con autor y fecha.
3. Abrir el mismo activo en dos sesiones/navegadores distintos
   simultáneamente → la segunda debe ver "en gestión por {primer usuario}"
   en vez del formulario.
4. Marcar un activo como `no_encontrado` con observación, sin fotos →
   debe guardar sin exigir fotos.

## 6. Cumplimiento (US4)

Ir a `/cumplimiento` → el % por centro de costos recién trabajado debe
reflejar las capturas hechas en el paso anterior, con el semáforo
correspondiente.

## 7. Administración de usuarios (US5)

Como Administrador, ir a `/admin/usuarios`, cambiar el rol de un usuario de
`Consulta` a `Inventariador` → ese usuario debe poder capturar en su
siguiente acción.

## 8. Exportación (US6)

1. Como Administrador, ir a `/admin/exportaciones`, solicitar una
   exportación filtrada por la empresa trabajada en el paso 5.
2. Esperar a que el job de cola la procese (`php artisan queue:work` debe
   estar corriendo), confirmar que el estado pasa a `lista`.
3. Descargar el archivo y abrirlo: debe contener los activos de esa
   empresa, con ambas fotos incrustadas y el resumen de cumplimiento.
4. Como usuario sin permiso `inventario.exportar`, confirmar que
   `/admin/exportaciones` y la descarga devuelven acceso denegado.
