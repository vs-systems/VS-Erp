<?php
/**
 * AJAX Handler - Billing & Current Accounts
 */
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';
require_once __DIR__ . '/src/modules/billing/CurrentAccounts.php';

use Vsys\Modules\Billing\Billing;
use Vsys\Modules\Billing\CurrentAccounts;

try {
    $action = $_POST['action'] ?? '';
    $billing = new Billing();
    $currentAccounts = new CurrentAccounts();
    $response = ['success' => false, 'error' => 'Acción no válida'];

    switch ($action) {
        case 'create_invoice':
            // Simplify data extraction
            $data = [
                'client_id' => $_POST['client_id'],
                'quote_id' => $_POST['quote_id'] ?? null,
                'type' => $_POST['invoice_type'],
                'date' => $_POST['date'],
                'due_date' => $_POST['due_date'],
                'total_net' => $_POST['total_net'],
                'total_iva' => $_POST['total_iva'],
                'total_amount' => $_POST['total_amount'],
                'notes' => $_POST['notes'] ?? '',
                'items' => json_decode($_POST['items'], true)
            ];

            $response = $billing->createInvoice($data);
            break;

        case 'get_balance':
            $balance = $currentAccounts->getBalance($_POST['client_id']);
            $response = ['success' => true, 'balance' => $balance];
            break;

        case 'get_movements':
            $movements = $currentAccounts->getMovements($_POST['client_id']);
            $response = ['success' => true, 'movements' => $movements];
            break;

        case 'register_receipt':
            $receiptId = $currentAccounts->addMovement(
                $_POST['client_id'],
                'Recibo',
                null, // reference_id (future: receipt_id)
                $_POST['amount'],
                $_POST['notes']
                // Payment method handling to be expanded
            );
            $response = ['success' => true, 'receipt_id' => $receiptId];
            break;

        case 'register_provider_payment':
            require_once __DIR__ . '/src/modules/billing/ProviderAccounts.php';
            $providerAccounts = new \Vsys\Modules\Billing\ProviderAccounts();
            $pId = $providerAccounts->addMovement(
                $_POST['provider_id'],
                'Pago',
                null,
                $_POST['amount'],
                $_POST['notes']
            );
            $response = ['success' => true, 'movement_id' => $pId];
            break;
    }

} catch (Exception $e) {
    error_log("AJAX Billing Error: " . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

$leakedOutput = ob_get_clean();
if ($leakedOutput) {
    error_log("AJAX Billing Leaked Output: " . $leakedOutput);
}

echo json_encode($response);
exit;
