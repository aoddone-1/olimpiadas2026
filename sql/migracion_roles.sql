-- ============================================================
-- Migración: REDEFINICIÓN DE ROLES de usuarios
-- Cambia el enum `usuarios.rol`:
--   'mesa_control'    -> 'carga_resultados'  (gente que carga resultados)
--   'seguridad_cenas' -> 'solo_lectura'      (ven todo, no modifican nada)
-- Roles válidos luego de la migración:
--   superadmin | admin | staff | carga_resultados | solo_lectura | delegado
-- Idempotente: si los valores nuevos ya existen, no hace nada.
-- Aplicar sobre la base existente (MySQL/MariaDB).
-- ============================================================

SET @enum_actual = (
  SELECT COLUMN_TYPE FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'rol'
);

SET @sql = IF(
  LOCATE('carga_resultados', @enum_actual) = 0,
  'ALTER TABLE `usuarios` MODIFY COLUMN `rol` enum(''superadmin'',''admin'',''staff'',''carga_resultados'',''solo_lectura'',''delegado'') NOT NULL',
  'SELECT ''El enum de roles ya fue migrado, no se hizo nada.'' AS aviso'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Nota: MySQL convierte automáticamente los valores viejos durante el ALTER:
--   'mesa_control'    pasa a 'carga_resultados' (misma posición del enum)
--   'seguridad_cenas' pasa a 'solo_lectura'     (misma posición del enum)
