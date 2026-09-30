# Contrato: Rutas y Autorización

Todas las rutas autenticadas llevan `auth` + `verified`; el permiso exacto se
indica en la columna `can:`. Ningún controlador/componente verifica rol
directamente (Principio IV de la constitución) — siempre verifica el permiso.

| Ruta | Método | `can:` | Componente / Controlador | Descripción |
|---|---|---|---|---|
| `/inventario` | GET | `inventario.ver` | Volt `inventario.seleccionar-centro-costos` | Digitar/seleccionar centro de costos |
| `/inventario/{centroCostos}` | GET | `inventario.ver` | Volt `inventario.lista-activos` | Listado automático de activos del centro |
| `/inventario/activos/{activo}/capturar` | GET/POST (Livewire) | `inventario.capturar` | Volt `inventario.capturar-activo` | Abrir (reserva exclusiva) y guardar captura de un activo |
| `/fotos/{inventarioDetalle}/{tipo}` | GET | `inventario.ver` | `FotoActivoController@mostrar` | Sirve la foto privada (`tipo` = `equipo`&#124;`placa`); valida que el detalle pertenezca al usuario autorizado |
| `/cumplimiento` | GET | `inventario.ver` | Volt `cumplimiento.panel` | % cumplimiento por empresa y centro de costos, semáforo, pendientes |
| `/admin/usuarios` | GET | `admin.usuarios` | Volt `admin.gestion-usuarios` | Listar/crear/editar/desactivar usuarios y roles |
| `/admin/exportaciones` | GET/POST (Livewire) | `inventario.exportar` | Volt `admin.solicitar-exportacion` | Solicitar exportación con filtros; ver estado |
| `/admin/exportaciones/{exportacion}/descargar` | GET | `inventario.exportar` | `DescargaExportacionController@descargar` | Descarga el Excel cuando `estado = 'lista'`; 404/409 si no |

**Registro** (`/register`, `/verify-email/...`): rutas estándar de Breeze;
la única adición es la regla de validación del dominio de correo (ver
`data-model.md` → no requiere tabla nueva, vive en `config/inventario.php`) y
la asignación automática del rol `Inventariador` en el listener de
`Verified` (evento nativo de Laravel `MustVerifyEmail`).

**Reserva exclusiva**: abrir `inventario/activos/{activo}/capturar` intenta
adquirir la reserva (ver `research.md` §3); si falla porque otro usuario la
tiene, el componente Volt renderiza el estado "en gestión por {usuario}" en
vez del formulario de captura, sin necesidad de una ruta distinta.
