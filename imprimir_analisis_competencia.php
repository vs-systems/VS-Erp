<?php
/**
 * VS System ERP - Print Competitor Analysis
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Utils.php';
require_once __DIR__ . '/src/modules/analysis/OperationCompetitor.php';

use Vsys\Modules\Analysis\OperationCompetitor;
use Vsys\Lib\Utils;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$analyzer = new OperationCompetitor();
$data = $analyzer->getAnalysis($id);

if (!$data)
    die("Análisis no encontrado.");

function u($str)
{
    return Utils::cleanString($str);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de Competencia - <?php echo $data['analysis_number']; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            border-bottom: 2px solid #136dec;
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
            color: #136dec;
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
            margin-bottom: 25px;
        }

        .items-table th {
            background: #136dec;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .cheaper {
            color: #27ae60;
            font-weight: bold;
        }

        .expensive {
            color: #e74c3c;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
        }

        .charts-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .chart-box {
            flex: 1;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 15px;
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
            style="padding: 12px 24px; background: #136dec; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
            IMPRIMIR / GUARDAR PDF
        </button>
    </div>

    <table class="header-table">
        <tr>
            <td><img src="logo_v2.jpg" class="logo" style="width: 250px; height: auto;"></td>
            <td class="quote-info">
                <h1>INFORME DE COMPETENCIA</h1>
                <p style="margin: 2px 0;"><strong>ANÁLISIS Nº:</strong> <?php echo $data['analysis_number']; ?></p>
                <p style="margin: 2px 0;"><strong>BASADO EN:</strong> <?php echo $data['quote_number']; ?></p>
                <p style="margin: 2px 0;"><strong>FECHA:</strong>
                    <?php echo date('d/m/Y', strtotime($data['created_at'])); ?></p>
            </td>
        </tr>
    </table>

    <table class="entity-grid">
        <tr>
            <td class="entity-box">
                <strong>DE:</strong><br>
                VECINO SEGURO<br>CUIT: 20-25562186-7<br>TEL: <?php echo COMPANY_PHONE; ?><br>EMAIL:
                VECINOSEGURO0@GMAIL.COM
            </td>
            <td width="2%"></td>
            <td class="entity-box">
                <strong>PARA:</strong><br>
                <span style="font-size: 16px; font-weight: 900;"><?php echo u($data['client_name']); ?></span><br>
                <?php echo $data['tax_id'] ? "CUIT: " . $data['tax_id'] . "<br>" : ""; ?>
                <?php echo $data['address'] ? u($data['address']) . ", " . u($data['city']) . "<br>" : ""; ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>CANT.</th>
                <th>DESCRIPCIÓN</th>
                <th style="text-align: right;">UNITARIO VS (ARS)</th>
                <th style="text-align: right;">UNITARIO COMP (ARS)</th>
                <th style="text-align: right;">DIFERENCIA %</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $labels = [];
            $vsData = [];
            $compData = [];
            foreach ($data['items'] as $item):
                $diff = 0;
                if ($item['comp_unit_ars'] > 0) {
                    $diff = (($item['comp_unit_ars'] - $item['vs_unit_ars']) / $item['vs_unit_ars']) * 100;
                }
                $labels[] = $item['sku'];
                $vsData[] = $item['vs_unit_ars'];
                $compData[] = $item['comp_unit_ars'] ?: 0;
                ?>
                <tr>
                    <td><?php echo $item['qty']; ?></td>
                    <td>
                        <div style="font-weight: bold;"><?php echo u($item['sku']); ?></div>
                        <div style="font-size: 10px; color: #666;"><?php echo u($item['description']); ?></div>
                    </td>
                    <td style="text-align: right;"
                        class="<?php echo ($item['comp_unit_ars'] > 0 && $item['vs_unit_ars'] < $item['comp_unit_ars']) ? 'cheaper' : ''; ?>">
                        $ <?php echo number_format($item['vs_unit_ars'], 2, ',', '.'); ?>
                    </td>
                    <td style="text-align: right;"
                        class="<?php echo ($item['comp_unit_ars'] > 0 && $item['vs_unit_ars'] > $item['comp_unit_ars']) ? 'cheaper' : ''; ?>">
                        $ <?php echo number_format($item['comp_unit_ars'], 2, ',', '.'); ?>
                    </td>
                    <td
                        style="text-align: right; font-weight: bold; <?php echo ($diff > 0) ? 'color: #27ae60;' : 'color: #e74c3c;'; ?>">
                        <?php echo ($item['comp_unit_ars'] > 0) ? ($diff > 0 ? '+' : '') . number_format($diff, 1) . '%' : '-'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="charts-container">
        <div class="chart-box">
            <h3 style="margin-top: 0; font-size: 11px; text-align: center;">COMPARATIVA DE PRECIOS POR ITEM (ARS)</h3>
            <canvas id="printChart" height="150"></canvas>
        </div>
    </div>

    <div class="footer">
        <p><strong>INFORME DE POSICIONAMIENTO COMPETITIVO</strong></p>
        <p>Dólar Billete BNA Venta Considerado: $ <?php echo number_format($data['exchange_rate'], 2, ',', '.'); ?></p>
        <p>Este informe tiene carácter informativo para la toma de decisiones comerciales.</p>
        <p style="margin-top: 20px;">Gracias por elegir VS Sistemas</p>
    </div>

    <script>
        const ctx = document.getElementById('printChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    { label: 'Vecino Seguro', data: <?php echo json_encode($vsData); ?>, backgroundColor: '#136dec' },
                    { label: 'Competencia', data: <?php echo json_encode($compData); ?>, backgroundColor: '#f43f5e' }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { font: { size: 9 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                },
                plugins: { legend: { labels: { font: { size: 9, weight: 'bold' } } } }
            }
        });

        // Auto print after a small delay to ensure chart rendering
        if (window.location.search.includes('autoprint')) {
            setTimeout(() => window.print(), 800);
        }
    </script>
</body>

</html>