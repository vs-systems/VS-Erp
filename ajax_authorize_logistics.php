<?php
/**
 * AJAX Handler - Authorize Logistics without payment
 */
header('Content-Type: application/json');
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    session_start();
    $db = Vsys\Lib\Database::getInstance();

    // Support JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $quotationId = $input['id'] ?? ($_POST['id'] ?? null);
    $authorizedBy = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');

    if (!$quotationId) {
        throw new Exception("ID de cotización es requerido.");
    }

    $stmt = $db->prepare("UPDATE quotations SET logistics_authorized_by = ?, logistics_authorized_at = NOW() WHERE id = ?");
    $stmt->execute([$authorizedBy, $quotationId]);

    // Also, if authorized, we should ensure it appears in logistics
    // The logistics automation usually triggers on 'Pagado' or 'is_confirmed'.
    // If we authorize it, we might want to manually poke the logistics_process table.
    $stmtQ = $db->prepare("SELECT quote_number FROM quotations WHERE id = ?");
    $stmtQ->execute([$quotationId]);
    $quoteNumber = $stmtQ->fetchColumn();

    if ($quoteNumber) {
        $db->prepare("INSERT INTO logistics_process (quote_number, current_phase) 
                     VALUES (?, 'En reserva') 
                     ON DUPLICATE KEY UPDATE updated_at = NOW()")
            ->execute([$quoteNumber]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
