-- ============================================================
-- Migración: deportes de ENFRENTAMIENTO por PUNTAJE (liga)
--
-- Hay deportes de enfrentamiento entre equipos (hockey, vóley, básquet...)
-- que NO definen el podio con una final + tercer puesto, sino por la tabla
-- de puntos del torneo: 3 pts al ganador, 1 pt por lado en empate y 0 al
-- perdedor. Esta migración agrega a `deportes` la opción de indicar ese
-- sistema ("PUNTAJE") junto con los valores de los puntos.
--
-- Ejecutar UNA sola vez sobre la base de datos del torneo:
--   mysql -u usuario -p base < sql/migracion_puntaje_liga.sql
--
-- Luego, en "Gestión de Deportes", marcá "Define por puntaje (liga)" en los
-- deportes que corresponda (o directamente):
--   UPDATE deportes SET sistema_definicion = 'PUNTAJE' WHERE nombre_deporte IN ('Hockey', ...);
-- ============================================================

ALTER TABLE `deportes`
  ADD COLUMN `sistema_definicion` ENUM('FASE_FINAL','PUNTAJE') NOT NULL DEFAULT 'FASE_FINAL'
    COMMENT 'FASE_FINAL = podio por FINAL + TERCER_PUESTO. PUNTAJE = podio por tabla de puntos (todos contra todos / liga)'
    AFTER `modalidad_competencia`,
  ADD COLUMN `puntosVictoria` int NOT NULL DEFAULT 3
    COMMENT 'Puntos al ganador de un enfrentamiento (sistema PUNTAJE)'
    AFTER `sistema_definicion`,
  ADD COLUMN `puntosEmpate` int NOT NULL DEFAULT 1
    COMMENT 'Puntos por equipo en empate (sistema PUNTAJE; 0 si el deporte no admite empate)'
    AFTER `puntosVictoria`;
