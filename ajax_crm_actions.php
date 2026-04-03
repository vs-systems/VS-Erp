<?php
/**
 * AJAX Handler - CRM Actions
 */
header('Content-Type: application/json');

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

// Support both JSON input and standard POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$crm = new CRM();

if (!$data || empty($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

try {
    switch ($data['action']) {
        case 'save_lead':
            $success = $crm->saveLead($data);
            echo json_encode(['success' => $success]);
            break;

        case 'log_interaction':
            $success = $crm->logInteraction(
                $data['entity_id'],
                $data['type'],
                $data['description'],
                $data['user_id'] ?? 1,
                $data['entity_type'] ?? 'entity'
            );
            echo json_encode(['success' => $success]);
            break;

        case 'move_lead':
            $success = $crm->moveLead($data['id'], $data['direction']);
            echo json_encode(['success' => $success]);
            break;

        case 'delete_lead':
            $success = $crm->deleteLead($data['id']);
            echo json_encode(['success' => $success]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $data['action']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





