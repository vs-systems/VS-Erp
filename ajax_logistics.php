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

        case 'upload_guide':
            $quoteNumber = $_POST['quote_number'];
            if (!empty($_FILES['guide_photo']['name'])) {
                $uploadDir = __DIR__ . '/uploads/guides/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);
                $fileName = $quoteNumber . '_' . time() . '_' . $_FILES['guide_photo']['name'];
                $dest = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['guide_photo']['tmp_name'], $dest)) {
                    $logistics->attachDocument($quoteNumber, 'quotation', 'Shipping Guide', 'uploads/guides/' . $fileName, 'Guía de transporte subida.');
                    $logistics->updateOrderPhase($quoteNumber, 'Entregado');
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'Error al guardar el archivo.'];
                }
            } else {
                $response = ['success' => false, 'error' => 'No se recibió ningún archivo.'];
            }
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





