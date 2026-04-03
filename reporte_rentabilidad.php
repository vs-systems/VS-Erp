<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';

$analysis = new \Vsys\Modules\Analysis\OperationAnalysis();
$report = $analysis->getGlobalProfitabilitySummary();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Rentabilidad - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
        }
    </script>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822] z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="bg-emerald-500/20 p-2 rounded-lg text-emerald-500">
                        <span class="material-symbols-outlined text-2xl">monitoring</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Reporte de
                        Rentabilidad Global</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto space-y-8">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Ingresos
                                Totales (Ventas Confirmadas)</p>
                            <h3 class="text-3xl font-black text-primary">USD
                                <?php echo number_format($report['total_revenue'], 2); ?>
                            </h3>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Costo de
                                Mercadería (Reposición/Real)</p>
                            <h3 class="text-3xl font-black text-red-500">USD
                                <?php echo number_format($report['total_cost'], 2); ?>
                            </h3>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Rentabilidad
                                Promedio</p>
                            <h3 class="text-3xl font-black text-emerald-500">
                                <?php echo $report['avg_margin']; ?>%
                            </h3>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8">
                        <div class="flex items-center gap-4 mb-8">
                            <div
                                class="size-16 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <span class="material-symbols-outlined text-4xl">margin</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">Resumen de Margen Operativo</h3>
                                <p class="text-slate-400">Balance neto de las operaciones proyectado a costos actuales.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div
                                class="flex justify-between items-end border-b border-slate-100 dark:border-white/5 pb-4">
                                <span class="text-slate-500 font-medium">Ganancia Total Proyectada (USD)</span>
                                <span class="text-3xl font-bold text-emerald-500">USD
                                    <?php echo number_format($report['total_profit'], 2); ?>
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-12 p-6 bg-slate-50 dark:bg-white/5 rounded-2xl border border-dashed border-slate-200 dark:border-white/10">
                            <h4 class="text-sm font-bold mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">info</span>
                                Nota Metodológica
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Este informe calcula la rentabilidad contrastando el precio de venta (sin IVA) de todos
                                los presupuestos marcados como <b>Confirmados</b> contra el costo unitario de
                                reposición. El sistema prioriza el último costo de compra registrado para cada SKU; en
                                su defecto, utiliza el costo cargado en el catálogo de productos.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>

</html>