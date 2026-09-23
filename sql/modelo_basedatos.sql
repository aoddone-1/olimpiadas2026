CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `id_deporte` int NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `genero` enum('MASCULINO','FEMENINO','MIXTO') NOT NULL DEFAULT 'MIXTO',
  `cupo_maximo` int DEFAULT '0',
  `id_lugar` int DEFAULT NULL,
  `dia_competencia` date DEFAULT NULL,
  `hora_competencia` time DEFAULT NULL,
  `tipo_torneo` enum('ELIMINACION_DIRECTA','GRUPO_Y_ELIMINATORIA','TODOS_CONTRA_TODOS','JORNADA_UNICA') NOT NULL DEFAULT 'GRUPO_Y_ELIMINATORIA',
  PRIMARY KEY (`id_categoria`),
  KEY `fk_deporte_cat` (`id_deporte`),
  KEY `fk_lugar_cat` (`id_lugar`),
  CONSTRAINT `fk_deporte_cat` FOREIGN KEY (`id_deporte`) REFERENCES `deportes` (`id_deporte`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lugar_cat` FOREIGN KEY (`id_lugar`) REFERENCES `lugares` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=latin1;

CREATE TABLE `deportes` (
  `id_deporte` int NOT NULL AUTO_INCREMENT,
  `nombre_deporte` varchar(100) NOT NULL,
  `genero` enum('MASCULINO','FEMENINO','MIXTO') NOT NULL DEFAULT 'MIXTO',
  `modalidad` enum('INDIVIDUAL','EQUIPO','AMBAS') NOT NULL DEFAULT 'INDIVIDUAL',
  `tipo_duracion` enum('UNICO_DIA','MULTIDIA') NOT NULL DEFAULT 'MULTIDIA' COMMENT 'Indica si requiere 1 sola jornada concentrada o varios días de torneo',
  `modalidad_competencia` enum('ENFRENTAMIENTO','MASIVO_TIEMPO') NOT NULL DEFAULT 'ENFRENTAMIENTO' COMMENT 'ENFRENTAMIENTO = Partidos 1vs1 / Equipo vs Equipo. MASIVO_TIEMPO = Todos compiten juntos en una jornada/largada (Running, Ciclismo, Pesca)',
  PRIMARY KEY (`id_deporte`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;

CREATE TABLE `encuestas_deportes` (
  `id_respuesta` int NOT NULL,
  `id_deporte` int NOT NULL,
  PRIMARY KEY (`id_respuesta`,`id_deporte`),
  KEY `id_respuesta` (`id_respuesta`),
  KEY `id_deporte` (`id_deporte`),
  CONSTRAINT `encuestas_deportes_ibfk_1` FOREIGN KEY (`id_respuesta`) REFERENCES `encuestas_respuestas` (`id_respuesta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `encuestas_deportes_ibfk_2` FOREIGN KEY (`id_deporte`) REFERENCES `deportes` (`id_deporte`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `encuestas_respuestas` (
  `id_respuesta` int NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `delegacion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sexo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `creado_el` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_respuesta`),
  UNIQUE KEY `dni_UNIQUE` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fixtures` (
  `id_fixture` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int NOT NULL,
  `id_lugar` int NOT NULL,
  `id_ute_1` int DEFAULT NULL COMMENT 'NULL si el clasificado está pendiente (ej. Ganador Llave 1)',
  `id_ute_2` int DEFAULT NULL COMMENT 'NULL si está pendiente o si es un deporte masivo',
  `nombre_prueba` varchar(150) DEFAULT NULL COMMENT 'Ej: Largada General 10K, Tanda 1, Serie A',
  `fase` enum('GRUPO','16AVOS','OCTAVOS','CUARTOS','SEMIFINAL','TERCER_PUESTO','FINAL','JORNADA_UNICA') NOT NULL DEFAULT 'GRUPO',
  `numero_fecha` int NOT NULL DEFAULT '1' COMMENT 'Fecha 1, Fecha 2, etc.',
  `fecha_competencia` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('PROGRAMADO','EN_CURSO','FINALIZADO','SUSPENDIDO') NOT NULL DEFAULT 'PROGRAMADO',
  `resultado` text COMMENT 'JSON con los id_ute en orden de llegada (deportes MASIVO_TIEMPO). Ej: [3,1,5]',
  PRIMARY KEY (`id_fixture`),
  KEY `fk_fixture_categoria` (`id_categoria`),
  KEY `fk_fixture_lugar` (`id_lugar`),
  KEY `fk_fixture_ute1` (`id_ute_1`),
  KEY `fk_fixture_ute2` (`id_ute_2`),
  KEY `idx_lugar_horario` (`id_lugar`,`fecha_competencia`,`hora_inicio`),
  CONSTRAINT `fk_fixture_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fixture_lugar` FOREIGN KEY (`id_lugar`) REFERENCES `lugares` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fixture_ute1` FOREIGN KEY (`id_ute_1`) REFERENCES `utes` (`id_ute`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fixture_ute2` FOREIGN KEY (`id_ute_2`) REFERENCES `utes` (`id_ute`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=298 DEFAULT CHARSET=latin1;

CREATE TABLE `inscripciones_deportivas` (
  `id_inscripcion` int NOT NULL AUTO_INCREMENT,
  `id_participante` int NOT NULL,
  `id_categoria` int NOT NULL,
  `asistio` tinyint NOT NULL DEFAULT '0',
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tiene_ute` tinyint(1) DEFAULT '0',
  `necesita_ute` tinyint(1) DEFAULT '0',
  `id_ute` int DEFAULT NULL,
  `detalle_ute` text NOT NULL,
  PRIMARY KEY (`id_inscripcion`),
  KEY `fk_categoria` (`id_categoria`),
  KEY `fk_participante` (`id_participante`),
  KEY `fk_ute` (`id_ute`),
  CONSTRAINT `fk_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_participante` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ute` FOREIGN KEY (`id_ute`) REFERENCES `utes` (`id_ute`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1225 DEFAULT CHARSET=latin1;

CREATE TABLE `lugares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

CREATE TABLE `participantes` (
  `id_participante` int NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `delegacion` varchar(100) DEFAULT NULL,
  `sexo` enum('Masculino','Femenino','Otro') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `obra_social` varchar(100) DEFAULT NULL,
  `tipo_empleado` enum('Planta Permanente','Jubilado','Contratado','Pasante','Otros') DEFAULT NULL,
  `dieta_especial` varchar(255) DEFAULT NULL,
  `hotel_alojamiento` varchar(150) DEFAULT NULL,
  `contacto_emergencia` varchar(255) DEFAULT NULL,
  `es_competidor` tinyint(1) NOT NULL DEFAULT '1',
  `es_delegado` tinyint(1) NOT NULL DEFAULT '0',
  `token_qr` varchar(100) DEFAULT NULL,
  `kit_entregado` tinyint(1) DEFAULT '0',
  `fecha_inscripcion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_participante`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `token_qr` (`token_qr`)
) ENGINE=InnoDB AUTO_INCREMENT=392 DEFAULT CHARSET=latin1;

CREATE TABLE `participantes_utes` (
  `id_ute` int NOT NULL,
  `id_participante` int NOT NULL,
  `fecha_asociacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ute`,`id_participante`),
  KEY `fk_pu_participante` (`id_participante`),
  CONSTRAINT `fk_pu_participante` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pu_ute` FOREIGN KEY (`id_ute`) REFERENCES `utes` (`id_ute`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `resultado_detalle` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_resultado` int NOT NULL,
  `id_ute` int DEFAULT NULL COMMENT 'UTE/equipo real; negativo = inscripcion individual',
  `nombre_libre` varchar(150) DEFAULT NULL COMMENT 'Nombre si no es una UTE registrada',
  `posicion` int DEFAULT NULL COMMENT 'Posición final (1 = ganador), para TIEMPO',
  `tiempo` varchar(20) DEFAULT NULL COMMENT 'Tiempo formateado HH:MM:SS o MM:SS',
  `marcador_local` int DEFAULT NULL COMMENT 'Solo MARCADOR: goles/tantos del equipo 1',
  `marcador_visita` int DEFAULT NULL COMMENT 'Solo MARCADOR: goles/tantos del equipo 2',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_det_resultado` (`id_resultado`),
  CONSTRAINT `fk_det_resultado` FOREIGN KEY (`id_resultado`) REFERENCES `resultados` (`id_resultado`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','staff','mesa_control','seguridad_cenas','delegado') NOT NULL,
  `nombre_usuario` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

CREATE TABLE `resultados` (
  `id_resultado` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int NOT NULL COMMENT 'FK categorias: deporte/categoría del resultado',
  `id_fixture` int DEFAULT NULL COMMENT 'FK fixtures: partido/jornada opcional al que pertenece',
  `nombre_evento` varchar(150) NOT NULL COMMENT 'Ej: Final - Partido 1, 5K Masculino, Largada B...',
  `tipo_resultado` enum('MARCADOR','TIEMPO') NOT NULL DEFAULT 'MARCADOR' COMMENT 'MARCADOR = enfrentamiento (X vs Y), TIEMPO = posiciones y tiempos',
  `fecha_resultado` date DEFAULT NULL,
  `lugar` varchar(150) DEFAULT NULL COMMENT 'Texto libre o nombre del lugar',
  `observaciones` text,
  `creado_por` int DEFAULT NULL COMMENT 'id_usuario que cargó el resultado',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_resultado`),
  KEY `fk_res_categoria` (`id_categoria`),
  KEY `fk_res_fixture` (`id_fixture`),
  CONSTRAINT `fk_res_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_res_fixture` FOREIGN KEY (`id_fixture`) REFERENCES `fixtures` (`id_fixture`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `utes` (
  `id_ute` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int NOT NULL,
  `nombre_ute` varchar(150) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ute`),
  KEY `fk_ute_categoria` (`id_categoria`),
  CONSTRAINT `fk_ute_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=latin1;
