-- ============================================================
-- Migración: Campo GENERO en la tabla UTES
-- Agrega la columna `genero` (FEMENINO / MASCULINO / MIXTO) a la
-- tabla `utes`, para poder asignar/modificar el género del equipo
-- desde el Panel de Equipos (modales Crear/Editar UTE).
-- Idempotente: se puede correr más de una vez sin error.
-- Aplicar sobre la base existente (MySQL/MariaDB).
-- ============================================================

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'utes'
    AND column_name = 'genero'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `utes` ADD COLUMN `genero` enum(''FEMENINO'',''MASCULINO'',''MIXTO'') NOT NULL DEFAULT ''MIXTO'' AFTER `nombre_ute`',
  'SELECT ''La columna utes.genero ya existe, no se hizo nada.'' AS aviso'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
