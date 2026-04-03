<?php
/**
 * VS System ERP - Print Purchase Order
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/purchases/Purchases.php';

use Vsys\Modules\Purchases\Purchases;

// Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$purchasesModule = new Purchases();
$purchase = $purchasesModule->getPurchase($id);
$items = $purchasesModule->getPurchaseItems($id);

if (!$purchase)
    die("Orden de Compra no encontrada.");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Orden de Compra
        <?php echo $purchase['purchase_number']; ?>
    </title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 40px;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #5d2fc1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            width: 250px;
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
            margin-bottom: 30px;
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
            margin-bottom: 30px;
        }

        .items-table th {
            background: #5d2fc1;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .items-table td {
            padding: 12px;
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
            font-size: 18px;
            color: #5d2fc1;
            border-top: 2px solid #5d2fc1;
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
            style="padding: 10px 20px; background: #5d2fc1; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ IMPRIMIR / GUARDAR PDF
        </button>
    </div>

    <table class="header-table">
        <tr>
            <!-- Direct logo reference to ensure it prints -->
            <td><img src="logo_v2.jpg" class="logo" style="width: 250px; height: auto;"></td>
            <td class="quote-info">
                <h1>ORDEN DE COMPRA</h1>
                <p><strong>Nº:</strong>
                    <?php echo $purchase['purchase_number']; ?>
                </p>
                <p><strong>Fecha:</strong>
                    <?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?>
                </p>
                <p><strong>Estado:</strong>
                    <?php echo $purchase['status']; ?>
                </p>
            </td>
        </tr>
    </table>

    <table class="entity-grid">
        <tr>
            <td class="entity-box">
                <strong>De:</strong><br>
                Vecino Seguro<br>
                CUIT: 20-25562186-7<br>
                Email: vecinoseguro0@gmail.com
            </td>
            <td width="10%"></td>
            <td class="entity-box">
                <strong>Proveedor:</strong><br>
                <?php echo $purchase['supplier_name']; ?><br>
                <?php echo isset($purchase['tax_id']) && $purchase['tax_id'] ? "CUIT: " . $purchase['tax_id'] . "<br>" : ""; ?>
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
                        <?php echo $item['qty']; ?>
                    </td>
                    <td>
                        <?php echo $item['sku']; ?>
                    </td>
                    <td>
                        <?php echo $item['description']; ?>
                    </td>
                    <td style="text-align: right;">$
                        <?php echo number_format($item['unit_price_usd'], 2); ?>
                    </td>
                    <td style="text-align: right;">$
                        <?php echo number_format($item['qty'] * $item['unit_price_usd'], 2); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal USD:</td>
            <td><strong>$
                    <?php echo number_format($purchase['subtotal_usd'], 2); ?>
                </strong></td>
        </tr>
        <?php if ($purchase['total_usd'] > $purchase['subtotal_usd']): ?>
            <tr>
                <td>IVA:</td>
                <td><strong>$
                        <?php echo number_format($purchase['total_usd'] - $purchase['subtotal_usd'], 2); ?>
                    </strong></td>
            </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td>TOTAL USD:</td>
            <td>$
                <?php echo number_format($purchase['total_usd'], 2); ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 11px; color: #777; padding-top: 10px;">
                T.C.: $
                <?php echo number_format($purchase['exchange_rate_usd'], 2); ?>
            </td>
        </tr>
        <tr style="font-size: 20px; color: #27ae60; font-weight: bold;">
            <td>TOTAL ARS:</td>
            <td>$
                <?php echo number_format($purchase['total_ars'], 2, ',', '.'); ?>
            </td>
        </tr>
    </table>

    <div class="footer">
        <?php if (!empty($purchase['notes'])): ?>
            <p><strong>OBSERVACIONES:</strong></p>
            <p>
                <?php echo nl2br($purchase['notes']); ?>
            </p>
        <?php endif; ?>
    </div>

    <script>
        // Auto print if requested via URL
        if (window.location.search.includes('autoprint')) {
            window.print();
        }
    </script>
</body>

</html>