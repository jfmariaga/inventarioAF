# Feature Specification: Inventario de Activos Fijos

**Feature Branch**: `001-inventario-activos-fijos`

**Created**: 2026-09-30

**Status**: Draft

**Input**: User description: "Aplicación web \"Inventario de Activos Fijos\" (inventarioAF) para Levapan. Carga de datos desde Excel con importación idempotente; registro restringido a correos corporativos con verificación por correo; captura de inventario por centro de costos con fotos, ubicación y observación; cálculo de % de cumplimiento por empresa y centro de costos; exportación en Excel/PDF solo para Administrador; interfaz en español basada en Breeze, usable desde celular con cámara."

## Clarifications

### Session 2026-09-30

- Q: Cuando un usuario nuevo verifica su correo corporativo, ¿obtiene acceso inmediato con un rol por defecto, o debe esperar que un Administrador apruebe manualmente su cuenta antes de poder usar el sistema? → A: Acceso inmediato con rol Inventariador (puede capturar inventario de una vez), sin aprobación manual de un Administrador.
- Q: ¿La empresa de cada activo debe guardarse directamente en el registro del Activo Fijo, o debe derivarse del Centro de Costos al que pertenece? → A: Derivar la empresa del Centro de Costos (normalizado): el Centro de Costos guarda su empresa una sola vez, y el Activo Fijo solo referencia al Centro de Costos.
- Q: ¿El inventario se maneja por periodos/ciclos con fecha de cierre, o es un estado continuo sin concepto de cierre? → A: Se manejan periodos con cierre formal; una vez cerrado un periodo, sus registros quedan de solo lectura salvo que un Administrador lo reabra explícitamente para corregir.
- Q: Al reemplazar la foto de un activo ya capturado, ¿el sistema debe conservar historial de versiones o solo la última? → A: Se reemplaza la foto visible para el Inventariador, pero un Administrador puede ver/recuperar la versión anterior durante un tiempo limitado antes de que se elimine definitivamente.
- Q: ¿En qué formato final debe entregarse la exportación del inventario con fotos? → A: Un único formato: Excel con las fotos incrustadas en las celdas correspondientes (no PDF, no ZIP).
- Q: ¿Cuáles son exactamente los dominios de correo permitidos para registrarse? → A: Los tres dominios corporativos: @levapan.com, @panalsas.com y @levacolsas.com (uno por cada empresa del inventario).
- Q: Cuando dos usuarios intentan capturar el mismo activo al mismo tiempo, ¿cómo debe comportarse el sistema? → A: Bloqueo exclusivo: cuando un usuario abre un activo para inventariarlo, si otro usuario intenta abrir ese mismo activo el sistema le indica que está en gestión por el primer usuario en ese momento.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Carga inicial del inventario desde el Excel corporativo (Priority: P1)

Un administrador técnico ejecuta un comando de importación que lee el Excel de
activos fijos ubicado en `documentacion/` y puebla la base de datos con los
activos, sus empresas y sus centros de costos, de forma que el resto de la
aplicación tenga datos reales sobre los cuales trabajar.

**Why this priority**: Sin esta carga inicial ninguna otra funcionalidad tiene
datos sobre los cuales operar; es la base de todo lo demás.

**Independent Test**: Ejecutar el comando de importación contra una base de
datos vacía y verificar que el número de activos, empresas y centros de costos
coincide con el Excel de origen. Ejecutarlo una segunda vez y verificar que no
se generan duplicados.

**Acceptance Scenarios**:

1. **Given** el Excel `documentacion/DATA AF APP VF.xlsx` y una base de datos
   vacía, **When** el administrador ejecuta el comando de importación,
   **Then** cada activo fijo del Excel existe en la base de datos con su
   empresa y centro de costos correctos.
2. **Given** una importación ya ejecutada previamente, **When** el comando se
   ejecuta de nuevo sobre el mismo archivo, **Then** no se crean activos
   duplicados y los registros de inventario ya capturados por los usuarios se
   conservan intactos.
3. **Given** una fila del Excel sin placa (campo vacío), **When** se importa,
   **Then** el activo se crea correctamente con la placa en blanco, sin
   rechazar la fila.

---

### User Story 2 - Inventariar activos de un centro de costos (Priority: P2)

Un usuario con permiso para capturar inventario digita o selecciona un centro
de costos y el sistema le muestra automáticamente los activos fijos a
inventariar de ese centro. Para cada activo captura evidencia (fotos,
ubicación, observación) y un estado, y el sistema calcula si el activo quedó
correctamente inventariado.

**Why this priority**: Es el flujo que entrega el valor central de la
aplicación: la captura física del inventario en campo.

**Independent Test**: Con datos de Story 1 ya cargados, iniciar sesión como
usuario con permiso de captura, seleccionar un centro de costos, capturar un
activo completo (dos fotos, ubicación, estado verificado) y confirmar que
queda marcado como inventariado con el usuario y la fecha/hora correctos.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado con permiso de captura, **When** digita o
   selecciona un centro de costos, **Then** ve automáticamente el listado de
   activos fijos de ese centro con su estado actual (pendiente/inventariado).
2. **Given** un activo pendiente, **When** el usuario sube foto del equipo,
   foto de la placa, indica la ubicación y marca el estado "verificado",
   **Then** el activo queda marcado como inventariado junto con el usuario y
   la fecha/hora de la captura.
3. **Given** un activo que no se encuentra físicamente, **When** el usuario
   marca el estado "no encontrado" y escribe una observación, **Then** el
   activo queda registrado como inventariado sin necesidad de fotos.
4. **Given** un activo encontrado en el centro de costos pero no listado en el
   inventario original, **When** el usuario lo registra con estado
   "sobrante", **Then** el sistema crea un nuevo registro de activo para ese
   centro de costos con la misma evidencia obligatoria que un activo
   verificado.
5. **Given** un activo que un primer usuario ya abrió para inventariar,
   **When** un segundo usuario intenta abrir ese mismo activo, **Then** el
   sistema le indica que el activo está en gestión por el primer usuario en
   ese momento y no le permite capturarlo hasta que quede libre.
6. **Given** un activo reservado por un usuario que guarda su captura,
   cancela, o cuya sesión queda inactiva más allá del tiempo límite, **When**
   ocurre cualquiera de esas situaciones, **Then** el activo queda libre para
   que cualquier otro usuario lo abra.

---

### User Story 3 - Registro y verificación de cuenta con correo corporativo (Priority: P3)

Una persona con correo corporativo crea su cuenta, recibe un correo de
confirmación y, solo después de verificarlo, obtiene acceso al sistema con un
rol por defecto.

**Why this priority**: Es la puerta de entrada para usuarios reales, pero el
resto del sistema puede construirse y probarse con cuentas de prueba
pre-verificadas mientras esta historia se desarrolla.

**Independent Test**: Registrar una cuenta con un correo de dominio permitido
y otra con un dominio no permitido; confirmar que la primera recibe el correo
de verificación y no puede iniciar sesión hasta verificarlo, y que la segunda
es rechazada de inmediato con un mensaje claro.

**Acceptance Scenarios**:

1. **Given** una persona con correo de un dominio corporativo permitido,
   **When** completa el registro, **Then** recibe un correo de confirmación y
   no puede acceder al inventario hasta verificarlo.
2. **Given** una persona con un correo de un dominio no permitido, **When**
   intenta registrarse, **Then** el sistema rechaza el registro con un mensaje
   claro indicando qué dominios son válidos.
3. **Given** un correo de verificación válido, **When** el usuario hace clic
   en el enlace, **Then** su cuenta queda activada de inmediato con el rol
   Inventariador y puede iniciar sesión, sin esperar aprobación manual.

---

### User Story 4 - Consultar % de cumplimiento por empresa y centro de costos (Priority: P4)

Un usuario autenticado consulta en cualquier momento el porcentaje de
cumplimiento del inventario, agrupado por empresa y por centro de costos, con
un indicador visual y el detalle de los activos pendientes.

**Why this priority**: Depende de que ya exista actividad de captura (Story
2) para tener datos que mostrar; es el mecanismo de seguimiento del avance.

**Independent Test**: Con un centro de costos donde se conoce de antemano
cuántos activos están inventariados y cuántos pendientes, abrir la vista de
cumplimiento y confirmar que el porcentaje, el color del semáforo y el listado
de pendientes coinciden con lo esperado.

**Acceptance Scenarios**:

1. **Given** un centro de costos con algunos activos inventariados y otros
   pendientes, **When** el usuario abre la vista de cumplimiento, **Then** ve
   el porcentaje calculado como activos inventariados sobre el total, con un
   color de semáforo acorde y el listado de pendientes.
2. **Given** varios centros de costos de una misma empresa, **When** el
   usuario consulta el cumplimiento por empresa, **Then** ve el porcentaje
   agregado de toda la empresa además del detalle por centro de costos.

---

### User Story 5 - Administración de usuarios, roles y permisos (Priority: P5)

Un Administrador gestiona las cuentas de usuario del sistema, asignando o
cambiando su rol (Administrador, Inventariador, Consulta) y revisando qué
permisos tiene cada uno.

**Why this priority**: Es necesaria para operar el sistema en el tiempo, pero
no bloquea la demostración de las historias anteriores si se parte de roles
pre-configurados.

**Independent Test**: Como Administrador, cambiar el rol de un usuario de
Consulta a Inventariador y confirmar que ese usuario gana acceso a capturar
inventario en su siguiente acción, sin necesidad de volver a iniciar sesión
manualmente más allá de lo que el sistema ya requiera.

**Acceptance Scenarios**:

1. **Given** un Administrador autenticado, **When** accede a la gestión de
   usuarios, **Then** puede ver todos los usuarios del sistema y su rol
   actual.
2. **Given** un Administrador autenticado, **When** cambia el rol de un
   usuario, **Then** los permisos de ese usuario se actualizan de forma
   efectiva.
3. **Given** un usuario con rol Consulta, **When** intenta capturar inventario
   o exportar, **Then** el sistema le niega la acción.

---

### User Story 6 - Exportación del inventario (Priority: P6)

Un Administrador exporta el inventario completo o filtrado por empresa y/o
centro de costos, incluyendo datos del activo, estado, ubicación, observación,
autor y fecha de captura, ambas imágenes, y el resumen de cumplimiento.

**Why this priority**: Es el cierre del ciclo de negocio (reporte para
terceros) y depende de que ya exista inventario capturado y cumplimiento
calculado.

**Independent Test**: Con inventario parcialmente capturado, solicitar una
exportación filtrada por una empresa y confirmar que el archivo generado
contiene exactamente los activos de esa empresa con toda la información
requerida.

**Acceptance Scenarios**:

1. **Given** un Administrador autenticado, **When** solicita la exportación
   completa o filtrada por empresa y/o centro de costos, **Then** recibe un
   archivo Excel con los datos del activo, estado, ubicación, observación,
   autor, fecha, ambas imágenes incrustadas y el resumen de cumplimiento.
2. **Given** un volumen de exportación grande, **When** se solicita, **Then**
   el sistema la procesa en segundo plano sin bloquear al administrador y le
   permite descargarla al finalizar.
3. **Given** un usuario sin rol Administrador, **When** intenta acceder a la
   exportación, **Then** el sistema le niega la acción.

---

### Edge Cases

- ¿Qué pasa si el Excel de carga inicial tiene dos filas con el mismo número
  de activo fijo?
- ¿Qué pasa si un centro de costos no tiene ningún activo asociado en el
  Excel?
- ¿Qué pasa si un usuario cierra la pestaña o pierde la conexión mientras
  tiene un activo reservado en gestión, sin guardar ni cancelar
  explícitamente?
- ¿Qué pasa si a un usuario se le corta la conexión a media subida de fotos?
- ¿Qué pasa si alguien intenta registrar dos cuentas con el mismo correo
  corporativo?
- ¿Qué pasa si el enlace de verificación de correo expira o no llega?
- ¿Qué pasa si se sube un archivo que no es una imagen válida como foto de
  equipo o de placa?
- ¿Qué pasa si se solicita una exportación sin ningún activo capturado
  todavía?
- ¿Qué pasa si se cambia el rol de un usuario mientras tiene una sesión
  activa capturando inventario?
- ¿Qué pasa si un usuario intenta capturar o editar un activo de un periodo
  de inventario ya cerrado?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST importar el listado de activos fijos desde el
  archivo Excel ubicado en `documentacion/` mediante un comando de
  importación, creando los registros de Activo Fijo, Empresa y Centro de
  Costos correspondientes.
- **FR-002**: El comando de importación MUST ser idempotente: ejecutarlo
  varias veces sobre el mismo archivo no debe crear activos duplicados ni
  afectar los registros de inventario ya capturados.
- **FR-003**: El sistema MUST permitir que cualquier persona con un correo de
  dominio corporativo permitido cree una cuenta.
- **FR-004**: El sistema MUST permitir el registro únicamente con correos de
  los dominios @levapan.com, @panalsas.com y @levacolsas.com, y MUST
  rechazar, con un mensaje claro, el registro de cualquier otro dominio.
- **FR-005**: El sistema MUST enviar un correo de confirmación al registrarse
  y MUST impedir el acceso al inventario hasta que el correo sea verificado.
- **FR-006**: Al verificar su correo, el sistema MUST asignar automáticamente
  al nuevo usuario el rol Inventariador y MUST otorgarle acceso inmediato,
  sin requerir aprobación manual de un Administrador.
- **FR-007**: El sistema MUST permitir a un Administrador crear, editar y
  desactivar usuarios, y asignarles uno o más roles (Administrador,
  Inventariador, Consulta).
- **FR-008**: El sistema MUST restringir cada acción protegida (capturar
  inventario, administrar usuarios, exportar) verificando el permiso
  asociado al usuario autenticado, no mediante verificación directa de rol.
- **FR-009**: El sistema MUST permitir a un usuario autenticado con permiso de
  captura digitar o seleccionar un centro de costos y MUST mostrarle
  automáticamente el listado de activos fijos asociados a ese centro.
- **FR-010**: El sistema MUST permitir capturar, por cada activo, foto del
  equipo, foto de la placa, ubicación y una observación opcional, junto con
  un estado: verificado, no encontrado o sobrante.
- **FR-011**: Cuando el estado capturado es "no encontrado", el sistema MUST
  NOT exigir las fotos, pero MUST exigir una observación.
- **FR-012**: Cuando el estado capturado es "verificado" o "sobrante", el
  sistema MUST exigir ambas fotos y la ubicación antes de considerar el
  activo inventariado.
- **FR-013**: El sistema MUST registrar qué usuario y en qué fecha/hora
  realizó cada captura o actualización del registro de inventario de un
  activo.
- **FR-013a**: Cuando un usuario reemplaza una foto (de equipo o de placa) ya
  subida para un activo, el sistema MUST mostrar la nueva foto como la
  vigente y MUST conservar la foto anterior, visible solo para usuarios con
  permiso de Administrador, durante un periodo de retención limitado antes de
  eliminarla definitivamente.
- **FR-014**: Cuando un usuario abre un activo para inventariarlo, el sistema
  MUST reservarlo de forma exclusiva para ese usuario mientras lo está
  gestionando. Si otro usuario intenta abrir ese mismo activo mientras sigue
  reservado, el sistema MUST informarle que el activo está en gestión por el
  primer usuario en ese momento, impidiéndole capturarlo hasta que quede
  libre.
- **FR-014a**: El sistema MUST liberar automáticamente la reserva exclusiva
  de un activo cuando el usuario que lo tenía en gestión guarda su captura,
  cancela la acción, o su sesión permanece inactiva más allá de un tiempo
  límite razonable, de forma que un activo no quede bloqueado indefinidamente
  por una sesión abandonada.
- **FR-015**: El sistema MUST calcular el porcentaje de cumplimiento como
  (activos inventariados / activos totales) x 100, agrupado por empresa y
  por centro de costos.
- **FR-016**: El sistema MUST mostrar el porcentaje de cumplimiento con un
  indicador visual tipo semáforo y el detalle de los activos pendientes.
- **FR-017**: El porcentaje de cumplimiento MUST estar disponible para
  consulta en cualquier momento por los usuarios autenticados con permiso
  correspondiente.
- **FR-017a**: El sistema MUST organizar la captura de inventario en periodos
  (ciclos) con fecha de apertura y de cierre. Mientras un periodo está
  abierto, los usuarios con permiso de captura MUST poder crear y modificar
  registros de inventario de ese periodo.
- **FR-017b**: Una vez cerrado un periodo, el sistema MUST impedir que
  usuarios sin permiso de Administrador modifiquen sus registros de
  inventario; un Administrador MUST poder reabrir el periodo (o editar un
  registro puntual de un periodo cerrado) cuando sea necesario, quedando esa
  reapertura también registrada con quién y cuándo.
- **FR-018**: El sistema MUST permitir únicamente a usuarios con permiso de
  Administrador exportar el inventario completo o filtrado por empresa y/o
  centro de costos.
- **FR-019**: La exportación MUST incluir, por cada activo: sus datos,
  estado, ubicación, observación, quién y cuándo lo capturó, ambas imágenes,
  y un resumen de cumplimiento.
- **FR-020**: El sistema MUST generar la exportación en formato Excel, con
  las fotos de equipo y de placa incrustadas en las celdas correspondientes
  de cada activo.
- **FR-021**: Cuando el volumen de la exportación sea grande, el sistema MUST
  procesarla en segundo plano y permitir su descarga una vez finalizada, sin
  bloquear al administrador mientras se genera.
- **FR-022**: La interfaz MUST estar en español y MUST ser usable desde un
  teléfono móvil, incluyendo la captura de fotos con la cámara del
  dispositivo.

### Key Entities *(include if feature involves data)*

- **Empresa**: cada una de las compañías del grupo presentes en el inventario
  (LEVAPAN, PANAL, LEVACOL); agrupa centros de costos y, a través de ellos,
  activos.
- **Centro de Costos**: unidad organizativa donde físicamente están los
  activos; pertenece a una única empresa (relación normalizada: la empresa se
  guarda solo aquí, no se repite en cada activo).
- **Activo Fijo**: bien físico a inventariar, proveniente del Excel (o creado
  como "sobrante" durante la captura); pertenece a un centro de costos, y a
  través de este obtiene su empresa.
- **Periodo de Inventario**: ciclo de captura con fecha de apertura y de
  cierre; mientras está abierto admite nuevas capturas y ediciones; una vez
  cerrado, sus registros quedan de solo lectura salvo reapertura explícita de
  un Administrador.
- **Registro de Inventario**: la captura realizada por un usuario sobre un
  activo fijo dentro de un periodo de inventario: estado (verificado / no
  encontrado / sobrante), fotos, ubicación real, observación, usuario autor y
  fecha/hora.
- **Usuario**: cuenta con correo corporativo verificado y uno o más roles
  asignados.
- **Rol / Permiso**: Administrador, Inventariador y Consulta, cada uno con un
  conjunto de permisos que determinan qué acciones puede ejecutar el usuario.
- **Exportación**: solicitud de exportación del inventario con sus filtros
  (empresa, centro de costos), estado (en proceso / lista) y archivo Excel
  resultante con las imágenes incrustadas.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un usuario con permiso de captura completa el registro de un
  activo (ambas fotos, ubicación y estado) en menos de 1 minuto desde un
  teléfono móvil con conectividad normal.
- **SC-002**: Al menos 20 usuarios pueden trabajar simultáneamente sobre el
  mismo centro de costos sin que se pierda una captura ni se duplique el
  conteo de un mismo activo en el cumplimiento.
- **SC-003**: El porcentaje de cumplimiento visible refleja cualquier captura
  nueva en menos de 5 segundos, sin que el usuario deba recargar la página
  manualmente.
- **SC-004**: La carga inicial de la totalidad de los activos del Excel
  corporativo (aproximadamente 10.700 filas) se completa sin errores y puede
  repetirse cualquier número de veces sin generar duplicados.
- **SC-005**: Un nuevo usuario con correo corporativo válido completa el
  proceso de registro y verificación de cuenta en menos de 5 minutos.
- **SC-006**: Un Administrador genera y descarga una exportación filtrada
  (por empresa o centro de costos) sin asistencia técnica, incluso cuando el
  volumen supera varios miles de registros con imágenes.
- **SC-007**: Un usuario sin el permiso requerido nunca logra completar una
  acción protegida (capturar, administrar usuarios, exportar); el sistema la
  bloquea el 100% de las veces.

## Assumptions

- El Excel de `documentacion/` deja de usarse como fuente activa una vez
  completada la carga inicial; los cambios posteriores a los datos de activos
  se hacen desde la aplicación, conforme al principio de la constitución del
  proyecto de que la base de datos es la fuente de verdad.
- Un activo "sobrante" (encontrado en campo pero no presente en el Excel
  original) se maneja creando un nuevo registro de Activo Fijo asociado al
  centro de costos donde se encontró, marcado con un origen distinto al de
  carga inicial, y exige la misma evidencia obligatoria que un activo
  "verificado".
- El envío del correo de confirmación de cuenta usa el mecanismo estándar de
  verificación de correo de Laravel, sin un proveedor de correo externo
  especial más allá de la configuración de correo saliente de cada entorno.
- El almacenamiento de las fotos de equipo y de placa es privado; no se
  exponen URLs públicas directas a las imágenes, conforme a la constitución
  del proyecto.
- La generación de exportaciones grandes en segundo plano usa el sistema de
  colas ya configurado en el proyecto, sin requerir infraestructura adicional
  fuera de lo ya disponible.
- El "tiempo límite razonable" de inactividad tras el cual se libera
  automáticamente la reserva exclusiva de un activo (FR-014a) se asume en 15
  minutos sin actividad, salvo que el negocio indique otro valor.
- El "tiempo limitado" durante el cual un Administrador puede recuperar una
  foto reemplazada (FR-013a) se asume en 30 días desde el reemplazo, salvo
  que el negocio indique otro valor; pasado ese plazo la foto anterior se
  elimina definitivamente.
