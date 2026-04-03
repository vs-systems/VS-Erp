<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';

if (($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php');
    exit;
}

$db = Vsys\Lib\Database::getInstance();
$message = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_movement') {
        $id = $_POST['id'];
        $type = $_POST['category']; // 'client' or 'provider'
        $table = ($type === 'client') ? 'client_movements' : 'provider_movements';

        if ($type === 'client') {
            $db->prepare("DELETE FROM treasury_movements WHERE reference_id = ? AND reference_type = 'client_payment'")->execute([$id]);
        } else {
            $db->prepare("DELETE FROM treasury_movements WHERE reference_id = ? AND reference_type = 'provider_payment'")->execute([$id]);
        }

        $db->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        $message = "Movimiento y su impacto en tesorería eliminados con éxito.";
    }
}

$clientMovements = $db->query("SELECT cm.*, e.name as entity_name FROM client_movements cm JOIN entities e ON cm.client_id = e.id ORDER BY cm.date DESC LIMIT 100")->fetchAll();
$providerMovements = $db->query("SELECT pm.*, e.name as entity_name FROM provider_movements pm JOIN entities e ON pm.provider_id = e.id ORDER BY pm.date DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Movimientos CC - VS System</title>
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
                            Gestión de Cuentas Corrientes</h2>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1.5">Auditoría de
                            débitos y créditos</p>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-7xl mx-auto space-y-12">

                    <?php if ($message): ?>
                        <div
                            class="bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-500 p-5 rounded-2xl flex items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-500 font-black uppercase tracking-tight text-xs">
                            <span class="material-symbols-outlined">warning</span>
                            <p class="normal-case"><?php echo $message; ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <!-- Clientes -->
                        <div
                            class="bg-white dark:bg-[#16202e]/70 border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
                            <div
                                class="p-8 border-b border-slate-200 dark:border-white/5 flex justify-between items-center bg-blue-500/5">
                                <h3 class="font-black text-xs tracking-widest text-blue-500 uppercase px-2">Movimientos
                                    Clientes</h3>
                                <span
                                    class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-500 text-[9px] font-black uppercase tracking-widest border border-blue-500/10">
                                    <?php echo count($clientMovements); ?> REGISTROS
                                </span>
                            </div>
                            <div class="overflow-x-auto max-h-[600px] custom-scrollbar px-2">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="bg-slate-50/50 dark:bg-white/5 text-[9px] uppercase text-slate-500 font-black tracking-widest border-b border-slate-200 dark:border-white/5 sticky top-0 z-10">
                                            <th class="px-6 py-5">Fecha</th>
                                            <th class="px-6 py-5">Entidad</th>
                                            <th class="px-6 py-5 text-right">D / H</th>
                                            <th class="px-6 py-5 text-center">X</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php foreach ($clientMovements as $m): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-all group">
                                                <td class="px-6 py-5 text-[9px] font-mono opacity-50 font-bold">
                                                    <?php echo date('d/m/y H:i', strtotime($m['date'])); ?></td>
                                                <td class="px-6 py-5 text-[10px] font-black truncate max-w-[120px]">
                                                    <?php echo htmlspecialchars($m['entity_name']); ?></td>
                                                <td
                                                    class="px-6 py-5 text-right font-mono font-black text-[11px] <?php echo $m['debit'] > 0 ? 'text-red-500' : 'text-emerald-500'; ?>">
                                                    <?php echo $m['debit'] > 0 ? '+' . number_format($m['debit'], 0, ',', '.') : '-' . number_format($m['credit'], 0, ',', '.'); ?>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <button onclick="confirmDelete(<?php echo $m['id']; ?>, 'client')"
                                                        class="size-8 rounded-xl bg-red-500/5 text-red-500/20 group-hover:text-red-500 group-hover:bg-red-500/10 transition-all flex items-center justify-center mx-auto">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Proveedores -->
                        <div
                            class="bg-white dark:bg-[#16202e]/70 border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
                            <div
                                class="p-8 border-b border-slate-200 dark:border-white/5 flex justify-between items-center bg-amber-500/5">
                                <h3 class="font-black text-xs tracking-widest text-amber-500 uppercase px-2">Movimientos
                                    Proveedores</h3>
                                <span
                                    class="px-3 py-1 rounded-full bg-amber-500/10 text-amber-500 text-[9px] font-black uppercase tracking-widest border border-amber-500/10">
                                    <?php echo count($providerMovements); ?> REGISTROS
                                </span>
                            </div>
                            <div class="overflow-x_auto max-h-[600px] custom-scrollbar px-2">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="bg-slate-50/50 dark:bg-white/5 text-[9px] uppercase text-slate-500 font-black tracking-widest border-b border-slate-200 dark:border-white/5 sticky top-0 z-10">
                                            <th class="px-6 py-5">Fecha</th>
                                            <th class="px-6 py-5">Entidad</th>
                                            <th class="px-6 py-5 text-right">D / H</th>
                                            <th class="px-6 py-5 text-center">X</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php foreach ($providerMovements as $m): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-all group">
                                                <td class="px-6 py-5 text-[9px] font-mono opacity-50 font-bold">
                                                    <?php echo date('d/m/y H:i', strtotime($m['date'])); ?></td>
                                                <td class="px-6 py-5 text-[10px] font-black truncate max-w-[120px]">
                                                    <?php echo htmlspecialchars($m['entity_name']); ?></td>
                                                <td
                                                    class="px-6 py-5 text-right font-mono font-black text-[11px] <?php echo $m['debit'] > 0 ? 'text-emerald-500' : 'text-red-500'; ?>">
                                                    <?php echo $m['debit'] > 0 ? '+' . number_format($m['debit'], 0, ',', '.') : '-' . number_format($m['credit'], 0, ',', '.'); ?>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <button onclick="confirmDelete(<?php echo $m['id']; ?>, 'provider')"
                                                        class="size-8 rounded-xl bg-red-500/5 text-red-500/20 group-hover:text-red-500 group-hover:bg-red-500/10 transition-all flex items-center justify-center mx-auto">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
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
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(id, category) {
            Swal.fire({
                title: '<span class="text-lg font-black uppercase tracking-tighter">¿ELIMINAR MOVIMIENTO?</span>',
                html: '<div class="text-[11px] text-slate-500 font-bold uppercase leading-relaxed tracking-wider py-4 normal-case">Se eliminará el registro de la cuenta corriente y su impacto en tesorería. Esta acción es definitiva.</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'SÍ, BORRAR',
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
                    form.innerHTML = `<input type="hidden" name="action" value="delete_movement"><input type="hidden" name="id" value="${id}"><input type="hidden" name="category" value="${category}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>