# Contrato: Comando de Importación del Excel

## Firma

```
php artisan inventario:importar-activos [--archivo=documentacion/DATA\ AF\ APP\ VF.xlsx]
```

- `--archivo` es opcional; por defecto apunta al Excel en `documentacion/`.

## Comportamiento (idempotente — FR-001, FR-002)

1. Abre el archivo con PhpSpreadsheet (`IOFactory::load`,
   `setReadDataOnly(true)`) y lee la hoja `APP VF`.
2. Para cada fila (a partir de la fila 2):
   - `firstOrCreate` de `Empresa` por `nombre` (columna `EMPRESA`).
   - `firstOrCreate` de `CentroCostos` por `codigo` (columna `CECO`),
     actualizando `descripcion`/`empresa_id` si cambiaron.
   - `updateOrCreate` de `Activo` por `numero_activo` (columna
     `ACTIVO FIJO`), con `origen = 'excel'` y los demás campos mapeados
     desde el Excel. **Nunca** toca `inventario_detalles` de ese activo si
     ya existen (no se pierden capturas ya hechas al re-importar).
3. Filas con `PLACA` vacía se importan igual (columna nullable).
4. Al final, imprime un resumen: activos creados, activos actualizados,
   empresas y centros de costos totales.

## Garantías verificables

- Ejecutar el comando dos veces seguidas sobre el mismo archivo produce el
  mismo número de filas en `activos`, `empresas` y `centros_costos` en la
  segunda ejecución que en la primera (0 duplicados).
- Ejecutar el comando después de que existan registros de inventario NO
  borra ni modifica esos registros de `inventario_detalles`.
- Cualquier fila sin `PLACA` no aborta la importación ni las filas
  siguientes.
