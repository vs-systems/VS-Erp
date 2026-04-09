<?php
/**
 * AJAX Handler - Logistics Actions (Hardened)
 */
ob_start(); // Start output buffering
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once LIB_PATH . '/Database.php';
require_once MODULES_PATH . '/logistica/Logistics.php';

use Vsys\Modules\Logistica\Logistics;

try {

    $logistics = new Logistics();
    $action = $_POST['action'] ?? '';

    $response = ['success' => false, 'error' => 'Acción no encontrada'];

    switch ($action) {
        case 'update_phase':
            $quoteNumber = $_POST['quote_number'] ?? '';
            $phase = $_POST['phase'] ?? '';
            error_log("AJAX Logistics: update_phase requested for [$quoteNumber] to [$phase]");
            $success = $logistics->updateOrderPhase($quoteNumber, $phase);
            $response = ['success' => $success];
            break;

        case 'despachar':
            $logistics->logFreightCost([
                'quote_number' => $_POST['quote_number'],
                'dispatch_date' => date('Y-m-d'),
                'client_id' => 0,
                'packages_qty' => $_POST['packages_qty'],
                'freight_cost' => $_POST['freight_cost'],
                'transport_id' => $_POST['transport_id']
            ]);
            $success = $logistics->updateOrderPhase($_POST['quote_number'], 'En su transporte');
            $response = ['success' => $success];
            break;

        case 'create_remito':
            $remito = $logistics->createRemito($_POST['quote_number'], $_POST['transport_id']);
            $response = ['success' => (bool) $remito, 'remito_number' => $remito];
            break;

        case 'upload_guide':    // legacy
        case 'dispatch_order':
            $quoteNumber = $_POST['quote_number'] ?? '';
            $guide       = trim($_POST['dispatch_guide'] ?? '');
            $dispatchedBy= trim($_POST['dispatched_by'] ?? $_SESSION['full_name'] ?? 'Sistema');
            $dispatchFile = null;

            // Subir archivo si viene
            if (!empty($_FILES['dispatch_file']['name'])) {
                $uploadDir = __DIR__ . '/uploads/guides/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext      = pathinfo($_FILES['dispatch_file']['name'], PATHINFO_EXTENSION);
                $fileName = $quoteNumber . '_' . time() . '.' . $ext;
                $dest     = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['dispatch_file']['tmp_name'], $dest)) {
                    $dispatchFile = 'uploads/guides/' . $fileName;
                }
            }
            // Legacy: guide_photo
            if (!empty($_FILES['guide_photo']['name'])) {
                $uploadDir = __DIR__ . '/uploads/guides/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = $quoteNumber . '_' . time() . '_' . $_FILES['guide_photo']['name'];
                $dest = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['guide_photo']['tmp_name'], $dest)) {
                    $dispatchFile = 'uploads/guides/' . $fileName;
                }
            }

            // Actualizar quotation
            $db = Vsys\Lib\Database::getInstance();
            $db->prepare("
                UPDATE quotations
                SET dispatched_at   = NOW(),
                    dispatch_guide  = COALESCE(NULLIF(?, ''), dispatch_guide),
                    dispatch_file   = COALESCE(NULLIF(?, ''), dispatch_file),
                    dispatched_by   = ?
                WHERE quote_number  = ?
            ")->execute([$guide, $dispatchFile, $dispatchedBy, $quoteNumber]);

            $response = ['success' => true];
            break;
    }

} catch (Exception $e) {
    error_log("AJAX Logistics CRITICAL ERROR: " . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

// Clean up buffer and check for unexpected output
$leakedOutput = ob_get_clean();
if (!empty($leakedOutput)) {
    error_log("AJAX Logistics: Unexpected output detected: " . $leakedOutput);
}

// Return clean JSON
echo json_encode($response);
exit;





