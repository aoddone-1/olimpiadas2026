-- ============================================================
-- Migración: REGLA DE PREMIACIÓN y DÍA SUGERIDO POR CATEGORÍA
--
-- Cada categoría define SU propia forma de premiar:
--   * cuántos puestos se premian (1, 2, 3 o más)
--   * qué tipo de premio recibe cada puesto (trofeo, medalla, etc.)
--   * día sugerido para la entrega (opcional; si no está, la noche sigue
--     derivándose del fixture como hasta ahora)
--
-- Ejecutar UNA sola vez:
--   mysql -u usuario -p base < sql/migracion_premiacion_categoria.sql
-- ============================================================

-- Catálogo de tipos de premio (editable por el organizador).
CREATE TABLE IF NOT EXISTS `premio_tipo` (
  `id_premio_tipo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Ej: Trofeo, Medalla, Diploma',
  `icono` varchar(16) DEFAULT NULL COMMENT 'Emoji/icono para mostrar en pantalla (🏆 🥇 🎖️ 📜)',
  PRIMARY KEY (`id_premio_tipo`),
  UNIQUE KEY `uq_premio_tipo_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de premio disponibles (trofeo, medalla, etc.)';

-- IMPORTANTE: la tabla usa utf8mb4 porque los emojis (🏆 🎖️ 📜 🏅) son
-- caracteres de 4 bytes que latin1/utf8 (3 bytes) NO pueden almacenar.
-- Si la tabla ya existía en otra codificación, la convertimos abajo.
-- OJO: si la tabla NO existe todavía (falló el CREATE de arriba), este ALTER
-- corta la migración con un error; por eso va protegido con un procedimiento.
DROP PROCEDURE IF EXISTS `_pm_convert_premio_tipo`;
CREATE PROCEDURE `_pm_convert_premio_tipo`()
BEGIN
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'premio_tipo') THEN
        ALTER TABLE `premio_tipo`
          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    END IF;
END;
CALL `_pm_convert_premio_tipo`();
DROP PROCEDURE IF EXISTS `_pm_convert_premio_tipo`;

-- ON DUPLICATE KEY evita errores si la migración se ejecuta más de una vez
-- (la columna `nombre` tiene índice único).
INSERT INTO `premio_tipo` (`nombre`, `icono`) VALUES
  ('Trofeo',   '🏆'),
  ('Medalla',  '🎖️'),
  ('Diploma',  '📜'),
  ('Puesto',   '🏅')
ON DUPLICATE KEY UPDATE `icono` = VALUES(`icono`);

-- Regla de premiación POR CATEGORÍA (JSON) + día sugerido de entrega.
-- Formato del JSON: un array con un objeto por puesto premiado, en orden:
--   [{"puesto":1,"premio":"Trofeo"},{"puesto":2,"premio":"Medalla"},...]
-- Si la columna es NULL, la categoría premia 1º/2º/3º con podio clásico
-- (compatibilidad total con los datos existentes).
-- Cada ADD COLUMN va protegido para que la migración pueda re-ejecutarse sin
-- error "Duplicate column name" si ya se había aplicado parcialmente.
DROP PROCEDURE IF EXISTS `_pm_add_cols_categorias`;
CREATE PROCEDURE `_pm_add_cols_categorias`()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias'
                     AND COLUMN_NAME = 'premiacion_regla') THEN
        ALTER TABLE `categorias`
          ADD COLUMN `premiacion_regla` text DEFAULT NULL
            COMMENT 'JSON: puestos que se premian y tipo de premio de cada uno. NULL = default (1,2,3)'
            AFTER `puntosEmpate`;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias'
                     AND COLUMN_NAME = 'dia_premiacion_sugerido') THEN
        ALTER TABLE `categorias`
          ADD COLUMN `dia_premiacion_sugerido` date DEFAULT NULL
            COMMENT 'Día sugerido para la entrega de premios de esta categoría (opcional)'
            AFTER `premiacion_regla`;
    END IF;
END;
CALL `_pm_add_cols_categorias`();
DROP PROCEDURE IF EXISTS `_pm_add_cols_categorias`;
