<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

$id = $_GET['id'] ?? null;
if (!$id)
    die("ID no proporcionado.");

$cot = new Cotizador();
$db = Vsys\Lib\Database::getInstance();

// 1. Get current quote info
$currentQuote = $cot->getQuotation($id);
if (!$currentQuote)
    die("Presupuesto no encontrado.");

// 2. Identify base number (e.g. VS-2026-01-0001 from VS-2026-01-0001_01)
$baseNumber = explode('_', $currentQuote['quote_number'])[0];

// 3. Get all versions
$stmt = $db->prepare("SELECT q.*, u.full_name as author 
                      FROM quotations q 
                      JOIN users u ON q.user_id = u.id 
                      WHERE q.quote_number LIKE ? 
                      ORDER BY q.version ASC");
$stmt->execute([$baseNumber . '%']);
$versions = $stmt->fetchAll();

// 4. Get items for each version to compare
$allVersionsData = [];
foreach ($versions as $v) {
    $items = $cot->getQuotationItems($v['id']);
    $allVersionsData[] = [
        'info' => $v,
        'items' => $items
    ];
}

?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resumen de Pedido - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 dark:bg-[#101822] text-slate-800 dark:text-white p-8">
    <div class="max-w-5xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-black uppercase">Resumen Histórico de Pedido</h1>
                <p class="text-slate-500 font-mono text-sm">
                    <?php echo $baseNumber; ?>
                </p>
            </div>
            <a href="presupuestos.php"
                class="bg-slate-200 dark:bg-[#16202e] px-4 py-2 rounded-xl text-xs font-bold uppercase hover:bg-slate-300 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Listado
            </a>
        </header>

        <div class="space-y-12 relative">
            <!-- Timeline Line -->
            <div class="absolute left-6 top-4 bottom-4 w-0.5 bg-slate-200 dark:bg-[#233348] z-0"></div>

            <?php foreach ($allVersionsData as $idx => $vData):
                $v = $vData['info'];
                $its = $vData['items'];
                $prevV = ($idx > 0) ? $allVersionsData[$idx - 1] : null;
                ?>
                <div class="relative pl-16 z-10">
                    <!-- Dot -->
                    <div
                        class="absolute left-4 top-2 w-5 h-5 rounded-full border-4 border-slate-50 dark:border-[#101822] <?php echo ($idx === count($allVersionsData) - 1) ? 'bg-primary' : 'bg-slate-300 dark:bg-[#233348]'; ?>">
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-lg">Versión
                                    <?php echo $v['version']; ?>
                                </h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                    <?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?> • Por
                                    <?php echo $v['author']; ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-primary font-mono">USD
                                    <?php echo number_format($v['total_usd'], 2); ?>
                                </p>
                                <p class="text-xs font-bold text-emerald-500">ARS $
                                    <?php echo number_format($v['total_ars'], 2, ',', '.'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Observations Change -->
                        <?php if ($v['observations']): ?>
                            <div class="bg-slate-50 dark:bg-[#101822] p-3 rounded-xl mb-4 text-xs italic text-slate-500 italic">
                                "
                                <?php echo nl2br(htmlspecialchars($v['observations'])); ?>"
                            </div>
                        <?php endif; ?>

                        <!-- Items Comparison -->
                        <div class="overflow-x_auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr
                                        class="text-left text-slate-400 font-bold uppercase border-b border-slate-100 dark:border-[#233348]">
                                        <th class="py-2">SKU</th>
                                        <th class="py-2">Producto</th>
                                        <th class="py-2 text-center">Cant.</th>
                                        <th class="py-2 text-right">Precio Unit. (USD)</th>
                                        <th class="py-2 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-[#233348]/20">
                                    <?php foreach ($its as $it):
                                        $isNew = $prevV ? !array_filter($prevV['items'], fn($pi) => $pi['product_id'] == $it['product_id']) : false;
                                        $prevItem = $prevV ? array_values(array_filter($prevV['items'], fn($pi) => $pi['product_id'] == $it['product_id']))[0] ?? null : null;
                                        $priceChanged = $prevItem && (float) $prevItem['unit_price_usd'] != (float) $it['unit_price_usd'];
                                        $qtyChanged = $prevItem && (int) $prevItem['quantity'] != (int) $it['quantity'];
                                        ?>
                                        <tr class="<?php echo $isNew ? 'bg-emerald-500/5' : ''; ?>">
                                            <td
                                                class="py-3 font-bold <?php echo $isNew ? 'text-emerald-500' : 'text-slate-500'; ?>">
                                                <?php echo $it['sku']; ?>
                                            </td>
                                            <td class="py-3 max-w-xs truncate">
                                                <?php echo $it['description']; ?>
                                            </td>
                                            <td
                                                class="py-3 text-center font-bold <?php echo $qtyChanged ? 'text-amber-500 underline decoration-dotted' : ''; ?>">
                                                <?php echo $it['quantity']; ?>
                                                <?php if ($qtyChanged): ?><span
                                                        class="text-[9px] opacity-50 block font-normal">antes:
                                                        <?php echo $prevItem['quantity']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td
                                                class="py-3 text-right font-mono <?php echo $priceChanged ? 'text-amber-500 underline decoration-dotted' : ''; ?>">
                                                USD
                                                <?php echo number_format($it['unit_price_usd'], 2); ?>
                                                <?php if ($priceChanged): ?><span
                                                        class="text-[9px] opacity-50 block font-normal text-right">antes:
                                                        <?php echo number_format($prevItem['unit_price_usd'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 text-right font-mono font-bold">USD
                                                <?php echo number_format($it['subtotal_usd'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>