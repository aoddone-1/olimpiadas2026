# Revisión de la implementación

Estas comprobaciones son criterios de aceptación para el sistema integrado. No indican que se haya probado el servidor actual desde esta entrega.

## Diseño y navegación

- [ ] Comparar la vista con `maqueta-olimpiadas.html`: colores, logo, jerarquía, tarjetas y desplegables.
- [ ] Revisar computadora y celular, incluyendo un ancho de 360 px: sin desplazamiento horizontal de la página y con controles legibles.
- [ ] Cambiar entre fixture, resultados y medallero.
- [ ] Recorrer filtros, pestañas y desplegables con teclado; verificar foco visible y estados accesibles.
- [ ] Abrir una disciplina y luego otra del mismo día; comprobar el cierre de la anterior en navegación normal.
- [ ] Desplegar y contraer todas; cambiar de día y volver.

## Datos y filtros

- [ ] Comparar el total de competencias con la consulta real del servidor, sin duplicados ni pérdidas por agrupación. Las 320 competencias corresponden solo a la captura de referencia.
- [ ] Seleccionar cada delegación y comprobar sus competencias contra el sistema original.
- [ ] Combinar deporte y categoría; cambiar de deporte y comprobar que no quede una categoría incompatible.
- [ ] Buscar por equipo, sede y categoría, incluyendo texto con y sin tildes.
- [ ] Probar un filtro sin competencias y una búsqueda sin coincidencias; limpiar y recuperar la agenda.
- [ ] Verificar encuentros entre equipos y pruebas masivas con varias categorías.
- [ ] Revisar horarios, sedes y estados cuando falta algún dato.
- [ ] Cargar o modificar una competencia en el entorno de prueba y confirmar que aparece actualizada en la vista pública al consultarla nuevamente.
- [ ] Recargar una dirección con filtros y comprobar que se conserva la selección válida.

## Resultados, medallas y publicación

- [ ] Verificar resultados vacíos y cargados con datos reales del entorno de prueba.
- [ ] Verificar medallero vacío y con medallas; comprobar orden y totales con el sistema existente.
- [ ] Acordar y comprobar el alcance de los filtros sobre resultados, medallero y resumen.
- [ ] Comprobar que los datos de texto se presentan de forma segura.
- [ ] Si hay consultas asíncronas, probar demora, error de red y cambios rápidos de filtros.
- [ ] Retirar textos y fecha fija de demostración una vez conectada la información real.
- [ ] Confirmar que el enlace Fixture de la web principal sigue llegando a la página correcta.
