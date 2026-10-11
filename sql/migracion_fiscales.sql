-- ============================================================
-- Migración: Campo FISCALES por deporte
-- Agrega la columna `fiscales` (texto libre) a la tabla `deportes`.
-- La información se muestra en: Gestión Deportiva, Panel/Reporte de
-- Fixture, Panel de Acreditación y Credencial del competidor.
-- Idempotente: se puede correr más de una vez sin error.
-- Aplicar sobre la base existente (MySQL/MariaDB).
-- ============================================================

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'deportes'
    AND column_name = 'fiscales'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `deportes` ADD COLUMN `fiscales` text COMMENT ''Texto libre con los fiscales asignados a la disciplina'' AFTER `modalidad_competencia`',
  'SELECT ''La columna deportes.fiscales ya existe, no se hizo nada.'' AS aviso'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
