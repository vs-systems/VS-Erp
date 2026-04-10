<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

$clientModule = new Client();
$message = '';
$status = '';

// -- Eliminar cliente --
$db = Vsys\Lib\Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_entity'])) {
    $delId = (int)($_POST['entity_id'] ?? 0);
    if ($delId) {
        try {
            // Eliminar usuario asociado si existe
            $db->prepare("DELETE FROM users WHERE entity_id = ?")->execute([$delId]);
            // Eliminar entidad
            $db->prepare("DELETE FROM entities WHERE id = ? AND type = 'client'")->execute([$delId]);
            $message = "Cliente eliminado correctamente.";
            $status = "success";
        } catch (\Exception $e) {
            $message = "Error al eliminar: " . $e->getMessage();
            $status = "error";
        }
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entity'])) {
    $data = [
        'type' => 'client',
        'tax_id' => $_POST['tax_id'],
        'document_number' => $_POST['document_number'],
        'name' => $_POST['name'],
        'fantasy_name' => $_POST['fantasy_name'],
        'contact' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'mobile' => $_POST['mobile'],
        'address' => $_POST['address'],
        'delivery_address' => $_POST['delivery_address'],
        'default_voucher' => $_POST['default_voucher_type'] ?? 'Factura',
        'tax_category' => $_POST['tax_category'] ?? 'No Aplica',
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention_agent']) ? 1 : 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method']
    ];

    if ($clientModule->saveClient($data)) {
        $message = "Cliente guardado correctamente.";
        $status = "success";
    } else {
        $message = "Error al guardar el cliente.";
        $status = "error";
    }
}

// Get verified clients only (is_verified=1 to avoid showing pending in main list)
$sql = "SELECT * FROM entities WHERE type = 'client' AND is_verified = 1 ORDER BY name ASC";
$clients = $db->query($sql)->fetchAll();

// Pendientes de verificación (Bloque 7)
$pendingClients = $db->query(
    "SELECT * FROM entities WHERE type = 'client' AND is_verified = 0 ORDER BY id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - VS System</title>
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
                        "surface-dark": "#16202e",
                    },
                },
            }
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
            background: transparent;
        }

        .dark ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #233348;
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
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">badge</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Gestión de
                        Clientes</h2>
                </div>
                <div class="flex items-center gap-4">
                    <a href="config_entities.php?type=client"
                        class="flex items-center gap-2 bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">person_add</span> NUEVO CLIENTE
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
                <div class="max-w-[1400px] mx-auto space-y-6">

                    <?php if (!empty($pendingClients)): ?>
                    <!-- ── PANEL SOLICITUDES PENDIENTES (Bloque 7) ── -->
                    <div class="bg-amber-500/5 border border-amber-500/20 rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-amber-500/15 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></div>
                                <span class="text-xs font-bold uppercase tracking-widest text-amber-400">Solicitudes pendientes de aprobación</span>
                                <span class="bg-amber-500/15 text-amber-400 text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo count($pendingClients); ?></span>
                            </div>
                            <span class="text-[10px] text-slate-500">Registros desde el Portal Web &mdash; pendientes de verificar y activar</span>
                        </div>

                        <div class="divide-y divide-amber-500/10">
                            <?php foreach ($pendingClients as $pc):
                                $pcName  = $pc['fantasy_name'] ?: $pc['name'];
                                $pcEmail = $pc['email'] ?? '(sin email)';
                                $pcTel   = $pc['mobile'] ?: $pc['phone'] ?: '(sin teléfono)';
                                $pcAddr  = $pc['address'] ?: 'Sin localidad';
                                $pcDate  = !empty($pc['created_at']) ? date('d/m/Y H:i', strtotime($pc['created_at'])) : '—';
                            ?>
                            <div class="px-6 py-4 flex flex-wrap items-center gap-4 group" id="pending-row-<?php echo $pc['id']; ?>">
                                <!-- Avatar -->
                                <div class="h-10 w-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 flex-shrink-0">
                                    <span class="material-symbols-outlined text-lg">person</span>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-[180px]">
                                    <div class="font-bold text-sm dark:text-white text-slate-800"><?php echo htmlspecialchars($pcName); ?></div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                        <span class="material-symbols-outlined text-[12px]">mail</span> <?php echo htmlspecialchars($pcEmail); ?>
                                        <span class="text-slate-600">·</span>
                                        <span class="material-symbols-outlined text-[12px]">phone_iphone</span> <?php echo htmlspecialchars($pcTel); ?>
                                        <span class="text-slate-600">·</span>
                                        <span class="material-symbols-outlined text-[12px]">location_on</span> <?php echo htmlspecialchars($pcAddr); ?>
                                    </div>
                                </div>

                                <!-- Fecha -->
                                <div class="text-[11px] text-slate-500 hidden lg:block">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Solicitó</div>
                                    <?php echo $pcDate; ?>
                                </div>

                                <!-- Selector tipo -->
                                <div class="flex items-center gap-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipo</label>
                                    <select id="tipo-select-<?php echo $pc['id']; ?>"
                                        class="bg-white/5 dark:bg-white/5 border border-slate-200 dark:border-[#233348] rounded-lg text-xs font-bold dark:text-white text-slate-800 px-2 py-1.5 outline-none focus:border-primary">
                                        <option value="gremio"  <?php echo ($pc['tipo_cliente'] ?? 'gremio') === 'gremio'  ? 'selected' : ''; ?>>Gremio</option>
                                        <option value="publico" <?php echo ($pc['tipo_cliente'] ?? '') === 'publico' ? 'selected' : ''; ?>>Público</option>
                                        <option value="partner" <?php echo ($pc['tipo_cliente'] ?? '') === 'partner' ? 'selected' : ''; ?>>Partner</option>
                                    </select>
                                </div>

                                <!-- Acciones -->
                                <div class="flex items-center gap-2">
                                    <button
                                        onclick="aprobarCliente(<?php echo $pc['id']; ?>, '<?php echo htmlspecialchars(addslashes($pcName)); ?>', '<?php echo htmlspecialchars($pcEmail); ?>')"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 border border-green-500/20 text-green-400 rounded-lg text-xs font-bold transition-all">
                                        <span class="material-symbols-outlined text-sm">verified</span>
                                        Aprobar
                                    </button>
                                    <a href="config_entities.php?id=<?php echo $pc['id']; ?>"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-slate-200 dark:border-[#233348] text-slate-400 hover:text-white rounded-lg text-xs font-bold transition-all">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                        Ver ficha
                                    </a>
                                </div>

                                <!-- Spinner de procesamiento (oculto hasta AJAX) -->
                                <div id="loading-<?php echo $pc['id']; ?>" class="hidden items-center gap-2 text-xs text-slate-400">
                                    <div class="h-4 w-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                    Procesando...
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- /panel pendientes -->
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border <?php echo $status === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span
                                class="material-symbols-outlined"><?php echo $status === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div
                            class="p-6 border-b border-slate-100 dark:border-[#233348] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-slate-400">group</span>
                                <h3
                                    class="font-bold text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest">
                                    Listado de Clientesregistrados</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="bg-slate-100 dark:bg-white/5 py-1 px-3 rounded-full text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                    Total: <?php echo count($clients); ?>
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4">Nombre / Razón Social</th>
                                        <th class="px-6 py-4">CUIT / DNI</th>
                                        <th class="px-6 py-4">Contacto</th>
                                        <th class="px-6 py-4">Cat. Fiscal</th>
                                        <th class="px-6 py-4">Tipo</th>
                                        <th class="px-6 py-4">Email / Tel</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($clients as $c): ?>
                                        <tr
                                            class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group <?php echo !$c['is_enabled'] ? 'opacity-60' : ''; ?>">
                                            <td class="px-6 py-5">
                                                <div class="font-bold text-sm dark:text-white text-slate-800">
                                                    <?php echo $c['name']; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-medium">
                                                    <?php echo $c['fantasy_name']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800 font-mono">
                                                    <?php echo $c['tax_id']; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500"><?php echo $c['document_number']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800 font-medium">
                                                    <?php echo $c['contact_person']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span
                                                    class="text-[10px] font-bold py-1 px-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                                                    <?php echo $c['tax_category']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <?php
                                                $tc = $c['tipo_cliente'] ?? 'publico';
                                                $tcLabel = ['partner' => 'Partner', 'gremio' => 'Gremio', 'publico' => 'Público'][$tc] ?? 'Público';
                                                $tcClass = [
                                                    'partner' => 'background:rgba(168,85,247,.12);color:#c084fc;border:1px solid rgba(168,85,247,.25);',
                                                    'gremio'  => 'background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.25);',
                                                    'publico' => 'background:rgba(100,116,139,.1);color:#94a3b8;border:1px solid rgba(100,116,139,.2);',
                                                ][$tc] ?? '';
                                                ?>
                                                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;<?php echo $tcClass; ?>">
                                                    <?php echo $tcLabel; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800">
                                                    <?php echo $c['email']; ?>
                                                </div>
                                                <div
                                                    class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                                    <?php echo $c['mobile'] ?: $c['phone']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <span
                                                    class="text-[10px] font-bold uppercase py-1 px-2 rounded-full <?php echo $c['is_enabled'] ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                    <?php echo $c['is_enabled'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="config_entities.php?id=<?php echo $c['id']; ?>&type=client"
                                                        class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all shadow-sm"
                                                        title="Editar Cliente">
                                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar a <?php echo addslashes(htmlspecialchars($c['name'])); ?>?\n\nEsta acción eliminará también el usuario asociado y no se puede deshacer.');">
                                                        <input type="hidden" name="delete_entity" value="1">
                                                        <input type="hidden" name="entity_id" value="<?php echo $c['id']; ?>">
                                                        <button type="submit"
                                                            class="p-2 rounded-lg hover:bg-red-500/10 text-slate-400 hover:text-red-500 transition-all shadow-sm"
                                                            title="Eliminar Cliente">
                                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                                        </button>
                                                    </form>
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
</body>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Credenciales generadas al aprobar cliente
     ════════════════════════════════════════════════════════════ -->
<div id="credModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.75);backdrop-filter:blur(6px);">
    <div class="bg-[#16202e] border border-[#233348] rounded-2xl w-full max-w-md shadow-2xl p-8 relative">
        <!-- Close -->
        <button onclick="closeCredModal()"
            class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>

        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="h-10 w-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-400">verified</span>
            </div>
            <div>
                <h3 class="font-bold text-white text-base">Cliente Aprobado</h3>
                <p id="cred-subtitle" class="text-xs text-slate-400"></p>
            </div>
        </div>

        <!-- Credenciales -->
        <div class="bg-[#0d1117] border border-[#233348] rounded-xl p-5 mb-5 space-y-4">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Usuario (Email)</p>
                <p id="cred-email" class="text-sm font-mono font-bold text-white"></p>
            </div>
            <div class="border-t border-[#233348] pt-4">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Contraseña Temporal</p>
                <div class="flex items-center gap-3">
                    <p id="cred-pass" class="text-2xl font-mono font-extrabold text-[#3b82f6] tracking-widest"></p>
                    <button onclick="copyPass()" id="btn-copy"
                        class="ml-auto flex items-center gap-1.5 px-3 py-1.5 bg-[#136dec]/10 hover:bg-[#136dec]/20 border border-[#136dec]/30 text-[#136dec] rounded-lg text-xs font-bold transition-all">
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                        Copiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Aviso -->
        <div class="flex items-start gap-2 p-3 bg-amber-500/5 border border-amber-500/20 rounded-xl mb-5">
            <span class="material-symbols-outlined text-amber-400 text-base mt-0.5 flex-shrink-0">info</span>
            <p class="text-xs text-amber-300/80 leading-relaxed">
                Compartí estas credenciales al cliente por <strong>WhatsApp</strong>.
                La contraseña puede cambiarse desde el perfil del portal.
            </p>
        </div>

        <!-- WhatsApp link -->
        <a id="btn-whatsapp" href="#" target="_blank"
            class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-green-500/10 hover:bg-green-500/20 border border-green-500/20 text-green-400 font-bold text-sm transition-all">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.113.551 4.096 1.512 5.813L.052 23.5l5.818-1.43A11.953 11.953 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.8 9.8 0 01-4.993-1.368l-.358-.213-3.448.847.876-3.339-.233-.373A9.79 9.79 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
            </svg>
            Enviar por WhatsApp
        </a>
    </div>
</div>

<script>
/**
 * Aprueba un cliente pendiente via AJAX
 */
async function aprobarCliente(entityId, nombre, email) {
    const tipoSelect = document.getElementById('tipo-select-' + entityId);
    const tipo       = tipoSelect ? tipoSelect.value : 'gremio';

    if (!confirm(`¿Aprobar a ${ nombre } [${ email }] como ${ tipo.toUpperCase() }?\n\nSe creará su usuario de acceso al portal.`)) return;

    // UI: mostrar spinner
    const row     = document.getElementById('pending-row-' + entityId);
    const loading = document.getElementById('loading-' + entityId);
    if (row) row.style.opacity = '.5';
    if (loading) loading.classList.replace('hidden', 'flex');

    try {
        const res  = await fetch('ajax_approve_client.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ entity_id: entityId, tipo_cliente: tipo }),
        });
        const data = await res.json();

        if (data.success) {
            // Remover fila del panel
            if (row) {
                row.style.transition = 'all .4s';
                row.style.opacity = '0';
                row.style.maxHeight = '0';
                row.style.overflow  = 'hidden';
                setTimeout(() => row.remove(), 420);
            }

            // Mostrar modal con contraseña generada
            if (data.temp_pass) {
                showCredModal(data.client_name || nombre, data.client_email || email, data.temp_pass);
            } else {
                showToast(data.message, 'success');
            }

            // Si no quedan pendientes, ocultar panel
            setTimeout(() => {
                const panel = document.querySelector('.bg-amber-500\\/5');
                if (panel && panel.querySelectorAll('[id^="pending-row-"]').length === 0) {
                    panel.remove();
                }
            }, 500);
        } else {
            if (row) row.style.opacity = '1';
            if (loading) loading.classList.replace('flex', 'hidden');
            showToast(data.message || 'Error desconocido', 'error');
        }
    } catch (err) {
        if (row) row.style.opacity = '1';
        if (loading) loading.classList.replace('flex', 'hidden');
        showToast('Error de red: ' + err.message, 'error');
    }
}

/**
 * Mostrar modal con credenciales generadas
 */
function showCredModal(nombre, email, password) {
    document.getElementById('cred-subtitle').textContent = nombre;
    document.getElementById('cred-email').textContent    = email;
    document.getElementById('cred-pass').textContent     = password;

    // WhatsApp link
    const msg = encodeURIComponent(
        `¡Hola ${nombre}! Tu cuenta en Vecino Seguro fue aprobada.\n\n` +
        `Usuario: ${email}\nContraseña: ${password}\n\n` +
        `Ingresá en: https://dev.vecinoseguro.com.ar/login.php`
    );
    document.getElementById('btn-whatsapp').href = `https://wa.me/?text=${msg}`;

    const modal = document.getElementById('credModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCredModal() {
    const modal = document.getElementById('credModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    // Recargar para actualizar lista
    location.reload();
}

function copyPass() {
    const pass = document.getElementById('cred-pass').textContent;
    navigator.clipboard.writeText(pass).then(() => {
        const btn = document.getElementById('btn-copy');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">check</span> Copiado';
        btn.style.color = '#4ade80';
        setTimeout(() => {
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">content_copy</span> Copiar';
            btn.style.color = '';
        }, 2000);
    });
}

/**
 * Toast minimalista (sin dependencias)
 */
function showToast(msg, type = 'success') {
    const colors = {
        success: 'background:#16202e;border:1px solid rgba(74,222,128,.3);color:#4ade80;',
        error:   'background:#16202e;border:1px solid rgba(248,113,113,.3);color:#f87171;',
    };
    const t = document.createElement('div');
    t.innerHTML = msg;
    t.style.cssText = `
        position:fixed;bottom:28px;right:28px;z-index:9999;
        padding:14px 20px;border-radius:14px;font-size:13px;font-weight:600;
        max-width:380px;line-height:1.5;box-shadow:0 20px 40px rgba(0,0,0,.5);
        transition:all .4s;${ colors[type] || colors.success }
    `;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 5000);
}
</script>

</html>