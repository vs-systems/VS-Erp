<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/CurrentAccounts.php';

use Vsys\Modules\Billing\CurrentAccounts;

$currentAccounts = new CurrentAccounts();
$clients = $currentAccounts->getClientsWithBalances();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas Corrientes - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase">Cuentas Corrientes</h2>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Stats / Totals -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php
                        $totalDebt = 0;
                        foreach ($clients as $c) {
                            if ($c['balance'] > 0)
                                $totalDebt += $c['balance'];
                        }
                        ?>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Total a Cobrar
                            </h3>
                            <p class="text-3xl font-bold text-red-500">
                                $
                                <?php echo number_format($totalDebt, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Clients Table -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
                        <div
                            class="p-6 border-b border-slate-200 dark:border-[#233348] flex justify-between items-center">
                            <h3 class="font-bold text-lg">Saldos de Clientes</h3>
                            <input type="text" placeholder="Buscar cliente..."
                                class="bg-slate-50 dark:bg-[#101822] border-none rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#136dec]">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="text-xs uppercase text-slate-500 border-b border-slate-200 dark:border-[#233348]">
                                        <th class="px-6 py-4 font-bold">Cliente</th>
                                        <th class="px-6 py-4 font-bold text-right">Debe</th>
                                        <th class="px-6 py-4 font-bold text-right">Haber</th>
                                        <th class="px-6 py-4 font-bold text-right">Saldo</th>
                                        <th class="px-6 py-4 font-bold text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-[#233348]">
                                    <?php foreach ($clients as $client): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b] transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold">
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    <?php echo htmlspecialchars($client['contact_person']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right text-slate-500">
                                                $
                                                <?php echo number_format($client['total_debit'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-green-500">
                                                $
                                                <?php echo number_format($client['total_credit'], 2, ',', '.'); ?>
                                            </td>
                                            <td
                                                class="px-6 py-4 text-right font-bold <?php echo $client['balance'] > 0 ? 'text-red-500' : 'text-green-500'; ?>">
                                                $
                                                <?php echo number_format($client['balance'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <a href="cuenta_corriente_detalle.php?id=<?php echo $client['id']; ?>"
                                                    class="text-[#136dec] hover:underline text-sm font-bold uppercase">Ver
                                                    Detalle</a>
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
</body>

</html>