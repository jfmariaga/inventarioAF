# Data Model: Inventario de Activos Fijos

Todas las tablas usan `id` autoincremental como llave primaria, y
`created_at`/`updated_at` salvo que se indique lo contrario. Nombres de
tabla y columna en español, según la constitución del proyecto (Principio
III). Los `ENUM` se documentan con sus valores exactos.

## empresas

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| nombre | string(100) unique | `LEVAPAN`, `PANAL`, `LEVACOL` |
| timestamps | | |

## centros_costos

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| codigo | string(20) unique | CECO del Excel (p. ej. `101706081`) |
| descripcion | string(150) | p. ej. `LEVALIQUIDA` |
| empresa_id | FK → empresas.id, `onDelete('restrict')` | fuente única de la relación con Empresa (spec: empresa se deriva del centro de costos, no se repite en cada activo) |
| timestamps | | |

## activos

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| numero_activo | string(30) **unique** | `ACTIVO FIJO` del Excel (p. ej. `22022-0`); clave de idempotencia de la importación |
| denominacion | string(255) | `DENOMINACIÓN ACTIVO FIJO` |
| fecha_capitalizacion | date nullable | |
| placa | string(30) nullable | ~3.4% vacía en el Excel de origen |
| ubicacion_original | string(100) nullable | `UBICACIÓN` del Excel (referencial; no es la ubicación capturada en el inventario) |
| centro_costos_id | FK → centros_costos.id, `onDelete('restrict')` | |
| origen | enum('excel','sobrante') default 'excel' | `sobrante` = creado durante la captura (FR-010/US2 escenario 4) |
| creado_por | FK nullable → users.id, `onDelete('set null')` | null para los importados del Excel; usuario que lo creó si `origen = sobrante` |
| timestamps | | |

Índices: `unique(numero_activo)`, índice normal en `centro_costos_id`.

## inventarios (Periodo de Inventario)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| nombre | string(100) | p. ej. `Inventario 2026` |
| fecha_apertura | date | |
| fecha_cierre | date nullable | null mientras está abierto |
| estado | enum('abierto','cerrado') default 'abierto' | |
| abierto_por | FK → users.id, `onDelete('restrict')` | |
| cerrado_por | FK nullable → users.id, `onDelete('set null')` | |
| reabierto_por | FK nullable → users.id, `onDelete('set null')` | último usuario que reabrió (FR-017b) |
| reabierto_en | timestamp nullable | |
| timestamps | | |

Solo un registro con `estado = 'abierto'` a la vez (regla de aplicación,
validada en el servicio que abre/cierra periodos; no se fuerza con un índice
único parcial por compatibilidad con MySQL 8 sin `WHERE` en índices únicos).

## inventario_detalles (Registro de Inventario)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| inventario_id | FK → inventarios.id, `onDelete('cascade')` | |
| activo_id | FK → activos.id, `onDelete('cascade')` | |
| estado | enum('pendiente','verificado','no_encontrado','sobrante') default 'pendiente' | |
| ubicacion | string(150) nullable | ubicación real capturada; requerida por la app cuando `estado` es `verificado`/`sobrante` (FR-012) |
| observacion | text nullable | requerida por la app cuando `estado = 'no_encontrado'` (FR-011) |
| foto_equipo_path | string(255) nullable | requerida por la app cuando `estado` es `verificado`/`sobrante` |
| foto_placa_path | string(255) nullable | ídem |
| foto_equipo_path_anterior | string(255) nullable | retenida tras un reemplazo (FR-013a) |
| foto_placa_path_anterior | string(255) nullable | ídem |
| fotos_reemplazadas_en | timestamp nullable | fecha del último reemplazo, usada para purgar tras 30 días |
| reservado_por | FK nullable → users.id, `onDelete('set null')` | reserva exclusiva activa (FR-014) |
| reservado_en | timestamp nullable | usada para expirar la reserva tras 15 min de inactividad (FR-014a) |
| actualizado_por | FK nullable → users.id, `onDelete('set null')` | autor de la última captura/edición |
| timestamps | | `created_at`/`updated_at` cubren el "cuándo" de FR-013 |

Índices: `unique(inventario_id, activo_id)` (un solo registro por activo y
periodo), índice en `reservado_por`.

**Transiciones de `estado`**: `pendiente → verificado`, `pendiente →
no_encontrado`, `pendiente → sobrante` (este último solo para activos con
`origen = 'sobrante'` recién creados); un registro puede volver a editarse
(p. ej. corregir `verificado` con nuevas fotos) mientras el periodo esté
abierto o mientras un Administrador lo haya reabierto.

## exportaciones

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| solicitado_por | FK → users.id, `onDelete('cascade')` | |
| empresa_id | FK nullable → empresas.id, `onDelete('set null')` | filtro opcional |
| centro_costos_id | FK nullable → centros_costos.id, `onDelete('set null')` | filtro opcional |
| estado | enum('en_proceso','lista','fallida') default 'en_proceso' | |
| archivo_path | string(255) nullable | solo cuando `estado = 'lista'` |
| generado_en | timestamp nullable | |
| timestamps | | |

## users (addendum de implementación)

Se agregó una columna a la tabla estándar `users` de Laravel, no prevista en
la primera versión de este documento, para soportar "desactivar" un usuario
(FR-007):

| Columna | Tipo | Notas |
|---|---|---|
| activo | boolean default true | si es `false`, el login se bloquea (`LoginForm::authenticate()` exige `activo = true`) |

## Roles y Permisos (spatie/laravel-permission)

Usa las tablas estándar ya migradas (`roles`, `permissions`,
`model_has_roles`, `model_has_permissions`, `role_has_permissions`); no se
agregan columnas ni tablas propias.

- **Permisos**: `inventario.ver`, `inventario.capturar`,
  `inventario.exportar`, `cumplimiento.ver`, `admin.usuarios`, `admin.roles`,
  `admin.periodos` y `admin.catalogos` (`cumplimiento.ver`,
  `admin.periodos` y `admin.catalogos` se agregaron durante las pruebas
  manuales por la web, ver addendum abajo).
- **Roles**:
  - `Administrador` → todos los permisos.
  - `Inventariador` → `inventario.ver`, `inventario.capturar` (solo
    inventariar; sin acceso a cumplimiento ni administración).
  - `Consulta` → `cumplimiento.ver` (solo consulta el cumplimiento, por
    ahora).

## Addendum: administración operativa (periodos y catálogos)

Para poder usar la aplicación completa desde el navegador hacía falta una
interfaz de administración que no estaba en el alcance original del spec
(la apertura/cierre de periodos solo existía como operación directa sobre
el modelo `Inventario`, sin pantalla):

- **`admin.periodos`**: abrir un nuevo periodo de inventario (valida que no
  haya otro ya abierto), cerrarlo, y reabrirlo — usa las mismas columnas de
  `inventarios` ya definidas arriba (`estado`, `abierto_por`, `cerrado_por`,
  `reabierto_por`, `reabierto_en`), sin tablas nuevas.
- **`admin.catalogos`**: vista de solo lectura de `empresas` y
  `centros_costos` (con conteo de activos), para verificar que la carga del
  Excel quedó correcta sin necesitar `tinker`. No agrega tablas ni columnas.
- **`cumplimiento.ver`**: separado de `inventario.ver` para que el rol
  Consulta pueda ver el panel de cumplimiento sin ganar acceso a inventariar
  activos ni ver sus fotos — antes ambas cosas dependían del mismo permiso.

## Relaciones (resumen)

```text
Empresa 1─N CentroCostos 1─N Activo 1─N InventarioDetalle N─1 Inventario
User 1─N InventarioDetalle (actualizado_por, reservado_por)
User 1─N Activo (creado_por, solo cuando origen = sobrante)
User 1─N Exportacion (solicitado_por)
User N─N Role, Role N─N Permission (spatie/laravel-permission)
```
