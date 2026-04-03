<?php
/**
 * VS System ERP - Print Quotation
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Utils.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Lib\Utils;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cot = new Cotizador();
$quote = $cot->getQuotation($id);
$items = $cot->getQuotationItems($id);

if (!$quote)
    die("Presupuesto no encontrado.");

// Function shorthand for cleaning
function u($str)
{
    return Utils::cleanString($str);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Presupuesto
        <?php echo $quote['quote_number']; ?>
    </title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            text-transform: uppercase;
        }

        @page {
            size: A4;
            margin: 1cm;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #5d2fc1;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            width: 180px;
        }

        .quote-info {
            text-align: right;
        }

        .quote-info h1 {
            margin: 0;
            color: #5d2fc1;
            font-size: 24px;
        }

        .entity-grid {
            width: 100%;
            margin-bottom: 15px;
        }

        .entity-box {
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 8px;
            width: 45%;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th {
            background: #5d2fc1;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-right: 0;
        }

        .totals-table td {
            padding: 8px;
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 22px;
            color: #5d2fc1;
            border-top: 3px solid #5d2fc1;
        }

        .footer {
            margin-top: 50px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()"
            style="padding: 12px 24px; background: #5d2fc1; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
            IMPRIMIR / GUARDAR PDF
        </button>
    </div>

    <table class="header-table">
        <tr>
            <!-- Direct logo reference to ensure it prints -->
            <td><img src="logo_v2.jpg" class="logo" style="width: 250px; height: auto;"></td>
            <td class="quote-info">
                <h1>PRESUPUESTO</h1>
                <p style="margin: 2px 0;"><strong>Nº:</strong> <?php echo $quote['quote_number']; ?></p>
                <p style="margin: 2px 0;"><strong>Fecha:</strong>
                    <?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></p>
                <p style="margin: 2px 0;"><strong>Validez:</strong> 48 Horas</p>
            </td>
        </tr>
    </table>

    <table class="entity-grid">
        <tr>
            <td class="entity-box">
                <strong>DE:</strong><br>
                VECINO SEGURO<br>
                CUIT: 20-25562186-7<br>
                TEL: <?php echo COMPANY_PHONE; ?><br>
                EMAIL: VECINOSEGURO0@GMAIL.COM
            </td>
            <td width="2%"></td>
            <td class="entity-box">
                <strong>PARA:</strong><br>
                <span style="font-size: 16px; font-weight: 900;"><?php echo u($quote['client_name']); ?></span><br>
                <?php echo $quote['tax_id'] ? "CUIT: " . $quote['tax_id'] . "<br>" : ""; ?>
                <?php echo !empty($quote['transport']) ? "TRANSPORTE: " . u($quote['transport']) : ""; ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Cant.</th>
                <th>SKU</th>
                <th>Descripción</th>
                <th style="text-align: right;">Unit. USD</th>
                <th style="text-align: right;">Subtotal USD</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php echo $item['quantity']; ?>
                    </td>
                    <td>
                        <?php echo u($item['sku']); ?>
                    </td>
                    <td>
                        <div style="font-weight: bold; font-size: 14px;"><?php echo u($item['description']); ?></div>
                    </td>
                    <td style="text-align: right;">USD
                        <?php echo number_format($item['unit_price_usd'], 2); ?>
                    </td>
                    <td style="text-align: right;">USD
                        <?php echo number_format($item['subtotal_usd'], 2); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal USD:</td>
            <td><strong>$
                    <?php echo number_format($quote['subtotal_usd'], 2); ?>
                </strong></td>
        </tr>
        <?php if ($quote['with_iva']): ?>
            <tr>
                <td>IVA (21%):</td>
                <td><strong>$
                        <?php echo number_format($quote['total_usd'] - $quote['subtotal_usd'], 2); ?>
                    </strong></td>
            </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td>TOTAL USD:</td>
            <td>$
                <?php echo number_format($quote['total_usd'], 2); ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 11px; color: #777; padding-top: 10px;">
                Cotización BNA: $
                <?php echo number_format($quote['exchange_rate_usd'], 2); ?>
            </td>
        </tr>
        <tr style="font-size: 20px; color: #27ae60; font-weight: bold;">
            <td>TOTAL ARS:</td>
            <td>$
                <?php echo number_format($quote['total_ars'], 0, ',', '.'); ?>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p><strong>OBSERVACIONES:</strong></p>
        <p>Los precios en pesos están sujetos a cambios sin previo aviso según la cotización del dólar BNA Billete Venta
            del día de pago.</p>
        <p>Forma de pago:
            <?php echo $quote['payment_method'] == 'bank' ? 'Transferencia Bancaria' : 'Contado / Efectivo'; ?>
        </p>
        <p style="text-align: center; margin-top: 30px;">Gracias por confiar en VS Sistemas</p>
    </div>

    <script>
        // Auto print if requested via URL
        if (window.location.search.includes('autoprint')) {
            window.print();
        }
    </script>
</body>

</html>