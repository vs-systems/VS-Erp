<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/billing/CurrentAccounts.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Modules\Billing\CurrentAccounts;
use Vsys\Lib\Database;

$clientId = $_GET['id'] ?? null;
if (!$clientId) {
    header('Location: cuentas_corrientes.php');
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM entities WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    die("Cliente no encontrado");
}

$currentAccounts = new CurrentAccounts();
$movements = $currentAccounts->getMovements($clientId, 100);
$balance = $currentAccounts->getBalance($clientId);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Cuenta Corriente -
        <?php echo htmlspecialchars($client['name']); ?>
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
                    <a href="cuentas_corrientes.php"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-[#1c2a3b] transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase">
                            <?php echo htmlspecialchars($client['name']); ?>
                        </h2>
                        <p class="text-xs text-slate-500 uppercase">Cuenta Corriente #
                            <?php echo $client['id']; ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="openReceiptModal()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm uppercase flex items-center gap-2">
                        <span class="material-symbols-outlined">payments</span>
                        Registrar Cobro
                    </button>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-500 uppercase font-bold">Saldo Actual</p>
                        <p class="text-xl font-bold <?php echo $balance > 0 ? 'text-red-500' : 'text-green-500'; ?>">
                            $
                            <?php echo number_format($balance, 2, ',', '.'); ?>
                        </p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto">
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="bg-slate-50 dark:bg-[#1c2a3b] text-xs uppercase text-slate-500 font-bold border-b border-slate-200 dark:border-[#233348]">
                                    <th class="px-6 py-4">Fecha</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Concepto / Notas</th>
                                    <th class="px-6 py-4 text-right">Debe</th>
                                    <th class="px-6 py-4 text-right">Haber</th>
                                    <th class="px-6 py-4 text-right">Total Acumulado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                <?php foreach ($movements as $mov): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b]/50 transition-colors">
                                        <td class="px-6 py-3 text-sm font-mono text-slate-500">
                                            <?php echo $mov['formatted_date']; ?>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span
                                                class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                            <?php echo ($mov['debit'] > 0) ? 'bg-red-100 text-red-600 dark:bg-red-900/30' : 'bg-green-100 text-green-600 dark:bg-green-900/30'; ?>">
                                                <?php echo $mov['type']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            <?php echo htmlspecialchars($mov['notes']); ?>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm text-slate-500">
                                            <?php echo $mov['debit'] > 0 ? '$ ' . number_format($mov['debit'], 2, ',', '.') : '-'; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm text-slate-500">
                                            <?php echo $mov['credit'] > 0 ? '$ ' . number_format($mov['credit'], 2, ',', '.') : '-'; ?>
                                        </td>
                                        <td
                                            class="px-6 py-3 text-right font-bold text-sm <?php echo $mov['balance'] > 0 ? 'text-red-500' : 'text-green-500'; ?>">
                                            $
                                            <?php echo number_format($mov['balance'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($movements)): ?>
                            <div class="p-10 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl mb-2">savings</span>
                                <p>No hay movimientos registrados en esta cuenta.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
        <div
            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-xl font-bold mb-4 dark:text-white text-slate-800">Registrar Cobro</h3>
            <form id="receiptForm" onsubmit="submitReceipt(event)">
                <input type="hidden" name="action" value="register_receipt">
                <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Monto Recibido ($)</label>
                        <input type="number" step="0.01" name="amount" required
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-lg px-4 py-2 font-bold text-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Notas / Concepto</label>
                        <textarea name="notes" rows="2"
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none"
                            placeholder="Ej: Pago parcial factura 001..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeReceiptModal()"
                            class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-[#233348] text-slate-500 font-bold hover:bg-slate-50 dark:hover:bg-[#1c2a3b]">CANCELAR</button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 shadow-lg shadow-green-500/20">CONFIRMAR
                            COBRO</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReceiptModal() {
            document.getElementById('receiptModal').classList.remove('hidden');
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').classList.add('hidden');
        }

        async function submitReceipt(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const res = await fetch('ajax_billing.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cobro Registrado',
                        text: 'El saldo del cliente ha sido actualizado.',
                        confirmButtonColor: '#10b981'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            }
        }
    </script>
</body>

</html>