<?php
/**
 * VS System ERP - Price Analyzer & Visual Insights
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';
require_once __DIR__ . '/src/modules/analizador/PriceAnalyzer.php';

use Vsys\Modules\Analizador\PriceAnalyzer;

$analyzer = new PriceAnalyzer();
$products = [];
$stats = ['categories' => [], 'brands' => []];

try {
    $products = $analyzer->getProductsForAnalysis(20);
    $stats = $analyzer->getAnalyticsSummary();
} catch (Exception $e) {
    error_log("Analyzer Error: " . $e->getMessage());
}

// Prepare chart data
$catLabels = json_encode(array_column($stats['categories'], 'label'));
$catData = json_encode(array_column($stats['categories'], 'value'));

$brandLabels = json_encode(array_column($stats['brands'], 'label'));
$brandData = json_encode(array_column($stats['brands'], 'value'));
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analizador de Precios - VS System ERP</title>
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
                        "surface-dark": "#16202e",
                    },
                },
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .dark ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #233348;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }
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
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300 no-print">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">monitoring</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Analizador
                        Inteligente</h2>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="window.print()"
                        class="flex items-center gap-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                        <span class="material-symbols-outlined text-sm">print</span> IMPRIMIR INFORME
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Category Chart -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none">
                            <h3
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">pie_chart</span> Distribución por
                                Categorías
                            </h3>
                            <div class="h-64 flex items-center justify-center">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Brand Chart -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none">
                            <h3
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">bar_chart</span> Top 5 Marcas (Precio
                                Promedio USD)
                            </h3>
                            <div class="h-64 flex items-center justify-center">
                                <canvas id="brandChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Comparison Table -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none">
                        <div
                            class="p-6 border-b border-slate-100 dark:border-[#233348] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-slate-400">compare_arrows</span>
                                <h3
                                    class="font-bold text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest">
                                    Comparativa Detallada de Productos</h3>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4">SKU / Marca</th>
                                        <th class="px-6 py-4">Precio VS (USD)</th>
                                        <th class="px-6 py-4">Comparativa de Proveedores</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($products as $p):
                                        $mainCost = floatval($p['unit_cost_usd']);
                                        $suppliersCount = count($p['suppliers']);
                                        ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-5">
                                                <div class="font-bold text-sm dark:text-white text-slate-800">
                                                    <?php echo $p['sku']; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-medium">
                                                    <?php echo $p['brand']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="font-bold text-sm dark:text-white text-slate-800">$
                                                    <?php echo number_format($mainCost, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <?php if ($suppliersCount > 0): ?>
                                                    <div class="space-y-2">
                                                        <?php foreach ($p['suppliers'] as $s):
                                                            $sCost = floatval($s['cost_usd']);
                                                            $diff = (($mainCost - $sCost) / $sCost) * 100;
                                                            $isBetter = $diff <= 0;
                                                            ?>
                                                            <div
                                                                class="flex items-center justify-between text-[11px] border-b border-slate-100 dark:border-white/5 pb-1">
                                                                <span
                                                                    class="text-slate-500 font-medium"><?php echo $s['supplier_name']; ?></span>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="dark:text-white text-slate-800 font-bold">$<?php echo number_format($sCost, 2); ?></span>
                                                                    <span
                                                                        class="px-1.5 py-0.5 rounded font-bold <?php echo $isBetter ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                                        <?php echo ($diff > 0 ? '+' : '') . number_format($diff, 1); ?>%
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-[11px] text-slate-400 italic">Sin otros proveedores</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

        // Charts Initialization
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $catLabels; ?>,
                datasets: [{
                    data: <?php echo $catData; ?>,
                    backgroundColor: ['#136dec', '#3b82f6', '#4f46e5', '#6366f1', '#818cf8', '#a5b4fc'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: textColor, font: { family: 'Inter', size: 10 } }
                    }
                }
            }
        });

        const brandCtx = document.getElementById('brandChart').getContext('2d');
        new Chart(brandCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $brandLabels; ?>,
                datasets: [{
                    label: 'Precio Promedio USD',
                    data: <?php echo $brandData; ?>,
                    backgroundColor: '#136dec',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: { color: textColor, font: { family: 'Inter', size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor, font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>

</html>