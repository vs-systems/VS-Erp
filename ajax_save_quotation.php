<?php
/**
 * VS System ERP - AJAX Save Quotation
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['items'])) {
        echo json_encode(['success' => false, 'error' => 'No data received']);
        exit;
    }

    $cot = new Cotizador();

    // Prepare data for save
    $isRetention = $input['is_retention'] ?? false;
    $isBank = $input['is_bank'] ?? false;

    $data = [
        'quote_number' => $input['quote_number'],
        'version' => $input['version'] ?? 1,
        'client_id' => $input['client_id'] ?? 1,
        'user_id' => 1,
        'payment_method' => $input['payment_method'],
        'with_iva' => $input['with_iva'] ? 1 : 0,
        'exchange_rate_usd' => $input['exchange_rate_usd'],
        'subtotal_usd' => $input['subtotal_usd'],
        'subtotal_ars' => $input['subtotal_usd'] * $input['exchange_rate_usd'],
        'total_usd' => $input['total_usd'],
        'total_ars' => $input['total_ars'],
        'valid_until' => date('Y-m-d', strtotime('+2 days')),
        'observations' => $input['observations'] ?? '',
        'items' => []
    ];

    foreach ($input['items'] as $item) {
        // Apply adjustments to unit price before saving to items table
        $adjustedPrice = $item['price'];
        if ($isRetention)
            $adjustedPrice *= 1.07;
        if ($isBank)
            $adjustedPrice *= 1.03;

        $data['items'][] = [
            'product_id' => $item['id'],
            'quantity' => $item['qty'],
            'unit_price_usd' => $adjustedPrice,
            'subtotal_usd' => $adjustedPrice * $item['qty'],
            'iva_rate' => $item['iva']
        ];
    }

    // Calculate IVA discrimination
    $totalIVA105 = 0;
    $totalIVA21 = 0;

    foreach ($data['items'] as $item) {
        if ($input['with_iva']) {
            $basePrice = $item['subtotal_usd'];
            $ivaRate = (float) $item['iva_rate'];

            $ivaAmount = $basePrice * ($ivaRate / 100);

            if (abs($ivaRate - 10.5) < 0.1) {
                $totalIVA105 += $ivaAmount;
            } elseif (abs($ivaRate - 21) < 0.1) {
                $totalIVA21 += $ivaAmount;
            }
        }
    }

    $data['total_iva_105'] = $totalIVA105;
    $data['total_iva_21'] = $totalIVA21;

    $result = $cot->saveQuotation($data);

    if ($result && is_array($result)) {
        // Build Public URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // Adjust path if in subfolder, assuming Vsys_ERP root for now or relative to current script
        $path = dirname($_SERVER['PHP_SELF']);
        $publicUrl = "$protocol://$host$path/ver_presupuesto.php?h=" . $result['hash'];

        echo json_encode([
            'success' => true,
            'id' => $result['id'],
            'public_url' => $publicUrl
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos (Execute falló)']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Excepción: ' . $e->getMessage()]);
}
?>