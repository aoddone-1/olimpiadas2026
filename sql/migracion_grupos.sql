-- ============================================================
-- Migración: FASE DE GRUPOS (Fixture tipo Mundial) - Parte 1
-- Aplicar sobre la base existente (MySQL/MariaDB).
-- Idempotente: se puede correr más de una vez sin error.
-- ============================================================

-- 1) Tabla de grupos (Grupo A, B, C... por categoría)
CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int NOT NULL,
  `nombre_grupo` varchar(10) NOT NULL COMMENT 'Letra del grupo: A, B, C...',
  PRIMARY KEY (`id_grupo`),
  UNIQUE KEY `uq_categoria_grupo` (`id_categoria`,`nombre_grupo`),
  KEY `fk_grupo_categoria` (`id_categoria`),
  CONSTRAINT `fk_grupo_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2) FK de grupo en utes (NULL = sin asignar aun)
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'utes' AND column_name = 'id_grupo'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `utes` ADD COLUMN `id_grupo` int DEFAULT NULL COMMENT ''NULL = sin grupo (aun no asignado a la fase de grupos)'' AFTER `fecha_creacion`',
  'SELECT ''columna utes.id_grupo ya existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'utes' AND index_name = 'fk_ute_grupo'
);
SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `utes` ADD KEY `fk_ute_grupo` (`id_grupo`)',
  'SELECT ''indice fk_ute_grupo ya existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE table_schema = DATABASE() AND table_name = 'utes' AND constraint_name = 'fk_ute_grupo'
);
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE `utes` ADD CONSTRAINT `fk_ute_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT ''FK fk_ute_grupo ya existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
