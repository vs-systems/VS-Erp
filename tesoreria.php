<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/treasury/Treasury.php';

use Vsys\Modules\Treasury\Treasury;

$treasury = new Treasury();
$totals = $treasury->getTotals();
$recentMovements = $treasury->getRecentMovements(50);
$balances = $treasury->getBalanceSummary();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesorería - VS System</title>
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
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase">Módulo de Tesorería</h2>
                </div>
                <div class="flex gap-2">
                    <button onclick="openMovementModal('Ingreso')"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-bold text-xs uppercase flex items-center gap-2 shadow-lg shadow-emerald-500/20">
                        <span class="material-symbols-outlined text-sm">add_circle</span> INGRESO (CAJA)
                    </button>
                    <button onclick="openMovementModal('Egreso')"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-xs uppercase flex items-center gap-2 shadow-lg shadow-red-500/20">
                        <span class="material-symbols-outlined text-sm">remove_circle</span> EGRESO / GASTO
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- Top Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Total Ingresos</p>
                            <h3 class="text-2xl font-black text-emerald-500 font-mono">
                                $<?php echo number_format($totals['total_in'], 2, ',', '.'); ?></h3>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-1">Total Egresos</p>
                            <h3 class="text-2xl font-black text-red-500 font-mono">
                                $<?php echo number_format($totals['total_out'], 2, ',', '.'); ?></h3>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest mb-1">Total Retenciones</p>
                            <h3 class="text-2xl font-black text-amber-500 font-mono">
                                $<?php echo number_format($totals['total_withholdings'], 2, ',', '.'); ?></h3>
                        </div>
                        <div
                            class="bg-white dark:bg-[#16202e] border-2 <?php echo $totals['net_cash'] >= 0 ? 'border-primary/30' : 'border-red-500/50'; ?> rounded-2xl p-6 shadow-xl <?php echo $totals['net_cash'] >= 0 ? 'shadow-primary/5 bg-gradient-to-br from-primary/5' : 'shadow-red-500/10 bg-gradient-to-br from-red-500/5'; ?> to-transparent">
                            <p class="text-[10px] font-bold <?php echo $totals['net_cash'] >= 0 ? 'text-primary' : 'text-red-500'; ?> uppercase tracking-widest mb-1">Saldo Neto Caja</p>
                            <h3 class="text-2xl font-black <?php echo $totals['net_cash'] >= 0 ? 'text-primary' : 'text-red-500'; ?> font-mono">
                                $<?php echo number_format($totals['net_cash'], 2, ',', '.'); ?></h3>
                        </div>
                    </div>

                    <!-- Balances by Method -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Disponibilidad por
                            Medio de Pago</h4>
                        <div class="flex flex-wrap gap-4">
                            <?php foreach ($balances as $b): ?>
                            <?php $isNegative = $b['balance'] < 0; ?>
                            <div class="px-4 py-3 <?php echo $isNegative ? 'bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700' : 'bg-slate-50 dark:bg-[#101822] border border-slate-100 dark:border-[#233348]'; ?> rounded-xl flex items-center gap-3">
                                <span class="material-symbols-outlined <?php echo $isNegative ? 'text-red-500' : 'text-slate-400'; ?> text-sm"><?php echo $isNegative ? 'warning' : 'account_balance_wallet'; ?></span>
                                <div class="flex-1">
                                    <p class="text-[9px] font-bold <?php echo $isNegative ? 'text-red-500' : 'text-slate-500'; ?> uppercase tracking-wider"><?php echo $b['payment_method']; ?></p>
                                    <p class="text-sm font-bold <?php echo $isNegative ? 'text-red-600 dark:text-red-400' : ''; ?>">
                                        $<?php echo number_format($b['balance'], 2, ',', '.'); ?></p>
                                    <?php if ($isNegative): ?>
                                        <p class="text-[9px] text-red-400 mt-0.5">⚠ Revisar movimientos</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Movements Table -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                        <div
                            class="px-6 py-4 border-b border-slate-200 dark:border-[#233348] flex justify-between items-center">
                            <h3 class="font-bold text-sm tracking-tight">Registro de Movimientos Recientes</h3>
                            <span
                                class="text-[10px] font-bold text-slate-400 uppercase"><?php echo count($recentMovements); ?>
                                MOVIMIENTOS</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="bg-slate-50 dark:bg-[#1c2a3b] text-[10px] uppercase text-slate-500 font-bold border-b border-slate-200 dark:border-[#233348]">
                                        <th class="px-6 py-4">Fecha</th>
                                        <th class="px-6 py-4 text-center">Tipo</th>
                                        <th class="px-6 py-4">Categoría</th>
                                        <th class="px-6 py-4">Medio</th>
                                        <th class="px-6 py-4">Notas / Referencia</th>
                                        <th class="px-6 py-4 text-right">Importe</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($recentMovements as $mov): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-[#1c2a3b]/50 transition-colors group">
                                            <td class="px-6 py-3 font-mono text-[10px] text-slate-500">
                                                <?php echo $mov['formatted_date']; ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <span
                                                    class="px-2 py-0.5 rounded text-[9px] font-bold <?php echo $mov['type'] === 'Ingreso' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30' : 'bg-red-100 text-red-600 dark:bg-red-900/30'; ?>">
                                                    <?php echo $mov['type']; ?>
                                                </span>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-xs font-bold text-slate-600 dark:text-slate-300 italic">
                                                <?php echo $mov['category']; ?>
                                            </td>
                                            <td class="px-6 py-3 text-[10px] text-slate-500 font-medium">
                                                <?php echo $mov['payment_method']; ?>
                                            </td>
                                            <td class="px-6 py-3 text-[11px] normal-case truncate max-w-xs"
                                                title="<?php echo htmlspecialchars($mov['notes']); ?>">
                                                <?php echo htmlspecialchars($mov['notes']); ?>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-right font-mono font-bold <?php echo $mov['type'] === 'Ingreso' ? 'text-emerald-500' : 'text-red-500'; ?>">
                                                $ <?php echo number_format($mov['amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <button
                                                    onclick="confirmDelete(<?php echo $mov['id']; ?>, '<?php echo htmlspecialchars($mov['notes']); ?>')"
                                                    class="size-8 rounded-lg bg-red-500/5 text-red-500/20 group-hover:text-red-500 group-hover:bg-red-500/10 transition-all flex items-center justify-center mx-auto"
                                                    title="Eliminar movimiento">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($recentMovements)): ?>
                                <div class="p-20 text-center text-slate-400">
                                    <span class="material-symbols-outlined text-5xl mb-4 opacity-20">receipt_long</span>
                                    <p class="text-xs font-bold tracking-widest uppercase">No hay movimientos registrados en
                                        tesorería</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Movement Modal -->
    <div id="movModal"
        class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div
            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl w-full max-w-md p-8 shadow-2xl scale-in-center">
            <h3 id="modalTitle" class="text-xl font-bold mb-6 dark:text-white text-slate-800 tracking-tight">REGISTRAR
                MOVIMIENTO</h3>
            <form id="movForm" class="space-y-4">
                <input type="hidden" name="action" value="add_movement">
                <input type="hidden" name="type" id="movType">

                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Importe
                        ($)</label>
                    <input type="number" step="0.01" name="amount" required
                        class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 font-mono font-bold text-lg focus:ring-2 focus:ring-primary outline-none text-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Categoría</label>
                        <select name="category"
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-primary outline-none">
                            <option value="Ventas">Ventas</option>
                            <option value="Servicios">Servicios</option>
                            <option value="Compras">Compras</option>
                            <option value="Gastos Generales">Gastos Generales</option>
                            <option value="Impuestos">Impuestos</option>
                            <option value="Sueldos">Sueldos</option>
                            <option value="Retiro Socios">Retiro Socios</option>
                            <option value="Retenciones">Retenciones</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Medio
                            de Pago</label>
                        <select name="payment_method"
                            class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-primary outline-none">
                            <option value="Efectivo">Efectivo (Caja)</option>
                            <option value="Banco">Banco / Transferencia</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Mercado Pago">Mercado Pago</option>
                            <option value="Retenciones">Retenciones</option>
                            <option value="Dólares">Caja Dólares</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Observaciones</label>
                    <textarea name="notes" rows="3"
                        class="w-full bg-slate-50 dark:bg-[#101822] border border-slate-200 dark:border-[#233348] rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-primary outline-none normal-case"
                        placeholder="Detalles del movimiento..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-[#233348] text-slate-500 font-bold text-xs hover:bg-slate-50 dark:hover:bg-[#1c2a3b]">CANCELAR</button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-primary text-white font-bold text-xs hover:scale-[1.02] transition-transform shadow-lg shadow-primary/20">CONFIRMAR</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMovementModal(type) {
            document.getElementById('movType').value = type;
            document.getElementById('modalTitle').innerText = type === 'Ingreso' ? 'REGISTRAR INGRESO' : 'REGISTRAR EGRESO / GASTO';
            document.getElementById('modalTitle').className = type === 'Ingreso' ? 'text-xl font-bold mb-6 text-emerald-500 tracking-tight' : 'text-xl font-bold mb-6 text-red-500 tracking-tight';
            document.getElementById('movModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('movModal').classList.add('hidden');
        }

        document.getElementById('movForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('ajax_treasury.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Movimiento Registrado', position: 'top-end', showConfirmButton: false, timer: 1500 });
                    location.reload();
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        };

        async function confirmDelete(id, notes) {
            Swal.fire({
                title: '¿ELIMINAR MOVIMIENTO?',
                text: notes ? `¿Desea eliminar: "${notes}"?` : '¿Desea eliminar este movimiento?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'SÍ, BORRAR',
                cancelButtonText: 'CANCELAR',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_movement');
                    formData.append('id', id);
                    const res = await fetch('ajax_treasury.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) location.reload();
                    else Swal.fire('Error', data.error, 'error');
                }
            });
        }
    </script>
</body>

</html>