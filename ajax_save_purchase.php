<?php
/**
 * AJAX Handler - Save Purchase
 */
header('Content-Type: application/json');

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/purchases/Purchases.php';

use Vsys\Modules\Purchases\Purchases;

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// Basic validation
if (empty($data['entity_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debe seleccionar un proveedor']);
    exit;
}

if (empty($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'La compra debe tener al menos un item']);
    exit;
}

try {
    $purchasesModule = new Purchases();
    $id = $purchasesModule->savePurchase($data);

    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'La base de datos rechazó³ la operació³n sin error especó­fico.']);
    }
} catch (Exception $e) {
    error_log("AJAX Save Purchase Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





