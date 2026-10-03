-- ============================================================================
-- MIGRACIÓN: Formato "Mundial" (Fase de Grupos + Fase Eliminatoria)
-- Sistema de Inscripciones - Olimpiadas IPV La Pampa 2026
-- Fecha: 2026-10-04
--
-- Objetivo: reemplazar el fixture actual (que falla con equipos impares) por
-- un formato tipo Copa del Mundo:
--   1. Fase de grupos con letras (A, B, C, ...), todos contra todos.
--      Victoria = 3 pts, Empate = 1 pt, Derrota = 0 pts.
--   2. Clasifican los primeros (y opcionalmente mejores segundos) a la fase
--      eliminatoria.
--   3. Cruces pre-armados (tipo Mundial 2022): 8vos -> 4tos -> Semi -> Final,
--      más partido por el 3er puesto.
--
-- Este script está pensado para MySQL/MariaDB. Ejecutar como ROOT en la BD
-- de olimpiadas. Antes de aplicar: HACER BACKUP.
-- ============================================================================

START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 1. TABLA: grupos
--    Cada categoría con tipo_torneo = 'GRUPO_Y_ELIMINATORIA' tendrá N grupos
--    (letra A..Z). El tamaño de grupo se guarda por categoría y puede ser
--    distinto entre categorías (3, 4 o 5 equipos según cantidad de inscriptos).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo`        int NOT NULL AUTO_INCREMENT,
  `id_categoria`    int NOT NULL,
  `nombre_grupo`    varchar(5) NOT NULL COMMENT 'Letra del grupo: A, B, C... (se muestra como "Grupo A")',
  `orden_grupo`     int NOT NULL DEFAULT 1 COMMENT 'Posición del grupo dentro del sorteo (1-based). Determina su lugar en el bracket',
  PRIMARY KEY (`id_grupo`),
  UNIQUE KEY `uq_categoria_grupo` (`id_categoria`, `nombre_grupo`),
  KEY `fk_grupo_categoria` (`id_categoria`),
  CONSTRAINT `fk_grupo_categoria` FOREIGN KEY (`id_categoria`)
      REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Grupos de la fase de grupos (formato Mundial)';

-- ----------------------------------------------------------------------------
-- 2. TABLA: grupo_utes
--    Miembros de cada grupo. Un equipo (UTE) pertenece a UN solo grupo por
--    categoría.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grupo_utes` (
  `id_grupo`  int NOT NULL,
  `id_ute`    int NOT NULL,
  PRIMARY KEY (`id_grupo`, `id_ute`),
  KEY `fk_gu_ute` (`id_ute`),
  CONSTRAINT `fk_gu_grupo` FOREIGN KEY (`id_grupo`)
      REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_gu_ute` FOREIGN KEY (`id_ute`)
      REFERENCES `utes` (`id_ute`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Equipos miembros de cada grupo';

-- ----------------------------------------------------------------------------
-- 3. TABLA: posiciones_grupo (vista materializada / tabla de posición)
--    La tabla de posiciones NO se calcula con queries complejas en cada
--    pantalla: se recalcula al guardar cada resultado de fase GRUPO.
--    Criterios de ordenamiento (como el Mundial):
--      Pts > Diferencia de gol > GF > Enfrentamiento directo > Sorteo (pos_or)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posiciones_grupo` (
  `id_posicion`   int NOT NULL AUTO_INCREMENT,
  `id_grupo`      int NOT NULL,
  `id_ute`        int NOT NULL,
  `pj`            int NOT NULL DEFAULT 0 COMMENT 'Partidos jugados',
  `pg`            int NOT NULL DEFAULT 0 COMMENT 'Partidos ganados (3 pts)',
  `pe`            int NOT NULL DEFAULT 0 COMMENT 'Empates (1 pt)',
  `pp`            int NOT NULL DEFAULT 0 COMMENT 'Perdidos (0 pts)',
  `gf`            int NOT NULL DEFAULT 0 COMMENT 'Goles/tantos a favor',
  `gc`            int NOT NULL DEFAULT 0 COMMENT 'Goles/tantos en contra',
  `dg`            int GENERATED ALWAYS AS (`gf` - `gc`) STORED COMMENT 'Diferencia de gol',
  `pts`           int GENERATED ALWAYS AS ((`pg` * 3) + `pe`) STORED COMMENT 'Puntos: 3 por victoria, 1 por empate',
  `pos_grupo`     int DEFAULT NULL COMMENT 'Posición final dentro del grupo (1 = primero). Se setea al cerrar el grupo',
  `clasificado`   tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = avanza a fase eliminatoria',
  PRIMARY KEY (`id_posicion`),
  UNIQUE KEY `uq_grupo_ute` (`id_grupo`, `id_ute`),
  KEY `fk_pg_ute` (`id_ute`),
  CONSTRAINT `fk_pg_grupo` FOREIGN KEY (`id_grupo`)
      REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pg_ute` FOREIGN KEY (`id_ute`)
      REFERENCES `utes` (`id_ute`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tabla de posiciones por grupo (recalculada por Fixture_model al cargar resultados)';

-- ----------------------------------------------------------------------------
-- 4. ALTER fixtures: soporte de fase de grupos y bracket eliminatorio
-- ----------------------------------------------------------------------------
ALTER TABLE `fixtures`
  MODIFY COLUMN `fase` enum(
      'GRUPO',
      'FASE_DE_GRUPOS',       -- nueva: partidos de todos contra todos dentro del grupo
      '16AVOS',               -- con 32 equipos (poco probable acá, se mantiene)
      'OCTAVOS',              -- 16 equipos (8vos de final)
      'CUARTOS',              -- 8 equipos (4tos de final)
      'SEMIFINAL',            -- 4 equipos
      'TERCER_PUESTO',        -- perdedores de semifinales
      'FINAL',                -- definicion del campeonato
      'JORNADA_UNICA'
  ) NOT NULL DEFAULT 'GRUPO';

ALTER TABLE `fixtures`
  ADD COLUMN `id_grupo` int DEFAULT NULL
      COMMENT 'Solo fase FASE_DE_GRUPOS: grupo al que pertenece el partido' AFTER `fase`,
  ADD COLUMN `llave` int DEFAULT NULL
      COMMENT 'Número de llave del bracket (1..N). Para cruces: ganador(llave X) vs ganador(llave Y)' AFTER `numero_fecha`,
  ADD COLUMN `origen_1_tipo` enum('UTE','GRUPO_POS','LLAVE_GANADOR','LLAVE_PERDEDOR') DEFAULT NULL
      COMMENT 'Cómo se define el participante del slot 1' AFTER `id_ute_2`,
  ADD COLUMN `origen_1_valor` varchar(20) DEFAULT NULL
      COMMENT 'Referencia del slot 1: id_ute | "letra-pos" (ej: A-1) | numero_llave' AFTER `origen_1_tipo`,
  ADD COLUMN `origen_2_tipo` enum('UTE','GRUPO_POS','LLAVE_GANADOR','LLAVE_PERDEDOR') DEFAULT NULL
      COMMENT 'Cómo se define el participante del slot 2' AFTER `origen_1_valor`,
  ADD COLUMN `origen_2_valor` varchar(20) DEFAULT NULL
      COMMENT 'Referencia del slot 2: id_ute | "letra-pos" (ej: B-1) | numero_llave' AFTER `origen_2_tipo`,
  ADD KEY `fk_fixture_grupo` (`id_grupo`),
  ADD CONSTRAINT `fk_fixture_grupo` FOREIGN KEY (`id_grupo`)
      REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------------------------------------------------------------
-- 5. ALTER categorias: parámetros del formato Mundial
-- ----------------------------------------------------------------------------
ALTER TABLE `categorias`
  ADD COLUMN `equipos_por_grupo` int NOT NULL DEFAULT 4
      COMMENT 'Tamaño deseado de grupo (3, 4 o 5). El generador ajusta si no divide exacto' AFTER `tipo_torneo`,
  ADD COLUMN `clasificados_por_grupo` int NOT NULL DEFAULT 2
      COMMENT 'Cuántos avanzan por grupo (1 = solo el primero, 2 = primero y segundo)' AFTER `equipos_por_grupo`,
  ADD COLUMN `mejores_segundos` int NOT NULL DEFAULT 0
      COMMENT 'Cuántos mejores segundos adicionales clasifican (para completar potencias de 2 en el bracket)' AFTER `clasificados_por_grupo`;

-- ----------------------------------------------------------------------------
-- 6. Reglas de desempate entre "mejores segundos" (si aplica)
--    No hace falta tabla extra: se ordena por pts, dg, gf usando las mismas
--    columnas de posiciones_grupo filtrando pos_grupo = 2.
-- ----------------------------------------------------------------------------

COMMIT;

-- ============================================================================
-- RESUMEN DE FLUJO DEL TORNEO (para documentar en el modelo/vistas)
--
--  FASE 1: FASE_DE_GRUPOS
--    - Generador: toma las UTEs inscriptas, las reparte en grupos A.. con
--      `equipos_por_grupo` equipos. Si sobran equipos, se balancea
--      (ej: 14 equipos con 4/grupo -> 2 grupos de 4 y 1 grupo de 3... o
--      3 grupos: 5+5+4 segun config; NUNCA deja un grupo de 1).
--    - Partidos: round-robin simple (todos vs todos dentro del grupo).
--      Con grupo impar de equipos queda una fecha con "libre" por grupo.
--    - Puntaje: G=3, E=1, P=0. Empate en fase de grupos es EMPATE DEFINITIVO
--      (no hay penales ni prórroga).
--    - Al guardar cada resultado -> trigger lógico en Fixture_model:
--      recalcular fila de posiciones_grupo de ambos equipos.
--
--  CORTE: cuando todos los partidos GRUPO están FINALIZADO:
--    - Ordenar cada grupo por (pts DESC, dg DESC, gf DESC, desempate directo).
--    - Setear pos_grupo y clasificado=1 según `clasificados_por_grupo`.
--    - Si hacen falta mejores segundos: ranking global de segundos.
--
--  FASE 2: ELIMINATORIA (bracket pre-armado estilo Mundial)
--    - Cantidad de clasificados C se redondea a la siguiente potencia de 2
--      mediante "byes" en primera llave (solo si C no es potencia de 2).
--    - Llaves numeradas; cada partido tiene origen_1/2 = LLAVE_GANADOR(n) o
--      GRUPO_POS("A-1"). Al finalizar un partido, el modelo resuelve los
--      slots vacíos de la llave siguiente automáticamente.
--    - En eliminatoria NO puede quedar empate: si termina igual =>
--      desempate_metodo obligatorio (PENALES/PRORROGA/...) y
--      id_ute_ganador en resultados.
--    - Semifinales -> perdedores van a TERCER_PUESTO, ganadores a FINAL.
-- ============================================================================
