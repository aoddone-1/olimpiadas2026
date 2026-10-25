-- Migración: control de impresión/reimpresión de credenciales
-- Agrega en "participantes" la marca temporal de cuándo se imprimió la
-- credencial del participante. NULL = nunca impresa (o reimpresión
-- habilitada desde la pantalla de Inscriptos).
ALTER TABLE `participantes`
ADD COLUMN `credencial_impresa` TIMESTAMP NULL AFTER `fecha_inscripcion`;
