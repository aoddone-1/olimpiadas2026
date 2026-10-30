# Guía de integración para la desarrolladora

## Objetivo y alcance

Reemplazar la presentación del panel público por el diseño incluido, usando las competencias y resultados reales del sistema existente. Mantener la administración y las reglas deportivas del sistema. La entrega es independiente del lenguaje o framework del servidor: no se presupone una API, una base de datos ni una tecnología determinada.

La maqueta trabaja con HTML público guardado. Su JavaScript no es un cliente de datos en vivo y no debe copiarse sin adaptar esa parte.

## Orden propuesto de integración

1. Trabajar en una copia de desarrollo del proyecto actual.
2. Llevar la estructura de `src/maqueta.html` a la plantilla pública del sistema. Reemplazar los marcadores `/*__...__*/` por las salidas correspondientes de la plantilla, recursos y datos reales. El archivo fuente no está listo para abrir por sí solo.
3. Aplicar `maqueta.css` y luego `theme-original.css`. Revisar las reglas generales para evitar interferencias con otras pantallas del proyecto. Usar el logo de `assets`.
4. Conservar los catálogos e identificadores reales de delegación, deporte y categoría, y conectar los filtros a la consulta existente.
5. Alimentar el fixture, el resumen, los resultados y el medallero con datos del servidor. Retirar el lector de capturas y los textos de demostración al completar esta conexión.
6. Validar los escenarios de `REVISION-FUNCIONAL.md` antes de sustituir la vista pública.

## Estructura visual y puntos de conexión

| Elemento | Referencia en las fuentes | Conexión requerida |
| --- | --- | --- |
| Filtros | `#filters-form`, campos `delegacion`, `deporte`, `categoria` | Catálogos y valores seleccionados del sistema |
| Categorías por deporte | `syncCategories()` | En la maqueta, cada opción usa `data-deporte` con el valor del deporte asociado |
| Resumen | `.stats`, marcador `STATS` | Conteos reales y su alcance claramente definido |
| Días | `#day-nav`, `renderDays()` | Días con competencias para los filtros seleccionados |
| Fixture | `#schedule`, `buildDiscipline()` | Competencias agrupadas por día y deporte |
| Búsqueda | `#search` | Búsqueda por equipo, sede o categoría dentro del día y filtros activos |
| Resultados | `#results-content` | Marcadores y posiciones calculados por el sistema |
| Medallero | `#medals-content` | Medallas del sistema; sin valores de ejemplo |
| Pestañas | `.primary-tabs`, `setTab()` | Conservar estados y navegación por teclado |

## Qué debe reemplazarse del JavaScript de la maqueta

`snapshots`, `readModel()` y el bloque `#snapshot-data` cargan copias de HTML. La elección de delegación selecciona una captura guardada, no hace una consulta al servidor. Sustituir esta fuente por la consulta o renderizado de la aplicación existente.

El lector actual reconoce `.grupo-dia`, `.partido`, `.deporte-chip`, `.categoria-linea`, `.hora-inicio`, `.meta`, `.badge` y `.masivo` de las capturas. Son dependencias de esta demostración, no un contrato impuesto al servidor. Si se cambia la estructura de las tarjetas, adaptar su renderizado y revisar los estilos asociados.

El modelo de presentación usa día, deporte, categoría, horario, sede, estado y contenido del encuentro. Las pruebas masivas se agrupan por deporte, horario, sede y estado. Preservar los identificadores y relaciones reales al transformar los datos; no usar nombres de equipos como identificadores.

Las funciones visuales pueden reutilizarse después de adaptar la fuente de datos. En particular, `render()` también actualiza resultados y medallero: revisar si los filtros deben afectar esas secciones según las reglas del sistema. No deducir que los filtros de la demostración ya resuelven esa lógica para producción.

La maqueta inserta fragmentos HTML guardados mediante `innerHTML`. En la implementación real, escapar los datos en la plantilla o crear texto con `textContent`; usar HTML únicamente cuando lo genere y controle la aplicación. No insertar entradas del público sin tratamiento.

## Consultas y direcciones

La referencia guardada utiliza un formulario GET a `/publico` con `delegacion`, `deporte` y `categoria`. Conservar esos parámetros y sus valores reales si forman parte de los enlaces actuales. La maqueta agrega `dia`, `q` y los fragmentos `#tab-resultados` / `#tab-medallero` para conservar la vista.

Puede mantenerse el renderizado del servidor con recarga al aplicar filtros, o utilizar la API interna si el proyecto ya cuenta con una. No es necesario crear una API pública ni consultar el dominio externo desde Netlify para implementar este diseño dentro del proyecto del fixture.

Si se utiliza carga asíncrona, añadir estados de carga y error, evitar que una respuesta anterior reemplace una selección posterior y no mostrar datos antiguos como si acabaran de actualizarse.

## Comportamiento que debe conservarse

- Día inicial: primer día disponible de los datos filtrados; opción para ver todos los días.
- Disciplinas cerradas al entrar. En navegación normal, abrir una cierra la anterior dentro del mismo día.
- Control para desplegar o contraer todas las disciplinas visibles.
- Buscar o filtrar por deporte/categoría abre las disciplinas con resultados pertinentes.
- Las categorías disponibles corresponden al deporte seleccionado; una categoría incompatible se limpia.
- Búsqueda sin distinción de mayúsculas ni tildes, dentro del alcance seleccionado.
- Vacíos explícitos cuando no hay competencias, resultados o medallas.
- Resultados, medallero y resumen deben conservar sus reglas de negocio actuales. No inventar marcadores ni medallas: las capturas incluidas no tenían esos datos.

## Textos y elementos exclusivos de la demostración

Al conectar los datos reales, retirar la barra «MAQUETA NAVEGABLE», la fecha fija del 29/10/2026, las referencias a capturas y los enlaces «Ver sitio actual» / «Consultar información actualizada» que apuntarían a la misma página. Actualizar la descripción de la página. Mostrar una fecha de actualización solo si el sistema tiene un dato fiable para ella.

## Entrega e implementación

Esta carpeta permite revisar y reconstruir la propuesta. No constituye un reemplazo directo del proyecto del servidor. La implementación termina cuando la plantilla usa los datos actuales y pasa la revisión funcional; publicar el HTML autónomo por sí solo dejaría información fija.
