<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$billing = new \Vsys\Modules\Billing\Billing();
if ($billing->updateStatus($id, $status)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error updating status']);
}
?>