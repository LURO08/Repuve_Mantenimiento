# Optimizacion y tecnicos

## Cambios aplicados

- Se agrego la tabla `tecnicos` para registrar responsables/encargados.
- Se agrego el apartado `views/tecnicos.php` con JS separado en `js/tecnicos.js`.
- Los campos manuales de tecnico en mantenimientos, bajas y bitacoras ahora usan select con tecnicos activos.
- Los nombres historicos de `revisiones`, `infraestructura_revisiones`, `bitacoras_arco` y `arcos_bajas` se importan automaticamente a `tecnicos` cuando se abren las vistas principales.
- En `arcos.php` se redujo la carga inicial:
  - ya no se consultan materiales/componentes por cada fila;
  - los componentes se cargan bajo demanda con `arcos_controller.php?action=get_componentes`;
  - ultima fecha de mantenimiento y bitacora se obtienen en la consulta principal, no con consultas por fila.
- Se agregaron indices para consultas frecuentes por arco/fecha y bitacora.
- Las bajas de arco aceptan multiples evidencias en imagen o PDF.
- Las evidencias de baja se guardan en `uploads/bajas/` y se relacionan mediante `arcos_bajas_evidencias`.
- Mientras un arco tenga estado `Baja`, queda excluido de:
  - la tabla operativa de mantenimientos;
  - indicadores, graficas y modales de reportes;
  - calculos de mantenimiento vencido/proximo;
  - conteos de materiales y ubicaciones activas.
- Al restaurar el arco, su historial vuelve a participar en mantenimientos y reportes.

## Reglas para mantenerlo rapido

- Evitar `json_encode(...)` grande dentro de botones o filas de tabla.
- Preferir endpoints AJAX para datos de modales pesados.
- Evitar consultas dentro de `foreach` de tablas principales.
- Mantener cada vista con su JS propio en `js/`.
- Para nuevas capturas de responsable, usar la tabla `tecnicos` y guardar el nombre seleccionado en el campo existente.

## Archivos principales

- `database.sql`
- `views/tecnicos.php`
- `controllers/tecnicos_controller.php`
- `js/tecnicos.js`
- `views/arcos.php`
- `controllers/arcos_controller.php`
- `js/arcos.js`
- `views/revisiones.php`
