<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

$clientModule = new Client();
$message = '';
$status = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entity'])) {
    $data = [
        'type' => 'supplier',
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
        'retention' => 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method']
    ];

    if ($clientModule->saveClient($data)) {
        $message = "Proveedor guardado correctamente.";
        $status = "success";
    } else {
        $message = "Error al guardar el proveedor.";
        $status = "error";
    }
}

// Get all suppliers
$sql = "SELECT * FROM entities WHERE type = 'supplier' OR type = 'provider' ORDER BY name ASC";
$db = Vsys\Lib\Database::getInstance();
$suppliers = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - VS System</title>
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
                        <span class="material-symbols-outlined text-2xl">factory</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Directorio de
                        Proveedores</h2>
                </div>
                <div class="flex items-center gap-4">
                    <a href="config_entities.php?type=supplier"
                        class="flex items-center gap-2 bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-primary/20 transition-all">
                        <span class="material-symbols-outlined text-sm">person_add</span> NUEVO PROVEEDOR
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
                <div class="max-w-[1400px] mx-auto space-y-6">

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
                                <span class="material-symbols-outlined text-slate-400">inventory_2</span>
                                <h3
                                    class="font-bold text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest">
                                    Listado de Proveedoresregistrados</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="bg-slate-100 dark:bg-white/5 py-1 px-3 rounded-full text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                    Total: <?php echo count($suppliers); ?>
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
                                        <th class="px-6 py-4">Email / Tel</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($suppliers as $s): ?>
                                        <tr
                                            class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group <?php echo !$s['is_enabled'] ? 'opacity-60' : ''; ?>">
                                            <td class="px-6 py-5">
                                                <div class="font-bold text-sm dark:text-white text-slate-800">
                                                    <?php echo $s['name']; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-medium">
                                                    <?php echo $s['fantasy_name']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800 font-mono">
                                                    <?php echo $s['tax_id']; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500"><?php echo $s['document_number']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800 font-medium">
                                                    <?php echo $s['contact_person']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span
                                                    class="text-[10px] font-bold py-1 px-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                                                    <?php echo $s['tax_category']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm dark:text-white text-slate-800">
                                                    <?php echo $s['email']; ?>
                                                </div>
                                                <div
                                                    class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                                    <?php echo $s['mobile'] ?: $s['phone']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <span
                                                    class="text-[10px] font-bold uppercase py-1 px-2 rounded-full <?php echo $s['is_enabled'] ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                    <?php echo $s['is_enabled'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="config_entities.php?id=<?php echo $s['id']; ?>&type=supplier"
                                                        class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all shadow-sm"
                                                        title="Editar Proveedor">
                                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                                    </a>
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

</html>