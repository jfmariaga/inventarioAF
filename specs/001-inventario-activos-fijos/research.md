# Research: Inventario de Activos Fijos

## 1. Lectura del Excel de carga inicial

**Decision**: usar `phpoffice/phpspreadsheet` (`IOFactory::load()`) leyendo la
hoja `"APP VF"` en modo `setReadDataOnly(true)` para velocidad, dentro de un
`Illuminate\Console\Command` (`app:importar-activos` o similar) que recorre
filas en chunks y usa `upsert`/`firstOrCreate` por `numero_activo` para ser
idempotente.

**Rationale**: es la librería estándar de facto en PHP para leer `.xlsx` sin
depender de Excel/COM; el volumen (~10.700 filas, 8 columnas) es manejable en
un solo proceso con `setReadDataOnly` sin necesitar streaming especial.

**Alternatives considered**: `maatwebsite/excel` (wrapper sobre
PhpSpreadsheet con imports basados en colecciones) — descartado por ahora
para no añadir una capa adicional cuando un comando artisan directo con
PhpSpreadsheet ya cubre el caso de uso sin las abstracciones de importación
por lotes que ese paquete ofrece y que aquí no se necesitan.

**Nuevo dependency**: `composer require phpoffice/phpspreadsheet` (no estaba
instalado en el setup inicial del proyecto).

## 2. Dominios de correo permitidos (reconciliación)

**Decision**: lista de 3 dominios en `config/inventario.php` →
`['levapan.com', 'panalsas.com', 'levacolsas.com']`, usada por una regla de
validación custom (`Rule::in` sobre el dominio extraído del correo, o una
`ValidationRule` dedicada) en el formulario de registro de Breeze.

**Rationale**: el spec (`FR-004`, clarificado explícitamente con el usuario)
ratificó los tres dominios — uno por cada empresa presente en el Excel
(LEVAPAN, PANAL, LEVACOL). El input técnico de este comando solo mencionó dos
por lo que se asume una omisión, no una decisión deliberada de excluir
LEVACOL; se sigue el spec como fuente de verdad.

**Alternatives considered**: hardcodear los dominios en el Form Request —
descartado porque `config/inventario.php` permite ajustar la lista sin tocar
código si el negocio agrega una empresa/dominio más adelante.

## 3. Concurrencia sobre un mismo activo (reconciliación)

**Decision**: columnas `reservado_por` (FK nullable a `users`) y
`reservado_en` (timestamp nullable) en `inventario_detalles`. Al abrir un
activo para capturar:
1. En una transacción, se verifica que `reservado_por` sea `null` **o** que
   `reservado_en` tenga más de 15 minutos (reserva expirada).
2. Si se cumple, se hace un `UPDATE ... WHERE id = ? AND (reservado_por IS
   NULL OR reservado_en < ?)` (escritura condicional/compare-and-swap) que
   fija `reservado_por`/`reservado_en` al usuario actual. Si el `UPDATE`
   afecta 0 filas, significa que otro usuario ganó la carrera justo antes —
   se recarga el registro y se muestra "en gestión por {usuario}".
3. Al guardar la captura, cancelar, o cuando expira el timeout de 15 min, se
   limpian `reservado_por`/`reservado_en` (liberación explícita al guardar/
   cancelar; liberación perezosa por expiración al siguiente intento de
   apertura, sin necesidad de un job programado).

**Rationale**: el spec (`FR-014`/`FR-014a`, clarificado explícitamente)
ratificó una reserva exclusiva **visible** ("el activo está en gestión por el
usuario X"), que un simple chequeo optimista sobre `updated_at` al guardar no
puede ofrecer (ese chequeo solo detecta el conflicto al final, no antes de
que el segundo usuario empiece a llenar el formulario). La escritura
condicional (`UPDATE ... WHERE`) sigue siendo una técnica "optimista" en el
sentido de que no usa `SELECT ... FOR UPDATE` ni locks de MySQL de larga
duración — coincide con el espíritu de "bloqueo optimista por fila" del input
técnico, aplicada a adquirir la reserva en lugar de aplicada al guardado
final.

**Alternatives considered**:
- `SELECT ... FOR UPDATE` con lock de base de datos mientras el formulario
  está abierto — descartado: un lock de fila sostenido durante minutos
  mientras alguien toma fotos en su celular es frágil (timeouts, conexiones
  que se cortan) y no es como MySQL/Laravel están pensados para usarse.
- Tabla de locks separada (`activo_locks`) — descartada por ahora: no aporta
  nada sobre tener las dos columnas directamente en `inventario_detalles`,
  dado que la reserva es 1:1 con esa fila.

## 4. Fotos: almacenamiento y procesamiento

**Decision**: subida vía `WithFileUploads` de Livewire a un disco temporal,
validación (`image`, `max:10240`, mimes jpg/png/webp/heic), luego
`Intervention\Image\Laravel\Facades\Image::read($file)->scaleDown(width: 1600)`
seguido de `->toJpeg(quality: 80)`, guardado en el disco privado
`local` (`storage/app/private/activos/{activo_id}/equipo.jpg` y
`.../placa.jpg`, con sufijo o carpeta versionada para la foto anterior
retenida por FR-013a). Servidas por una ruta autenticada
(`FotoActivoController`) con middleware `auth`, `verified`,
`can:inventario.ver`.

**Rationale**: cumple el Principio VI de la constitución (almacenamiento
privado, sin URLs públicas) y mantiene el tamaño de archivo predecible para
que la exportación Excel con miles de imágenes incrustadas no crezca sin
control.

**Alternatives considered**: guardar en disco `public` con URL firmada
temporal — descartado porque añade complejidad de firmas sin necesidad,
cuando una ruta autenticada por permiso ya resuelve el requisito.

## 5. Retención de foto reemplazada (FR-013a)

**Decision**: al reemplazar una foto, el archivo anterior se mueve a una
ruta con sufijo `_anterior` y se registra `foto_*_reemplazada_en` (timestamp)
en `inventario_detalles`. Un comando artisan programado
(`inventario:purgar-fotos-antiguas`, agendado diariamente vía el scheduler de
Laravel) elimina físicamente los archivos `_anterior` cuyo
`foto_*_reemplazada_en` supere 30 días, y limpia las columnas.

**Rationale**: evita un job en cola adicional por cada reemplazo; un barrido
diario es suficiente para un límite de 30 días (asumido en `Assumptions` del
spec) y es fácil de razonar/probar.

## 6. Cálculo de cumplimiento

**Decision**: consultas Eloquent con `groupBy` + `selectRaw` de conteos
condicionales (`SUM(CASE WHEN estado IN ('verificado','no_encontrado',
'sobrante') THEN 1 ELSE 0 END)` vs. total de activos del centro de costos)
ejecutadas directamente en el componente Volt de cumplimiento, sin tabla de
caché/materialización.

**Rationale**: a la escala del proyecto (~10.700 activos, 395 centros de
costos) una agregación SQL directa responde muy por debajo del objetivo de
5s (SC-003) sin la complejidad de mantener una tabla resumen sincronizada.

**Alternatives considered**: tabla `cumplimiento_cache` recalculada por
eventos — descartada por prematura dado el volumen de datos; se puede
introducir más adelante si el perfilado muestra que hace falta.

## 7. Exportación a Excel con imágenes (reconciliación de formato)

**Decision**: solo formato Excel (spec `FR-020`, ratificado). Un
`Illuminate\Contracts\Queue\ShouldQueue` Job
(`GenerarExportacionInventario`) usa PhpSpreadsheet
(`PhpOffice\PhpSpreadsheet\Worksheet\Drawing`) para incrustar cada foto en la
fila de su activo, corre en la cola `database` ya configurada, y al terminar
marca el registro `exportaciones.estado = 'lista'` con `archivo_path`. La
descarga se sirve por una ruta autenticada con permiso `inventario.exportar`
que valida que el estado sea `lista`.

**Rationale**: cumple exactamente el FR-020 ratificado; no se construyen
caminos de PDF ni ZIP para esta funcionalidad, evitando código especulativo
que el spec explícitamente descartó. `barryvdh/laravel-dompdf` permanece
instalado (podría usarse para otro reporte en el futuro) pero no se conecta a
este flujo.

**Alternatives considered**: generar la exportación de forma síncrona para
datasets pequeños y solo encolar los grandes — descartado por simplicidad:
FR-021 no distingue un umbral exacto, así que siempre se encola, cumpliendo
igualmente SC-006 sin dos caminos de código distintos para exportar.

## 8. Corrección de `phpunit.xml` (gate de constitución)

**Decision**: cambiar `phpunit.xml` para usar
`DB_CONNECTION=mysql`/`DB_DATABASE=inventario_af_testing` (base MySQL
dedicada a pruebas, creada en el setup de esta feature) en vez de
`sqlite`/`:memory:`.

**Rationale**: el Principio I de la constitución prohíbe SQLite incluso en
pruebas. Los tests de este feature dependen de particularidades de MySQL
(índices únicos compuestos, columnas `ENUM`, FKs) que SQLite no reproduce de
forma confiable.

## 9. Roles y permisos por defecto

**Decision**: seeder `RolesYPermisosSeeder` crea los permisos
`inventario.ver`, `inventario.capturar`, `inventario.exportar`,
`admin.usuarios`, `admin.roles`, y los roles Administrador (todos los
permisos), Inventariador (`inventario.ver`, `inventario.capturar`) y Consulta
(`inventario.ver`). El rol Inventariador se asigna automáticamente al
verificar el correo (spec, clarificación de rol por defecto).

**Rationale**: implementa directamente FR-006/FR-007/FR-008 y el Principio IV
de la constitución (autorización por permiso, no por rol).
