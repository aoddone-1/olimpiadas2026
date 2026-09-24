-- ============================================================
-- PREMIACIONES (sección "Premiación" del menú principal)
-- Registro diario de las entregas de premios: cada fila es un
-- puesto (1º/2º/3º) de una categoría cerrada con resultados.
-- Correr este script UNA VEZ antes de usar la sección.
-- ============================================================

CREATE TABLE IF NOT EXISTS `premiaciones` (
  `id_premiacion` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int NOT NULL COMMENT 'Categoría/deporte premiado',
  `puesto` tinyint NOT NULL COMMENT '1 = oro, 2 = plata, 3 = bronce',
  `id_ute` int DEFAULT NULL COMMENT 'UTE/equipo premiado (si existe registrado)',
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre del premiado (snapshot al momento de entregar)',
  `origen_resultado` enum('MARCADOR','TIEMPO') NOT NULL DEFAULT 'MARCADOR' COMMENT 'Cómo se definió el podio',
  `id_resultado_ref` int DEFAULT NULL COMMENT 'Resultado del que se tomó el podio',
  `fecha_entrega` date NOT NULL COMMENT 'Noche/ceremonia en la que se entrega',
  `entregado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = pendiente, 1 = entregado',
  `fecha_entregado` datetime DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_por` int DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_premiacion`),
  UNIQUE KEY `uq_cat_puesto` (`id_categoria`, `puesto`),
  KEY `idx_fecha_entrega` (`fecha_entrega`),
  CONSTRAINT `fk_prem_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Entrega diaria de premios (podios)';
