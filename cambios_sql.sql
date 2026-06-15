ALTER TABLE `deportes` 
ADD COLUMN `modalidad` ENUM('INDIVIDUAL', 'EQUIPO', 'AMBAS') NOT NULL DEFAULT 'INDIVIDUAL' 
AFTER `genero`;

ALTER TABLE `participantes` 
ADD COLUMN `es_competidor` TINYINT(1) NOT NULL DEFAULT '1' AFTER `contacto_emergencia`,
ADD COLUMN `es_delegado` TINYINT(1) NOT NULL DEFAULT '0' AFTER `es_competidor`;

ALTER TABLE inscripciones_deportivas 
ADD COLUMN tiene_ute TINYINT(1) DEFAULT 0,
ADD COLUMN necesita_ute TINYINT(1) DEFAULT 0,
ADD COLUMN detalle_ute TEXT NULL;