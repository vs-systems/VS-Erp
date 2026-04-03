<?php
/**
 * VS System ERP - AJAX Search Clients
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$clientMod = new Client();
$results = $clientMod->searchClients($query, 'client');

// Transform and add 'origin' field
$finalResults = [];
foreach ($results as $r) {
    $finalResults[] = [
        'id' => $r['id'],
        'name' => $r['name'],
        'type' => $r['type'] ?? 'client',
        'tax_id' => $r['tax_id'] ?? '',
        'address' => $r['address'] ?? '',
        'is_retention_agent' => $r['is_retention_agent'] ?? 0,
        'preferred_payment_method' => $r['preferred_payment_method'] ?? '',
        'client_profile' => $r['client_profile'] ?? 'Mostrador',
        'origin' => 'entity'
    ];
}

// Search Leads too
$db = Vsys\Lib\Database::getInstance();
$q = "%" . strtolower($query) . "%";
$leads = $db->prepare("SELECT id, name, tax_id, address FROM crm_leads WHERE LOWER(name) LIKE ? LIMIT 10");
$leads->execute([$q]);

// Helper to check if lead exists in clients
$existingClientNames = array_map(function ($c) {
    return strtolower($c['name']);
}, $results);
$existingClientTaxIds = array_map(function ($c) {
    return $c['tax_id'];
}, $results);

foreach ($leads->fetchAll() as $l) {
    // Deduplication Logic: Skip Lead if Name or Tax ID matches an existing Client
    if (in_array(strtolower($l['name']), $existingClientNames))
        continue;
    if (!empty($l['tax_id']) && in_array($l['tax_id'], $existingClientTaxIds))
        continue;

    $finalResults[] = [
        'id' => $l['id'],
        'name' => $l['name'],
        'type' => 'Lead',
        'tax_id' => $l['tax_id'] ?? '',
        'address' => $l['address'] ?? '',
        'is_retention_agent' => 0,
        'preferred_payment_method' => '',
        'client_profile' => 'Prospecto',
        'origin' => 'lead'
    ];
}

echo json_encode($finalResults);





