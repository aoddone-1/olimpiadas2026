-- ============================================================
-- FIXTURE: soporte para resultados de deportes MASIVO_TIEMPO
-- (running, ciclismo, pesca...): orden de llegada de las UTEs.
-- Ejecutar ESTE script antes de usar "Cargar resultados".
-- Si ya lo ejecutaste y la columna existe, ignoralo.
-- ============================================================

ALTER TABLE `fixtures`
  ADD COLUMN IF NOT EXISTS `resultado` TEXT NULL
  COMMENT 'JSON con los id_ute en orden de llegada (deportes MASIVO_TIEMPO). Ej: [3,1,5]'
  AFTER `estado`;
