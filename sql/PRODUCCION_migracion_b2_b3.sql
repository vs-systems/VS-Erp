-- ================================================================
-- VECINOS SEGUROS ERP — Script de Migración para PRODUCCIÓN
-- Incluye: Bloque 2 (tipo_cliente) + Bloque 3 (products ARS)
-- ¡EJECUTAR UNA SOLA VEZ sobre la base de producción!
-- Fecha generación: 2026-04-09
-- ================================================================

-- ── PRECAUCIÓN: hacer backup antes de continuar ──────────────────
-- mysqldump -u USUARIO -p NOMBRE_BASE > backup_vecinoseguro_$(date +%Y%m%d).sql


-- ================================================================
-- BLOQUE 2 — Tabla entities: campo tipo_cliente
-- ================================================================

ALTER TABLE `entities`
    ADD COLUMN IF NOT EXISTS `tipo_cliente`
        ENUM('partner','gremio','publico') NOT NULL DEFAULT 'publico'
        COMMENT 'Tipo de cliente para selección de lista de precios en catálogo'
        AFTER `client_profile`;

-- Migrar valores desde client_profile histórico
UPDATE `entities`
SET `tipo_cliente` = CASE
    WHEN LOWER(TRIM(`client_profile`)) IN ('gremio')  THEN 'gremio'
    WHEN LOWER(TRIM(`client_profile`)) IN ('partner') THEN 'partner'
    ELSE 'publico'
END
WHERE `type` = 'client';

-- Verificación B2
SELECT 'BLOQUE 2 OK — Distribución tipo_cliente:' AS info;
SELECT tipo_cliente, COUNT(*) AS total
FROM entities
WHERE type = 'client'
GROUP BY tipo_cliente;


-- ================================================================
-- BLOQUE 3 — Tabla products: columnas de precio ARS
-- ================================================================

-- 3a. Agregar price_partner si no existe
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_partner`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS lista Partner (importado desde CSV)'
        AFTER `unit_price_usd`;

-- 3b. Agregar price_gremio si no existe
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_gremio`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS lista Gremio (importado desde CSV)'
        AFTER `price_partner`;

-- 3c. Agregar price_pvp si no existe
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `price_pvp`
        DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Precio ARS público PVP (importado desde CSV)'
        AFTER `price_gremio`;

-- 3d. stock_current: agregar si no existe (MariaDB / MySQL 8+ soportan IF NOT EXISTS en ALTER)
--     Si tu versión es MySQL 5.7, reemplazá ADD COLUMN IF NOT EXISTS por ADD COLUMN
--     y verificá que no exista antes.
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `stock_current`
        INT NOT NULL DEFAULT 0
        COMMENT 'Stock físico actual del producto'
        AFTER `price_pvp`;

-- 3e. Las columnas legacy (unit_cost_usd, unit_price_usd, etc.) se mantienen sin cambios

-- Verificación B3
SELECT 'BLOQUE 3 OK — Columnas en products:' AS info;
DESCRIBE products;


-- ================================================================
-- RESUMEN FINAL
-- ================================================================
SELECT 'Migración completada correctamente.' AS resultado;
