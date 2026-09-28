-- ============================================================
-- DESEMPATE en resultados de enfrentamiento (MARCADOR)
-- Correr UNA sola vez sobre la base existente.
--
-- Un partido puede terminar con marcador igualado (ej: 0-0). A veces el
-- empate es definitivo (fase de grupos) y otras se resolvió por penales,
-- prórroga, puntos de oro, etc. Estas columnas guardan CÓMO se resolvió
-- y QUÉ equipo quedó como ganador a los efectos de la clasificación,
-- sin alterar el marcador real del tiempo regular.
-- ============================================================

ALTER TABLE `resultados`
  ADD COLUMN `hubo_desempate` tinyint(1) NOT NULL DEFAULT 0
      COMMENT '1 = el partido terminó empatado en tiempo regular pero hubo desempate'
      AFTER `observaciones`,
  ADD COLUMN `desempate_metodo` enum('PENALES','PRORROGA','PUNTOS_DE_ORO','MUERTE_SUBITA','LANZAMIENTO_TIRLIBRE','OTRO')
      DEFAULT NULL
      COMMENT 'Cómo se quebró el empate (NULL = empate definitivo o no hubo empate)'
      AFTER `hubo_desempate`,
  ADD COLUMN `id_ute_ganador` int DEFAULT NULL
      COMMENT 'UTE que ganó el desempate (avanza en llaves); NULL si el empate fue definitivo'
      AFTER `desempate_metodo`,
  ADD KEY `fk_res_ganador_desempate` (`id_ute_ganador`),
  ADD CONSTRAINT `fk_res_ganador_desempate` FOREIGN KEY (`id_ute_ganador`)
      REFERENCES `utes` (`id_ute`) ON DELETE SET NULL ON UPDATE CASCADE;
