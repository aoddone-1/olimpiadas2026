-- ============================================================
-- Migración: el SISTEMA DE DEFINICIÓN vive en la CATEGORÍA, no en el deporte
--
-- Dentro de un mismo deporte de enfrentamiento hay categorías que definen su
-- podio con FINAL + TERCER PUESTO y otras que lo definen por TABLA DE PUNTOS
-- del torneo (3 pts al ganador, 1 pt por lado en empate, 0 al perdedor).
-- Por eso estas columnas van en `categorias`.
--
-- Ejecutar UNA sola vez:
--   mysql -u usuario -p base < sql/migracion_puntaje_categoria.sql
-- ============================================================

ALTER TABLE `categorias`
  ADD COLUMN `sistema_definicion` ENUM('FASE_FINAL','PUNTAJE') NOT NULL DEFAULT 'FASE_FINAL'
    COMMENT 'ENFRENTAMIENTO: FASE_FINAL = podio por FINAL + TERCER_PUESTO. PUNTAJE = podio por tabla de puntos de la categoría (liga)'
    AFTER `mejores_segundos`,
  ADD COLUMN `puntosVictoria` int NOT NULL DEFAULT 3
    COMMENT 'Puntos al ganador en esta categoría (sistema PUNTAJE)'
    AFTER `sistema_definicion`,
  ADD COLUMN `puntosEmpate` int NOT NULL DEFAULT 1
    COMMENT 'Puntos por equipo en empate en esta categoría (sistema PUNTAJE; 0 si no admite empate)'
    AFTER `puntosVictoria`;

-- Si en algún momento se llegó a aplicar sql/migracion_puntaje_liga.sql
-- (columnas en `deportes`), se copian los valores ya configurados a TODAS las
-- categorías de ese deporte para no perder la configuración, y después se
-- eliminan las columnas del deporte. Si nunca se aplicó, estos statements son
-- inofensivos (el UPDATE no encuentra nada y el DROP puede fallar: ignoralo).
UPDATE `categorias` c
  JOIN `deportes` d ON d.id_deporte = c.id_deporte
   SET c.sistema_definicion = d.sistema_definicion,
       c.puntosVictoria     = d.puntosVictoria,
       c.puntosEmpate       = d.puntosEmpate
 WHERE d.sistema_definicion = 'PUNTAJE';

ALTER TABLE `deportes`
  DROP COLUMN `sistema_definicion`,
  DROP COLUMN `puntosVictoria`,
  DROP COLUMN `puntosEmpate`;

-- Configuración manual por categoría (ejemplo):
--   UPDATE categorias SET sistema_definicion = 'PUNTAJE'
--    WHERE id_categoria = 12;
