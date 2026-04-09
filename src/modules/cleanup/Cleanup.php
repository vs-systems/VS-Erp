<?php
namespace Vsys\Modules\Cleanup;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class Cleanup
{
    private static function logAction($action, $details)
    {
        $logDir = __DIR__ . '/../../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/cleanup_' . date('Ymd') . '.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $user = $_SESSION['username'] ?? 'System';
        $line = "[" . date('Y-m-d H:i:s') . "] [$ip] [$user] [$action] $details\n";
        file_put_contents($logFile, $line, FILE_APPEND);
    }

    public static function cleanPriceLists()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            // Assuming price lists are in a specific table or configuration.
            // Currently config_prices.json is used for general config, but if there's a price_lists table:
            $stmt = $db->query("SHOW TABLES LIKE 'price_lists'");
            if ($stmt->rowCount() > 0) {
                $count = $db->exec("DELETE FROM price_lists");
                self::logAction('CLEAN_PRICE_LISTS', "Eliminadas $count filas de price_lists.");
            } else {
                self::logAction('CLEAN_PRICE_LISTS', "No se encontró la tabla price_lists. Accion ignorada.");
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_PRICE_LISTS_ERROR', $e->getMessage());
            throw $e;
        }
    }

    public static function cleanUsers()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $count = $db->exec("DELETE FROM users WHERE role != 'admin' AND role != 'Admin'");
            self::logAction('CLEAN_USERS', "Eliminados $count usuarios (no admins).");
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_USERS_ERROR', $e->getMessage());
            throw $e;
        }
    }

    public static function cleanCategories()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            // Si las categorias son entidades o tablas separadas
            $stmt = $db->query("SHOW TABLES LIKE 'categories'");
            if ($stmt->rowCount() > 0) {
                $count = $db->exec("DELETE FROM categories");
                self::logAction('CLEAN_CATEGORIES', "Eliminadas $count categorias.");
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_CATEGORIES_ERROR', $e->getMessage());
            throw $e;
        }
    }

    public static function cleanBrands()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $count = $db->exec("DELETE FROM brands");
            self::logAction('CLEAN_BRANDS', "Eliminadas $count marcas.");
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_BRANDS_ERROR', $e->getMessage());
            throw $e;
        }
    }

    public static function cleanAllProducts($backup = false)
    {
        $db = Database::getInstance();
        try {
            // Generar backup simple si se solicita
            if ($backup) {
                $products = $db->query("SELECT * FROM products")->fetchAll(\PDO::FETCH_ASSOC);
                $backupDir = __DIR__ . '/../../../backups';
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0777, true);
                }
                file_put_contents($backupDir . '/products_backup_' . date('Ymd_His') . '.json', json_encode($products));
            }

            $db->beginTransaction();

            // Borrar relaciones primero (supplier_prices, etc)
            $db->exec("DELETE FROM supplier_prices");

            // Borrar productos
            $count = $db->exec("DELETE FROM products");

            self::logAction('CLEAN_PRODUCTS', "Eliminados $count productos y sus dependencias de precios.");
            $db->commit();
            return $count;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_PRODUCTS_ERROR', $e->getMessage());
            throw $e;
        }
    }

    // Funciones adicionales de limpieza según entidades
    public static function cleanClients()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            // Delete child items first to avoid FK constraints
            try { $db->exec("DELETE FROM quotation_items WHERE quotation_id IN (SELECT id FROM quotations WHERE client_id IN (SELECT id FROM entities WHERE type='client'))"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM quotations WHERE client_id IN (SELECT id FROM entities WHERE type='client')"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM invoice_items WHERE invoice_id IN (SELECT id FROM invoices WHERE client_id IN (SELECT id FROM entities WHERE type='client'))"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM invoices WHERE client_id IN (SELECT id FROM entities WHERE type='client')"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM ctas_corrientes WHERE entity_id IN (SELECT id FROM entities WHERE type='client')"); } catch (\Exception $e) {}
            
            $count = $db->exec("DELETE FROM entities WHERE type='client'");
            self::logAction('CLEAN_CLIENTS', "Eliminados $count clientes y sus FK dependientes.");
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_CLIENTS_ERROR', $e->getMessage());
            throw $e;
        }
    }

    public static function cleanSuppliers()
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            try { $db->exec("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE entity_id IN (SELECT id FROM entities WHERE type='supplier' OR type='provider'))"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM purchases WHERE entity_id IN (SELECT id FROM entities WHERE type='supplier' OR type='provider')"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM supplier_prices WHERE supplier_id IN (SELECT id FROM entities WHERE type='supplier' OR type='provider')"); } catch (\Exception $e) {}
            try { $db->exec("DELETE FROM ctas_corrientes_proveedores WHERE entity_id IN (SELECT id FROM entities WHERE type='supplier' OR type='provider')"); } catch (\Exception $e) {}

            $count = $db->exec("DELETE FROM entities WHERE type='supplier' OR type='provider'");
            self::logAction('CLEAN_SUPPLIERS', "Eliminados $count proveedores y sus compras asociadas.");
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            self::logAction('CLEAN_SUPPLIERS_ERROR', $e->getMessage());
            throw $e;
        }
    }
}
