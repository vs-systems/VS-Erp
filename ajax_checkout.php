<?php
/**
 * AJAX Checkout Processor
 * Receives JSON order data and creates a CRM Lead.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$customer = $input['customer'];
$cart = $input['cart'];
$total = $input['total'];

if (empty($cart) || empty($customer['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

try {
    $db = Database::getInstance();

    // 1. Format Order Details
    $orderDetails = "PEDIDO WEB\n";
    $orderDetails .= "Fecha: " . date('d/m/Y H:i') . "\n";
    $orderDetails .= "Cliente: " . $customer['name'] . "\n";
    $orderDetails .= "DNI/CUIT: " . $customer['dni'] . "\n";
    $orderDetails .= "------------- PRODUCTOS -------------\n";

    foreach ($cart as $item) {
        $orderDetails .= "- {$item['quantity']}x [{$item['sku']}] {$item['title']} ($ " . number_format($item['price'], 0, ',', '.') . ")\n";
    }

    $orderDetails .= "-------------------------------------\n";
    $orderDetails .= "TOTAL ESTIMADO: $ " . number_format($total, 0, ',', '.') . "\n";

    // 2. Format Contact Details JSON
    $contactJson = json_encode([
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'dni' => $customer['dni']
    ]);

    // 3. Insert into CRM Leads
    // Assuming table 'crm_leads' columns: name, contact_details, source, status, notes, created_at
    // Check if 'budget_value' exists? Yes, usually. We can put total there but it's in ARS. 
    // CRM usually expects USD? Let's check. 
    // If CRM is multi-currency, fine. If not, maybe just leave value 0 or put it in notes.
    // Safest is to put everything in notes for now to avoid messing up stats with mixed currencies if system is USD-based.

    $stmt = $db->prepare("INSERT INTO crm_leads (name, contact_details, source, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $customer['name'],
        $contactJson,
        'Web',
        'Nuevo', // Or 'Pendiente'
        $orderDetails
    ]);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}





