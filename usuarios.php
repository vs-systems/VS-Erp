<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\User;
use Vsys\Lib\Database;

$userAuth = new User();
if (!$userAuth->hasRole('Admin')) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = 'success'; // success or error

// Handle User Creation/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $userId = $_POST['user_id'] ?? null;
    $password = $_POST['password'] ?? '';

    try {
        if ($userId) {
            // Update
            $sql = "UPDATE users SET username = ?, role = ?, status = ? WHERE id = ?";
            $params = [$username, $role, $status, $userId];
            if ($password) {
                $sql = "UPDATE users SET username = ?, role = ?, status = ?, password_hash = ? WHERE id = ?";
                $params = [$username, $role, $status, password_hash($password, PASSWORD_DEFAULT), $userId];
            }
            $db->prepare($sql)->execute($params);
            $message = "Usuario actualizado correctamente.";
        } else {
            // Create
            if ($userAuth->createUser(['username' => $username, 'password' => $password, 'role' => $role, 'status' => $status])) {
                $message = "Usuario creado correctamente.";
            } else {
                throw new Exception("Error al crear el usuario. El nombre de usuario ya puede existir.");
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$users = $db->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - VS System</title>
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

        .form-input {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors;
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
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Administración
                        de Usuarios</h2>
                </div>
                <div class="flex items-center gap-4 text-xs font-bold text-slate-500">
                    <span class="material-symbols-outlined text-sm">shield_person</span>
                    ADMINISTRADOR
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <?php if ($message): ?>
                        <div
                            class="flex items-center gap-3 p-4 rounded-2xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-500' : 'bg-red-500/10 border-red-500/20 text-red-500'; ?> animate-in fade-in slide-in-from-top-4 duration-300">
                            <span
                                class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase tracking-widest"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <!-- Form Section -->
                        <div class="lg:col-span-4 h-fit sticky top-0">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors">
                                <div
                                    class="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                    <span class="material-symbols-outlined text-primary"
                                        id="form-icon">person_add</span>
                                    <h3 class="font-bold text-lg dark:text-white text-slate-800" id="form-title">Nuevo
                                        Usuario</h3>
                                </div>

                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="user_id" id="edit_user_id">

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Username</label>
                                        <input type="text" name="username" id="edit_username" required
                                            placeholder="Nombre de usuario" class="form-input">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Password</label>
                                        <input type="password" name="password" id="edit_password" placeholder="••••••••"
                                            class="form-input">
                                        <p class="text-[9px] text-slate-400 mt-1 ml-1" id="pass-hint">Deje vacío para
                                            mantener la actual en edición.</p>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Rol
                                            de Acceso</label>
                                        <select name="role" id="edit_role" required class="form-input">
                                            <option value="Admin">Admin</option>
                                            <option value="Vendedor">Vendedor</option>
                                            <option value="Contabilidad">Contabilidad</option>
                                            <option value="Deposito">Depósito</option>
                                            <option value="Compras">Compras</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Sistemas">Sistemas</option>
                                            <option value="Cliente">Cliente</option>
                                            <option value="Invitado">Invitado</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Estado</label>
                                        <select name="status" id="edit_status" required class="form-input">
                                            <option value="Active">Activo</option>
                                            <option value="Inactive">Inactivo</option>
                                            <option value="Pending">Pendiente</option>
                                        </select>
                                    </div>

                                    <div class="pt-4 flex gap-2">
                                        <button type="button" onclick="resetForm()" id="btn-reset"
                                            class="hidden flex-1 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-[#233348] text-slate-500 font-bold py-3 rounded-xl text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">Cancelar</button>
                                        <button type="submit" name="save_user"
                                            class="flex-[2] bg-primary hover:bg-blue-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                                            <span class="material-symbols-outlined text-sm">save</span> GUARDAR
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Table Section -->
                        <div class="lg:col-span-8">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Usuario</th>
                                            <th class="px-6 py-4">Rol / Permisos</th>
                                            <th class="px-6 py-4">Estado</th>
                                            <th class="px-6 py-4">Último Acceso</th>
                                            <th class="px-6 py-4 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php foreach ($users as $u):
                                            $currStatus = $u['status'] ?? (($u['active'] ?? 1) ? 'Active' : 'Inactive');
                                            ?>
                                            <tr
                                                class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                <td class="px-6 py-5">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                                                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                                        </div>
                                                        <div class="font-bold text-sm dark:text-white text-slate-800">
                                                            <?php echo $u['username']; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div
                                                        class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/5 w-fit">
                                                        <span
                                                            class="material-symbols-outlined text-xs text-slate-400">lock_open</span>
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest"><?php echo $u['role']; ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <span class="text-[10px] font-bold uppercase py-1 px-2 rounded-full <?php
                                                    echo $currStatus === 'Active' ? 'bg-green-500/10 text-green-500' : ($currStatus === 'Pending' ? 'bg-amber-500/10 text-amber-500' : 'bg-red-500/10 text-red-500');
                                                    ?>">
                                                        <?php echo $currStatus; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="text-[11px] text-slate-500">
                                                        <?php echo $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca'; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex items-center justify-center">
                                                        <button
                                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                                                            class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all"
                                                            title="Editar Usuario">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
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
            </div>
        </main>
    </div>

    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_status').value = user.status;

            document.getElementById('form-title').innerText = 'Editar Usuario';
            document.getElementById('form-icon').innerText = 'manage_accounts';
            document.getElementById('btn-reset').classList.remove('hidden');

            // Scroll to form on mobile
            if (window.innerWidth < 1024) {
                document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
            }
        }

        function resetForm() {
            document.querySelector('form').reset();
            document.getElementById('edit_user_id').value = '';
            document.getElementById('form-title').innerText = 'Nuevo Usuario';
            document.getElementById('form-icon').innerText = 'person_add';
            document.getElementById('btn-reset').classList.add('hidden');
        }
    </script>
</body>

</html>