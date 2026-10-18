-- =====================================================================
-- Migración: Agregar el rol 'mesa_control' a la tabla `usuarios`
-- La columna `rol` es un ENUM, por lo que hay que re-definirla.
-- Ejecutar: mysql -u usuario -p base_datos < migracion_rol_mesa_control.sql
-- =====================================================================

ALTER TABLE `usuarios`
  MODIFY COLUMN `rol` enum(
    'superadmin',
    'admin',
    'staff',
    'mesa_control',
    'carga_resultados',
    'solo_lectura',
    'delegado'
  ) NOT NULL;

-- Opcional: crear un usuario de mesa de control de ejemplo
-- (reemplaza 'PASSWORD_HASH_AQUI' por el hash real o usa texto plano si estás en desarrollo)
-- INSERT INTO `usuarios` (`username`, `password`, `rol`, `nombre_usuario`)
-- VALUES ('mesa01', 'PASSWORD_HASH_AQUI', 'mesa_control', 'Mesa de Control 1');
