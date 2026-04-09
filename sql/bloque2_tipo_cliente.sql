-- ============================================================
-- BLOQUE 2 — Vecinos Seguros ERP
-- Agrega campo tipo_cliente a la tabla entities
-- Ejecutar una sola vez
-- ============================================================

ALTER TABLE `entities`
    ADD COLUMN `tipo_cliente` ENUM('partner', 'gremio', 'publico') NOT NULL DEFAULT 'publico'
    AFTER `client_profile`;

-- Migrar datos existentes segun client_profile histórico
UPDATE `entities`
SET `tipo_cliente` = CASE
    WHEN LOWER(`client_profile`) IN ('gremio')   THEN 'gremio'
    WHEN LOWER(`client_profile`) IN ('partner')  THEN 'partner'
    ELSE 'publico'
END
WHERE `type` = 'client';

-- Verificación
SELECT tipo_cliente, COUNT(*) AS total
FROM entities
WHERE type = 'client'
GROUP BY tipo_cliente;
