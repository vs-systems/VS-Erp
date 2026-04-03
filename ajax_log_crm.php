<?php
/**
 * AJAX Handler: Log CRM Interaction
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['entity_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $crm = new Vsys\Modules\Crm\CRM();
    $success = $crm->logInteraction(
        $input['entity_id'],
        $input['type'],
        $input['description'],
        $_SESSION['user_id'] ?? 1
    );

    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





