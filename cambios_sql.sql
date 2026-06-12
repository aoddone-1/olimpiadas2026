ALTER TABLE `olimpico_sistema_deportivo`.`usuarios` 
CHANGE COLUMN `rol` `rol` ENUM('superadmin', 'admin', 'staff', 'mesa_control', 'seguridad_cenas') NOT NULL ;