<?php
/**
 * AJAX Handler - Delete Purchase
 */
header('Content-Type: application/json');

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID no especificado']);
    exit;
}

try {
    $db = Vsys\Lib\Database::getInstance();
    $db->beginTransaction();

    // 1. Delete items
    $stmt1 = $db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt1->execute([$data['id']]);

    // 2. Delete purchase
    $stmt2 = $db->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt2->execute([$data['id']]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db))
        $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





