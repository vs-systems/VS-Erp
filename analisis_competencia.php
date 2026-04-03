<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/analysis/OperationCompetitor.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';

use Vsys\Modules\Analysis\OperationCompetitor;
use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Lib\BCRAClient;

$analyzer = new OperationCompetitor();
$cot = new Cotizador();
$currency = new BCRAClient();

$quotationId = $_GET['quote_id'] ?? null;
$analysisId = $_GET['id'] ?? null;

$data = null;
$headerTitle = "Nuevo Análisis de Competencia";
$analysisNumber = "NUEVO";

if ($analysisId) {
    $data = $analyzer->getAnalysis($analysisId);
    if ($data) {
        $headerTitle = "Análisis de Competencia";
        $analysisNumber = $data['analysis_number'];
        $bnaRate = $data['exchange_rate'];
    }
} elseif ($quotationId) {
    $quote = $cot->getQuotation($quotationId);
    if ($quote) {
        $items = $cot->getQuotationItems($quotationId);
        $bnaRate = $quote['exchange_rate_usd'] ?: ($currency->getCurrentRate('oficial') ?? 1000.00);

        $data = [
            'quote_id' => $quotationId,
            'client_id' => $quote['client_id'],
            'client_name' => $quote['client_name'],
            'exchange_rate' => $bnaRate,
            'items' => []
        ];

        foreach ($items as $it) {
            $data['items'][] = [
                'product_id' => $it['product_id'],
                'sku' => $it['sku'],
                'description' => $it['description'],
                'qty' => $it['quantity'],
                'vs_unit_usd' => $it['unit_price_usd'],
                'vs_unit_ars' => $it['unit_price_usd'] * $bnaRate,
                'comp_unit_ars' => 0
            ];
        }
    }
}

$bnaRate = $bnaRate ?? ($currency->getCurrentRate('oficial') ?? 1000.00);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analizador de Competencia - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            text-transform: uppercase !important;
        }

        .normal-case {
            text-transform: none !important;
        }

        input {
            text-transform: none !important;
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
                class="h-20 flex items-center justify-between px-8 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">compare_arrows</span>
                    </div>
                    <div>
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight leading-none">
                            <?php echo $headerTitle; ?>
                        </h2>
                        <span class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">
                            ID:
                            <?php echo $analysisNumber; ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($analysisId): ?>
                        <a href="imprimir_analisis_competencia.php?id=<?php echo $analysisId; ?>" target="_blank"
                            class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20">
                            <span class="material-symbols-outlined text-sm text-[16px]">picture_as_pdf</span>
                            INFORME PDF
                        </a>
                    <?php endif; ?>
                    <button onclick="saveAnalysis()"
                        class="bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm text-[16px]">save</span>
                        GUARDAR ANÁLISIS
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
                <?php if (!$data): ?>
                    <div class="max-w-4xl mx-auto py-20 text-center">
                        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">find_in_page</span>
                        <h3 class="text-xl font-bold">No se seleccionó una cotización</h3>
                        <p class="text-slate-500 mt-2">Inicie el análisis desde el historial de presupuestos.</p>
                        <a href="presupuestos.php"
                            class="inline-block mt-6 bg-primary text-white px-6 py-3 rounded-xl font-bold">VOLVER AL
                            LISTADO</a>
                    </div>
                <?php else: ?>
                    <div class="max-w-[1600px] mx-auto space-y-8">

                        <!-- Client & Options Row -->
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                            <div
                                class="lg:col-span-3 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-3xl shadow-sm">
                                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Datos del
                                    Cliente</h3>
                                <div class="flex gap-8">
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">Cliente</p>
                                        <p class="text-lg font-black dark:text-white">
                                            <?php echo $data['client_name']; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">Referencia</p>
                                        <p class="text-lg font-black text-primary">
                                            <?php echo $data['quote_number'] ?? 'NUEVO'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-3xl shadow-sm">
                                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Cotización
                                    Dólar</h3>
                                <input type="number" step="0.01" id="exchange_rate" value="<?php echo $bnaRate; ?>"
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-none rounded-xl px-4 py-3 font-black text-lg text-primary focus:ring-2 focus:ring-primary text-center"
                                    onchange="updateAllARS()">
                            </div>
                        </div>

                        <!-- Main Comparison Table -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-[2.5rem] overflow-hidden shadow-2xl">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead
                                        class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                                        <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                            <th class="px-8 py-6">Producto / Item</th>
                                            <th class="px-8 py-6 text-center">Cant</th>
                                            <th class="px-8 py-6 text-right">Unitario USD (VS)</th>
                                            <th class="px-8 py-6 text-right">Unitario ARS (VS)</th>
                                            <th class="px-8 py-6 text-right bg-primary/5">Precio Competencia (ARS)</th>
                                            <th class="px-8 py-6 text-center">Diferencia %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-body" class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php foreach ($data['items'] as $idx => $item): ?>
                                            <tr class="item-row group" data-idx="<?php echo $idx; ?>">
                                                <input type="hidden" class="product-id"
                                                    value="<?php echo $item['product_id']; ?>">
                                                <input type="hidden" class="sku" value="<?php echo $item['sku']; ?>">
                                                <input type="hidden" class="description"
                                                    value="<?php echo htmlspecialchars($item['description']); ?>">

                                                <td class="px-8 py-6">
                                                    <div class="font-black text-sm dark:text-white">
                                                        <?php echo $item['sku']; ?>
                                                    </div>
                                                    <div class="text-[9px] text-slate-500 font-bold normal-case">
                                                        <?php echo $item['description']; ?>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-6 text-center font-bold qty">
                                                    <?php echo $item['qty']; ?>
                                                </td>
                                                <td class="px-8 py-6 text-right font-mono font-bold vs-usd">
                                                    $
                                                    <?php echo number_format($item['vs_unit_usd'], 2); ?>
                                                </td>
                                                <td class="px-8 py-6 text-right">
                                                    <input type="number" step="0.01"
                                                        class="vs-ars bg-slate-50 dark:bg-white/5 border-none rounded-lg px-3 py-2 w-32 text-right font-mono font-bold text-xs"
                                                        value="<?php echo round($item['vs_unit_ars'], 2); ?>"
                                                        onchange="recalcRow(this)">
                                                </td>
                                                <td class="px-8 py-6 text-right bg-primary/5">
                                                    <input type="number" step="0.01"
                                                        class="comp-ars bg-white dark:bg-[#101822] border-2 border-primary/20 rounded-lg px-3 py-2 w-32 text-right font-mono font-black text-xs text-primary focus:border-primary outline-none"
                                                        value="<?php echo $item['comp_unit_ars'] ?: ''; ?>" placeholder="0.00"
                                                        onchange="recalcRow(this)">
                                                </td>
                                                <td class="px-8 py-6 text-center diff-cell">
                                                    <span
                                                        class="diff-val px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest hidden">-</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div
                                class="bg-white dark:bg-[#16202e] p-8 rounded-[2.5rem] border border-slate-200 dark:border-[#233348] shadow-sm">
                                <h3
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-8 border-b border-slate-100 dark:border-[#233348] pb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">bar_chart</span> Comparativa de
                                    Precios ARS
                                </h3>
                                <div class="h-80">
                                    <canvas id="compChart"></canvas>
                                </div>
                            </div>
                            <div
                                class="bg-white dark:bg-[#16202e] p-8 rounded-[2.5rem] border border-slate-200 dark:border-[#233348] shadow-sm flex flex-col">
                                <h3
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-8 border-b border-slate-100 dark:border-[#233348] pb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">analytics</span> Resumen de Ventaja
                                    Competitiva
                                </h3>
                                <div id="summary-content" class="flex-1 flex flex-col justify-center space-y-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div class="bg-slate-50 dark:bg-[#101822] p-5 rounded-3xl text-center">
                                            <p class="text-[9px] font-bold text-slate-500 uppercase mb-1">Items más Baratos
                                            </p>
                                            <p id="cheaper-count" class="text-3xl font-black text-emerald-500">0</p>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-[#101822] p-5 rounded-3xl text-center">
                                            <p class="text-[9px] font-bold text-slate-500 uppercase mb-1">Diferencia
                                                Promedio</p>
                                            <p id="avg-diff" class="text-3xl font-black text-slate-400">0%</p>
                                        </div>
                                    </div>
                                    <div id="competitive-status"
                                        class="bg-primary/10 border border-primary/20 p-6 rounded-3xl text-center">
                                        <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-1">
                                            Nuestra Posición</p>
                                        <p class="text-xl font-black dark:text-white">INGRESE PRECIOS PARA ANALIZAR</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let chartInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            initChart();
            updateAllARS(true);
        });

        function updateAllARS(initial = false) {
            const rate = parseFloat(document.getElementById('exchange_rate').value) || 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const usd = parseFloat(row.querySelector('.vs-usd').innerText.replace('$ ', '').replace(',', '')) || 0;
                if (!initial || row.querySelector('.vs-ars').value == 0) {
                    row.querySelector('.vs-ars').value = (usd * rate).toFixed(2);
                }
                recalcRow(row.querySelector('.vs-ars'), false);
            });
            updateChart();
        }

        function recalcRow(el, updateGlobal = true) {
            const row = el.closest('tr');
            const vsArs = parseFloat(row.querySelector('.vs-ars').value) || 0;
            const compArs = parseFloat(row.querySelector('.comp-ars').value) || 0;
            const diffCell = row.querySelector('.diff-cell');
            const diffSpan = row.querySelector('.diff-val');

            if (compArs > 0) {
                const diffPerc = ((compArs - vsArs) / vsArs) * 100;
                diffSpan.innerText = (diffPerc > 0 ? '+' : '') + diffPerc.toFixed(1) + '%';
                diffSpan.classList.remove('hidden');

                // Color coding
                if (vsArs < compArs) {
                    row.querySelector('.vs-ars').classList.add('bg-green-500/20', 'text-green-500');
                    row.querySelector('.vs-ars').classList.remove('bg-red-500/20', 'text-red-500');
                    row.querySelector('.comp-ars').classList.add('bg-red-500/20', 'text-red-500');
                    row.querySelector('.comp-ars').classList.remove('bg-green-500/20', 'text-green-500');
                    diffSpan.className = "diff-val px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-500 text-white";
                } else if (vsArs > compArs) {
                    row.querySelector('.vs-ars').classList.add('bg-red-500/20', 'text-red-500');
                    row.querySelector('.vs-ars').classList.remove('bg-green-500/20', 'text-green-500');
                    row.querySelector('.comp-ars').classList.add('bg-green-500/20', 'text-green-500');
                    row.querySelector('.comp-ars').classList.remove('bg-red-500/20', 'text-red-500');
                    diffSpan.className = "diff-val px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-500 text-white";
                } else {
                    row.querySelector('.vs-ars').classList.remove('bg-green-500/20', 'text-green-500', 'bg-red-500/20', 'text-red-500');
                    row.querySelector('.comp-ars').classList.remove('bg-green-500/20', 'text-green-500', 'bg-red-500/20', 'text-red-500');
                    diffSpan.className = "diff-val px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-500 text-white";
                }
            } else {
                diffSpan.classList.add('hidden');
                row.querySelector('.vs-ars').classList.remove('bg-green-500/20', 'text-green-500', 'bg-red-500/20', 'text-red-500');
                row.querySelector('.comp-ars').classList.remove('bg-green-500/20', 'text-green-500', 'bg-red-500/20', 'text-red-500');
            }

            if (updateGlobal) updateChart();
        }

        function updateChart() {
            const labels = [];
            const vsData = [];
            const compData = [];
            let cheaperCount = 0;
            let totalDiff = 0;
            let comparedCount = 0;

            document.querySelectorAll('.item-row').forEach(row => {
                const sku = row.querySelector('.sku').value;
                const vsArs = parseFloat(row.querySelector('.vs-ars').value) || 0;
                const compArs = parseFloat(row.querySelector('.comp-ars').value) || 0;

                labels.push(sku);
                vsData.push(vsArs);
                compData.push(compArs);

                if (compArs > 0) {
                    comparedCount++;
                    if (vsArs < compArs) cheaperCount++;
                    totalDiff += ((compArs - vsArs) / vsArs) * 100;
                }
            });

            if (chartInstance) {
                chartInstance.data.labels = labels;
                chartInstance.data.datasets[0].data = vsData;
                chartInstance.data.datasets[1].data = compData;
                chartInstance.update();
            }

            // Update Summary
            document.getElementById('cheaper-count').innerText = cheaperCount;
            if (comparedCount > 0) {
                const avg = totalDiff / comparedCount;
                document.getElementById('avg-diff').innerText = (avg > 0 ? '+' : '') + avg.toFixed(1) + '%';
                document.getElementById('avg-diff').className = "text-3xl font-black " + (avg > 0 ? 'text-emerald-500' : 'text-red-500');

                const statusP = document.querySelector('#competitive-status p:last-child');
                if (cheaperCount === comparedCount) {
                    statusP.innerText = "MÁXIMA COMPETITIVIDAD";
                    statusP.className = "text-xl font-black text-emerald-500";
                } else if (cheaperCount > comparedCount / 2) {
                    statusP.innerText = "POSICIÓN FUERTE";
                    statusP.className = "text-xl font-black text-emerald-500";
                } else {
                    statusP.innerText = "REVISAR PRECIOS";
                    statusP.className = "text-xl font-black text-amber-500";
                }
            }
        }

        function initChart() {
            const ctx = document.getElementById('compChart').getContext('2d');
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Vecino Seguro', data: [], backgroundColor: '#136dec', borderRadius: 4 },
                        { label: 'Competencia', data: [], backgroundColor: '#f43f5e', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { color: '#94a3b8', font: { weight: 'bold', size: 10 } } }
                    },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b' } },
                        x: { grid: { display: false }, ticks: { color: '#64748b' } }
                    }
                }
            });
        }

        async function saveAnalysis() {
            const items = [];
            document.querySelectorAll('.item-row').forEach(row => {
                items.push({
                    product_id: row.querySelector('.product-id').value,
                    sku: row.querySelector('.sku').value,
                    description: row.querySelector('.description').value,
                    qty: row.querySelector('.qty').innerText,
                    vs_unit_usd: parseFloat(row.querySelector('.vs-usd').innerText.replace('$ ', '').replace(',', '')) || 0,
                    vs_unit_ars: parseFloat(row.querySelector('.vs-ars').value) || 0,
                    comp_unit_ars: parseFloat(row.querySelector('.comp-ars').value) || 0
                });
            });

            const data = {
                id: "<?php echo $analysisId; ?>",
                quote_id: "<?php echo $quotationId ?? ($data['quote_id'] ?? ''); ?>",
                client_id: "<?php echo $data['client_id'] ?? ''; ?>",
                exchange_rate: document.getElementById('exchange_rate').value,
                items: items
            };

            Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

            try {
                const resp = await fetch('ajax_save_competitor_analysis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const res = await resp.json();
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'GUARDADO', text: 'El análisis se guardó correctamente.', timer: 1500, showConfirmButton: false })
                        .then(() => {
                            window.location.href = 'analisis_competencia.php?id=' + res.id;
                        });
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }
    </script>
</body>

</html>