<?php
/**
 * VS System ERP - AJAX Product Search
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$catalog = new Catalog();
$priceList = new \Vsys\Modules\Config\PriceList();
$results = $catalog->searchProducts($query);

// Inject calculated prices for each profile
$margins = $priceList->getMargins(); // Returns ['gremio' => 25, 'web' => 40, 'mostrador' => 55]

foreach ($results as &$r) {
    $cost = (float) ($r['unit_cost_usd'] ?? $r['cost_usd'] ?? 0);
    $r['cost_usd'] = $cost;

    $r['prices'] = [
        'Gremio' => round($cost * (1 + (($margins['gremio'] ?? 25) / 100)), 2),
        'Web' => round($cost * (1 + (($margins['web'] ?? 40) / 100)), 2),
        'Mostrador' => round($cost * (1 + (($margins['mostrador'] ?? 55) / 100)), 2)
    ];

    $r['prices_by_name'] = $r['prices']; // Consolidate
}

echo json_encode($results);
?>