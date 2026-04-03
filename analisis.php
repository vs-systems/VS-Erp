<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Análisis de Operaciones
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';

use Vsys\Modules\Analysis\OperationAnalysis;
use Vsys\Lib\BCRAClient;

$analyzer = new OperationAnalysis();
$currency = new BCRAClient();
$bnaRate = $currency->getCurrentRate('oficial') ?? 1000.00;
$quotationId = $_GET['id'] ?? null;
$analysis = null;
if ($quotationId) {
    try {
        $analysis = $analyzer->getQuotationAnalysis($quotationId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Operación - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                    },
                },
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">analytics</span>
                    </div>
                    <div class="flex flex-col">
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight leading-none">
                            Análisis de Rentabilidad
                        </h2>
                        <span class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1">
                            <?php echo $quotationId ? '#' . $analysis['quote_number'] : 'Operaciones'; ?>
                        </span>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <?php if (!$quotationId): ?>
                    <!-- Search & Recent -->
                    <div class="max-w-4xl mx-auto space-y-8">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-12 text-center shadow-lg dark:shadow-none">
                            <div
                                class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6 text-primary">
                                <span class="material-symbols-outlined text-4xl">search_activity</span>
                            </div>
                            <h3 class="text-xl font-bold dark:text-white text-slate-800 mb-2">Analizar Rentabilidad</h3>
                            <p class="text-slate-500 mb-8 max-w-md mx-auto">Ingrese el ID de la cotización para ver métricas
                                detalladas de margen, costos y utilidad.</p>

                            <form method="GET" class="flex gap-2 max-w-sm mx-auto">
                                <input type="number" name="id" placeholder="ID Cotización"
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-center font-mono dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary">
                                <button type="submit"
                                    class="bg-primary hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
                                    <span class="material-symbols-outlined">search</span>
                                </button>
                            </form>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <h3
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                Últimas Operaciones
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[10px] uppercase tracking-widest text-slate-500 font-bold border-b border-slate-100 dark:border-[#233348]">
                                            <th class="pb-3 pl-4">ID</th>
                                            <th class="pb-3">Cliente</th>
                                            <th class="pb-3 text-right">Fecha</th>
                                            <th class="pb-3 text-right">Monto (USD)</th>
                                            <th class="pb-3 text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php
                                        $db = Vsys\Lib\Database::getInstance();
                                        $recentOps = $db->query("SELECT q.id, q.quote_number, q.created_at, q.subtotal_usd, e.name as client_name 
                                                                 FROM quotations q 
                                                                 JOIN entities e ON q.client_id = e.id 
                                                                 ORDER BY q.id DESC LIMIT 10")->fetchAll();
                                        foreach ($recentOps as $op):
                                            ?>
                                            <tr class="group hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                                <td
                                                    class="py-4 pl-4 font-mono text-xs font-bold dark:text-white group-hover:text-primary transition-colors">
                                                    #<?php echo $op['quote_number']; ?></td>
                                                <td class="py-4 text-sm font-medium dark:text-slate-300">
                                                    <?php echo $op['client_name']; ?>
                                                </td>
                                                <td class="py-4 text-right text-xs text-slate-500 font-mono">
                                                    <?php echo date('d/m/Y', strtotime($op['created_at'])); ?>
                                                </td>
                                                <td class="py-4 text-right font-mono font-bold dark:text-white">$
                                                    <?php echo number_format($op['subtotal_usd'], 2); ?>
                                                </td>
                                                <td class="py-4 text-center">
                                                    <a href="analisis.php?id=<?php echo $op['id']; ?>"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all">
                                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif (isset($error)): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <span class="font-bold"><?php echo $error; ?></span>
                    </div>
                <?php else: ?>

                    <!-- Header Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
                        <div
                            class="lg:col-span-8 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h1 class="text-2xl font-bold dark:text-white text-slate-800">
                                        #<?php echo $analysis['quote_number']; ?></h1>
                                    <p class="text-slate-500 text-sm mt-1">Cliente: <strong
                                            class="dark:text-slate-300"><?php echo $analysis['client_name']; ?></strong></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-widest text-slate-500 mb-1">Fecha Emisión</p>
                                    <p class="font-mono font-bold dark:text-white"><?php echo $analysis['date']; ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                    <?php echo $analysis['profit'] >= 0 ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                    <?php echo $analysis['profit'] >= 0 ? 'Rentable' : 'Pérdida'; ?>
                                </span>
                                <span
                                    class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">
                                    Margen: <?php echo number_format($analysis['margin_percent'], 2); ?>%
                                </span>
                            </div>
                        </div>

                        <!-- Main Metrics -->
                        <div class="lg:col-span-4 grid grid-cols-1 gap-4">
                            <!-- Revenue -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-5 relative overflow-hidden group">
                                <div
                                    class="absolute right-0 top-0 w-24 h-24 bg-blue-500/5 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Ingresos
                                    (Neto)</p>
                                <p class="text-3xl font-black text-blue-500 font-mono">
                                    $<?php echo number_format($analysis['total_revenue'], 2); ?></p>
                            </div>
                            <!-- Cost -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-5 relative overflow-hidden group">
                                <div
                                    class="absolute right-0 top-0 w-24 h-24 bg-red-500/5 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Costo (CMV)
                                </p>
                                <p class="text-3xl font-black text-red-400 font-mono">
                                    $<?php echo number_format($analysis['total_cost'], 2); ?></p>
                            </div>
                            <!-- Profit -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-5 relative overflow-hidden group">
                                <div
                                    class="absolute right-0 top-0 w-24 h-24 bg-green-500/5 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Utilidad
                                    Bruta</p>
                                <p
                                    class="text-3xl font-black <?php echo $analysis['profit'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-mono">
                                    $<?php echo number_format($analysis['profit'], 2); ?>
                                </p>
                                <p class="text-[10px] font-bold text-slate-400 mt-1 font-mono">
                                    ≈ ARS <?php echo number_format($analysis['profit'] * $bnaRate, 2, ',', '.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown & Chart -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Products Table -->
                        <div
                            class="lg:col-span-2 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm flex flex-col h-full">
                            <h3
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                Desglose de Productos
                            </h3>
                            <div class="overflow-x-auto flex-1">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[10px] uppercase tracking-widest text-slate-500 font-bold border-b border-slate-100 dark:border-[#233348]">
                                            <th class="pb-3">Producto</th>
                                            <th class="pb-3 text-right">Venta Unit</th>
                                            <th class="pb-3 text-right">Costo Unit</th>
                                            <th class="pb-3 text-right">Utilidad</th>
                                            <th class="pb-3 text-center">% Margen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($analysis['items'] as $item):
                                            $margin = ($item['unit_price'] > 0) ? (($item['unit_price'] - $item['unit_cost']) / $item['unit_price']) * 100 : 0;
                                            ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                                <td class="py-3 pr-4">
                                                    <div class="font-bold text-sm dark:text-white"><?php echo $item['sku']; ?>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 uppercase line-clamp-1">
                                                        <?php echo $item['description']; ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-right font-mono text-sm dark:text-slate-300">
                                                    $<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td class="py-3 text-right font-mono text-xs text-red-400">
                                                    $<?php echo number_format($item['unit_cost'], 2); ?>
                                                    <?php if (!empty($item['is_real_cost'])): ?>
                                                        <span
                                                            class="block text-[9px] text-green-500 font-bold uppercase tracking-tight"
                                                            title="Costo Real de última compra">Real (Compra)</span>
                                                    <?php else: ?>
                                                        <span
                                                            class="block text-[9px] text-slate-500 font-bold uppercase tracking-tight"
                                                            title="Costo de Lista">Est. (Lista)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-right font-mono text-sm font-bold text-green-500">
                                                    $<?php echo number_format(($item['unit_price'] - $item['unit_cost']) * $item['qty'], 2); ?>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                                        <?php echo $margin < 20 ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-500'; ?>">
                                                        <?php echo number_format($margin, 1); ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Chart & Summary -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm flex flex-col h-full">
                            <h3
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                Estructura de Margen
                            </h3>
                            <div class="relative h-64 mb-6">
                                <canvas id="marginChart"></canvas>
                            </div>

                            <div class="space-y-3 mt-auto">
                                <div
                                    class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-[#233348] pb-2">
                                    <span class="text-slate-500">Costo Mercadería</span>
                                    <span
                                        class="font-bold text-red-400"><?php echo number_format(($analysis['total_cost'] / $analysis['total_revenue']) * 100, 1); ?>%</span>
                                </div>
                                <div
                                    class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-[#233348] pb-2">
                                    <span class="text-slate-500">Impuestos (Est.)</span>
                                    <span
                                        class="font-bold text-amber-500"><?php echo number_format($analysis['taxes'], 2); ?>
                                        (3.5%)</span>
                                </div>
                                <div class="flex justify-between items-center bg-slate-50 dark:bg-[#101822] p-3 rounded-lg">
                                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Utilidad Neta
                                        Real</span>
                                    <span
                                        class="font-bold text-lg text-green-500 font-mono">$<?php echo number_format($analysis['profit'] - $analysis['taxes'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        const ctx = document.getElementById('marginChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Costo', 'Utilidad Neta', 'Impuestos'],
                                datasets: [{
                                    data: [
                                        <?php echo $analysis['total_cost']; ?>,
                                        <?php echo $analysis['profit'] - $analysis['taxes']; ?>,
                                        <?php echo $analysis['taxes']; ?>
                                    ],
                                    backgroundColor: [
                                        '#f87171', // Red 400
                                        '#22c55e', // Green 500
                                        '#f59e0b'  // Amber 500
                                    ],
                                    borderWidth: 0,
                                    hoverOffset: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            color: '#94a3b8',
                                            usePointStyle: true,
                                            padding: 20
                                        }
                                    }
                                }
                            }
                        });
                    </script>

                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>