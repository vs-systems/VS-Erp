<?php
/**
 * VS System ERP - Catalog Module
 */

namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;

class Catalog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllProducts()
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.description ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchProducts($query)
    {
        $sql = "SELECT * FROM products WHERE 
                sku LIKE ? OR 
                barcode LIKE ? OR 
                provider_code LIKE ? OR 
                description LIKE ? 
                LIMIT 20";
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public function addProduct($data)
    {
        $sql = "INSERT INTO products (sku, barcode, provider_code, description, category_id, unit_cost_usd, unit_price_usd, iva_rate, brand, has_serial_number, stock_current) 
                VALUES (:sku, :barcode, :provider_code, :description, :category_id, :unit_cost_usd, :unit_price_usd, :iva_rate, :brand, :has_serial_number, :stock_current)
                ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                brand = VALUES(brand),
                category_id = VALUES(category_id),
                unit_cost_usd = VALUES(unit_cost_usd),
                unit_price_usd = VALUES(unit_price_usd),
                iva_rate = VALUES(iva_rate)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function importProductsFromCsv($filePath)
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        // Skip header
        fgetcsv($handle, 1000, ";");

        $imported = 0;
        $categories = [];

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) < 7)
                continue;

            $sku = trim($data[0]);
            $description = trim($data[1]);
            $brand = trim($data[2]);
            $cost = floatval(str_replace(',', '.', $data[3]));
            $price = floatval(str_replace(',', '.', $data[4]));
            $iva = floatval($data[5]);
            $catName = trim($data[6]);

            // Category logic
            if (!isset($categories[$catName])) {
                $stmt = $this->db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
                $stmt->execute([$catName]);
                $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$catName]);
                $categories[$catName] = $stmt->fetchColumn();
            }
            $catId = $categories[$catName];

            $this->addProduct([
                'sku' => $sku,
                'barcode' => null,
                'provider_code' => null,
                'description' => $description,
                'category_id' => $catId,
                'unit_cost_usd' => $cost,
                'unit_price_usd' => $price,
                'iva_rate' => $iva,
                'brand' => $brand,
                'has_serial_number' => 0,
                'stock_current' => 0
            ]);

            $imported++;
        }

        fclose($handle);
        return $imported;
    }
}
?>

