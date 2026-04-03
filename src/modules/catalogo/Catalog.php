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
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY description ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProductById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function deleteProduct($id)
    {
        // First delete supplier prices
        $this->db->prepare("DELETE FROM supplier_prices WHERE product_id = ?")->execute([$id]);
        // Then delete the product
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }



    public function getCategoriesWithSubcategories()
    {
        // Get all distinct category + subcategory pairs
        $stmt = $this->db->query("SELECT DISTINCT category, subcategory FROM products WHERE category != '' ORDER BY category, subcategory");
        $rows = $stmt->fetchAll();

        $tree = [];
        foreach ($rows as $row) {
            $cat = $row['category'];
            $sub = $row['subcategory'];
            if (!isset($tree[$cat])) {
                $tree[$cat] = [];
            }
            if ($sub && !in_array($sub, $tree[$cat])) {
                $tree[$cat][] = $sub;
            }
        }
        return $tree;
    }

    public function getProviders()
    {
        $stmt = $this->db->prepare("SELECT id, name FROM entities WHERE type = 'provider' OR type = 'supplier' ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchProducts($query)
    {
        $sql = "SELECT *, 
                (CASE WHEN sku LIKE ? THEN 1 ELSE 2 END) as priority
                FROM products WHERE 
                sku LIKE ? OR 
                barcode LIKE ? OR 
                provider_code LIKE ? OR 
                description LIKE ? 
                ORDER BY priority ASC, description ASC
                LIMIT 50";
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$exactTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public function addProduct($data)
    {
        // 1. Insert or Update main product
        $sql = "INSERT INTO products (sku, barcode, image_url, provider_code, description, category, subcategory, unit_cost_usd, unit_price_usd, iva_rate, brand, has_serial_number, stock_current) 
                VALUES (:sku, :barcode, :image_url, :provider_code, :description, :category, :subcategory, :unit_cost_usd, :unit_price_usd, :iva_rate, :brand, :has_serial_number, :stock_current)
                ON DUPLICATE KEY UPDATE 
                barcode = VALUES(barcode),
                image_url = IF(VALUES(image_url) IS NOT NULL AND VALUES(image_url) != '', VALUES(image_url), image_url),
                description = VALUES(description),
                category = VALUES(category),
                subcategory = VALUES(subcategory),
                brand = VALUES(brand),
                iva_rate = VALUES(iva_rate),
                has_serial_number = VALUES(has_serial_number),
                stock_current = VALUES(stock_current)";

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([
            ':sku' => $data['sku'],
            ':barcode' => $data['barcode'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':provider_code' => $data['provider_code'] ?? null,
            ':description' => $data['description'],
            ':category' => $data['category'] ?? '',
            ':subcategory' => $data['subcategory'] ?? '',
            ':unit_cost_usd' => $data['unit_cost_usd'],
            ':unit_price_usd' => $data['unit_price_usd'] ?? ($data['unit_cost_usd'] * 1.4),
            ':iva_rate' => $data['iva_rate'] ?? 21.00,
            ':brand' => $data['brand'] ?? '',
            ':has_serial_number' => $data['has_serial_number'] ?? 0,
            ':stock_current' => $data['stock_current'] ?? 0
        ]);

        if (!$res)
            return false;

        // 2. Get the product ID
        $stmtId = $this->db->prepare("SELECT id FROM products WHERE sku = ?");
        $stmtId->execute([$data['sku']]);
        $productId = $stmtId->fetchColumn();

        // 3. Insert or Update supplier price
        if ($productId && isset($data['supplier_id']) && $data['supplier_id']) {
            $sqlSup = "INSERT INTO supplier_prices (product_id, supplier_id, cost_usd) 
                       VALUES (:p_id, :s_id, :cost)
                       ON DUPLICATE KEY UPDATE cost_usd = VALUES(cost_usd)";
            $stmtSup = $this->db->prepare($sqlSup);
            $stmtSup->execute([
                ':p_id' => $productId,
                ':s_id' => $data['supplier_id'],
                ':cost' => $data['unit_cost_usd']
            ]);

            // 4. Update the main product's unit_cost_usd to be the minimum of all suppliers
            $sqlMin = "UPDATE products p 
                       SET unit_cost_usd = (SELECT MIN(cost_usd) FROM supplier_prices WHERE product_id = p.id)
                       WHERE p.id = ?";
            $this->db->prepare($sqlMin)->execute([$productId]);
        }

        return $res;
    }

    public function getCategories()
    {
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function importProductsFromCsv($filePath, $defaultProviderId = null)
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        // Try to detect delimiter (semicolon or comma)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        // Skip header
        fgetcsv($handle, 1000, $delimiter);

        $imported = 0;
        $suppliers = [];

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if (count($data) < 4)
                continue; // Basic check (SKU, Desc, Brand, Cost)

            $sku = trim($data[0]);
            $description = trim($data[1]);
            $brand = trim($data[2]);
            $cost = floatval(str_replace(',', '.', $data[3]));
            $iva = isset($data[4]) ? floatval(str_replace(',', '.', $data[4])) : 21.00;
            $catName = $data[5] ?? '';
            $subcatName = $data[6] ?? '';
            $providerName = trim($data[7] ?? '');
            $stock = isset($data[8]) ? intval($data[8]) : 0;

            $supplierId = $defaultProviderId;
            if ($providerName) {
                if (!isset($suppliers[$providerName])) {
                    $stmt = $this->db->prepare("SELECT id FROM entities WHERE name = ? AND (type = 'provider' OR type = 'supplier')");
                    $stmt->execute([$providerName]);
                    $id = $stmt->fetchColumn();
                    if (!$id) {
                        $this->db->prepare("INSERT INTO entities (type, name, is_enabled) VALUES ('provider', ?, 1)")->execute([$providerName]);
                        $id = $this->db->lastInsertId();
                    }
                    $suppliers[$providerName] = $id;
                }
                $supplierId = $suppliers[$providerName];
            }

            $this->addProduct([
                'sku' => $sku,
                'description' => $description,
                'brand' => $brand,
                'unit_cost_usd' => $cost,
                'iva_rate' => $iva,
                'category' => $catName,
                'subcategory' => $subcatName,
                'supplier_id' => $supplierId,
                'stock_current' => $stock
            ]);

            $imported++;
        }
        fclose($handle);
        return $imported;
    }

    public function importEntitiesFromCsv($filePath, $type = 'client')
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return false;

        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        // Skip header
        fgetcsv($handle, 1000, $delimiter);

        $imported = 0;
        $clientModule = new \Vsys\Modules\Clientes\Client();

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if (count($data) < 1)
                continue;

            $name = trim($data[0] ?? '');
            if (!$name)
                continue;

            $fantasyName = trim($data[1] ?? '');
            $taxId = trim($data[2] ?? '');
            $docNum = trim($data[3] ?? '');
            $email = trim($data[4] ?? '');
            $phone = trim($data[5] ?? '');
            $mobile = trim($data[6] ?? '');
            $contact = trim($data[7] ?? '');
            $address = trim($data[8] ?? '');
            $delivery = trim($data[9] ?? '');

            $clientModule->saveClient([
                'type' => $type,
                'name' => $name,
                'fantasy_name' => $fantasyName,
                'tax_id' => $taxId,
                'document_number' => $docNum,
                'email' => $email,
                'phone' => $phone,
                'mobile' => $mobile,
                'contact' => $contact,
                'address' => $address,
                'delivery_address' => $delivery,
                'is_enabled' => 1,
                'tax_category' => ($type === 'client' ? 'Consumidor Final' : 'No Aplica'),
                'default_voucher' => 'Factura',
                'payment_condition' => 'Contado',
                'payment_method' => 'Transferencia',
                'is_transport' => 0 // Default for imports, can be changed later or detected by keyword
            ]);

            $imported++;
        }
        fclose($handle);
        return $imported;
    }
}
