-- ============================================================
-- TABLA RESULTADOS (pestaña "Resultados" de Control Total)
-- Carga manual de resultados:
--   - Deportes de enfrentamiento (fútbol, básquet, vóley...): marcador (goles/tantos).
--   - Deportes masivos / de tiempo (running, ciclismo, natación...): posición + tiempo.
-- Ejecutar ESTE script una sola vez antes de usar la pestaña.
-- ============================================================

CREATE TABLE IF NOT EXISTS `resultados` (
  `id_resultado`   int(11) NOT NULL AUTO_INCREMENT,
  `id_categoria`   int(11) NOT NULL COMMENT 'FK categorias: deporte/categoría del resultado',
  `id_fixture`     int(11) DEFAULT NULL COMMENT 'FK fixtures: partido/jornada opcional al que pertenece',
  `nombre_evento`  varchar(150) NOT NULL COMMENT 'Ej: Final - Partido 1, 5K Masculino, Largada B...',
  `tipo_resultado` enum('MARCADOR','TIEMPO') NOT NULL DEFAULT 'MARCADOR' COMMENT 'MARCADOR = enfrentamiento (X vs Y), TIEMPO = posiciones y tiempos',
  `fecha_resultado` date DEFAULT NULL,
  `lugar`          varchar(150) DEFAULT NULL COMMENT 'Texto libre o nombre del lugar',
  `observaciones`  text DEFAULT NULL,
  `creado_por`     int(11) DEFAULT NULL COMMENT 'id_usuario que cargó el resultado',
  `creado_en`      timestamp NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_resultado`),
  KEY `fk_res_categoria` (`id_categoria`),
  KEY `fk_res_fixture` (`id_fixture`),
  CONSTRAINT `fk_res_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_res_fixture`  FOREIGN KEY (`id_fixture`)  REFERENCES `fixtures` (`id_fixture`)  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Detalle de cada resultado:
--   MARCADOR -> 2 filas: id_equipo_1 vs id_equipo_2 con marcador_local/marcador_visita.
--   TIEMPO   -> N filas: posicion + tiempo (cada competidor/UTE que llegó).
CREATE TABLE IF NOT EXISTS `resultado_detalle` (
  `id_detalle`       int(11) NOT NULL AUTO_INCREMENT,
  `id_resultado`     int(11) NOT NULL,
  `id_ute`           int(11) DEFAULT NULL COMMENT 'UTE/equipo real; negativo = inscripcion individual',
  `nombre_libre`     varchar(150) DEFAULT NULL COMMENT 'Nombre si no es una UTE registrada',
  `posicion`         int(11) DEFAULT NULL COMMENT 'Posición final (1 = ganador), para TIEMPO',
  `tiempo`           varchar(20) DEFAULT NULL COMMENT 'Tiempo formateado HH:MM:SS o MM:SS',
  `marcador_local`   int(11) DEFAULT NULL COMMENT 'Solo MARCADOR: goles/tantos del equipo 1',
  `marcador_visita`  int(11) DEFAULT NULL COMMENT 'Solo MARCADOR: goles/tantos del equipo 2',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_det_resultado` (`id_resultado`),
  CONSTRAINT `fk_det_resultado` FOREIGN KEY (`id_resultado`) REFERENCES `resultados` (`id_resultado`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
