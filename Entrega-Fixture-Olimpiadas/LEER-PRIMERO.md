# Entrega de diseño · Fixture Olimpiadas La Pampa 2026

Esta entrega contiene la maqueta aprobada como base del rediseño del panel público de competencias. Está destinada a la desarrolladora que mantiene el fixture.

## Ver la propuesta

Abrir `maqueta-olimpiadas.html` con Chrome o Edge. Funciona sin instalación ni conexión. Es una demostración con información pública capturada el 29/10/2026, no una conexión al sistema actual.

## Qué se solicita implementar

Aplicar esta presentación a la página pública del fixture en el proyecto existente, conservando su carga de competencias, resultados y medallas. La web principal puede continuar enlazando a esa página.

Se conserva el diseño de esta maqueta: fondo azul oscuro, paneles azules, acentos verdes, logo oficial, filtros superiores, pestañas de fixture/resultados/medallero, selección de día y disciplinas desplegables.

No se solicita publicar esta copia de datos como si fuera el fixture actualizado. Tampoco se incluye una modificación de la web principal de Netlify.

## Archivos incluidos

- `maqueta-olimpiadas.html`: demostración autónoma lista para abrir y revisar.
- `src/maqueta.html`: estructura visual con marcadores que completa el generador.
- `src/maqueta.css`: estilos base y adaptación a pantallas pequeñas.
- `src/theme-original.css`: tema visual y disciplinas desplegables; se aplica después del CSS base.
- `src/maqueta.js`: comportamiento de la demostración.
- `assets/logo_olimpiadas.png`: logo utilizado.
- `data/`: copias de páginas públicas que alimentan exclusivamente la demostración.
- `scripts/build.mjs`: genera el HTML autónomo a partir de las fuentes.
- `GUIA-DE-INTEGRACION.md`: criterios y puntos de conexión con el sistema existente.
- `REVISION-FUNCIONAL.md`: lista de aceptación para validar la implementación real.

Para reconstruir la demostración con Node.js, desde esta carpeta ejecutar `node scripts/build.mjs`. No hay que instalar paquetes.

## Estado de la entrega

Diseño y navegación de referencia preparados. Integración con el servidor pendiente de realizar en el proyecto de la desarrolladora. No se dispone de su código fuente, esquema de base de datos ni acceso al alojamiento. Los nombres de campos internos y la tecnología del servidor deben resolverse en ese proyecto.
