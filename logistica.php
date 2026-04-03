<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';
use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();
$view = $_GET['view'] ?? 'armado';
$allOrders = $logistics->getOrdersForPreparation();
$transports = $logistics->getTransports();
$pendingCount = count(array_filter($allOrders, function ($q) {
    return ($q['payment_status'] !== 'Pagado' && $q['logistics_authorized_by'] === null && $q['archived_at'] === null);
}));
$pending = $pendingCount; // Legacy support for the variable name in some versions


// Logic for categorization:
// Pendientes: Confirmed but NOT paid/authorized.
// En Armado: Paid or Authorized.
// Archivados: Archived.

$orders = array_filter($allOrders, function ($q) use ($view) {
    $isArchived = ($q['archived_at'] !== null);
    $isPaidOrAuth = ($q['payment_status'] === 'Pagado' || $q['logistics_authorized_by'] !== null);

    if ($view === 'archivados') {
        return $isArchived;
    }

    if ($isArchived)
        return false; // Hide archived from other views

    if ($view === 'pendientes') {
        return !$isPaidOrAuth;
    }

    // Default view: armado
    return $isPaidOrAuth;
});

// Map phases to colors and icons (Material Symbols)
$phases = [
    'En reserva' => ['color' => '#f59e0b', 'icon' => 'schedule', 'label' => 'En Reserva'],
    'En preparación' => ['color' => '#3b82f6', 'icon' => 'engineering', 'label' => 'En Preparación'],
    'Disponible' => ['color' => '#10b981', 'icon' => 'check_circle', 'label' => 'Disponible'],
    'En su transporte' => ['color' => '#8b5cf6', 'icon' => 'local_shipping', 'label' => 'En Transporte'],
    'Entregado' => ['color' => '#64748b', 'icon' => 'flag', 'label' => 'Entregado']
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logística Premium - VS System</title>
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
                        "background-dark": "#101822",
                        "surface-dark": "#16202e",
                        "surface-border": "#233348",
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

        .phase-badge {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
        }

        .cost-input {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            padding: 4px 8px;
            border-radius: 6px;
            width: 70px;
            font-size: 0.8rem;
            outline: none;
            transition: all 0.2s;
        }

        .dark .cost-input {
            background: #0f172a;
            border: 1px solid #233348;
            color: white;
        }

        .cost-input:focus {
            border-color: #136dec;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">local_shipping</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Gestión
                        Logística</h2>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="max-w-[1400px] mx-auto space-y-6">

                    <div class="flex justify-between items-end">
                        <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Centro de
                            Operaciones Logísticas</h1>
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] px-6 py-3 rounded-xl flex flex-col items-center shadow-sm dark:shadow-none transition-colors">
                            <span class="text-xl font-bold text-[#f59e0b]"><?php echo count($orders); ?></span>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-[#101822]/50 transition-colors">
                                    <tr class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                        <th class="px-6 py-6">Pedido</th>
                                        <th class="px-6 py-6 text-center">Estado Pago</th>
                                        <th class="px-6 py-6 text-center">
                                            <?php echo $view === 'archivados' ? 'Fecha Archivo' : 'Fase Logística'; ?>
                                        </th>
                                        <th class="px-6 py-6 text-center">Transporte Sugerido</th>
                                        <th class="px-6 py-6 text-right">Costos Logísticos</th>
                                        <th class="px-6 py-6 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348] transition-colors">
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-20 text-center">
                                                <div class="flex flex-col items-center gap-2 opacity-30">
                                                    <span class="material-symbols-outlined text-6xl">inventory_2</span>
                                                    <p class="font-black uppercase tracking-widest text-[10px]">No hay
                                                        pedidos en esta sección</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($orders as $p): ?>
                                        <?php
                                        $currentPhase = $p['current_phase'] ?? 'En reserva';
                                        $phaseInfo = $phases[$currentPhase] ?? $phases['En reserva'];
                                        $isPaid = ($p['payment_status'] === 'Pagado');
                                        ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-6 min-w-[180px]">
                                                <button onclick="verDetallesPedido('<?php echo $p['quote_number']; ?>')"
                                                    class="font-extrabold dark:text-white text-slate-800 hover:text-[#136dec] transition-colors flex items-center gap-1 group/link">
                                                    <?php echo $p['quote_number']; ?>
                                                    <span
                                                        class="material-symbols-outlined text-xs opacity-0 group-hover/link:opacity-100 transition-all">open_in_new</span>
                                                </button>
                                                <div
                                                    class="text-[11px] font-bold text-slate-500 mt-1 uppercase tracking-tight">
                                                    <?php echo $p['client_name']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-6 text-center">
                                                <?php if ($isPaid): ?>
                                                    <div
                                                        class="flex items-center gap-2 text-green-500 font-bold text-xs justify-center">
                                                        <span class="material-symbols-outlined text-sm">verified</span> PAGADO
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex flex-col gap-1 items-center">
                                                        <div class="flex items-center gap-2 text-amber-500 font-bold text-xs">
                                                            <span class="material-symbols-outlined text-sm">warning</span>
                                                            PENDIENTE
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 italic">Verificar comprobante
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-6 text-center">
                                                <?php if ($view === 'archivados'): ?>
                                                    <div class="text-[10px] font-black dark:text-slate-400 text-slate-600">
                                                        <?php echo date('d/m/Y', strtotime($p['archived_at'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="phase-badge flex-col"
                                                        style="background: <?php echo $phaseInfo['color']; ?>20; color: <?php echo $phaseInfo['color']; ?>; border: 1px solid <?php echo $phaseInfo['color']; ?>30">
                                                        <div class="flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[14px]">
                                                                <?php echo $phaseInfo['icon']; ?>
                                                            </span>
                                                            <?php echo $phaseInfo['label']; ?>
                                                        </div>
                                                        <!-- Visual Progress Tracker -->
                                                        <div
                                                            class="flex gap-1 justify-center mt-3 h-1 w-24 mx-auto bg-slate-200 dark:bg-white/5 rounded-full overflow-hidden">
                                                            <?php
                                                            $found = false;
                                                            foreach ($phases as $k => $v):
                                                                $active = ($k === $currentPhase);
                                                                $complete = !$found && !$active;
                                                                if ($active)
                                                                    $found = true;
                                                                $bgColor = ($active || $complete) ? $v['color'] : 'transparent';
                                                                ?>
                                                                <div class="flex-1"
                                                                    style="background-color: <?php echo $bgColor; ?>;">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-6 text-center">
                                                <?php if (!empty($p['transport_name'])): ?>
                                                    <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                                        <?php echo $p['transport_name']; ?>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 italic">
                                                        <?php echo $p['transport_address']; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 italic">No asignado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-6 text-right">
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-end gap-4">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 uppercase">Bultos</span>
                                                        <input type="number" class="cost-input"
                                                            id="qty-<?php echo $p['quote_number']; ?>"
                                                            value="<?php echo $p['packages_qty'] ?? 1; ?>">
                                                    </div>
                                                    <div class="flex items-center justify-end gap-4">
                                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Flete
                                                            ARS</span>
                                                        <input type="number" class="cost-input"
                                                            id="cost-<?php echo $p['quote_number']; ?>"
                                                            value="<?php echo $p['freight_cost'] ?? 0; ?>" step="1">
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-6 text-right">
                                                <div class="flex flex-col gap-2 max-w-[140px] mx-auto">
                                                    <?php if ($view === 'archivados'): ?>
                                                        <div
                                                            class="bg-slate-500/10 text-slate-500 py-1.5 px-3 rounded-lg text-[10px] font-bold uppercase border border-slate-500/20">
                                                            ARCHIVADO
                                                        </div>
                                                    <?php elseif ($view === 'pendientes'): ?>
                                                        <button
                                                            class="bg-[#136dec]/10 hover:bg-[#136dec] text-[#136dec] hover:text-white py-2 px-4 rounded-xl text-xs font-bold transition-all border border-[#136dec]/20 flex items-center justify-center gap-2"
                                                            onclick="autorizarPedido(<?php echo $p['id']; ?>, '<?php echo $p['quote_number']; ?>')">
                                                            <span class="material-symbols-outlined text-sm">verified_user</span>
                                                            AUTORIZAR
                                                        </button>
                                                    <?php elseif ($currentPhase === 'En reserva'): ?>
                                                        <button
                                                            class="bg-green-500/10 hover:bg-green-500 text-green-500 hover:text-white py-2 px-4 rounded-xl text-xs font-bold transition-all border border-green-500/20 flex items-center justify-center gap-2"
                                                            onclick="avanzarFase('<?php echo $p['quote_number']; ?>', 'En preparación')">
                                                            <span class="material-symbols-outlined text-sm">play_arrow</span>
                                                            INICIAR PREP.
                                                        </button>
                                                    <?php elseif ($currentPhase === 'En preparación'): ?>
                                                        <button
                                                            class="bg-[#136dec]/10 hover:bg-[#136dec] text-[#136dec] hover:text-white py-2 px-4 rounded-xl text-xs font-bold transition-all border border-[#136dec]/20 flex items-center justify-center gap-2"
                                                            onclick="avanzarFase('<?php echo $p['quote_number']; ?>', 'Disponible')">
                                                            <span class="material-symbols-outlined text-sm">package_2</span>
                                                            MARCAR LISTO
                                                        </button>
                                                    <?php elseif ($currentPhase === 'Disponible'): ?>
                                                        <button
                                                            class="bg-primary text-white py-2 px-4 rounded-xl text-xs font-extra-bold shadow-lg shadow-primary/20 hover:bg-blue-600 transition-all flex items-center justify-center gap-2 mb-2"
                                                            onclick="despachar('<?php echo $p['quote_number']; ?>')">
                                                            <span
                                                                class="material-symbols-outlined text-sm">local_shipping</span>
                                                            DESPACHAR
                                                        </button>
                                                        <button
                                                            class="bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white py-2 px-4 rounded-xl text-xs font-bold transition-all border border-blue-500/20 flex items-center justify-center gap-2"
                                                            onclick="subirGuia('<?php echo $p['quote_number']; ?>')">
                                                            <span class="material-symbols-outlined text-sm">file_present</span>
                                                            SUBIR GUÍA/REMITO
                                                        </button>
                                                    <?php elseif ($currentPhase === 'En su transporte'): ?>
                                                        <button
                                                            class="bg-purple-500/10 hover:bg-purple-500 text-purple-500 hover:text-white py-1.5 px-3 rounded-lg text-xs font-bold transition-all border border-purple-500/20 flex items-center justify-center gap-2"
                                                            onclick="subirGuia('<?php echo $p['quote_number']; ?>')">
                                                            <span class="material-symbols-outlined text-sm">file_present</span>
                                                            SUBIR GUÍA
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($view === 'armado'): ?>
                                                        <button
                                                            class="mt-2 text-slate-400 hover:text-red-500 text-[9px] font-bold uppercase flex items-center justify-center gap-1 transition-colors"
                                                            onclick="archivarPedido(<?php echo $p['id']; ?>, '<?php echo $p['quote_number']; ?>')">
                                                            <span class="material-symbols-outlined text-xs">archive</span>
                                                            Archivar Pedido
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Product Detail Modal -->
    <div id="modalDetalles"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#16202e] w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-slate-200 dark:border-white/5 transition-all transform scale-95 opacity-0 duration-300"
            id="modalDetallesContent">
            <div
                class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-white/[0.02]">
                <div>
                    <h3 class="text-lg font-black dark:text-white text-slate-800 uppercase tracking-tighter"
                        id="modalTitle">Detalle de Pedido</h3>
                    <p class="text-[10px] font-bold text-primary uppercase tracking-widest" id="modalSubtitle"></p>
                </div>
                <button onclick="cerrarDetalles()"
                    class="p-2 rounded-xl hover:bg-slate-200 dark:hover:bg-white/10 text-slate-400 transition-all">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-white/5">
                            <th class="pb-3 px-2">SKU</th>
                            <th class="pb-3 px-2">Descripción</th>
                            <th class="pb-3 px-2 text-center">Cant.</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody" class="divide-y divide-slate-100 dark:divide-white/5">
                        <!-- Items populated via JS -->
                    </tbody>
                </table>
            </div>
            <div
                class="p-6 bg-slate-50/50 dark:bg-white/[0.02] border-t border-slate-100 dark:border-white/5 flex justify-end">
                <button onclick="cerrarDetalles()"
                    class="px-6 py-2 bg-slate-200 dark:bg-white/10 hover:bg-slate-300 dark:hover:bg-white/20 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-black uppercase transition-all">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        async function avanzarFase(quoteNumber, phase) {
            if (!confirm(`¿Mover pedido ${quoteNumber} a fase ${phase}?`)) return;
            const formData = new FormData();
            formData.append('action', 'update_phase');
            formData.append('quote_number', quoteNumber);
            formData.append('phase', phase);
            const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error);
        }

        async function despachar(quoteNumber) {
            const qty = document.getElementById('qty-' + quoteNumber).value;
            const cost = document.getElementById('cost-' + quoteNumber).value;
            const transportId = prompt("Ingrese ID de Transportista (ID Entidad):", "1");
            if (!transportId) return;
            const formData = new FormData();
            formData.append('action', 'despachar');
            formData.append('quote_number', quoteNumber);
            formData.append('transport_id', transportId);
            formData.append('packages_qty', qty);
            formData.append('freight_cost', cost);
            const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert("Despacho registrado correctamente.");
                location.reload();
            } else { alert(data.error); }
        }

        function subirGuia(quoteNumber) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'application/pdf,image/*';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const formData = new FormData();
                formData.append('action', 'upload_guide');
                formData.append('quote_number', quoteNumber);
                formData.append('guide_photo', file);
                const res = await fetch('ajax_logistics.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert("Guía subida y pedido entregado.");
                    location.reload();
                } else { alert("Error: " + data.error); }
            };
            input.click();
        }

        async function verDetallesPedido(quoteNumber) {
            const modal = document.getElementById('modalDetalles');
            const content = document.getElementById('modalDetallesContent');
            const tbody = document.getElementById('modalTableBody');

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
            }, 10);

            document.getElementById('modalSubtitle').innerText = 'Cargando información...';
            tbody.innerHTML = '<tr><td colspan="3" class="py-8 text-center text-slate-400 font-bold uppercase text-[10px]">Consultando base de datos...</td></tr>';

            try {
                const res = await fetch(`ajax_order_details.php?quote_number=${quoteNumber}`);
                const data = await res.json();

                if (data.success) {
                    document.getElementById('modalSubtitle').innerText = quoteNumber + ' - ' + data.quote.client_name;
                    tbody.innerHTML = data.items.map(item => `
                        <tr class="text-[11px] font-bold text-slate-600 dark:text-slate-300">
                            <td class="py-4 px-2 font-mono text-primary">${item.sku}</td>
                            <td class="py-4 px-2">${item.description}</td>
                            <td class="py-4 px-2 text-center text-lg">${item.quantity}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="3" class="py-8 text-center text-red-500 font-bold uppercase text-[10px]">${data.error}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="3" class="py-8 text-center text-red-500 font-bold uppercase text-[10px]">Error de conexión</td></tr>';
            }
        }

        function cerrarDetalles() {
            const modal = document.getElementById('modalDetalles');
            const content = document.getElementById('modalDetallesContent');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        async function autorizarPedido(id, quoteNumber) {
            if (!confirm(`¿Autorizar despacho del pedido ${quoteNumber} sin pago?`)) return;
            const res = await fetch('ajax_authorize_logistics.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('Éxito', 'Pedido autorizado correctamente', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.error || 'No se pudo autorizar', 'error');
            }
        }

        async function archivarPedido(id, quoteNumber) {
            const { value: reason } = await Swal.fire({
                title: 'Archivar Pedido',
                text: `¿Por qué motivo deseas archivar el pedido ${quoteNumber}?`,
                input: 'select',
                inputOptions: {
                    'Vendido': 'Entregado / Cerrado',
                    'Suspendido': 'Suspendido',
                    'Rechazado': 'Cancelado'
                },
                inputPlaceholder: 'Selecciona un motivo',
                showCancelButton: true,
                confirmButtonText: 'Archivar',
                cancelButtonText: 'Cancelar'
            });

            if (reason) {
                const res = await fetch('ajax_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: id,
                        type: 'quotation',
                        fields: {
                            archived_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                            archive_reason: reason,
                            status: reason === 'Vendido' ? 'accepted' : 'rejected'
                        }
                    })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Archivado', 'El pedido se ha movido al archivo.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', 'No se pudo archivar el pedido.', 'error');
                }
            }
        }
    </script>
</body>

</html>