<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';

require_once __DIR__ . '/src/lib/BCRAClient.php';
require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';
require_once __DIR__ . '/src/modules/dashboard/SellerDashboard.php';

$analysis = new \Vsys\Modules\Analysis\OperationAnalysis();
$logistics = new \Vsys\Modules\Logistica\Logistics();
$crm = new \Vsys\Modules\CRM\CRM();
$userRole = $_SESSION['role'] ?? 'Invitado';
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['full_name'] ?? ($_SESSION['user_name'] ?? 'Usuario');

$stats = ['total_sales' => 0, 'pending_collections' => 0, 'total_purchases' => 0, 'pending_payments' => 0, 'effectiveness' => 0];
$sellerStats = ['total' => 0, 'converted' => 0];
$shipStats = [];
$monthlyStats = [];

// Get current exchange rate for display
$currency = new \Vsys\Lib\BCRAClient();
$exchangeRate = $currency->getCurrentRate('oficial') ?? 1425.00;

$db = \Vsys\Lib\Database::getInstance();

if ($userRole === 'Vendedor') {
    $sellerDash = new \Vsys\Modules\Dashboard\SellerDashboard($userId);
    $sellerStats = $sellerDash->getEfficiencyStats() ?: $sellerStats;
    $recentQuotations = $sellerDash->getRecentQuotes();
    $recentShipments = $sellerDash->getClientShipments();
} else {
    $stats = $analysis->getDashboardSummary() ?: $stats;
    $shipStats = $logistics->getShippingStats() ?: $shipStats;
    $crmLeadStats = $crm->getLeadsStats();
    $sellerRanking = $crm->getSellerRanking();
    $monthlyStats = $analysis->getMonthlyProfitability(6);

    // Fetch budget stats for ring chart
    $budgetStats = [
        'Aprobados' => $db->query("SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1")->fetchColumn(),
        'Pendientes' => $db->query("SELECT COUNT(*) FROM quotations WHERE is_confirmed = 0 AND status NOT IN ('Perdido', 'Cancelado', 'Rechazado')")->fetchColumn(),
        'Perdidos' => $db->query("SELECT COUNT(*) FROM quotations WHERE status IN ('Perdido', 'Cancelado', 'Rechazado')")->fetchColumn(),
    ];
    $totalBudgets = array_sum($budgetStats);
}

$effectivenessStats = $analysis->getDashboardSummary();

// Phase colors for logistics widget
$logisticsPhases = [
    'En reserva' => ['color' => 'bg-slate-500/10 text-slate-500', 'label' => 'Reserva'],
    'En espera' => ['color' => 'bg-amber-500/10 text-amber-500', 'label' => 'En Espera'],
    'En preparación' => ['color' => 'bg-blue-500/10 text-blue-500', 'label' => 'Armado'],
    'Disponible' => ['color' => 'bg-green-500/10 text-green-500', 'label' => 'Preparado'],
    'En su transporte' => ['color' => 'bg-purple-500/20 text-purple-500', 'label' => 'Despachado'],
    'Entregado' => ['color' => 'bg-emerald-500/20 text-emerald-500', 'label' => 'Entregado']
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VS System - Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101822",
                        "surface-dark": "#16202e",
                        "surface-border": "#233348",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
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
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #324867;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }

        .dark .glass-card {
            background: rgba(22, 32, 46, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #233348;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300 uppercase">
    <div class="flex h-screen w-full">
        <!-- Sidebar Inclusion -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Top Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-4 lg:hidden">
                    <button onclick="toggleVsysSidebar()" class="dark:text-white text-slate-800">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <span class="dark:text-white text-slate-800 font-bold text-lg">VS System</span>
                </div>

                <div class="hidden lg:flex items-center flex-1 max-w-xl">
                    <!-- Global search removed as requested -->
                </div>

                <div class="flex items-center gap-4 ml-auto">
                    <div class="flex flex-col items-end mr-2">
                        <span
                            class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest flex items-center gap-1">
                            USD Oficial: <span
                                class="text-sm font-black">$<?php echo number_format($exchangeRate, 2, ',', '.'); ?></span>
                        </span>
                        <span
                            class="text-[9px] text-slate-500 uppercase font-bold tracking-tighter"><?php echo date('d M, Y'); ?>
                            - <?php echo $userRole; ?></span>
                    </div>
                    <div class="h-8 w-px bg-[#233348]"></div>
                    <button class="text-slate-400 hover:text-white transition-colors relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span
                            class="absolute top-0 right-0 size-2 bg-red-500 rounded-full border-2 border-[#101822]"></span>
                    </button>
                </div>
            </header>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-7xl mx-auto space-y-8">

                    <!-- Welcome Header -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-bold dark:text-white text-slate-800 tracking-tight">Panel de
                                Control</h2>
                            <p class="text-slate-400 mt-1">Bienvenido de nuevo, <span
                                    class="text-[#136dec] font-semibold"><?php echo $userName; ?></span>.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="cotizador.php"
                                class="flex items-center gap-2 bg-[#136dec] hover:bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-[#136dec]/20 transition-all text-sm">
                                <span class="material-symbols-outlined text-lg">add</span> NUEVA COTIZACIÓN
                            </a>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <?php if ($userRole === 'Admin' || $userRole === 'Sistemas'):
                        $statusStats = $analysis->getStatusStats();
                        ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-green-500/10 rounded-lg text-green-500 group-hover:bg-green-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">payments</span>
                                    </div>
                                    <span
                                        class="text-green-500 text-[10px] font-bold bg-green-500/10 px-2 py-1 rounded-full uppercase">Ventas
                                        Netas</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">USD
                                    <?php echo number_format($stats['total_sales'], 2); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Pendiente de cobro: <span
                                        class="text-emerald-500 font-bold">ARS
                                        <?php echo number_format($stats['pending_collections'] ?? 0, 2, ',', '.'); ?></span>
                                </p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-red-500/10 rounded-lg text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">shopping_cart</span>
                                    </div>
                                    <span
                                        class="text-red-500 text-[10px] font-bold bg-red-500/10 px-2 py-1 rounded-full uppercase">Compras
                                        Totales</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">USD
                                    <?php echo number_format($stats['total_purchases'], 2); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Pendiente de pago: <span
                                        class="text-red-500 font-bold">ARS
                                        <?php echo number_format($stats['pending_payments'] ?? 0, 2, ',', '.'); ?></span>
                                </p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-[#136dec]/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-[#136dec]/10 rounded-lg text-[#136dec] group-hover:bg-[#136dec] group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">query_stats</span>
                                    </div>
                                    <span
                                        class="text-[#136dec] text-[10px] font-bold bg-[#136dec]/10 px-2 py-1 rounded-full uppercase">Eficiencia</span>
                                </div>
                                <h3 class="text-3xl font-black text-primary font-mono tracking-tighter">
                                    <?php echo $effectivenessStats['effectiveness']; ?>%
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Cierre de presupuestos</p>
                            </div>

                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl p-5 hover:border-amber-500/50 transition-all group shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-3">
                                    <div
                                        class="p-2.5 bg-emerald-500/10 rounded-lg text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                        <span class="material-symbols-outlined">trending_up</span>
                                    </div>
                                    <span
                                        class="text-emerald-500 text-[10px] font-bold bg-emerald-500/10 px-2 py-1 rounded-full uppercase">Rentabilidad</span>
                                </div>
                                <h3 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">
                                    <?php echo number_format(($stats['total_sales'] - $stats['total_purchases']), 2); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-2">Margen bruto actual (USD)</p>
                            </div>
                        </div>

                        <!-- Status Charts Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Quotations Chart -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 flex flex-col items-center gap-4 transition-all hover:shadow-lg dark:hover:shadow-none">
                                <h3 class="w-full text-sm font-bold uppercase tracking-widest text-slate-500 mb-2">
                                    Presupuestos</h3>
                                <div class="size-32 relative">
                                    <canvas id="quoteStatusChart"></canvas>
                                </div>
                                <div class="w-full space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-emerald-500"></span> Confirmadas</span>
                                        <span
                                            class="font-bold"><?php echo $statusStats['quotations']['confirmadas']; ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-amber-500"></span> Pendientes</span>
                                        <span
                                            class="font-bold"><?php echo $statusStats['quotations']['pendientes']; ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-red-500"></span> Perdidas</span>
                                        <span class="font-bold"><?php echo $statusStats['quotations']['perdidas']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- CRM Chart (New) -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 flex flex-col items-center gap-4 transition-all hover:shadow-lg dark:hover:shadow-none">
                                <h3 class="w-full text-sm font-bold uppercase tracking-widest text-slate-500 mb-2">Embudo
                                    CRM (Leads)</h3>
                                <div class="size-32 relative">
                                    <canvas id="crmLeadChart"></canvas>
                                </div>
                                <div class="w-full space-y-1">
                                    <?php foreach ($crmLeadStats as $st => $count): ?>
                                        <div class="flex justify-between items-center text-[10px]">
                                            <span class="text-slate-500 uppercase font-medium"><?php echo $st; ?></span>
                                            <span class="font-bold dark:text-white"><?php echo $count ?: 0; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Purchases Chart -->
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 flex flex-col items-center gap-4 transition-all hover:shadow-lg dark:hover:shadow-none">
                                <h3 class="w-full text-sm font-bold uppercase tracking-widest text-slate-500 mb-2">Compras
                                </h3>
                                <div class="size-32 relative">
                                    <canvas id="purchaseStatusChart"></canvas>
                                </div>
                                <div class="w-full space-y-1">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-blue-500"></span> Confirmadas</span>
                                        <span
                                            class="font-bold"><?php echo $statusStats['purchases']['confirmadas']; ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-purple-500"></span> Pagadas</span>
                                        <span class="font-bold"><?php echo $statusStats['purchases']['pagadas']; ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="flex items-center gap-2"><span
                                                class="size-2 rounded-full bg-amber-500"></span> Pendientes</span>
                                        <span
                                            class="font-bold"><?php echo $statusStats['purchases']['pendientes']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Initialization Scripts Moved to Bottom -->
                    <?php endif; ?>

                    <!-- Charts & Ranking -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Budget Summary Chart -->
                        <div
                            class="lg:col-span-2 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-6">Resumen de
                                Presupuestos</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                <div class="h-[380px] relative">
                                    <canvas id="budgetRingChart"></canvas>
                                    <div
                                        class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Total</span>
                                        <span
                                            class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $totalBudgets; ?></span>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div
                                        class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                            <span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aprobados</span>
                                        </div>
                                        <span
                                            class="text-sm font-black text-slate-700 dark:text-white"><?php echo $budgetStats['Aprobados']; ?></span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                            <span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendientes</span>
                                        </div>
                                        <span
                                            class="text-sm font-black text-slate-700 dark:text-white"><?php echo $budgetStats['Pendientes']; ?></span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                            <span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wider">Perdidos
                                                / Cancelados</span>
                                        </div>
                                        <span
                                            class="text-sm font-black text-slate-700 dark:text-white"><?php echo $budgetStats['Perdidos']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seller Ranking (New) -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm flex flex-col">
                            <h3
                                class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-500">military_tech</span> Ranking
                                Vendedores
                            </h3>
                            <div class="flex-1 overflow-y-auto custom-scrollbar">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[9px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:border-white/5">
                                            <th class="pb-2">Vendedor</th>
                                            <th class="pb-2 text-center">Pts</th>
                                            <th class="pb-2 text-right">Cierres</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                        <?php foreach ($sellerRanking as $idx => $s): ?>
                                            <tr class="group">
                                                <td class="py-3 flex items-center gap-2">
                                                    <span class="text-[10px]"><?php echo $idx + 1; ?>.</span>
                                                    <span
                                                        class="text-xs font-bold dark:text-slate-300"><?php echo explode(' ', $s['seller_name'])[0]; ?></span>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span
                                                        class="text-[10px] font-mono text-primary font-bold"><?php echo ($s['orders_closed'] * 10) + ($s['total_quotes'] * 2) + ($s['interactions']); ?></span>
                                                </td>
                                                <td class="py-3 text-right">
                                                    <span
                                                        class="px-2 py-0.5 rounded bg-green-500/10 text-green-500 text-[10px] font-bold"><?php echo $s['orders_closed']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Tables: Logistics & Recent Quotes -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Logistics: Pedidos para entregar (New) -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                            <div
                                class="p-5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center">
                                <h3
                                    class="text-sm font-bold dark:text-white flex items-center gap-2 uppercase tracking-widest">
                                    <span class="material-symbols-outlined text-primary">local_shipping</span> Pedidos
                                    para Entregar
                                </h3>
                                <a href="logistica.php"
                                    class="text-[10px] font-bold text-primary hover:underline">GESTIÓN LOGÍSTICA</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[9px] uppercase font-bold text-slate-400 bg-slate-50/50 dark:bg-white/5">
                                            <th class="px-5 py-3">Pedido</th>
                                            <th class="px-5 py-3">Cliente</th>
                                            <th class="px-5 py-3 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php
                                        $pendingLogs = $logistics->getOrdersForPreparation();
                                        $pendingLogsLimit = array_slice($pendingLogs, 0, 6);
                                        foreach ($pendingLogsLimit as $pl):
                                            $stData = $logisticsPhases[$pl['current_phase'] ?? 'En reserva'] ?? $logisticsPhases['En reserva'];
                                            ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                <td class="px-5 py-3 text-xs font-bold dark:text-slate-300">
                                                    <?php echo $pl['quote_number']; ?>
                                                </td>
                                                <td class="px-5 py-3 text-[10px] text-slate-500 truncate max-w-[120px]">
                                                    <?php echo $pl['client_name']; ?>
                                                </td>
                                                <td class="px-5 py-3 text-center">
                                                    <span
                                                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase <?php echo $stData['color']; ?>">
                                                        <?php echo $stData['label']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($pendingLogsLimit)): ?>
                                            <tr>
                                                <td colspan="3" class="p-8 text-center text-xs text-slate-500 italic">No hay
                                                    pedidos pendientes de entrega</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Recent Pending Purchases / Quotes -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                            <div
                                class="p-5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center">
                                <h3 class="text-sm font-bold dark:text-white uppercase tracking-widest">
                                    <?php echo ($userRole === 'Admin' || $userRole === 'Sistemas') ? 'Compras Pendientes' : 'Cotizaciones Recientes'; ?>
                                </h3>
                                <a href="<?php echo ($userRole === 'Admin' || $userRole === 'Sistemas') ? 'purchases.php' : 'presupuestos.php'; ?>"
                                    class="text-[10px] font-bold text-primary hover:underline">VER TODAS</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[9px] uppercase font-bold text-slate-400 bg-slate-50/50 dark:bg-white/5">
                                            <th class="px-5 py-3">Ref</th>
                                            <th class="px-5 py-3">Proveedor / Cliente</th>
                                            <th class="px-5 py-3 text-right">Total USD</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php
                                        if ($userRole === 'Admin' || $userRole === 'Sistemas') {
                                            require_once __DIR__ . '/src/modules/purchases/Purchases.php';
                                            $purchModule = new \Vsys\Modules\Purchases\Purchases();
                                            $pendingP = $purchModule->getPendingPurchases();
                                            $displayItems = array_slice($pendingP, 0, 6);
                                        } else {
                                            $displayItems = $db->query("SELECT q.quote_number as ref, e.name as extra, q.total_usd FROM quotations q JOIN entities e ON q.client_id = e.id ORDER BY q.id DESC LIMIT 6")->fetchAll();
                                        }

                                        foreach ($displayItems as $r):
                                            $ref = $r['purchase_number'] ?? ($r['quote_number'] ?? ($r['ref'] ?? 'N/A'));
                                            $name = $r['supplier_name'] ?? ($r['client_name'] ?? ($r['extra'] ?? 'N/A'));
                                            $total = $r['total_usd'];
                                            ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                <td class="px-5 py-3 text-xs font-bold"><?php echo $ref; ?></td>
                                                <td class="px-5 py-3 text-[10px] text-slate-500"><?php echo $name; ?></td>
                                                <td class="px-5 py-3 text-right text-xs font-mono">
                                                    $<?php echo number_format($total, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($displayItems)): ?>
                                            <tr>
                                                <td colspan="3" class="p-8 text-center text-xs text-slate-500 italic">No hay
                                                    registros pendientes</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- Spacing at bottom -->
                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- Charts Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Stats Charts
            const quoteCtx = document.getElementById('quoteStatusChart');
            if (quoteCtx) {
                new Chart(quoteCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Confirmadas', 'Pendientes', 'Perdidas'],
                        datasets: [{
                            data: [<?php echo $statusStats['quotations']['confirmadas']; ?>, <?php echo $statusStats['quotations']['pendientes']; ?>, <?php echo $statusStats['quotations']['perdidas']; ?>],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '75%' }
                });
            }


            const purchaseCtx = document.getElementById('purchaseStatusChart');
            if (purchaseCtx) {
                new Chart(purchaseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Confirmadas', 'Pagadas', 'Pendientes'],
                        datasets: [{
                            data: [<?php echo $statusStats['purchases']['confirmadas']; ?>, <?php echo $statusStats['purchases']['pagadas']; ?>, <?php echo $statusStats['purchases']['pendientes']; ?>],
                            backgroundColor: ['#3b82f6', '#a855f7', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '75%' }
                });
            }

            // Budget Ring Chart
            const ctxBudget = document.getElementById('budgetRingChart');
            if (ctxBudget) {
                new Chart(ctxBudget, {
                    type: 'doughnut',
                    data: {
                        labels: ['Aprobados', 'Pendientes', 'Perdidos'],
                        datasets: [{
                            data: [<?php echo $budgetStats['Aprobados']; ?>, <?php echo $budgetStats['Pendientes']; ?>, <?php echo $budgetStats['Perdidos']; ?>],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let label = context.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed !== null) label += context.parsed;
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>