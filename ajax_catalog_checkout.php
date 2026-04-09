<?php
/**
 * AJAX — Checkout del Catálogo Web (Bloque 8)
 * Guarda la consulta como cotización en el sistema antes de derivar a WhatsApp.
 *
 * Acepta precios en ARS (nuevos catálogos B4) y legacy en USD.
 * POST JSON: {
 *   items: [{ sku, description, image, price, iva, brand, qty }],
 *   dolar: float,          // tipo de cambio del día (para referencia)
 *   tipo_cliente: string,  // 'publico'|'gremio'|'partner'
 *   client: { name, email, phone, logged: bool }
 * }
 * Respuesta: { success: bool, quote_number: string, message: string }
 */
header('Content-Type: application/json; charset=utf-8');
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

$db    = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'Carrito vacío o datos inválidos.']);
    exit;
}

try {
    $db->beginTransaction();

    $items       = $input['items'];
    $clientInfo  = $input['client']      ?? [];
    $tipoCliente = $input['tipo_cliente'] ?? ($_SESSION['tipo_cliente'] ?? 'publico');
    $dolar       = (float)($input['dolar'] ?? 1425.00);
    $isLogged    = !empty($clientInfo['logged']) && isset($_SESSION['user_id']);
    $adminUserId = $_SESSION['user_id'] ?? 1;

    // ── 1. IDENTIFICAR CLIENTE ────────────────────────────────────
    $clientId = null;

    if ($isLogged) {
        // Cliente autenticado: usar entity_id de sesión
        $clientId = $_SESSION['entity_id'] ?? null;

        if (!$clientId) {
            // Buscar por usuario
            $stmt = $db->prepare(
                "SELECT entity_id FROM users WHERE id = ? AND entity_id IS NOT NULL"
            );
            $stmt->execute([$_SESSION['user_id']]);
            $clientId = $stmt->fetchColumn();
        }
    }

    // Si es invitado o no encontramos entity_id:
    if (!$clientId) {
        $email = trim($clientInfo['email'] ?? '');
        $phone = trim($clientInfo['phone'] ?? '');
        $name  = trim($clientInfo['name']  ?? 'Visitante Web');

        if ($email) {
            // Buscar por email
            $stmt = $db->prepare(
                "SELECT id FROM entities WHERE email = ? AND type = 'client' LIMIT 1"
            );
            $stmt->execute([$email]);
            $clientId = $stmt->fetchColumn();
        }

        if (!$clientId && $phone) {
            // Buscar por teléfono
            $stmt = $db->prepare(
                "SELECT id FROM entities WHERE (mobile = ? OR phone = ?) AND type = 'client' LIMIT 1"
            );
            $stmt->execute([$phone, $phone]);
            $clientId = $stmt->fetchColumn();
        }

        if (!$clientId) {
            // Crear entidad guest / lead
            $clientModule = new Client();
            $guestData = [
                'id'                => null,
                'type'              => 'client',
                'tax_id'            => 'WEB_' . time(),
                'document_number'   => '',
                'name'              => $name ?: 'Visitante Web',
                'fantasy_name'      => '',
                'contact'           => $name,
                'email'             => $email,
                'phone'             => $phone,
                'mobile'            => $phone,
                'address'           => '',
                'delivery_address'  => '',
                'default_voucher'   => 'Ninguno',
                'tax_category'      => 'Consumidor Final',
                'is_enabled'        => 1,
                'retention'         => 0,
                'payment_condition' => 'Contado',
                'payment_method'    => 'Efectivo',
                'seller_id'         => null,
                'client_profile'    => ucfirst($tipoCliente),
                'is_verified'       => 0,
                'is_transport'      => 0,
                'tipo_cliente'      => $tipoCliente,
                'city'              => '',
                'lat'               => null,
                'lng'               => null,
                'transport'         => null,
            ];

            if ($clientModule->saveClient($guestData)) {
                $clientId = $db->lastInsertId();
            }
        }

        // Registrar lead en CRM solo si tiene email
        if ($clientId && $email) {
            try {
                $crm = new CRM();
                $crm->saveLead([
                    'name'           => $name,
                    'contact_person' => $name,
                    'email'          => $email,
                    'phone'          => $phone,
                    'status'         => 'Nuevo',
                    'notes'          => "Consulta desde Catálogo Web ($tipoCliente). Fecha: " . date('d/m/Y H:i'),
                ]);
            } catch (\Exception $e) { /* CRM es opcional */ }
        }
    }

    if (!$clientId) {
        // Fallback absoluto: usar entidad genérica "Web"
        $clientId = $db->query(
            "SELECT id FROM entities WHERE type='client' ORDER BY id ASC LIMIT 1"
        )->fetchColumn() ?: 1;
    }

    // ── 2. PREPARAR ÍTEMS DE COTIZACIÓN ──────────────────────────
    // Los precios vienen en ARS sin IVA (nuevo catálogo B4).
    // La cotización los guarda en ARS y hace referencia al dolar del día.

    $quoteItems   = [];
    $subtotalArs  = 0.0;
    $totalIvaArs  = 0.0;

    foreach ($items as $item) {
        $priceArs = (float)($item['price'] ?? 0);    // ARS sin IVA
        $qty      = max(1, (int)($item['qty']  ?? 1));
        $ivaRate  = (float)($item['iva']   ?? 21);
        $sku      = $item['sku']         ?? '';
        $desc     = $item['description'] ?? $sku;

        $subArs = $priceArs * $qty;
        $ivaAmt = $subArs * ($ivaRate / 100);

        $subtotalArs += $subArs;
        $totalIvaArs += $ivaAmt;

        // Buscar product_id por SKU
        $productId = null;
        if ($sku) {
            $stmtP = $db->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
            $stmtP->execute([$sku]);
            $productId = $stmtP->fetchColumn() ?: null;
        }

        // Para compatibilidad con la tabla quotation_items que usa USD:
        // Calculamos precio USD de referencia usando el dolar del input
        $priceUsd = $dolar > 0 ? ($priceArs / $dolar) : 0;
        $subUsd   = $priceUsd * $qty;

        $quoteItems[] = [
            'product_id'    => $productId,
            'sku'           => $sku,
            'description'   => $desc,
            'quantity'      => $qty,
            'unit_price_usd' => round($priceUsd, 4),
            'subtotal_usd'  => round($subUsd, 4),
            'iva_rate'      => $ivaRate,
            // Campos extra ARS (para cotizador actualizado)
            'unit_price_ars' => $priceArs,
            'subtotal_ars'  => $subArs,
        ];
    }

    $totalArs = $subtotalArs + $totalIvaArs;
    $totalUsd = $dolar > 0 ? ($totalArs / $dolar) : 0;
    $subUsdTotal = $dolar > 0 ? ($subtotalArs / $dolar) : 0;

    // ── 3. GENERAR COTIZACIÓN ─────────────────────────────────────
    $cotizador   = new Cotizador();
    $quoteNumber = $cotizador->generateQuoteNumber($clientId);

    $originLabel = [
        'publico' => 'Catálogo Web — Público',
        'gremio'  => 'Catálogo Web — Gremio',
        'partner' => 'Catálogo Web — Partner',
    ][$tipoCliente] ?? 'Catálogo Web';

    $quoteData = [
        'quote_number'      => $quoteNumber,
        'version'           => 1,
        'client_id'         => $clientId,
        'user_id'           => $adminUserId,
        'payment_method'    => 'cash',
        'with_iva'          => 1,
        'exchange_rate_usd' => $dolar,
        'subtotal_usd'      => round($subUsdTotal, 2),
        'total_iva_21'      => round($totalIvaArs / ($dolar ?: 1), 2),
        'subtotal_ars'      => round($subtotalArs, 2),
        'total_usd'         => round($totalUsd, 2),
        'total_ars'         => round($totalArs, 2),
        'valid_until'       => date('Y-m-d', strtotime('+3 days')),
        'observations'      => "Consulta desde $originLabel. Precios en ARS. Dólar referencia: $" . number_format($dolar, 2, '.', ''),
        'items'             => $quoteItems,
    ];

    $saved = $cotizador->saveQuotation($quoteData);

    if (!$saved) {
        throw new \Exception('Error al guardar la cotización en la base de datos.');
    }

    $db->commit();

    echo json_encode([
        'success'      => true,
        'quote_number' => $quoteNumber,
        'total_ars'    => round($totalArs, 2),
        'total_usd'    => round($totalUsd, 2),
        'message'      => "Consulta #$quoteNumber registrada correctamente.",
    ]);

} catch (\Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    // No fallar el flujo del cliente por un error de DB — devolver success parcial
    echo json_encode([
        'success'      => true,     // El WA igual se abre
        'quote_number' => null,
        '_db_error'    => $e->getMessage(),
        'message'      => 'Consulta derivada por WhatsApp (no se pudo registrar en el sistema).',
    ]);
}
