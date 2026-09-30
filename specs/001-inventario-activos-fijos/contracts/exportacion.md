# Contrato: Exportación del Inventario (Excel con imágenes)

## Solicitud

`POST /admin/exportaciones` (acción Livewire en `admin.solicitar-exportacion`),
permiso `inventario.exportar`. Parámetros: `empresa_id` (nullable),
`centro_costos_id` (nullable, debe pertenecer a `empresa_id` si ambos se
envían), `inventario_id` (periodo; por defecto el periodo abierto actual).

Crea un registro `exportaciones` con `estado = 'en_proceso'` y despacha
`GenerarExportacionInventario` a la cola `database`.

## Job `GenerarExportacionInventario`

1. Recorre los `inventario_detalles` del periodo/filtro solicitado (con
   `activo`, `centroCostos`, `empresa`, `actualizadoPor` precargados).
2. Por cada fila agrega una fila en la hoja Excel con: número de activo,
   denominación, placa, empresa, centro de costos, estado, ubicación,
   observación, autor (`actualizado_por.name`), fecha de captura
   (`updated_at`), y ambas fotos incrustadas como `Drawing` en las celdas
   correspondientes (redimensionadas a un tamaño de celda razonable, no al
   tamaño original de 1600px).
3. Agrega una hoja/resumen de cumplimiento (por empresa y centro de costos)
   igual al cálculo de `PanelCumplimiento` (research.md §6).
4. Guarda el archivo en el disco privado (`storage/app/private/exportaciones/`)
   y actualiza el registro: `estado = 'lista'`, `archivo_path`,
   `generado_en`.
5. Si algo falla, el registro queda `estado = 'fallida'` (Laravel reintenta
   el job según la config por defecto de la cola antes de marcarlo fallido).

## Descarga

`GET /admin/exportaciones/{exportacion}/descargar`, permiso
`inventario.exportar`. Si `estado != 'lista'` responde 409 (aún no está
lista) en vez de 404, para que la UI pueda mostrar "en proceso, intenta de
nuevo en un momento".

## Garantías verificables

- Una exportación filtrada por una empresa contiene únicamente activos de
  esa empresa (y, si además se filtra por centro de costos, solo los de ese
  centro).
- El archivo generado abre correctamente en Excel/LibreOffice y cada fila
  de activo tiene sus dos fotos visibles en las celdas correspondientes.
- Mientras `estado = 'en_proceso'`, la ruta de descarga no entrega el
  archivo.
