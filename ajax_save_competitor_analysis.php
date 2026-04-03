<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/analysis/OperationCompetitor.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

try {
    $analyzer = new \Vsys\Modules\Analysis\OperationCompetitor();
    $id = $analyzer->saveAnalysis($data);

    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error saving analysis']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
