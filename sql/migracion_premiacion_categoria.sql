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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tipos de premio disponibles (trofeo, medalla, etc.)';

INSERT INTO `premio_tipo` (`nombre`, `icono`) VALUES
  ('Trofeo',   '🏆'),
  ('Medalla',  '🎖️'),
  ('Diploma',  '📜'),
  ('Puesto',   '🏅');

-- Regla de premiación POR CATEGORÍA (JSON) + día sugerido de entrega.
-- Formato del JSON: un array con un objeto por puesto premiado, en orden:
--   [{"puesto":1,"premio":"Trofeo"},{"puesto":2,"premio":"Medalla"},...]
-- Si la columna es NULL, la categoría premia 1º/2º/3º con podio clásico
-- (compatibilidad total con los datos existentes).
ALTER TABLE `categorias`
  ADD COLUMN `premiacion_regla` text DEFAULT NULL
    COMMENT 'JSON: puestos que se premian y tipo de premio de cada uno. NULL = default (1,2,3)'
    AFTER `puntosEmpate`,
  ADD COLUMN `dia_premiacion_sugerido` date DEFAULT NULL
    COMMENT 'Día sugerido para la entrega de premios de esta categoría (opcional)'
    AFTER `premiacion_regla`;
