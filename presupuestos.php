<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

// Auto-migration for dispatch and logistics fields
try {
    $db = Vsys\Lib\Database::getInstance();
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_by VARCHAR(100) DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_at DATETIME DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS status ENUM('Pendiente', 'Aceptado', 'Perdido', 'En espera', 'rejected', 'ordered', 'draft', 'sent', 'accepted', 'expired') DEFAULT 'Pendiente' AFTER quote_number");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS archive_reason ENUM('Vendido', 'Suspendido', 'Rechazado') DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS dispatch_guide VARCHAR(120) DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS dispatch_file VARCHAR(255) DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS dispatched_at DATETIME DEFAULT NULL");
    $db->exec("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS dispatched_by VARCHAR(100) DEFAULT NULL");

    // Legacy mapping
    $db->exec("UPDATE quotations SET status = 'Aceptado' WHERE (status = 'Pendiente' OR status IS NULL) AND is_confirmed = 1");
    $db->exec("UPDATE quotations SET status = 'Perdido' WHERE status = 'rejected'");
} catch (Exception $e) { /* ignorar errores no críticos */ }

use Vsys\Modules\Cotizador\Cotizador;

$cot = new Cotizador();

// ── Filtros ──────────────────────────────────────────────────────
$search        = $_GET['search']        ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == '1';
$only_unpaid   = isset($_GET['only_unpaid'])   && $_GET['only_unpaid']   == '1';
$show_lost     = isset($_GET['show_lost'])     && $_GET['show_lost']     == '1';

$quotes = $cot->getAllQuotations(500);

// Filtro
$quotes = array_filter($quotes, function ($q) use ($search, $status_filter, $show_archived, $only_unpaid, $show_lost) {
    // Archivados
    if (!$show_archived && $q['archived_at'] !== null) return false;
    if ($show_archived  && $q['archived_at'] === null) return false;

    // Perdidos: solo mostrar si el checkbox está activado
    if (!$show_lost && ($q['status'] === 'Perdido' || $q['status'] === 'rejected')) return false;

    // Solo impagos
    if ($only_unpaid && $q['payment_status'] === 'Pagado') return false;

    // Búsqueda
    if ($search) {
        $s = strtolower($search);
        if (strpos(strtolower($q['client_name']), $s) === false &&
            strpos(strtolower($q['quote_number']), $s) === false) {
            return false;
        }
    }

    if ($status_filter && $q['status'] !== $status_filter) return false;

    return true;
});

// Ordenar: perdidos al fondo, impagos primero, luego por fecha desc
usort($quotes, function ($a, $b) {
    $aLost = in_array($a['status'], ['Perdido', 'rejected']);
    $bLost = in_array($b['status'], ['Perdido', 'rejected']);
    if ($aLost !== $bLost) return $aLost ? 1 : -1;
    if ($a['payment_status'] !== 'Pagado' && $b['payment_status'] === 'Pagado') return -1;
    if ($a['payment_status'] === 'Pagado' && $b['payment_status'] !== 'Pagado') return 1;
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos — VS System</title>
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
        .normal-case { text-transform: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        .dark ::-webkit-scrollbar-track { background: #101822; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #233348; }
        tr.is-lost td { background: rgba(239, 68, 68, .04); }
        tr.is-lost:hover td { background: rgba(239, 68, 68, .07) !important; }
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
                    <div class="bg-primary/20 p-2 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">history</span>
                    </div>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg tracking-tight leading-none">
                            Histórico de Pedidos
                        </h2>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">
                            Registro histórico · Gestión de estados
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
                <div class="max-w-[1500px] mx-auto space-y-6">

                    <!-- Filtros -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <h1 class="text-2xl font-black dark:text-white text-slate-800 tracking-tighter">
                            Operaciones
                            <span class="text-sm font-medium text-slate-400 ml-2"><?php echo count($quotes); ?> registros</span>
                        </h1>

                        <form method="GET" class="flex flex-wrap items-center gap-2 bg-white dark:bg-white/5 p-2 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm">
                            <!-- Buscador -->
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Cliente / referencia"
                                    class="pl-9 pr-4 py-2 bg-slate-100 dark:bg-white/5 border-none rounded-xl text-xs font-medium w-44 focus:ring-2 focus:ring-primary transition-all normal-case">
                            </div>

                            <!-- Estado -->
                            <select name="status_filter" class="bg-slate-100 dark:bg-white/5 border-none rounded-xl text-xs font-bold px-3 py-2 focus:ring-2 focus:ring-primary transition-all">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente" <?php echo $status_filter === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="Aceptado"  <?php echo $status_filter === 'Aceptado'  ? 'selected' : ''; ?>>Aceptado</option>
                                <option value="En espera" <?php echo $status_filter === 'En espera' ? 'selected' : ''; ?>>En espera</option>
                                <option value="Perdido"   <?php echo $status_filter === 'Perdido'   ? 'selected' : ''; ?>>Perdido</option>
                            </select>

                            <!-- Checkboxes -->
                            <label class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-all">
                                <input type="checkbox" name="only_unpaid" value="1" <?php echo $only_unpaid ? 'checked' : ''; ?>
                                    class="rounded border-slate-300 dark:border-white/10 text-primary focus:ring-primary bg-transparent">
                                <span class="text-[10px] font-bold text-slate-500">SOLO IMPAGOS</span>
                            </label>

                            <label class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-all">
                                <input type="checkbox" name="show_lost" value="1" <?php echo $show_lost ? 'checked' : ''; ?>
                                    class="rounded border-red-300 text-red-500 focus:ring-red-500 bg-transparent">
                                <span class="text-[10px] font-bold text-red-400">INCLUIR PERDIDOS</span>
                            </label>

                            <label class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-all">
                                <input type="checkbox" name="show_archived" value="1" <?php echo $show_archived ? 'checked' : ''; ?>
                                    class="rounded border-slate-300 dark:border-white/10 text-slate-500 focus:ring-slate-500 bg-transparent">
                                <span class="text-[10px] font-bold text-slate-500">CANALIZADOS</span>
                            </label>

                            <button type="submit" class="bg-primary text-white p-2 rounded-xl hover:bg-blue-600 transition-all shadow-lg shadow-primary/20">
                                <span class="material-symbols-outlined text-sm">filter_alt</span>
                            </button>

                            <?php if ($search || $status_filter || $show_archived || $only_unpaid || $show_lost): ?>
                                <a href="presupuestos.php" class="text-slate-400 hover:text-red-500 p-2 transition-all" title="Limpiar Filtros">
                                    <span class="material-symbols-outlined text-sm">filter_alt_off</span>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Tabla -->
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                                    <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                        <th class="px-6 py-5">Fecha</th>
                                        <th class="px-6 py-5">Referencia</th>
                                        <th class="px-6 py-5">Cliente</th>
                                        <th class="px-6 py-5 text-right">Total USD</th>
                                        <th class="px-6 py-5 text-right">Total ARS</th>
                                        <th class="px-6 py-5 text-center">Estado</th>
                                        <th class="px-6 py-5 text-center">Pago</th>
                                        <th class="px-6 py-5 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php if (empty($quotes)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-16 text-center">
                                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">inbox</span>
                                                <p class="text-slate-400 text-sm font-medium">No se encontraron pedidos con los filtros aplicados.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($quotes as $q):
                                        $isLost = in_array($q['status'] ?? '', ['Perdido', 'rejected']);
                                    ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-all group <?php echo $isLost ? 'is-lost' : ''; ?>">

                                            <!-- Fecha -->
                                            <td class="px-6 py-5">
                                                <div class="text-[11px] font-bold dark:text-slate-300 text-slate-600">
                                                    <?php echo date('d/m/Y', strtotime($q['created_at'])); ?>
                                                </div>
                                                <div class="text-[9px] font-black text-slate-400 tracking-widest">
                                                    <?php echo date('H:i', strtotime($q['created_at'])); ?>
                                                </div>
                                            </td>

                                            <!-- Referencia -->
                                            <td class="px-6 py-5">
                                                <span class="font-black dark:text-white text-slate-800 group-hover:text-primary transition-colors text-sm">
                                                    <?php echo $q['quote_number']; ?>
                                                </span>
                                            </td>

                                            <!-- Cliente -->
                                            <td class="px-6 py-5 whitespace-nowrap">
                                                <div class="text-[11px] font-black dark:text-slate-100 text-slate-800">
                                                    <?php echo $q['client_name']; ?>
                                                </div>
                                            </td>

                                            <!-- Total USD -->
                                            <td class="px-6 py-5 text-right font-mono text-sm dark:text-white text-slate-800 font-black">
                                                $ <?php echo number_format($q['total_usd'] ?? 0, 2); ?>
                                            </td>

                                            <!-- Total ARS -->
                                            <td class="px-6 py-5 text-right font-mono text-[11px] text-slate-500 font-bold">
                                                $ <?php echo number_format($q['total_ars'] ?? 0, 2, ',', '.'); ?>
                                            </td>

                                            <!-- Estado Comercial -->
                                            <td class="px-6 py-5 text-center">
                                                <?php if ($q['archived_at']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-500/10 text-slate-500 text-[9px] font-black uppercase tracking-widest border border-slate-500/20">
                                                        <span class="material-symbols-outlined text-[12px]">inventory_2</span>
                                                        Canalizado
                                                    </span>
                                                <?php elseif ($isLost): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-500/10 text-red-500 text-[9px] font-black uppercase tracking-widest border border-red-500/20">
                                                        <span class="material-symbols-outlined text-[12px] fill-1">cancel</span>
                                                        Perdido
                                                    </span>
                                                <?php elseif (($q['status'] ?? '') === 'En espera'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-500 text-[9px] font-black uppercase tracking-widest border border-amber-500/20">
                                                        <span class="material-symbols-outlined text-[12px]">pause_circle</span>
                                                        En Espera
                                                    </span>
                                                <?php elseif ($q['is_confirmed']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-green-500/10 text-green-500 text-[9px] font-black uppercase tracking-widest border border-green-500/20">
                                                        <span class="material-symbols-outlined text-[12px] fill-1">verified</span>
                                                        Confirmado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-500/10 text-slate-500 text-[9px] font-black uppercase tracking-widest border border-slate-500/10">
                                                        <span class="material-symbols-outlined text-[12px]">draft</span>
                                                        Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Pago -->
                                            <td class="px-6 py-5 text-center">
                                                <?php if (($q['payment_status'] ?? '') === 'Pagado'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-500/10 text-purple-400 text-[9px] font-black uppercase border border-purple-500/20">
                                                        <span class="material-symbols-outlined text-[12px] fill-1">payments</span>
                                                        Pagado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-white/5 text-slate-400 text-[9px] font-black uppercase border border-slate-200 dark:border-white/5">
                                                        <span class="material-symbols-outlined text-[12px]">payments</span>
                                                        Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Acciones -->
                                            <td class="px-6 py-5">
                                                <div class="flex items-center justify-center gap-0.5">

                                                    <!-- Ver / Imprimir -->
                                                    <button onclick="openQuote(<?php echo $q['id']; ?>, <?php echo $q['client_id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-primary transition-all" title="Ver / Imprimir">
                                                        <span class="material-symbols-outlined text-lg">print</span>
                                                    </button>

                                                    <!-- Editar -->
                                                    <a href="cotizador.php?id=<?php echo $q['id']; ?>"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-emerald-500 transition-all" title="Editar">
                                                        <span class="material-symbols-outlined text-lg">edit</span>
                                                    </a>

                                                    <!-- Resumen -->
                                                    <a href="resumen_pedido.php?id=<?php echo $q['id']; ?>"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-blue-500 transition-all" title="Historial de cambios">
                                                        <span class="material-symbols-outlined text-lg">history_edu</span>
                                                    </a>

                                                    <!-- Confirmar -->
                                                    <button onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'is_confirmed', <?php echo $q['is_confirmed'] ? 0 : 1; ?>)"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $q['is_confirmed'] ? 'text-green-500' : 'text-slate-400'; ?>"
                                                        title="<?php echo $q['is_confirmed'] ? 'Desmarcar' : 'Confirmar pedido'; ?>">
                                                        <span class="material-symbols-outlined text-lg <?php echo $q['is_confirmed'] ? 'fill-1' : ''; ?>">check_circle</span>
                                                    </button>

                                                    <!-- Marcar Perdido -->
                                                    <button onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'status', '<?php echo $isLost ? 'Pendiente' : 'Perdido'; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo $isLost ? 'text-red-500' : 'text-slate-400 hover:text-red-500'; ?>"
                                                        title="<?php echo $isLost ? 'Recuperar' : 'Marcar como Perdido'; ?>">
                                                        <span class="material-symbols-outlined text-lg <?php echo $isLost ? 'fill-1' : ''; ?>">cancel</span>
                                                    </button>

                                                    <!-- En Espera -->
                                                    <button onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'status', 'En espera')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-amber-500 transition-all" title="Poner en Espera">
                                                        <span class="material-symbols-outlined text-lg">pause_circle</span>
                                                    </button>

                                                    <!-- Subir Pago -->
                                                    <button onclick="openPaymentUpload(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-purple-500 transition-all" title="Subir comprobante de pago">
                                                        <span class="material-symbols-outlined text-lg">upload_file</span>
                                                    </button>

                                                    <!-- Autorizar Logística -->
                                                    <?php if (($q['payment_status'] ?? '') !== 'Pagado' && empty($q['logistics_authorized_by'])): ?>
                                                        <button onclick="openAuthModal(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                            class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-amber-500 transition-all" title="Autorizar envío sin cobro">
                                                            <span class="material-symbols-outlined text-lg">verified_user</span>
                                                        </button>
                                                    <?php elseif (!empty($q['logistics_authorized_by'])): ?>
                                                        <span class="p-2 text-amber-500" title="Autorizado por: <?php echo $q['logistics_authorized_by']; ?>">
                                                            <span class="material-symbols-outlined text-lg fill-1">verified_user</span>
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- Toggle Pago -->
                                                    <button onclick="toggleStatus(<?php echo $q['id']; ?>, 'quotation', 'payment_status', '<?php echo ($q['payment_status'] ?? '') === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all <?php echo ($q['payment_status'] ?? '') === 'Pagado' ? 'text-purple-500' : 'text-slate-400'; ?>"
                                                        title="Estado Pago: <?php echo $q['payment_status'] ?? 'Pendiente'; ?>">
                                                        <span class="material-symbols-outlined text-lg <?php echo ($q['payment_status'] ?? '') === 'Pagado' ? 'fill-1' : ''; ?>">payments</span>
                                                    </button>

                                                    <!-- Email -->
                                                    <button onclick="sendEmail(<?php echo $q['id']; ?>)"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-blue-500 transition-all" title="Enviar Email">
                                                        <span class="material-symbols-outlined text-lg">mail</span>
                                                    </button>

                                                    <!-- Archivar -->
                                                    <button onclick="openArchiveModal(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-orange-500 transition-all" title="Canalizar operación">
                                                        <span class="material-symbols-outlined text-lg">archive</span>
                                                    </button>

                                                    <!-- Eliminar -->
                                                    <button onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo $q['quote_number']; ?>')"
                                                        class="p-2 rounded-xl hover:bg-red-500/10 text-red-500/20 hover:text-red-500 transition-all" title="Eliminar">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </div>
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

    <!-- Modal: Autorizar Logística -->
    <div id="authModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-white/5 rounded-3xl w-full max-w-md p-8 shadow-2xl">
            <h3 class="text-xl font-black mb-4 dark:text-white text-slate-800 tracking-tight">Autorizar Envío sin Cobro</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">Pedido <span id="authQuoteNumber" class="text-primary font-black normal-case"></span></p>
            <form id="authForm" class="space-y-4">
                <input type="hidden" name="id" id="authQuoteId">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Responsable de autorización</label>
                    <input type="text" name="authorized_by" required placeholder="Ej: Autoriza Javier"
                        class="w-full bg-slate-50 dark:bg-[#020617] border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 font-bold text-sm focus:ring-2 focus:ring-primary outline-none transition-all normal-case">
                </div>
                <div class="flex gap-4 pt-2">
                    <button type="button" onclick="closeAuthModal()"
                        class="flex-1 py-4 rounded-2xl border border-slate-200 dark:border-white/5 text-slate-500 font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-white/5 transition-all">Cancelar</button>
                    <button type="submit"
                        class="flex-1 py-4 rounded-2xl bg-amber-500 text-white font-black text-xs uppercase tracking-widest hover:scale-[1.02] transition-transform shadow-xl shadow-amber-500/30">Autorizar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Subir Comprobante de Pago -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[110] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-white/5 rounded-3xl w-full max-w-md p-8 shadow-2xl">
            <h3 class="text-xl font-black mb-1 dark:text-white text-slate-800 tracking-tight">Subir Comprobante</h3>
            <p id="modalQuoteNumber" class="text-[10px] font-bold text-primary uppercase tracking-widest mb-6"></p>
            <form id="paymentUploadForm" class="space-y-4">
                <input type="hidden" name="quote_number" id="uploadQuoteNumber">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Medio de Pago</label>
                    <select name="payment_method" required class="w-full bg-slate-50 dark:bg-[#020617] border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-3 font-bold text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                        <option value="Transferencia">Transferencia Bancaria</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Mercado Pago">Mercado Pago</option>
                        <option value="Retenciones">Retenciones</option>
                    </select>
                </div>
                <div class="border-2 border-dashed border-slate-200 dark:border-white/5 rounded-3xl p-6 text-center hover:border-primary/50 transition-colors cursor-pointer"
                    onclick="document.getElementById('paymentFile').click()">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-3">cloud_upload</span>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Clic para seleccionar archivo</p>
                    <p class="text-[9px] text-slate-400 mt-1">PDF, JPG o PNG</p>
                    <input type="file" name="payment_file" id="paymentFile" class="hidden" accept=".pdf,image/*" onchange="updateFileName(this)">
                    <div id="fileNameDisplay" class="mt-4 text-[10px] font-mono text-primary font-bold break-all"></div>
                </div>
                <div class="flex gap-4 pt-2">
                    <button type="button" onclick="closePaymentModal()"
                        class="flex-1 py-4 rounded-2xl border border-slate-200 dark:border-white/5 text-slate-500 font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-white/5 transition-all">Cancelar</button>
                    <button type="submit"
                        class="flex-1 py-4 rounded-2xl bg-primary text-white font-black text-xs uppercase tracking-widest hover:scale-[1.02] transition-transform shadow-xl shadow-primary/30">Subir</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ── Helpers ──────────────────────────────────────────────
        function openQuote(id, entityId, quoteNo) {
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entity_id: entityId, type: 'Email/PDF', description: `Visualización pedido ${quoteNo}` })
            });
            window.open('imprimir_cotizacion.php?id=' + id, '_blank');
        }

        async function toggleStatus(id, type, field, val) {
            const res  = await fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, type, field, value: val })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert('Error: ' + data.error);
        }

        async function deleteQuote(id, number) {
            const confirm = await Swal.fire({
                title: '¿Eliminar pedido?',
                text: `Se eliminará el comprobante ${number}. Esta acción no se puede deshacer.`,
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
            });
            if (!confirm.isConfirmed) return;
            const res  = await fetch('ajax_delete_quotation.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert('Error: ' + data.error);
        }

        async function sendEmail(id) {
            const confirm = await Swal.fire({
                title: '¿Enviar por email?', text: 'Se enviará el pedido en PDF al cliente.',
                icon: 'question', showCancelButton: true,
                confirmButtonColor: '#136dec', confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
            });
            if (!confirm.isConfirmed) return;
            Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            try {
                const res  = await fetch('ajax_send_email.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'quotation', id })
                });
                const data = await res.json();
                if (data.success) Swal.fire('Enviado', 'El correo se envió con éxito.', 'success');
                else Swal.fire('Error', data.error, 'error');
            } catch (e) { Swal.fire('Error', 'Error de conexión', 'error'); }
        }

        // ── Auth Modal ───────────────────────────────────────────
        function openAuthModal(id, number) {
            document.getElementById('authQuoteId').value  = id;
            document.getElementById('authQuoteNumber').innerText = number;
            document.getElementById('authModal').classList.remove('hidden');
        }
        function closeAuthModal() { document.getElementById('authModal').classList.add('hidden'); }
        document.getElementById('authForm').onsubmit = async (e) => {
            e.preventDefault();
            const res  = await fetch('ajax_authorize_logistics.php', { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Envío autorizado', text: 'El pedido es visible en logística.', timer: 1500, showConfirmButton: false });
                location.reload();
            } else Swal.fire('Error', data.error, 'error');
        };

        // ── Payment Modal ────────────────────────────────────────
        function openPaymentUpload(id, quoteNo) {
            document.getElementById('modalQuoteNumber').innerText  = 'Pedido: ' + quoteNo;
            document.getElementById('uploadQuoteNumber').value     = quoteNo;
            document.getElementById('paymentModal').classList.remove('hidden');
        }
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentUploadForm').reset();
            document.getElementById('fileNameDisplay').innerText = '';
        }
        function updateFileName(input) {
            if (input.files && input.files[0])
                document.getElementById('fileNameDisplay').innerText = input.files[0].name;
        }
        document.getElementById('paymentUploadForm').onsubmit = async (e) => {
            e.preventDefault();
            Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            try {
                const res  = await fetch('ajax_upload_payment.php', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Comprobante guardado', timer: 1500, showConfirmButton: false });
                    closePaymentModal();
                    setTimeout(() => location.reload(), 1500);
                } else Swal.fire('Error', data.error, 'error');
            } catch (err) { Swal.fire('Error', 'Error de conexión', 'error'); }
        };

        // ── Archive Modal ────────────────────────────────────────
        function openArchiveModal(id, number) {
            Swal.fire({
                title: 'Canalizar operación',
                html: `<div class="text-left space-y-4 pt-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase mb-2 block">Motivo</label>
                        <select id="archiveReason" class="w-full bg-slate-100 dark:bg-white/10 border-none rounded-xl text-sm font-bold p-3 focus:ring-2 focus:ring-primary">
                            <option value="Vendido">VENDIDO</option>
                            <option value="Suspendido">SUSPENDIDO</option>
                            <option value="Rechazado">RECHAZADO</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase mb-2 block">Observaciones</label>
                        <textarea id="archiveDesc" placeholder="Detalles adicionales..." class="w-full bg-slate-100 dark:bg-white/10 border-none rounded-xl text-sm p-3 h-24 focus:ring-2 focus:ring-primary"></textarea>
                    </div></div>`,
                icon: 'info', showCancelButton: true,
                confirmButtonColor: '#136dec', confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#16202e' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000',
                preConfirm: () => ({
                    reason: document.getElementById('archiveReason').value,
                    desc:   document.getElementById('archiveDesc').value
                })
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                Swal.fire({ title: 'Canalizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                const res  = await fetch('ajax_update_status.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id, type: 'quotation',
                        fields: {
                            archived_at:         new Date().toISOString().slice(0, 19).replace('T', ' '),
                            archive_reason:      result.value.reason,
                            archive_description: result.value.desc,
                            status:              result.value.reason === 'Vendido' ? 'accepted' : 'rejected'
                        }
                    })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Canalizado', timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1500);
                } else Swal.fire('Error', data.error, 'error');
            });
        }
    </script>
</body>
</html>