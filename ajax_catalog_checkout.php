<?php
/**
 * AJAX Handler for Catalog Checkout
 * Integrated with CRM (Leads) and Quotations
 */
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Lib\Database;
use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Modules\CRM\CRM;
use Vsys\Modules\Clientes\Client;

$db = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'Carrito vacío o datos inválidos']);
    exit;
}

try {
    $db->beginTransaction();

    $items = $input['items'];
    $clientInfo = $input['client'] ?? [];
    $catalogType = $input['catalog_type'] ?? 'publico';

    $clientId = null;
    $userId = $_SESSION['user_id'] ?? 1; // Default to Admin if guest

    // 1. Identify or Create Client/Lead
    if (isset($clientInfo['logged']) && $clientInfo['logged'] && isset($_SESSION['user_id'])) {
        // Logged in user: Find their entity_id if they are a client
        // If it's a seller logged in, they might be doing it for a generic/selected client
        // For now, if logged, assume session has some client context or it's an internal test
        // Actually, catalogo.php is for Gremio (Logged clients)
        // Let's assume $_SESSION['entity_id'] exists if they are a client login
        $clientId = $_SESSION['entity_id'] ?? null;

        if (!$clientId) {
            // Fallback: search for first client or use ID 1
            $clientId = $db->query("SELECT id FROM entities WHERE type='client' LIMIT 1")->fetchColumn() ?: 1;
        }
    } else {
        // Guest: Create or Find Entity as "Potencial Cliente" (Lead)
        $email = $clientInfo['email'] ?? '';
        $phone = $clientInfo['phone'] ?? '';
        $name = $clientInfo['name'] ?? 'Invitado Web';

        // Search by email or phone
        $stmt = $db->prepare("SELECT id FROM entities WHERE email = ? OR phone = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$email, $phone, $phone]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $clientId = $existing;
        } else {
            // Create new guest entity
            $clientModule = new Client();
            $newEntityData = [
                'id' => null,
                'type' => 'client',
                'tax_id' => 'LEAD_' . time(),
                'document_number' => '',
                'name' => $name,
                'fantasy_name' => '',
                'contact' => $name,
                'email' => $email,
                'phone' => $phone,
                'mobile' => $phone,
                'address' => '',
                'delivery_address' => '',
                'default_voucher' => 'Ninguno',
                'tax_category' => 'Consumidor Final',
                'is_enabled' => 1,
                'retention' => 0,
                'payment_condition' => 'Contado',
                'payment_method' => 'Efectivo',
                'seller_id' => null,
                'client_profile' => ucfirst($catalogType),
                'is_verified' => 0,
                'city' => '',
                'lat' => null,
                'lng' => null,
                'transport' => ''
            ];
            if ($clientModule->saveClient($newEntityData)) {
                $clientId = $db->lastInsertId();
            }
        }
    }

    if (!$clientId) {
        throw new Exception("No se pudo identificar un cliente para la cotización.");
    }

    // 2. Create Lead in CRM if guest
    if (!isset($clientInfo['logged'])) {
        $crm = new CRM();
        $crm->saveLead([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => 'Nuevo',
            'notes' => "Pedido automático desde Catálogo " . ucfirst($catalogType) . "."
        ]);
        $leadId = $db->lastInsertId();
    }

    // 3. Generate Quotation
    $cotizador = new Cotizador();
    $quoteNumber = $cotizador->generateQuoteNumber($clientId);

    // Fetch rate
    $stmtRate = $db->query("SELECT rate FROM exchange_rates WHERE currency_to = 'ARS' ORDER BY fetched_at DESC LIMIT 1");
    $rate = $stmtRate->fetchColumn() ?: 1450.00;

    // Prepare Invoice Items & Totals
    $quoteItems = [];
    $subtotalUsd = 0;

    foreach ($items as $item) {
        $priceFinalUsd = (float) $item['price_final_usd'];
        $qty = (int) $item['qty'];
        $ivaRate = 21.00; // Default, could try to get from item if present

        // Items in cart come with IVA included in some catalogs?
        // Let's assume prices in cart are already with IVA if it's public/web
        // For the quote table, we usually want subtotal (without IVA) and IVA separate
        $priceUsdNoIva = $priceFinalUsd / (1 + ($ivaRate / 100));

        $sub = $priceUsdNoIva * $qty;
        $subtotalUsd += $sub;

        $quoteItems[] = [
            'product_id' => $item['id'] ?? null, // Need to make sure ID is passed
            'sku' => $item['sku'],
            'description' => $item['description'],
            'quantity' => $qty,
            'unit_price_usd' => $priceUsdNoIva,
            'subtotal_usd' => $sub,
            'iva_rate' => $ivaRate
        ];

        // If product_id is missing, try to find it by SKU
        if (!$quoteItems[count($quoteItems) - 1]['product_id']) {
            $stmtP = $db->prepare("SELECT id FROM products WHERE sku = ?");
            $stmtP->execute([$item['sku']]);
            $pid = $stmtP->fetchColumn();
            $quoteItems[count($quoteItems) - 1]['product_id'] = $pid;
        }
    }

    $totalIva = $subtotalUsd * 0.21;
    $totalUsd = $subtotalUsd + $totalIva;
    $totalArs = $totalUsd * $rate;

    $quoteData = [
        'quote_number' => $quoteNumber,
        'version' => 1,
        'client_id' => $clientId,
        'user_id' => $userId,
        'payment_method' => 'cash',
        'with_iva' => 1,
        'exchange_rate_usd' => $rate,
        'subtotal_usd' => $subtotalUsd,
        'total_iva_21' => $totalIva,
        'subtotal_ars' => $subtotalUsd * $rate,
        'total_usd' => $totalUsd,
        'total_ars' => $totalArs,
        'valid_until' => date('Y-m-d', strtotime('+3 days')),
        'observations' => "Pedido desde Catálogo Online (" . ucfirst($catalogType) . ")",
        'items' => $quoteItems
    ];

    $saveResult = $cotizador->saveQuotation($quoteData);

    if ($saveResult) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'quote_number' => $quoteNumber,
            'client_id' => $clientId
        ]);
    } else {
        throw new Exception("Error al guardar la cotización.");
    }

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
