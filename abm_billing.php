<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';

use Vsys\Modules\Billing\Billing;

if (($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php');
    exit;
}

$billing = new Billing();
$db = Vsys\Lib\Database::getInstance();

// Handle Actions
$message = '';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_invoice') {
        $id = $_POST['id'];

        $stmtM = $db->prepare("SELECT id FROM client_movements WHERE reference_id = ? AND type = 'Factura'");
        $stmtM->execute([$id]);
        $movementIds = $stmtM->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($movementIds)) {
            $placeholders = implode(',', array_fill(0, count($movementIds), '?'));
            $db->prepare("DELETE FROM treasury_movements WHERE reference_id IN ($placeholders) AND reference_type = 'client_payment'")->execute($movementIds);
            $db->prepare("DELETE FROM client_movements WHERE id IN ($placeholders)")->execute($movementIds);
        }

        $db->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);

        $message = "Factura y movimientos asociados eliminados con éxito.";
    }
}

$invoices = $billing->getRecentInvoices(200);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturación - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
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

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 3px;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#020617] text-slate-800 dark:text-slate-200 antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-20 flex items-center justify-between px-8 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-[#020617]/80 backdrop-blur-xl z-20">
                <div class="flex items-center gap-4">
                    <a href="configuration.php"
                        class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 transition-all text-slate-400">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <div>
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-xl uppercase tracking-tight leading-none">
                            ABM Facturación</h2>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">Control de
                            comprobantes y sincronización</p>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-6xl mx-auto space-y-8">

                    <?php if ($message): ?>
                        <div
                            class="bg-blue-500/10 border border-blue-500/20 text-blue-500 p-5 rounded-2xl flex items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
                            <span class="material-symbols-outlined">info</span>
                            <p class="text-xs font-black uppercase tracking-wide normal-case"><?php echo $message; ?></p>
                        </div>
                    <?php endif; ?>

                    <div
                        class="bg-white dark:bg-[#16202e]/70 border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
                        <div
                            class="p-8 border-b border-slate-200 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-white/5">
                            <h3 class="font-black text-xs tracking-widest text-slate-500 uppercase px-2">Facturas
                                Emitidas</h3>
                            <span
                                class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-500 text-[10px] font-black uppercase tracking-widest border border-blue-500/10">
                                <?php echo count($invoices); ?> comprobantes
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="bg-slate-50/50 dark:bg-[#101822]/50 text-[10px] uppercase text-slate-500 font-black tracking-widest border-b border-slate-200 dark:border-white/5">
                                        <th class="px-10 py-6">Punto y Número</th>
                                        <th class="px-10 py-6">Fecha Emisión</th>
                                        <th class="px-10 py-6">Cliente Entidad</th>
                                        <th class="px-10 py-6 text-right">Total Monto</th>
                                        <th class="px-10 py-6 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-all group">
                                            <td class="px-10 py-6">
                                                <span
                                                    class="font-mono font-black text-blue-500 text-sm"><?php echo $inv['invoice_number']; ?></span>
                                            </td>
                                            <td class="px-10 py-6 text-[10px] font-mono text-slate-500 font-bold uppercase">
                                                <?php echo date('d/m/Y', strtotime($inv['date'])); ?>
                                            </td>
                                            <td class="px-10 py-6">
                                                <span
                                                    class="text-xs font-black text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($inv['client_name']); ?></span>
                                            </td>
                                            <td
                                                class="px-10 py-6 text-right font-mono font-black text-slate-800 dark:text-white">
                                                $ <?php echo number_format($inv['total_amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-10 py-6 text-center">
                                                <button
                                                    onclick="confirmDelete(<?php echo $inv['id']; ?>, '<?php echo $inv['invoice_number']; ?>')"
                                                    class="size-10 rounded-2xl bg-red-500/5 text-red-500/20 group-hover:text-red-500 group-hover:bg-red-500/10 transition-all flex items-center justify-center mx-auto shadow-sm">
                                                    <span class="material-symbols-outlined text-xl">delete</span>
                                                </button>
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
        function confirmDelete(id, number) {
            Swal.fire({
                title: '<span class="text-lg font-black tracking-tighter uppercase">¿ELIMINAR FACTURA ' + number + '?</span>',
                html: '<div class="text-[11px] text-slate-500 font-bold uppercase leading-relaxed tracking-wider py-4 normal-case">Esta acción anulará el cargo en cuenta corriente y el ingreso en tesorería. No es reversible.</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'SÍ, ELIMINAR',
                cancelButtonText: 'CANCELAR',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#1e293b',
                customClass: {
                    popup: 'rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-2xl',
                    confirmButton: 'rounded-xl px-6 py-3 font-black text-[10px] uppercase tracking-widest',
                    cancelButton: 'rounded-xl px-6 py-3 font-black text-[10px] uppercase tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>