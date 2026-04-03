<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/ProviderAccounts.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Modules\Billing\ProviderAccounts;
use Vsys\Lib\Database;

$providerId = $_GET['id'] ?? null;
if (!$providerId) {
    header('Location: cuentas_corrientes_proveedores.php');
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM entities WHERE id = ?");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    die("Proveedor no encontrado");
}

$providerAccounts = new ProviderAccounts();
$movements = $providerAccounts->getMovements($providerId, 100);
$balance = $providerAccounts->getBalance($providerId);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Cuenta Corriente Proveedor -
        <?php echo htmlspecialchars($provider['name']); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
        }

        .normal-case {
            text-transform: none;
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]">
                <div class="flex items-center gap-3">
                    <a href="cuentas_corrientes_proveedores.php"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-[#1c2a3b] transition-colors">
                        <span class="material-symbols-outlined text-slate-400">arrow_back</span>
                    </a>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase">
                            <?php echo htmlspecialchars($provider['name']); ?>
                        </h2>
                        <p class="text-[10px] text-slate-500 uppercase font-bold">Cuenta Corriente Proveedor #
                            <?php echo $provider['id']; ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="openPaymentModal()"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase flex items-center gap-2 shadow-lg shadow-amber-500/20">
                        <span class="material-symbols-outlined text-sm">payments</span>
                        Registrar Pago
                    </button>
                    <div class="text-right">
                        <p class="text-[9px] text-slate-500 uppercase font-bold">Saldo Pendiente</p>
                        <p
                            class="text-xl font-bold <?php echo $balance > 0 ? 'text-amber-500' : 'text-green-500'; ?> font-mono">
                            $
                            <?php echo number_format($balance, 2, ',', '.'); ?>
                        </p>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto">
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="bg-slate-50 dark:bg-[#1c2a3b] text-[10px] uppercase text-slate-500 font-bold border-b border-slate-200 dark:border-[#233348]">
                                    <th class="px-6 py-4">Fecha</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Concepto</th>
                                    <th class="px-6 py-4 text-right">Haber (Debo)</th>
                                    <th class="px-6 py-4 text-right">Debe (Pagado)</th>
                                    <th class="px-6 py-4 text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                <?php foreach ($movements as $mov): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b]/50 transition-colors">
                                        <td class="px-6 py-3 text-[10px] font-mono text-slate-500">
                                            <?php echo $mov['formatted_date']; ?>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span
                                                class="px-2 py-1 rounded text-[9px] font-bold uppercase
                                            <?php echo ($mov['debit'] > 0) ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30' : 'bg-green-100 text-green-600 dark:bg-green-900/30'; ?>">
                                                <?php echo $mov['type']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-[11px]">
                                            <?php echo htmlspecialchars($mov['notes']); ?>
                                        </td>
                                        <td class="px-6 py-3 text-right text-xs text-slate-500 font-mono">
                                            <?php echo $mov['debit'] > 0 ? '$ ' . number_format($mov['debit'], 2, ',', '.') : '-'; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right text-xs text-slate-500 font-mono">
                                            <?php echo $mov['credit'] > 0 ? '$ ' . number_format($mov['credit'], 2, ',', '.') : '-'; ?>
                                        </td>
                                        <td
                                            class="px-6 py-3 text-right font-bold text-xs <?php echo $mov['balance'] > 0 ? 'text-amber-500' : 'text-green-500'; ?> font-mono">
                                            $
                                            <?php echo number_format($mov['balance'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($movements)): ?>
                            <div class="p-10 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl mb-2">inventory</span>
                                <p class="text-xs font-bold uppercase tracking-widest">Sin movimientos registrados</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div
            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
            <h3 class="text-lg font-bold mb-4 dark:text-white text-slate-800 uppercase tracking-tight">Registrar Pago a
                Proveedor</h3>
            <form id="paymentForm" onsubmit="submitPayment(event)">
                <input type="hidden" name="action" value="register_provider_payment">
                <input type="hidden" name="provider_id" value="<?php echo $providerId; ?>">

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5 ml-1">Monto Pagado
                            ($)</label>
                        <input type="number" step="0.01" name="amount" required
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 font-bold text-lg focus:ring-2 focus:ring-amber-500 outline-none text-amber-500 font-mono">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5 ml-1">Observaciones</label>
                        <textarea name="notes" rows="3"
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-amber-500 outline-none normal-case"
                            placeholder="Ej: Pago factura 123..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closePaymentModal()"
                            class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-[#233348] text-slate-500 font-bold text-[10px] tracking-widest hover:bg-slate-50 dark:hover:bg-[#1c2a3b] uppercase">CANCELAR</button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-amber-600 text-white font-bold text-[10px] tracking-widest hover:bg-amber-700 shadow-lg shadow-amber-500/20 uppercase">CONFIRMAR</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal() { document.getElementById('paymentModal').classList.remove('hidden'); }
        function closePaymentModal() { document.getElementById('paymentModal').classList.add('hidden'); }

        async function submitPayment(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const res = await fetch('ajax_billing.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Pago Registrado', confirmButtonColor: '#d97706' }).then(() => location.reload());
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message });
            }
        }
    </script>
</body>

</html>