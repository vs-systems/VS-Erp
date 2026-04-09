<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

$action = $_GET['action'] ?? '';
if ($action === 'new') {
    header("Location: cotizador.php");
    exit;
}

$view = $_GET['view'] ?? 'pendientes';
$cot = new Cotizador();
$allQuotes = $cot->getAllQuotations(500);

// Filtrar según vista (ignorando archivados y perdidos totalmente para limpiar esta vista contable)
$pedidos = array_filter($allQuotes, function ($q) use ($view) {
    if ($q['archived_at'] !== null) return false;
    if ($q['status'] === 'Perdido' || $q['status'] === 'rejected') return false;

    $isPaid = ($q['payment_status'] === 'Pagado');
    if ($view === 'pendientes') {
        return !$isPaid;
    } else {
        return $isPaid;
    }
});

usort($pedidos, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Sumatorias
$totalUSD = array_sum(array_column($pedidos, 'total_usd'));
$totalARS = array_sum(array_column($pedidos, 'total_ars'));

?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Pedidos — VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        .dark ::-webkit-scrollbar-track { background: #101822; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #233348; }
    </style>
</head>

<body class="bg-white dark:bg-[#020617] text-slate-800 dark:text-slate-200 antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header class="h-20 flex items-center justify-between px-8 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-[#020617]/80 backdrop-blur-xl z-20">
                <div class="flex items-center gap-4">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-purple-500/20 p-2 rounded-xl text-purple-500">
                        <span class="material-symbols-outlined text-2xl">receipt_long</span>
                    </div>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg tracking-tight leading-none uppercase">
                            Facturación » Pedidos
                        </h2>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">
                            Gestión simplificada de pagos y cobros
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="cotizador.php"
                        class="bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">add</span>
                        NUEVO PEDIDO
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <!-- Cabecera y Tabs -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                        <div>
                            <h1 class="text-3xl font-black dark:text-white text-slate-800 tracking-tighter uppercase mb-2">
                                <?php echo $view === 'pendientes' ? 'Pedidos Pendientes de Cobro' : 'Pedidos Cobrados'; ?>
                            </h1>
                            <div class="flex gap-4">
                                <a href="facturacion.php?view=pendientes" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?php echo $view === 'pendientes' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-primary'; ?>">Pendientes</a>
                                <a href="facturacion.php?view=cobrados" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?php echo $view === 'cobrados' ? 'bg-purple-500 text-white shadow-lg shadow-purple-500/20' : 'bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-purple-500'; ?>">Cobrados</a>
                            </div>
                        </div>

                        <!-- Resumen Financiero -->
                        <div class="flex gap-4">
                            <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-4 min-w-[150px] shadow-sm text-right">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Total USD (Referencia)</p>
                                <p class="text-xl font-black font-mono dark:text-white">U$S <?php echo number_format($totalUSD, 2); ?></p>
                            </div>
                            <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-4 min-w-[180px] shadow-sm text-right">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Total ARS</p>
                                <p class="text-xl font-black font-mono text-emerald-500">$ <?php echo number_format($totalARS, 2, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Pedidos -->
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                                    <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                        <th class="px-6 py-5">Fecha</th>
                                        <th class="px-6 py-5">Pedido</th>
                                        <th class="px-6 py-5">Cliente</th>
                                        <th class="px-6 py-5 text-right">Total ARS</th>
                                        <th class="px-6 py-5 text-center">Medio de Pago</th>
                                        <th class="px-6 py-5 text-center">Comprobante</th>
                                        <th class="px-6 py-5 text-center">Acción Rápida</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php if (empty($pedidos)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-16 text-center">
                                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">done_all</span>
                                                <p class="text-slate-400 text-sm font-medium">No hay pedidos en esta vista.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($pedidos as $q): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-all group">
                                            
                                            <td class="px-6 py-5">
                                                <div class="text-[11px] font-bold dark:text-slate-300 text-slate-600">
                                                    <?php echo date('d/m/Y', strtotime($q['created_at'])); ?>
                                                </div>
                                            </td>

                                            <td class="px-6 py-5">
                                                <span class="font-black dark:text-white text-slate-800 text-sm">
                                                    <?php echo $q['quote_number']; ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-5 whitespace-nowrap">
                                                <div class="text-[11px] font-black dark:text-slate-100 text-slate-800">
                                                    <?php echo $q['client_name']; ?>
                                                </div>
                                            </td>

                                            <td class="px-6 py-5 text-right">
                                                <span class="font-black text-sm dark:text-white text-slate-800 font-mono">
                                                    $ <?php echo number_format($q['total_ars'] ?? 0, 2, ',', '.'); ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-5 text-center">
                                                <?php if (!empty($q['payment_method'])): ?>
                                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest bg-slate-100 dark:bg-white/5 px-3 py-1 rounded-lg">
                                                        <?php echo $q['payment_method']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 italic">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-5 text-center">
                                                <?php 
                                                // Check for payment receipt attachment
                                                $db = Vsys\Lib\Database::getInstance();
                                                $docs = $db->query("SELECT id, file_path FROM documents WHERE entity_type='quotation' AND entity_id=? AND document_type='Payment Receipt'", [$q['quote_number']])->fetchAll();
                                                if (!empty($docs)): 
                                                    $doc = $docs[0];
                                                ?>
                                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank"
                                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-500/10 text-blue-500 text-[9px] font-black uppercase tracking-widest border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all">
                                                        <span class="material-symbols-outlined text-[14px]">visibility</span>
                                                        Ver Comprobante
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400">Sin archivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-5 text-center">
                                                <button onclick="togglePaymentStatus(<?php echo $q['id']; ?>, '<?php echo $view === 'pendientes' ? 'Pagado' : 'Pendiente'; ?>')"
                                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $view === 'pendientes' ? 'bg-purple-500/10 border border-purple-500/20 text-purple-500 hover:bg-purple-500 hover:text-white' : 'bg-slate-500/10 border border-slate-500/20 text-slate-500 hover:bg-slate-500 hover:text-white'; ?>">
                                                    <span class="material-symbols-outlined text-[16px]"><?php echo $view === 'pendientes' ? 'payments' : 'undo'; ?></span>
                                                    <?php echo $view === 'pendientes' ? 'Marcar Cobrado' : 'Deshacer'; ?>
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
        async function togglePaymentStatus(id, newStatus) {
            Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch('ajax_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, type: 'quotation', field: 'payment_status', value: newStatus })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }
    </script>
</body>
</html>