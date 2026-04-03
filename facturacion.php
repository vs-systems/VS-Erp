<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';

use Vsys\Modules\Billing\Billing;

$billing = new Billing();
$recentInvoices = $billing->getRecentInvoices(20);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase">Módulo de Facturación</h2>
                </div>
                <a href="nueva_factura.php"
                    class="bg-[#136dec] hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-sm uppercase flex items-center gap-2 shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined">add</span>
                    Nueva Factura
                </a>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Invoices List -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-[#233348]">
                            <h3 class="font-bold text-lg">Últimas Facturas Emitidas</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="bg-slate-50 dark:bg-[#1c2a3b] text-xs uppercase text-slate-500 font-bold border-b border-slate-200 dark:border-[#233348]">
                                        <th class="px-6 py-4">N° Factura</th>
                                        <th class="px-6 py-4">N° Orden</th>
                                        <th class="px-6 py-4">Fecha</th>
                                        <th class="px-6 py-4">Cliente</th>
                                        <th class="px-6 py-4 text-center">Tipo</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-right">Importe Total</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($recentInvoices as $inv): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b]/50 transition-colors">
                                            <td class="px-6 py-3 font-mono font-bold"><?php echo $inv['invoice_number']; ?>
                                            </td>
                                            <td class="px-6 py-3 text-xs font-bold text-[#136dec]">
                                                <?php echo $inv['quote_number'] ?: '-'; ?>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-slate-500">
                                                <?php echo date('d/m/Y', strtotime($inv['date'])); ?>
                                            </td>
                                            <td class="px-6 py-3 font-bold">
                                                <?php echo htmlspecialchars($inv['client_name']); ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <span
                                                    class="w-6 h-6 inline-flex items-center justify-center rounded bg-slate-100 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] font-bold text-xs">
                                                    <?php echo $inv['invoice_type']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <span
                                                    class="px-2 py-1 rounded text-[10px] font-bold uppercase 
                                                <?php echo $inv['status'] === 'Finalizada' ? 'bg-green-100 text-green-600' : ($inv['status'] === 'Pagado' ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600'); ?>">
                                                    <?php echo $inv['status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-right font-bold">
                                                $ <?php echo number_format($inv['total_amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <?php if ($inv['status'] !== 'Finalizada'): ?>
                                                    <button onclick="finalizeInvoice(<?php echo $inv['id']; ?>)"
                                                        class="text-[10px] font-bold text-[#136dec] hover:underline uppercase">
                                                        Finalizar
                                                    </button>
                                                <?php else: ?>
                                                    <span
                                                        class="text-[10px] text-slate-400 uppercase font-medium">Cerrada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($recentInvoices)): ?>
                                <div class="p-10 text-center text-slate-400">
                                    <span class="material-symbols-outlined text-4xl mb-2">description</span>
                                    <p>Aún no has emitido ninguna factura.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <script>
                            function finalizeInvoice(id) {
                                Swal.fire({
                                    title: '¿Finalizar factura?',
                                    text: "Esta acción marcará la factura como completada.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#136dec',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Sí, finalizar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const formData = new FormData();
                                        formData.append('id', id);
                                        formData.append('status', 'Finalizada');

                                        fetch('ajax_update_invoice_status.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire('¡Éxito!', 'Factura finalizada correctamente.', 'success')
                                                        .then(() => location.reload());
                                                } else {
                                                    Swal.fire('Error', data.error || 'No se pudo actualizar la factura.', 'error');
                                                }
                                            });
                                    }
                                })
                            }
                        </script>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>

</html>