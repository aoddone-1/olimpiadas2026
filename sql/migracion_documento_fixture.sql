-- ============================================
-- Migración: columna `documento_fixture` en la tabla `deportes`
-- --------------------------------------------
-- El botón "Descargar fixture" de Fixture.php (y del panel del delegado) ya
-- NO genera un PDF dinámico: ahora muestra el PDF estático subido a
-- assets/docs/, cuyo nombre de archivo se guarda en esta columna
-- (ej: "fx_BASQUET_5X5_Y_3X3.pdf").
--
-- Ejecutar UNA VEZ antes de usar la nueva funcionalidad:
--   mysql -u usuario -p base_de_datos < sql/migracion_documento_fixture.sql
-- ============================================

ALTER TABLE `deportes`
  ADD COLUMN `documento_fixture` VARCHAR(200) NULL DEFAULT NULL
    COMMENT 'Nombre del archivo PDF del fixture (dentro de assets/docs/), ej: fx_BASQUET.pdf'
    AFTER `fiscales`;

-- Precarga con los PDFs que ya están en assets/docs/.
-- (Si algún deporte no existe, el UPDATE simplemente no afecta filas.)
UPDATE `deportes` SET `documento_fixture` = 'fx_BASQUET_5X5_Y_3X3.pdf' WHERE LOWER(`nombre_deporte`) LIKE '%basquet%';
UPDATE `deportes` SET `documento_fixture` = 'fx_BEACH_VOLEY.pdf'       WHERE LOWER(`nombre_deporte`) LIKE '%beach%';
UPDATE `deportes` SET `documento_fixture` = 'fx_BOCHAS.pdf'            WHERE LOWER(`nombre_deporte`) LIKE '%bochas%';
UPDATE `deportes` SET `documento_fixture` = 'fx_FUTBOL.pdf'            WHERE LOWER(`nombre_deporte`) LIKE '%futbol%' OR LOWER(`nombre_deporte`) LIKE '%fútbol%';
UPDATE `deportes` SET `documento_fixture` = 'fx_NEWCOM.pdf'            WHERE LOWER(`nombre_deporte`) LIKE '%newcom%';
UPDATE `deportes` SET `documento_fixture` = 'fx_PADEL.pdf'             WHERE LOWER(`nombre_deporte`) LIKE '%padel%';
UPDATE `deportes` SET `documento_fixture` = 'fx_POOL.pdf'              WHERE LOWER(`nombre_deporte`) LIKE '%pool%';
UPDATE `deportes` SET `documento_fixture` = 'fx_TEJO.pdf'              WHERE LOWER(`nombre_deporte`) LIKE '%tejo%';
UPDATE `deportes` SET `documento_fixture` = 'fx_TENIS.pdf'             WHERE LOWER(`nombre_deporte`) LIKE '%tenis%';
UPDATE `deportes` SET `documento_fixture` = 'fx_TRUCO.pdf'             WHERE LOWER(`nombre_deporte`) LIKE '%truco%';
-- Voley "puro" (sin la palabra beach): el LIKE '%voley%' también toma a
-- Beach Voley, pero como la fila de BEACH_VOLEY se ejecutó antes y esta
-- excluye explícitamente los nombres con "beach", no hay pisa.
UPDATE `deportes` SET `documento_fixture` = 'fx_VOLEY.pdf'             WHERE LOWER(`nombre_deporte`) LIKE '%voley%' AND LOWER(`nombre_deporte`) NOT LIKE '%beach%';
