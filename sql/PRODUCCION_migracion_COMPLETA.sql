-- ================================================================
-- VECINOS SEGUROS ERP — Script de Migración COMPLETO para PRODUCCIÓN
-- Cubre: Bloques 2, 3, 5, 7, 8 (todos los cambios de schéma del proyecto)
-- ¡EJECUTAR UNA SOLA VEZ sobre la base de producción!
-- Generado: 2026-04-09
-- ================================================================

-- ── ANTES DE EJECUTAR: hacer backup ──────────────────────────────
-- mysqldump -u USUARIO -p vecinoseguro > backup_vecinoseguro_$(date +%Y%m%d_%H%M).sql

SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- BLOQUE 2 — entities: campo tipo_cliente
-- ================================================================

ALTER TABLE `entities`
    ADD COLUMN IF NOT EXISTS `tipo_cliente`
        ENUM('partner','gremio','publico') NOT NULL DEFAULT 'publico'
        COMMENT 'Tipo de cliente para lista de precios: partner | gremio | publico'
        AFTER `client_profile`;

-- Migrar desde client_profile histórico (idempotente)
UPDATE `entities`
SET `tipo_cliente` = CASE
    WHEN LOWER(TRIM(`client_profile`)) IN ('gremio','web')   THEN 'gremio'
    WHEN LOWER(TRIM(`client_profile`)) IN ('partner')        THEN 'partner'
    ELSE 'publico'
END
WHERE `type` = 'client'
  AND `tipo_cliente` = 'publico';   -- Solo actualizar los que están en default

SELECT 'B2 OK — tipo_cliente en entities.' AS info;
SELECT tipo_cliente, COUNT(*) AS total FROM entities WHERE type='client' GROUP BY tipo_cliente;


-- ================================================================
-- BLOQUE 3 — products: columnas de precio ARS + stock + IVA
-- ================================================================

-- Precio Partner (ARS sin IVA)
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_partner`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS lista Partner'
        AFTER `unit_price_usd`;

-- Precio Gremio
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_gremio`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS lista Gremio'
        AFTER `price_partner`;

-- PVP público
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_pvp`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS público (PVP)'
        AFTER `price_gremio`;

-- Stock físico actual
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `stock_current`
        INT NOT NULL DEFAULT 0
        COMMENT 'Stock físico disponible'
        AFTER `price_pvp`;

-- Alícuota de IVA del producto (default 21%)
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `iva_rate`
        DECIMAL(5,2) NOT NULL DEFAULT 21.00
        COMMENT 'Alícuota de IVA aplicable al producto (%)'
        AFTER `stock_current`;

SELECT 'B3 OK — columnas de precio ARS en products.' AS info;


-- ================================================================
-- BLOQUE 5-6 — users: columna entity_id + full_name
-- (ya deberían existir; ADD IF NOT EXISTS es seguro)
-- ================================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `entity_id`
        INT DEFAULT NULL
        COMMENT 'Entidad (cliente/proveedor) asociada al usuario'
        AFTER `role`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `full_name`
        VARCHAR(180) DEFAULT NULL
        COMMENT 'Nombre completo visible del usuario'
        AFTER `username`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `last_login`
        DATETIME DEFAULT NULL
        COMMENT 'Fecha y hora del último login'
        AFTER `status`;

-- Índice único sobre username si no existe
ALTER TABLE `users`
    ADD CONSTRAINT IF NOT EXISTS `uq_users_username` UNIQUE (`username`);

SELECT 'B5-6 OK — columnas en users verificadas.' AS info;


-- ================================================================
-- BLOQUE 7 — entities: campo is_verified
-- ================================================================

ALTER TABLE `entities`
    ADD COLUMN IF NOT EXISTS `is_verified`
        TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0=pendiente aprobación, 1=cuenta verificada/activa'
        AFTER `is_enabled`;

-- Marcar como verificados todos los clientes existentes pre-migración
UPDATE `entities`
SET `is_verified` = 1
WHERE `type` = 'client'
  AND `is_verified` = 0
  AND `id` IN (
      SELECT entity_id FROM users WHERE entity_id IS NOT NULL
  );

SELECT 'B7 OK — is_verified en entities.' AS info;
SELECT is_verified, COUNT(*) AS total FROM entities WHERE type='client' GROUP BY is_verified;


ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `reset_token`
        VARCHAR(128) DEFAULT NULL
        COMMENT 'Token para recuperación de contraseña (SHA-256, 64 chars)'
        AFTER `last_login`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `reset_token_expires`
        DATETIME DEFAULT NULL
        COMMENT 'Expiración del reset_token (1 hora desde generación)'
        AFTER `reset_token`;

SELECT 'B9 OK — columnas reset_token en users.' AS info;


-- ================================================================
-- BLOQUE 8 — quotations: columnas ARS para checkout web
-- ================================================================

-- Total en ARS en la cabecera de cotización
ALTER TABLE `quotations`
    ADD COLUMN IF NOT EXISTS `subtotal_ars`
        DECIMAL(14,2) DEFAULT NULL
        COMMENT 'Subtotal en ARS sin IVA'
        AFTER `subtotal_usd`;

ALTER TABLE `quotations`
    ADD COLUMN IF NOT EXISTS `total_ars`
        DECIMAL(14,2) DEFAULT NULL
        COMMENT 'Total en ARS con IVA'
        AFTER `total_usd`;

-- En quotation_items: precio unitario ARS
ALTER TABLE `quotation_items`
    ADD COLUMN IF NOT EXISTS `unit_price_ars`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio unitario en ARS (catálogo web)'
        AFTER `unit_price_usd`;

ALTER TABLE `quotation_items`
    ADD COLUMN IF NOT EXISTS `subtotal_ars`
        DECIMAL(14,2) DEFAULT NULL
        COMMENT 'Subtotal línea en ARS'
        AFTER `subtotal_usd`;

SELECT 'B8 OK — columnas ARS en quotations y quotation_items.' AS info;


-- ================================================================
-- BLOQUE 11 — quotations: nuevas columnas para logística y despachos
-- ================================================================

ALTER TABLE `quotations`
    ADD COLUMN IF NOT EXISTS `dispatch_guide` VARCHAR(120) DEFAULT NULL COMMENT 'Número de guía o remito',
    ADD COLUMN IF NOT EXISTS `dispatch_file` VARCHAR(255) DEFAULT NULL COMMENT 'Comprobante de despacho PDF/IMG',
    ADD COLUMN IF NOT EXISTS `dispatched_at` DATETIME DEFAULT NULL COMMENT 'Fecha en que se despachó',
    ADD COLUMN IF NOT EXISTS `dispatched_by` VARCHAR(100) DEFAULT NULL COMMENT 'Usuario responsable del despacho';

SELECT 'B11 OK — columnas logística en quotations.' AS info;


-- ================================================================
-- VERIFICACIÓN FINAL
-- ================================================================

SELECT 'DESCRIBE entities:' AS '---'; DESCRIBE entities;
SELECT 'DESCRIBE products:' AS '---'; DESCRIBE products;
SELECT 'DESCRIBE users:'    AS '---'; DESCRIBE users;
SELECT 'DESCRIBE quotations:' AS '---'; DESCRIBE quotations;
SELECT 'DESCRIBE quotation_items:' AS '---'; DESCRIBE quotation_items;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '================================================================' AS resultado;
SELECT 'Migración completa B2→B8 ejecutada correctamente.' AS resultado;
SELECT 'Vecinos Seguros ERP — lista la base de producción.' AS resultado;
SELECT '================================================================' AS resultado;
