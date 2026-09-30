# Tasks: Inventario de Activos Fijos

**Input**: Design documents from `/specs/001-inventario-activos-fijos/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: incluidos — la constitución del proyecto (Calidad y Pruebas) exige
PHPUnit para todo cambio de lógica de negocio.

**Organization**: tareas agrupadas por historia de usuario (spec.md) para
poder implementar y probar cada una de forma independiente.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: se puede ejecutar en paralelo (archivos distintos, sin
  dependencias de tareas incompletas)
- **[Story]**: historia de usuario a la que pertenece (US1...US6)

## Path Conventions

Proyecto Laravel único (ver `plan.md` → Project Structure):
`app/`, `database/`, `resources/views/livewire/`, `routes/`, `tests/Feature/`.

---

## Phase 1: Setup

- [X] T001 Ejecutar `composer require phpoffice/phpspreadsheet` (actualiza `composer.json`/`composer.lock`)
- [X] T002 [P] Crear `config/inventario.php` con la lista de dominios de correo permitidos (`levapan.com`, `panalsas.com`, `levacolsas.com`) por FR-004
- [X] T003 Crear la base MySQL `inventario_af_testing` y corregir `phpunit.xml` para usar `DB_CONNECTION=mysql`/`DB_DATABASE=inventario_af_testing` en vez de SQLite (corrige el gate de constitución documentado en `research.md` §8)

**Checkpoint**: entorno de desarrollo y pruebas listo sobre MySQL, sin SQLite.

---

## Phase 2: Foundational (bloqueante para todas las historias)

**Nota de orden de migraciones**: aunque los archivos se autoran en
paralelo, sus timestamps deben respetar el orden de FKs:
`empresas` → `centros_costos` → `activos` → `inventarios` →
`inventario_detalles` → `exportaciones` (usar `php artisan make:migration`
en ese orden para que el timestamp quede correcto).

- [X] T004 [P] Migración `empresas` en `database/migrations/xxxx_create_empresas_table.php` (columnas de `data-model.md` → Empresas)
- [X] T005 [P] Migración `centros_costos` en `database/migrations/xxxx_create_centros_costos_table.php` (FK `empresa_id` → `empresas`, `onDelete('restrict')`, índice único en `codigo`)
- [X] T006 [P] Migración `activos` en `database/migrations/xxxx_create_activos_table.php` (FK `centro_costos_id` → `centros_costos`, FK nullable `creado_por` → `users`, índice único en `numero_activo`)
- [X] T007 [P] Migración `inventarios` en `database/migrations/xxxx_create_inventarios_table.php` (FKs `abierto_por`/`cerrado_por`/`reabierto_por` → `users`)
- [X] T008 [P] Migración `inventario_detalles` en `database/migrations/xxxx_create_inventario_detalles_table.php` (FKs `inventario_id`/`activo_id`/`reservado_por`/`actualizado_por`, índice único en `(inventario_id, activo_id)`)
- [X] T009 [P] Migración `exportaciones` en `database/migrations/xxxx_create_exportaciones_table.php` (FKs `solicitado_por`, `empresa_id` nullable, `centro_costos_id` nullable)
- [X] T010 [P] Modelo `Empresa` en `app/Models/Empresa.php` (relación `hasMany` a `CentroCostos`)
- [X] T011 [P] Modelo `CentroCostos` en `app/Models/CentroCostos.php` (`belongsTo Empresa`, `hasMany Activo`)
- [X] T012 [P] Modelo `Activo` en `app/Models/Activo.php` (`belongsTo CentroCostos`, `hasMany InventarioDetalle`, `belongsTo User` como `creadoPor`)
- [X] T013 [P] Modelo `Inventario` en `app/Models/Inventario.php` (`hasMany InventarioDetalle`, relaciones a `User` para apertura/cierre/reapertura)
- [X] T014 [P] Modelo `InventarioDetalle` en `app/Models/InventarioDetalle.php` (`belongsTo Inventario`, `belongsTo Activo`, `belongsTo User` como `reservadoPor`/`actualizadoPor`)
- [X] T015 [P] Modelo `Exportacion` en `app/Models/Exportacion.php` (`belongsTo User`, `belongsTo Empresa`/`CentroCostos` opcionales)
- [X] T016 Seeder `RolesYPermisosSeeder` en `database/seeders/RolesYPermisosSeeder.php` (permisos `inventario.ver`, `inventario.capturar`, `inventario.exportar`, `admin.usuarios`, `admin.roles`; roles Administrador/Inventariador/Consulta con sus permisos por `data-model.md`), registrado en `database/seeders/DatabaseSeeder.php`

**Checkpoint**: migraciones, modelos y roles/permisos base listos — ninguna
historia de usuario puede implementarse sin esto.

---

## Phase 3: User Story 1 - Carga inicial del inventario desde el Excel corporativo (P1) 🎯 MVP parte 1

**Goal**: poblar `activos`/`empresas`/`centros_costos` desde el Excel de
forma idempotente (spec US1).

**Independent Test**: correr el comando contra una BD vacía, verificar
conteos contra el Excel; correrlo de nuevo y verificar 0 duplicados
(`contracts/comando-importacion.md`).

- [X] T017 [P] [US1] Test de feature en `tests/Feature/ImportarActivosExcelTest.php`: primera corrida crea los registros esperados, segunda corrida es idempotente, fila con placa vacía se importa igual
- [X] T018 [US1] Comando `inventario:importar-activos` en `app/Console/Commands/ImportarActivosExcel.php` usando `PhpOffice\PhpSpreadsheet\IOFactory` sobre `documentacion/DATA AF APP VF.xlsx` (hoja `APP VF`), `firstOrCreate`/`updateOrCreate` por `numero_activo`/`codigo`/`nombre` según `contracts/comando-importacion.md`

**Checkpoint**: `php artisan inventario:importar-activos` funcionando de
forma idempotente contra MySQL real.

---

## Phase 4: User Story 2 - Inventariar activos de un centro de costos (P2) 🎯 MVP parte 2

**Goal**: un usuario con permiso de captura selecciona un centro de costos,
ve sus activos, y captura evidencia por activo con reserva exclusiva
(spec US2, FR-009 a FR-014a).

**Independent Test**: con datos de US1 ya cargados, iniciar sesión, capturar
un activo completo end-to-end, y confirmar que un segundo usuario ve "en
gestión" si intenta abrir el mismo activo mientras el primero lo tiene
reservado.

- [X] T019 [P] [US2] Test de feature en `tests/Feature/CapturarActivoTest.php`: captura completa exige ambas fotos + ubicación para `verificado`/`sobrante`; estado `no_encontrado` exige solo observación
- [X] T020 [P] [US2] Test de feature en `tests/Feature/ReservaExclusivaActivoTest.php`: segundo usuario no puede abrir un activo reservado; la reserva se libera al guardar/cancelar y por expiración de 15 min (`research.md` §3)
- [X] T021 [US2] `CapturaActivoForm` (Livewire Form object) en `app/Livewire/Forms/CapturaActivoForm.php` con las reglas de validación de FR-011/FR-012
- [X] T022 [P] [US2] Servicio `ProcesadorFotoActivo` en `app/Services/ProcesadorFotoActivo.php` (Intervention Image: `scaleDown(width: 1600)` + `toJpeg(quality: 80)`, guarda en disco privado, mueve la foto anterior a `_anterior` con `fotos_reemplazadas_en` por FR-013a)
- [X] T023 [US2] Componente Volt `inventario.seleccionar-centro-costos` en `resources/views/livewire/inventario/seleccionar-centro-costos.blade.php`
- [X] T024 [US2] Componente Volt `inventario.lista-activos` en `resources/views/livewire/inventario/lista-activos.blade.php` (listado automático de activos del centro seleccionado, con su estado)
- [X] T025 [US2] Componente Volt `inventario.capturar-activo` en `resources/views/livewire/inventario/capturar-activo.blade.php`: adquiere la reserva exclusiva vía escritura condicional (`UPDATE ... WHERE reservado_por IS NULL OR reservado_en < ?`), muestra "en gestión por {usuario}" si falla, usa `CapturaActivoForm` y `ProcesadorFotoActivo` para guardar y libera la reserva al terminar
- [X] T026 [US2] `FotoActivoController` en `app/Http/Controllers/FotoActivoController.php` que sirve `foto_equipo_path`/`foto_placa_path` desde el disco privado, validando permiso `inventario.ver`
- [X] T027 [US2] Rutas `/inventario`, `/inventario/{centroCostos}`, `/inventario/activos/{activo}/capturar`, `/fotos/{inventarioDetalle}/{tipo}` en `routes/web.php` con middleware `auth`, `verified`, `can:inventario.ver`/`can:inventario.capturar` según `contracts/rutas.md`
- [X] T028 [P] [US2] Comando programado `inventario:purgar-fotos-antiguas` en `app/Console/Commands/PurgarFotosAntiguas.php` (elimina fotos `_anterior` con más de 30 días) y su registro en el scheduler de `routes/console.php`

**Checkpoint**: flujo completo de captura de inventario funcionando en
MySQL, con fotos privadas servidas por ruta autenticada y reserva exclusiva
por activo — junto con US1, esto ya es un MVP usable en campo.

---

## Phase 5: User Story 3 - Registro y verificación de cuenta con correo corporativo (P3)

**Goal**: solo correos `@levapan.com`/`@panalsas.com`/`@levacolsas.com`
pueden registrarse; al verificar el correo se obtiene acceso inmediato con
rol Inventariador (spec US3, FR-003 a FR-006).

**Independent Test**: registrar con dominio permitido y con dominio no
permitido; verificar que solo el primero recibe correo de verificación y
que, al verificarlo, queda con rol Inventariador y acceso inmediato.

- [X] T029 [P] [US3] Test de feature en `tests/Feature/RegistroDominioCorporativoTest.php`: registro rechazado para dominio no permitido con mensaje claro; registro aceptado para los 3 dominios permitidos; verificar correo asigna rol Inventariador sin aprobación manual
- [X] T030 [P] [US3] Regla de validación `CorreoDominioPermitido` en `app/Rules/CorreoDominioPermitido.php` (lee la lista de `config/inventario.php`)
- [X] T031 [US3] Conectar `CorreoDominioPermitido` al formulario de registro en `resources/views/livewire/pages/auth/register.blade.php` (componente Volt generado por Breeze)
- [X] T032 [US3] Listener `AsignarRolInventariadorPorDefecto` en `app/Listeners/AsignarRolInventariadorPorDefecto.php` para el evento `Illuminate\Auth\Events\Verified`, registrado en `bootstrap/app.php` (auto-descubierto por Laravel, confirmado con `php artisan event:list`)

**Checkpoint**: registro autoservicio restringido a los 3 dominios
corporativos, con verificación por correo y rol por defecto automático.

---

## Phase 6: User Story 4 - Consultar % de cumplimiento por empresa y centro de costos (P4)

**Goal**: cualquier usuario autenticado con permiso `inventario.ver` puede
consultar el % de cumplimiento con semáforo y detalle de pendientes (spec
US4, FR-015 a FR-017).

**Independent Test**: con una proporción conocida de activos
inventariados/pendientes en un centro de costos, abrir la vista y confirmar
que el porcentaje y el color coinciden.

- [X] T033 [P] [US4] Test de feature en `tests/Feature/CumplimientoTest.php`: porcentaje correcto por centro de costos y por empresa, color de semáforo acorde a rangos
- [X] T034 [US4] Componente Volt `cumplimiento.panel` en `resources/views/livewire/cumplimiento/panel.blade.php` con agregación SQL por empresa/centro de costos (`research.md` §6) y listado de pendientes
- [X] T035 [US4] Ruta `/cumplimiento` en `routes/web.php` con `can:inventario.ver`

**Checkpoint**: cumplimiento consultable en cualquier momento, reflejando
las capturas de US2.

---

## Phase 7: User Story 5 - Administración de usuarios, roles y permisos (P5)

**Goal**: un Administrador ve, crea, edita y desactiva usuarios, y les
asigna rol (spec US5, FR-007, FR-008).

**Independent Test**: como Administrador, cambiar el rol de un usuario de
Consulta a Inventariador y confirmar que gana acceso a capturar en su
siguiente acción; confirmar que un usuario Consulta no puede capturar ni
exportar.

- [X] T036 [P] [US5] Test de feature en `tests/Feature/GestionUsuariosTest.php`: Administrador puede cambiar roles; usuario Consulta recibe 403 al intentar capturar o exportar
- [X] T037 [US5] Componente Volt `admin.gestion-usuarios` en `resources/views/livewire/admin/gestion-usuarios.blade.php` (listar usuarios, asignar rol, activar/desactivar vía nueva columna `users.activo` — ver nota abajo)
- [X] T038 [US5] Ruta `/admin/usuarios` en `routes/web.php` con `can:admin.usuarios`

> Nota de implementación: se agregó una migración adicional (`add_activo_to_users_table`) y el chequeo correspondiente en `LoginForm::authenticate()` para soportar "desactivar" un usuario (FR-007), ya que `data-model.md` no había definido explícitamente esa columna.

**Checkpoint**: gestión de usuarios y roles operativa, con autorización por
permiso verificada en pruebas.

---

## Phase 8: User Story 6 - Exportación del inventario (P6)

**Goal**: un Administrador exporta el inventario completo o filtrado a
Excel con imágenes incrustadas, en segundo plano si el volumen es grande
(spec US6, FR-018 a FR-021).

**Independent Test**: con inventario parcialmente capturado, solicitar una
exportación filtrada por empresa y confirmar que el archivo generado
contiene exactamente esos activos con toda la información y las fotos.

- [X] T039 [P] [US6] Test de feature en `tests/Feature/ExportacionInventarioTest.php`: exportación filtrada contiene solo los activos esperados; descarga devuelve 409 mientras `estado != 'lista'`; usuario sin `inventario.exportar` recibe 403
- [X] T040 [US6] Job `GenerarExportacionInventario` en `app/Jobs/GenerarExportacionInventario.php` (`ShouldQueue`, cola `database`; usa PhpSpreadsheet `Drawing` para incrustar fotos por fila según `contracts/exportacion.md`)
- [X] T041 [US6] Componente Volt `admin.solicitar-exportacion` en `resources/views/livewire/admin/solicitar-exportacion.blade.php` (filtros empresa/centro de costos, despacha el job, muestra estado)
- [X] T042 [US6] `DescargaExportacionController` en `app/Http/Controllers/DescargaExportacionController.php` (sirve el archivo solo si `estado = 'lista'`)
- [X] T043 [US6] Rutas `/admin/exportaciones`, `/admin/exportaciones/{exportacion}/descargar` en `routes/web.php` con `can:inventario.exportar`

> Nota: se corrigió un bug de pluralización de Eloquent (`Exportacion` mapeaba por defecto a la tabla inexistente `exportacions`); se fijó `protected $table = 'exportaciones'` explícitamente.

**Checkpoint**: exportación Excel con imágenes funcionando en segundo
plano, incluyendo el resumen de cumplimiento (cierra el ciclo de negocio).

---

## Phase 9: Polish & Cross-Cutting Concerns

- [X] T044 [P] Ejecutar `./vendor/bin/pint` sobre `app/` y corregir hallazgos (gate de constitución "Calidad y Pruebas")
- [X] T045 [P] Actualizar `.env.example` con los valores MySQL/es actuales (`DB_CONNECTION=mysql`, `DB_DATABASE=inventario_af`, `APP_LOCALE=es`) para que coincidan con el `.env` real del proyecto
- [X] T046 Ejecutar manualmente los 8 pasos de `quickstart.md` de punta a punta y registrar cualquier desviación

---

## Dependencies & Execution Order

- **Setup (Phase 1)** no depende de nada; bloquea todo lo demás.
- **Foundational (Phase 2)** depende de Setup; bloquea todas las historias
  de usuario (migraciones/modelos/roles son prerrequisito de todas).
- **US1 (Phase 3)** depende solo de Foundational. No depende de otras
  historias.
- **US2 (Phase 4)** depende de Foundational (modelos/migraciones) y de que
  existan datos importados (US1) para ser útil en la práctica, aunque el
  código de US2 no depende técnicamente del código de US1.
- **US3 (Phase 5)** depende de Foundational (roles/permisos ya sembrados).
  Independiente de US1/US2 en código, aunque en la práctica se prueba mejor
  con datos ya cargados.
- **US4 (Phase 6)** depende de Foundational y, para tener datos que mostrar,
  de que exista actividad de captura (US2).
- **US5 (Phase 7)** depende de Foundational (roles/permisos). Independiente
  de US1/US2/US3/US4 en código.
- **US6 (Phase 8)** depende de Foundational y, para exportar algo con
  sentido, de que exista inventario capturado (US2).
- **Polish (Phase 9)** depende de que todas las historias que se vayan a
  entregar estén completas.

Dentro de cada fase, los IDs sin `[P]` deben hacerse en el orden listado
(dependencias de archivo/lógica); los `[P]` pueden hacerse en paralelo entre
sí.

## Parallel Execution Examples

- **Foundational**: T004-T009 (migraciones, respetando el orden de
  timestamps indicado) en paralelo; luego T010-T015 (modelos) en paralelo;
  T016 (seeder) al final de esa fase.
- **US2**: T019 y T020 (tests) en paralelo; T022 (servicio de fotos) en
  paralelo con T021 (form object); T028 (purga de fotos) en paralelo con
  cualquier tarea de US2 una vez exista el modelo `InventarioDetalle`.
- **US3**: T029 y T030 en paralelo.
- Los tests `[P]` de cada historia (T017, T019/T020, T029, T033, T036,
  T039) pueden escribirse en paralelo con los de otras historias si varios
  desarrolladores trabajan historias distintas a la vez, ya que Foundational
  ya dejó todo lo compartido resuelto.

## Implementation Strategy

**MVP** = US1 + US2 (Phases 1-4): sin esto no hay producto usable — se
importan los activos y se puede inventariar en campo con reserva exclusiva.
US3 (registro autoservicio) puede diferirse inicialmente usando cuentas
creadas manualmente por un Administrador vía tinker/seeder, pero antes de
salir a usuarios reales de Levapan es indispensable.

**Entrega incremental sugerida**: Setup → Foundational → US1 → US2 (MVP
funcional para un piloto con cuentas creadas a mano) → US3 (abre el
registro autoservicio) → US4 (visibilidad de avance) → US5 (administración
delegable) → US6 (cierre del ciclo con exportación) → Polish.

Cada historia, al completarse, deja el sistema en un estado desplegable y
demostrable por sí sola, conforme a sus criterios de "Independent Test" en
`spec.md`.
