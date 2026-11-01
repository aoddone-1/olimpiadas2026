-- Migración: observaciones en la acreditación de kits
-- Permite acreditar la entrega del kit de un inscripto con una OBSERVACIÓN
-- (ej: "Faltó documentación", "Entregado fuera de término", etc.).
-- NULL = entregado sin observación. Se muestra como tooltip/badge en la
-- columna "Kit" de la pantalla de Inscriptos.
ALTER TABLE `participantes`
ADD COLUMN `kit_observacion` VARCHAR(255) DEFAULT NULL AFTER `kit_entregado`;
