<?php
/**
 * VS System ERP - AJAX Get Order Details
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
$quoteNumber = $_GET['quote_number'] ?? null;

if (!$id && !$quoteNumber) {
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos']);
    exit;
}

try {
    $cot = new \Vsys\Modules\Cotizador\Cotizador();
    $db = \Vsys\Lib\Database::getInstance();

    if (!$id && $quoteNumber) {
        $stmt = $db->prepare("SELECT id FROM quotations WHERE quote_number = ?");
        $stmt->execute([$quoteNumber]);
        $id = $stmt->fetchColumn();
    }

    if (!$id) {
        throw new Exception("Presupuesto no encontrado");
    }

    $quote = $cot->getQuotation($id);
    $items = $cot->getQuotationItems($id);

    echo json_encode([
        'success' => true,
        'quote' => $quote,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
