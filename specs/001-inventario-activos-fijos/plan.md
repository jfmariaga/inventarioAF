# Implementation Plan: Inventario de Activos Fijos

**Branch**: `001-inventario-activos-fijos` | **Date**: 2026-09-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-inventario-activos-fijos/spec.md`

## Summary

Aplicación web Laravel 12 para que Levapan/Panal/Levacol inventaríen sus
activos fijos por centro de costos: un comando artisan importa el Excel
corporativo a MySQL de forma idempotente; usuarios con correo corporativo
verificado capturan evidencia (fotos, ubicación, estado) por activo dentro de
un periodo de inventario abierto, con reserva exclusiva por activo para que
dos personas no se pisen; el sistema calcula cumplimiento por empresa y
centro de costos; y un Administrador exporta el inventario a Excel con
imágenes incrustadas, en segundo plano si el volumen es grande.

**Reconciliación con el spec ratificado**: el input técnico de este comando
difiere en tres puntos de lo ya clarificado en `spec.md`; este plan sigue el
spec (fuente de verdad) y deja la razón documentada en `research.md`:

1. **Dominios de correo**: el input menciona solo `levapan.com` y
   `panalsas.com`; el spec (FR-004) ratificó **tres** dominios, incluyendo
   `levacolsas.com`. Este plan usa los tres.
2. **Concurrencia**: el input pide "bloqueo optimista por fila"; el spec
   (FR-014/FR-014a) ratificó **reserva exclusiva visible** ("activo en
   gestión por el usuario X"). Este plan implementa la reserva exclusiva del
   spec, usando una escritura condicional (compare-and-swap) como mecanismo
   de bajo nivel para adquirirla sin condiciones de carrera — así se cumple
   la UX pedida por el spec usando una técnica de concurrencia similar a la
   que pedía el input.
3. **Formato de exportación**: el input reintroduce PDF/ZIP; el spec
   (FR-020) ratificó **solo Excel** con imágenes incrustadas. Este plan
   construye solo el camino Excel; `barryvdh/laravel-dompdf` queda instalado
   pero sin usarse en este feature (no se retira del proyecto, no se
   construye código PDF especulativo).

**Hallazgo de constitución a corregir**: `phpunit.xml` actualmente fuerza
`DB_CONNECTION=sqlite` / `:memory:` para pruebas, lo que viola el Principio I
de la constitución ("NO se usa SQLite bajo ninguna circunstancia, ni siquiera
para pruebas"). Este plan corrige `phpunit.xml` para apuntar a una base MySQL
de pruebas dedicada (`inventario_af_testing`) como parte del setup.

## Technical Context

**Language/Version**: PHP 8.2, Laravel 12

**Primary Dependencies**: Livewire 3 + Volt (componentes de clase), Laravel
Breeze (Tailwind), spatie/laravel-permission (ya instalado), intervention/image-laravel
(ya instalado, redimensionado/recompresión de fotos), phpoffice/phpspreadsheet
(**nuevo**, requerido para el comando de importación vía `IOFactory`),
barryvdh/laravel-dompdf (ya instalado, sin uso funcional en este feature)

**Storage**: MySQL 8, base `inventario_af`; fotos en disco privado de Laravel
(`storage/app/private`, no `public/`)

**Testing**: PHPUnit contra MySQL real (base `inventario_af_testing`, no
SQLite), `Volt::test()` para componentes Livewire/Volt, Laravel Pint para
estilo

**Target Platform**: Navegador web, uso prioritario desde celular con cámara
(subida de fotos vía `<input type="file" accept="image/*" capture>`)

**Project Type**: Aplicación web monolítica Laravel (single project)

**Performance Goals**: cumplimiento visible en <5s tras una captura (SC-003);
captura de un activo completa en <1 min en móvil (SC-001)

**Constraints**: ~10.700 activos iniciales desde Excel; ≥20 usuarios
concurrentes sobre el mismo centro de costos sin pisarse (SC-002); fotos
re-comprimidas a ~1600px de lado mayor, JPEG calidad ~80%, para no saturar el
disco ni la exportación

**Scale/Scope**: 3 empresas, ~395 centros de costos, 6 historias de usuario
(importación, captura, registro/verificación, cumplimiento, administración de
usuarios, exportación)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Estado | Nota |
|---|---|---|
| I. Stack Tecnológico Fijo (Laravel 12/PHP 8.2/MySQL 8, sin SQLite) | ⚠️ Acción requerida | Stack correcto, pero `phpunit.xml` fuerza SQLite en pruebas — se corrige en Phase 0 (ver Hallazgo arriba) |
| II. UI 100% Livewire 3 + Volt | ✅ Pass | Todos los flujos como componentes Volt de clase sobre Breeze/Tailwind |
| III. Dominio en Español | ✅ Pass | Tablas y columnas de negocio en español (`empresas`, `centros_costos`, `activos`, `inventarios`, `inventario_detalles`); UI en español |
| IV. Autorización por Permiso | ✅ Pass | `spatie/laravel-permission`; middleware `can:` en rutas y `abort_unless` en acciones Livewire; ningún `hasRole()` en lógica de negocio |
| V. Base de Datos como Fuente de Verdad | ✅ Pass | Excel solo se lee en el comando de importación inicial; nada en runtime vuelve a leerlo |
| VI. Almacenamiento Privado de Imágenes | ✅ Pass | Disco privado + ruta autenticada con permiso `inventario.ver` |
| VII. Trazabilidad de Cambios | ✅ Pass | `actualizado_por`/timestamps en `inventario_detalles`; apertura/cierre/reapertura de periodo también registra autor y fecha |
| VIII. Integridad Referencial en Migraciones | ✅ Pass | FKs con `onDelete` explícito; índices únicos en `numero_activo`, `codigo` de centro de costos, y `(inventario_id, activo_id)` |
| Calidad y Pruebas | ⚠️ Acción requerida | Depende de la misma corrección de `phpunit.xml`; con eso corregido, PHPUnit + Pint quedan satisfechos |
| Flujo de Desarrollo (Spec-Driven) | ✅ Pass | Este plan sigue `/speckit-specify` → `/speckit-clarify` → `/speckit-plan` ya ejecutados |

No hay violaciones que requieran justificación en `Complexity Tracking`: el
único punto de acción (SQLite en `phpunit.xml`) es una corrección directa,
no una excepción a negociar.

**Re-chequeo post-diseño (Fase 1)**: `data-model.md`, `contracts/` y
`quickstart.md` no introducen ninguna dependencia, tabla o ruta que viole
los principios anteriores; el único punto pendiente sigue siendo la
corrección de `phpunit.xml`, que queda como tarea de setup en
`/speckit-tasks`.

## Project Structure

### Documentation (this feature)

```text
specs/001-inventario-activos-fijos/
├── plan.md              # Este archivo
├── research.md          # Fase 0: decisiones técnicas y reconciliaciones
├── data-model.md         # Fase 1: entidades, columnas, relaciones
├── quickstart.md         # Fase 1: guía de validación end-to-end
├── contracts/             # Fase 1: rutas, comando de importación, export
└── tasks.md               # Fase 2 (/speckit-tasks, no generado aquí)
```

### Source Code (repository root)

```text
app/
├── Console/Commands/
│   └── ImportarActivosExcel.php       # Comando idempotente de importación
├── Jobs/
│   └── GenerarExportacionInventario.php
├── Livewire/
│   ├── Inventario/
│   │   ├── SeleccionarCentroCostos.php
│   │   ├── ListaActivos.php
│   │   └── CapturarActivo.php
│   ├── Cumplimiento/
│   │   └── PanelCumplimiento.php
│   ├── Admin/
│   │   ├── GestionUsuarios.php
│   │   └── SolicitarExportacion.php
│   └── Forms/
│       └── CapturaActivoForm.php
├── Models/
│   ├── Empresa.php
│   ├── CentroCostos.php
│   ├── Activo.php
│   ├── Inventario.php                 # Periodo de inventario
│   ├── InventarioDetalle.php
│   └── Exportacion.php
└── Http/Controllers/
    ├── FotoActivoController.php        # Ruta autenticada que sirve fotos privadas
    └── DescargaExportacionController.php

database/
├── migrations/                         # empresas, centros_costos, activos,
│                                        # inventarios, inventario_detalles,
│                                        # exportaciones (+ permisos ya creados)
└── seeders/
    └── RolesYPermisosSeeder.php

resources/views/livewire/
├── inventario/...
├── cumplimiento/...
└── admin/...

routes/web.php                          # rutas con middleware auth/verified/can:

tests/Feature/
├── ImportarActivosExcelTest.php
├── CapturarActivoTest.php
├── ReservaExclusivaActivoTest.php
├── CumplimientoTest.php
├── RegistroDominioCorporativoTest.php
├── GestionUsuariosTest.php
└── ExportacionInventarioTest.php
```

**Structure Decision**: Proyecto Laravel único (sin separar backend/frontend);
Livewire/Volt vive dentro de `app/Livewire` + `resources/views/livewire`,
siguiendo la estructura que ya dejó `breeze:install livewire`. No se introduce
un proyecto o repositorio separado para frontend.

## Complexity Tracking

*Sin violaciones de la constitución que requieran justificación.*
