-- ============================================================
-- BLOQUE 3 — Vecinos Seguros ERP
-- Migración tabla products: soporte de columnas nuevas
-- Formato CSV: SKU|DESCRIPCION|MARCA|PARTNER|GREMIO|PVP|IVA%|STOCK|CATEGORIA|SUBCATEGORIA
-- Ejecutar una sola vez
-- ============================================================

-- 1. Agregar columnas de precio directo (ARS)
ALTER TABLE `products`
    ADD COLUMN `price_partner` DECIMAL(12,2) DEFAULT NULL   COMMENT 'Precio Partner ARS (del CSV)'        AFTER `unit_price_usd`,
    ADD COLUMN `price_gremio`  DECIMAL(12,2) DEFAULT NULL   COMMENT 'Precio Gremio ARS (del CSV)'         AFTER `price_partner`,
    ADD COLUMN `price_pvp`     DECIMAL(12,2) DEFAULT NULL   COMMENT 'Precio PVP ARS (del CSV)'            AFTER `price_gremio`,
    ADD COLUMN `stock_current` INT           NOT NULL DEFAULT 0 COMMENT 'Stock físico actual'            
        -- Solo agrega si no existe; si ya existe, comentar esta línea
        ;

-- NOTA: Si stock_current ya existe en tu tabla, comentá esa línea de arriba
-- y descomentá la siguiente para solo asegurarte que tenga la columna:
-- ALTER TABLE `products` MODIFY `stock_current` INT NOT NULL DEFAULT 0;

-- 2. Verificar columnas existentes vs nuevas (no elimina columnas legacy)
--    Las columnas antiguas (unit_cost_usd, unit_price_usd, recargo_gremio, recargo_web)
--    se mantienen en desuso pero no se eliminan para no romper código existente.

-- 3. Verificación
DESCRIBE products;
