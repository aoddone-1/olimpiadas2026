-- ============================================================
-- Migración: resultados por PUNTAJE en disciplinas Masivo / Tiempo
--
-- Permite cargar, además de tiempos (mm:ss), un puntaje numérico
-- por participante. En puntajes la posición se calcula de forma
-- INVERSA: gana el de MAYOR puntaje.
--
-- Ejecutar UNA sola vez sobre la base de datos del torneo:
--   mysql -u usuario -p base < sql/migracion_puntaje.sql
-- ============================================================

-- 1) Nuevo valor en el enum de tipos de resultado.
ALTER TABLE `resultados`
  MODIFY COLUMN `tipo_resultado` ENUM('MARCADOR','TIEMPO','PUNTAJE')
  NOT NULL DEFAULT 'MARCADOR'
  COMMENT 'MARCADOR = enfrentamiento (X vs Y), TIEMPO = posiciones y tiempos, PUNTAJE = posiciones por puntaje (mayor = mejor)';

-- 2) La columna tiempo es varchar(50): alcanza para tiempos (hh:mm:ss)
--    y también para puntajes numéricos (se guarda el número como texto).
ALTER TABLE `resultado_detalle`
  MODIFY COLUMN `tiempo` VARCHAR(50) NULL
  COMMENT 'Tiempo formateado HH:MM:SS o MM:SS (TIEMPO) / puntaje numérico (PUNTAJE)';
