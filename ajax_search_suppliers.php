<?php
/**
 * VS System ERP - AJAX Supplier Search
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$client = new Vsys\Modules\Clientes\Client();
$results = $client->searchClients($query, 'all'); // Search all and filter

$finalResults = [];
foreach ($results as $r) {
    if (in_array($r['type'] ?? '', ['provider', 'supplier'])) {
        $finalResults[] = [
            'id' => $r['id'],
            'name' => $r['name'],
            'fantasy_name' => $r['fantasy_name'] ?? '',
            'tax_id' => $r['tax_id'] ?? '',
            'type' => $r['type']
        ];
    }
}

echo json_encode($finalResults);





