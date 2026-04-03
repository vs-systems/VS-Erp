<?php
/**
 * VS System ERP - Product Import Script
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/Database.php';

use Vsys\Lib\Database;

$csvFile = BASE_PATH . '/data/catalogo.csv';

if (!file_exists($csvFile)) {
    die("Error: catalogo.csv not found in data folder.\n");
}

$db = Database::getInstance();
$handle = fopen($csvFile, "r");

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
    $subCatName = trim($data[7]);

    // Simple category handling: Upsert category and get ID
    if (!isset($categories[$catName])) {
        $stmt = $db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->execute([$catName]);
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$catName]);
        $categories[$catName] = $stmt->fetchColumn();
    }
    $catId = $categories[$catName];

    // Insert or Update product
    $sql = "INSERT INTO products (sku, description, brand, category_id, unit_cost_usd, unit_price_usd, iva_rate) 
            VALUES (:sku, :description, :brand, :cat_id, :cost, :price, :iva)
            ON DUPLICATE KEY UPDATE 
            description = VALUES(description),
            brand = VALUES(brand),
            category_id = VALUES(category_id),
            unit_cost_usd = VALUES(unit_cost_usd),
            unit_price_usd = VALUES(unit_price_usd),
            iva_rate = VALUES(iva_rate)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'sku' => $sku,
        'description' => $description,
        'brand' => $brand,
        'cat_id' => $catId,
        'cost' => $cost,
        'price' => $price,
        'iva' => $iva
    ]);

    $imported++;
}

fclose($handle);
echo "Import finished. Total products processed: $imported\n";
?>

