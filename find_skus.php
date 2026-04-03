<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

$catalog = new Vsys\Modules\Catalogo\Catalog();
$products = $catalog->getAllProducts();

echo "ID | SKU | BRAND | DESCRIPTION\n";
echo str_repeat("-", 50) . "\n";
foreach (array_slice($products, 0, 20) as $p) {
    echo $p['id'] . " | " . $p['sku'] . " | " . ($p['brand'] ?? 'N/A') . " | " . $p['description'] . "\n";
}





