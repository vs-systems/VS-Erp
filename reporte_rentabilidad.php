<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();

// ── Query: agrupar ventas confirmadas por tipo_cliente ────────────
// Usa is_confirmed=1 (pedidos cerrados) + discrimina por tipo de lista
$sql = "
    SELECT
        COALESCE(e.tipo_cliente, 'publico')                 AS lista,
        COUNT(DISTINCT q.id)                                AS total_pedidos,
        SUM(COALESCE(q.total_ars, 0))                       AS total_ars,
        SUM(COALESCE(q.total_usd, 0))                       AS total_usd
    FROM quotations q
    LEFT JOIN entities e ON q.client_id = e.id
    WHERE q.is_confirmed = 1
      AND (q.status NOT IN ('rejected','Perdido') OR q.status IS NULL)
      AND q.archived_at IS NULL
    GROUP BY COALESCE(e.tipo_cliente, 'publico')
    ORDER BY FIELD(COALESCE(e.tipo_cliente,'publico'), 'partner','gremio','publico')
";

$rows = [];
try {
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
} catch (Exception $e) { /* tabla vacía o columna inexistente */ }

// Normalizar: asegurar que existan las 3 listas aunque no tengan datos
$listas = ['partner' => null, 'gremio' => null, 'publico' => null];
foreach ($rows as $r) {
    $listas[$r['lista']] = $r;
}

// Totales
$grandTotalArs    = array_sum(array_column($rows, 'total_ars'));
$grandTotalPedidos = array_sum(array_column($rows, 'total_pedidos'));

// Labels y colores por lista
$listaConfig = [
    'partner' => ['label' => 'Lista Partner', 'color' => 'text-blue-400',   'bg' => 'bg-blue-500/10',   'border' => 'border-blue-500/20',   'icon' => 'handshake'],
    'gremio'  => ['label' => 'Lista Gremio',  'color' => 'text-amber-400',  'bg' => 'bg-amber-500/10',  'border' => 'border-amber-500/20',  'icon' => 'engineering'],
    'publico' => ['label' => 'Lista PVP',      'color' => 'text-emerald-400','bg' => 'bg-emerald-500/10','border' => 'border-emerald-500/20','icon' => 'storefront'],
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentabilidad por Lista — VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">

            <!-- Header -->
            <header class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822] z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-emerald-500/20 p-2 rounded-lg text-emerald-500">
                        <span class="material-symbols-outlined text-2xl">monitoring</span>
                    </div>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg tracking-tight">Rentabilidad por Lista</h2>
                        <p class="text-[10px] text-slate-500 font-medium tracking-widest uppercase">Solo pedidos confirmados</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-400 font-medium">
                        <span class="text-primary font-black"><?php echo $grandTotalPedidos; ?></span> pedidos confirmados
                    </span>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto space-y-6">

                    <!-- Aviso metodológico -->
                    <div class="flex items-start gap-3 bg-blue-500/5 border border-blue-500/15 rounded-2xl px-5 py-4">
                        <span class="material-symbols-outlined text-blue-400 text-lg mt-0.5">info</span>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Este informe agrupa los <strong class="text-white">pedidos confirmados</strong> según el tipo de lista del cliente.
                            Ingresá el <strong class="text-white">% de comisión</strong> en cada fila para calcular el importe estimado de comisión.
                            El cálculo es solo orientativo y no se guarda.
                        </p>
                    </div>

                    <!-- Tabla por lista -->
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/5">
                                <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                    <th class="px-6 py-4 text-left">Lista</th>
                                    <th class="px-6 py-4 text-right">Pedidos</th>
                                    <th class="px-6 py-4 text-right">Total ARS</th>
                                    <th class="px-6 py-4 text-center" style="width:140px;">% Comisión</th>
                                    <th class="px-6 py-4 text-right">Importe Comisión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">

                                <?php foreach ($listas as $key => $data):
                                    $cfg     = $listaConfig[$key];
                                    $pedidos = $data ? (int)$data['total_pedidos'] : 0;
                                    $arsTotal = $data ? (float)$data['total_ars']  : 0;
                                    $hasData = $pedidos > 0;
                                ?>
                                <tr class="group hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-all" data-lista="<?php echo $key; ?>">

                                    <!-- Lista -->
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="<?php echo $cfg['bg']; ?> <?php echo $cfg['border']; ?> border p-2 rounded-xl">
                                                <span class="material-symbols-outlined text-lg <?php echo $cfg['color']; ?>"><?php echo $cfg['icon']; ?></span>
                                            </div>
                                            <div>
                                                <div class="font-black text-sm dark:text-white text-slate-800"><?php echo $cfg['label']; ?></div>
                                                <?php if (!$hasData): ?>
                                                    <div class="text-[10px] text-slate-400">Sin pedidos confirmados</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Pedidos -->
                                    <td class="px-6 py-5 text-right">
                                        <span class="font-black text-lg <?php echo $hasData ? $cfg['color'] : 'text-slate-400'; ?>">
                                            <?php echo $pedidos; ?>
                                        </span>
                                    </td>

                                    <!-- Total ARS -->
                                    <td class="px-6 py-5 text-right">
                                        <span class="font-black text-base dark:text-white text-slate-800 font-mono">
                                            <?php echo $hasData ? '$ ' . number_format($arsTotal, 2, ',', '.') : '—'; ?>
                                        </span>
                                    </td>

                                    <!-- % Comisión (input editable) -->
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <input
                                                type="number"
                                                min="0" max="100" step="0.1"
                                                placeholder="0"
                                                data-total="<?php echo $arsTotal; ?>"
                                                oninput="calcComision(this)"
                                                class="commission-input w-16 text-center bg-slate-100 dark:bg-white/10 border border-slate-200 dark:border-white/10 rounded-lg px-2 py-1.5 text-sm font-black text-slate-700 dark:text-white focus:ring-2 focus:ring-primary outline-none transition-all <?php echo !$hasData ? 'opacity-30 cursor-not-allowed' : ''; ?>"
                                                <?php echo !$hasData ? 'disabled' : ''; ?>
                                            >
                                            <span class="text-slate-400 text-xs font-bold">%</span>
                                        </div>
                                    </td>

                                    <!-- Importe comisión (calculado) -->
                                    <td class="px-6 py-5 text-right">
                                        <span class="commission-result font-black text-base font-mono text-emerald-400">
                                            <?php echo $hasData ? '$ 0,00' : '—'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                            </tbody>

                            <!-- Totales -->
                            <tfoot class="bg-slate-50 dark:bg-white/5 border-t-2 border-slate-200 dark:border-white/10">
                                <tr>
                                    <td class="px-6 py-5">
                                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Total General</span>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <span class="font-black text-lg text-primary"><?php echo $grandTotalPedidos; ?></span>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <span class="font-black text-base dark:text-white text-slate-800 font-mono">
                                            $ <?php echo number_format($grandTotalArs, 2, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center text-slate-400 text-xs">—</td>
                                    <td class="px-6 py-5 text-right">
                                        <span id="grandTotalCommission" class="font-black text-base font-mono text-emerald-400">$ 0,00</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Detalle meses (tabla secundaria agrupada por mes) -->
                    <?php
                    // Últimos 6 meses discriminados
                    $sqlMeses = "
                        SELECT
                            DATE_FORMAT(q.created_at, '%Y-%m') AS mes,
                            COALESCE(e.tipo_cliente, 'publico') AS lista,
                            COUNT(*) AS pedidos,
                            SUM(COALESCE(q.total_ars, 0)) AS total_ars
                        FROM quotations q
                        LEFT JOIN entities e ON q.client_id = e.id
                        WHERE q.is_confirmed = 1
                          AND (q.status NOT IN ('rejected','Perdido') OR q.status IS NULL)
                          AND q.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY mes, lista
                        ORDER BY mes DESC, lista
                    ";
                    $meses = [];
                    try {
                        $meses = $db->query($sqlMeses)->fetchAll();
                    } catch (Exception $e) {}

                    if (!empty($meses)):
                        // Agrupar por mes
                        $byMes = [];
                        foreach ($meses as $m) {
                            $byMes[$m['mes']][$m['lista']] = $m;
                        }
                    ?>
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400">calendar_month</span>
                            <h3 class="font-black text-sm dark:text-white text-slate-800">Evolución — Últimos 6 meses</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                                    <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        <th class="px-6 py-3 text-left">Mes</th>
                                        <th class="px-6 py-3 text-right text-blue-400">Partner</th>
                                        <th class="px-6 py-3 text-right text-amber-400">Gremio</th>
                                        <th class="px-6 py-3 text-right text-emerald-400">PVP</th>
                                        <th class="px-6 py-3 text-right">Total ARS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php foreach ($byMes as $mes => $listas_mes):
                                        $p  = ($listas_mes['partner']['total_ars'] ?? 0);
                                        $g  = ($listas_mes['gremio']['total_ars']  ?? 0);
                                        $pv = ($listas_mes['publico']['total_ars'] ?? 0);
                                        $tot = $p + $g + $pv;
                                    ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-all">
                                        <td class="px-6 py-3 font-bold text-sm dark:text-slate-300"><?php echo date('M Y', strtotime($mes . '-01')); ?></td>
                                        <td class="px-6 py-3 text-right font-mono text-xs text-blue-400 font-bold">
                                            <?php echo $p > 0 ? '$ ' . number_format($p, 2, ',', '.') : '—'; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono text-xs text-amber-400 font-bold">
                                            <?php echo $g > 0 ? '$ ' . number_format($g, 2, ',', '.') : '—'; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono text-xs text-emerald-400 font-bold">
                                            <?php echo $pv > 0 ? '$ ' . number_format($pv, 2, ',', '.') : '—'; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono text-sm font-black dark:text-white">
                                            $ <?php echo number_format($tot, 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <script>
        function formatARS(n) {
            return '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calcComision(input) {
            const pct    = parseFloat(input.value) || 0;
            const total  = parseFloat(input.dataset.total) || 0;
            const result = total * pct / 100;

            // Actualizar celda de comisión de la misma fila
            const row    = input.closest('tr');
            const span   = row.querySelector('.commission-result');
            if (span) span.textContent = formatARS(result);

            // Recalcular total general
            let grandTotal = 0;
            document.querySelectorAll('.commission-result').forEach(s => {
                const val = parseFloat(s.textContent.replace(/[^0-9,.-]/g, '').replace(',', '.')) || 0;
                grandTotal += val;
            });
            const gt = document.getElementById('grandTotalCommission');
            if (gt) gt.textContent = formatARS(grandTotal);
        }
    </script>
</body>
</html>