<!--
Sync Impact Report
- Version change: (template) → 1.0.0
- Ratification: initial adoption, no prior ratified version existed
- Modified principles: n/a (first ratified version)
- Added sections:
  - Core Principles: I. Stack Tecnológico Fijo, II. UI 100% Livewire + Volt,
    III. Dominio en Español, IV. Autorización por Permisos,
    V. Base de Datos como Fuente de Verdad, VI. Almacenamiento Privado de Imágenes,
    VII. Trazabilidad de Cambios, VIII. Integridad Referencial en Migraciones
  - Calidad y Pruebas (PHPUnit + Pint como gate de calidad)
  - Flujo de Desarrollo (Spec-Driven Development con Spec Kit)
  - Governance (procedimiento de enmienda, versionado semántico, revisión de cumplimiento)
- Removed sections: none (template placeholders replaced)
- Deferred TODOs: none
- Templates requiring follow-up: .specify/templates/plan-template.md,
  .specify/templates/spec-template.md, .specify/templates/tasks-template.md —
  no direct placeholder references to this constitution found; review manually
  on first /speckit-plan run to confirm alignment with the principles below.
-->

# inventarioAF Constitution

## Core Principles

### I. Stack Tecnológico Fijo
El proyecto usa Laravel 12 sobre PHP 8.2 con MySQL 8 como único motor de base de
datos en todos los entornos (desarrollo, pruebas y producción). NO se usa SQLite
bajo ninguna circunstancia, ni siquiera para pruebas automatizadas: la suite de
PHPUnit debe correr contra una base MySQL real (o un esquema MySQL de pruebas
dedicado) para que el comportamiento validado coincida con producción.
**Racional**: el sistema depende de particularidades de MySQL (collation
utf8mb4, índices únicos compuestos, claves foráneas) que SQLite no reproduce de
forma confiable, y un cambio de motor entre entornos ha causado antes bugs
silenciosos de compatibilidad.

### II. UI 100% Livewire 3 + Volt
Toda interfaz de usuario se construye con Livewire 3 usando componentes Volt
(clases), sobre el scaffolding de Breeze y estilos Tailwind. NO se introducen
controladores Blade tradicionales con lógica de formularios, ni frameworks de
frontend adicionales (Vue, React, Inertia, Alpine standalone fuera de lo que
Livewire ya incluye). Las excepciones —si alguna vez son necesarias— deben
justificarse explícitamente en el plan de la funcionalidad correspondiente.
**Racional**: mantener un único paradigma de UI reduce la carga cognitiva del
equipo y evita la duplicación de lógica de validación entre cliente y servidor.

### III. Dominio en Español
Todo el vocabulario visible o propio del dominio de negocio se expresa en
español: nombres de tablas, columnas, rutas nombradas, mensajes de validación,
textos de la interfaz y nombres de eventos/acciones Livewire relacionados con
el negocio (p. ej. `activos_fijos`, `centro_costos`, `ruta activos.inventariar`).
El código de infraestructura (nombres de clases PHP, métodos, namespaces) sigue
las convenciones estándar de Laravel en inglés cuando así lo exige el framework
o un paquete de terceros (p. ej. `class ActivoFijo extends Model`), pero los
valores, etiquetas y mensajes orientados al usuario final son siempre en
español.
**Racional**: los usuarios finales (personal de Levapan, Panal y Levacol) y el
equipo de negocio trabajan en español; mezclar idiomas en la capa visible
genera confusión y errores de interpretación en campo.

### IV. Autorización por Permiso y no por Rol
El control de acceso se implementa con `spatie/laravel-permission` verificando
siempre permisos explícitos (`can`, `@can`, middleware `permission:`) en el
código de autorización, nunca verificaciones directas de rol (`hasRole()`)
para decidir si una acción está permitida. Los roles existen únicamente como
agrupadores de permisos para administración de usuarios, no como mecanismo de
decisión en la lógica de la aplicación.
**Racional**: verificar por permiso desacopla la lógica de negocio de la
taxonomía de roles, permitiendo reconfigurar quién puede hacer qué sin tocar
código cuando cambien las políticas de la organización.

### V. Base de Datos como Fuente de Verdad
La base de datos MySQL es la única fuente de verdad del inventario de activos
fijos una vez completada la carga inicial. El Excel en `documentacion/` se usa
exclusivamente como insumo de importación inicial (seeder/comando de importación
ejecutado una vez, o reejecutable de forma idempotente); ninguna funcionalidad
de la aplicación en producción lee o escribe directamente sobre el archivo
Excel. Cambios posteriores a los datos de activos se hacen a través de la
aplicación o de migraciones/seeders versionados, no editando el Excel original.
**Racional**: depender del Excel como fuente viva impediría el trabajo
concurrente de múltiples usuarios sobre el mismo inventario y rompería la
trazabilidad de auditoría.

### VI. Almacenamiento Privado de Imágenes
Las fotos de equipo y de placa capturadas durante el inventario se almacenan en
un disco privado de Laravel (no en `public/` ni en un disco con acceso
anónimo). El acceso a las imágenes se sirve siempre a través de rutas
autenticadas y autorizadas (URLs firmadas o controladores que validan permisos),
nunca mediante URLs públicas directas al archivo.
**Racional**: las fotos de activos e instalaciones son información interna de
la compañía; exponerlas públicamente sería un riesgo de seguridad y de
confidencialidad operativa.

### VII. Trazabilidad de Cambios
Toda tabla que represente estado mutable del negocio (activos, registros de
inventario, evidencias fotográficas, usuarios) registra como mínimo quién
realizó el cambio y cuándo (autor y timestamp), usando los mecanismos estándar
de Laravel (`created_at`/`updated_at`, columnas `*_by` con clave foránea a
`users`, u observers/eventos de modelo). Ninguna operación de creación,
actualización o eliminación de datos de negocio se implementa sin capturar esta
trazabilidad.
**Racional**: con múltiples usuarios trabajando el mismo centro de costos de
forma concurrente, la auditoría de quién hizo qué y cuándo es indispensable
para resolver disputas y calcular el cumplimiento de forma confiable.

### VIII. Integridad Referencial en Migraciones
Toda migración que introduce una relación entre tablas define la clave foránea
correspondiente con la acción `onDelete`/`onUpdate` explícita (no queda
implícita ni se omite). Toda columna o combinación de columnas que el negocio
considera identificador único (p. ej. número de activo fijo, combinación
placa + activo) se declara con un índice único a nivel de base de datos, no
solo con validación en la capa de aplicación.
**Racional**: con ~10,700 activos y múltiples usuarios escribiendo de forma
concurrente, las reglas de unicidad e integridad deben vivir en MySQL para
evitar condiciones de carrera que la validación de Livewire por sí sola no
puede prevenir.

## Calidad y Pruebas

Todo cambio de código que afecte lógica de negocio (modelos, componentes
Livewire/Volt, importadores, cálculos de cumplimiento, exportaciones) debe
venir acompañado de pruebas PHPUnit que lo cubran, ejecutadas contra MySQL
(Principio I). El estilo de código se valida con Laravel Pint antes de
considerar un cambio listo para revisión; no se mezclan reglas de estilo
alternativas ni configuraciones de Pint por módulo. Un cambio sin pruebas
correspondientes o que no pasa Pint no se considera terminado.

## Flujo de Desarrollo

El proyecto sigue Spec-Driven Development con Spec Kit: cada funcionalidad
nueva pasa por especificación (`/speckit-specify`), plan (`/speckit-plan`) y
tareas (`/speckit-tasks`) antes de implementarse (`/speckit-implement`), salvo
tareas triviales de mantenimiento que no cambian comportamiento de negocio. Los
artefactos de especificación y plan se conservan en el repositorio como
documentación viva de las decisiones tomadas para cada funcionalidad.

## Governance

Esta constitución prevalece sobre cualquier otra guía o convención informal del
proyecto. Toda enmienda se realiza mediante el comando `/speckit-constitution`,
que debe: (a) documentar el cambio en un Sync Impact Report al inicio del
archivo, (b) incrementar `CONSTITUTION_VERSION` según semver (MAJOR: remoción o
redefinición incompatible de un principio; MINOR: principio o sección nueva, o
expansión material de una guía existente; PATCH: aclaraciones o correcciones de
redacción sin cambio de significado), y (c) actualizar `Last Amended` a la
fecha de la enmienda. Todo plan (`/speckit-plan`) y revisión de código debe
verificar cumplimiento de los principios anteriores; cualquier desviación debe
justificarse explícitamente en el plan de la funcionalidad correspondiente, con
la alternativa más simple documentada como descartada y el motivo.

**Version**: 1.0.0 | **Ratified**: 2026-09-30 | **Last Amended**: 2026-09-30
