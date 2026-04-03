<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tables = [];

    switch ($action) {
        case 'delete_facturas':
            $tables = ['invoices', 'invoice_items'];
            break;
        case 'delete_pedidos':
            $tables = ['quotations', 'quotation_items'];
            break;
        case 'delete_clientes':
            $tables = ['clients'];
            break;
        case 'delete_productos':
            $tables = ['products'];
            break;
        case 'delete_marcas':
            $tables = ['brands'];
            break;
        case 'delete_all':
            $tables = ['invoices', 'invoice_items', 'quotations', 'quotation_items', 'clients', 'products', 'brands', 'suppliers', 'purchase_orders', 'purchase_order_items'];
            break;
    }

    try {
        $db->beginTransaction();
        foreach ($tables as $table) {
            $db->exec("DELETE FROM $table");
            // Reset auto-increment if possible (dialect dependent, assuming MySQL/PostgreSQL common practice)
            try {
                $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            } catch (Exception $e) { /* ignore if fails */
            }
        }
        $db->commit();
        $message = "<div class='bg-green-500/10 border border-green-500 text-green-500 p-4 rounded-xl mb-6'>Datos eliminados correctamente: " . implode(', ', $tables) . "</div>";
    } catch (Exception $e) {
        $db->rollBack();
        $message = "<div class='bg-red-500/10 border border-red-500 text-red-500 p-4 rounded-xl mb-6'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Eliminación de Datos - VS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
</head>

<body class="bg-[#101822] text-white font-['Inter']">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-2 uppercase tracking-tight">Eliminación de Datos</h1>
            <p class="text-slate-400 text-sm mb-8 uppercase tracking-widest font-bold">Mantenimiento Crítico del Sistema
            </p>

            <?php echo $message; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card -->
                <div class="bg-[#16202e] border border-[#233348] rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <span class="material-symbols-outlined text-amber-500 mb-4">receipt</span>
                        <h3 class="font-bold mb-2">FACTURACIÓN</h3>
                        <p class="text-xs text-slate-500 mb-6">Elimina todas las facturas y sus ítems cargados en el
                            sistema.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('¿ESTÁ SEGURO? Esta acción no se puede deshacer.')">
                        <input type="hidden" name="action" value="delete_facturas">
                        <button
                            class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white py-3 rounded-xl font-bold text-xs transition-all uppercase tracking-widest">Eliminar
                            Facturas</button>
                    </form>
                </div>

                <div class="bg-[#16202e] border border-[#233348] rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <span class="material-symbols-outlined text-blue-500 mb-4">description</span>
                        <h3 class="font-bold mb-2">PEDIDOS / COTIZACIONES</h3>
                        <p class="text-xs text-slate-500 mb-6">Elimina el historial de presupuestos y cotizaciones.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('¿ESTÁ SEGURO? Esta acción no se puede deshacer.')">
                        <input type="hidden" name="action" value="delete_pedidos">
                        <button
                            class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white py-3 rounded-xl font-bold text-xs transition-all uppercase tracking-widest">Eliminar
                            Pedidos</button>
                    </form>
                </div>

                <div class="bg-[#16202e] border border-[#233348] rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <span class="material-symbols-outlined text-primary mb-4">groups</span>
                        <h3 class="font-bold mb-2">CLIENTES</h3>
                        <p class="text-xs text-slate-500 mb-6">Elimina toda la base de datos de clientes.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('¿ESTÁ SEGURO? Esta acción no se puede deshacer.')">
                        <input type="hidden" name="action" value="delete_clientes">
                        <button
                            class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white py-3 rounded-xl font-bold text-xs transition-all uppercase tracking-widest">Eliminar
                            Clientes</button>
                    </form>
                </div>

                <div class="bg-[#16202e] border border-[#233348] rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <span class="material-symbols-outlined text-green-500 mb-4">inventory_2</span>
                        <h3 class="font-bold mb-2">PRODUCTOS Y MARCAS</h3>
                        <p class="text-xs text-slate-500 mb-6">Elimina el catálogo completo de productos y marcas.</p>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" class="flex-1" onsubmit="return confirm('¿Eliminar todos los PRODUCTOS?')">
                            <input type="hidden" name="action" value="delete_productos">
                            <button
                                class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white py-3 rounded-xl font-bold text-[10px] transition-all uppercase tracking-widest">Prod.</button>
                        </form>
                        <form method="POST" class="flex-1" onsubmit="return confirm('¿Eliminar todas las MARCAS?')">
                            <input type="hidden" name="action" value="delete_marcas">
                            <button
                                class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white py-3 rounded-xl font-bold text-[10px] transition-all uppercase tracking-widest">Marcas</button>
                        </form>
                    </div>
                </div>

                <div
                    class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6 flex flex-col justify-between md:col-span-2">
                    <div>
                        <span class="material-symbols-outlined text-red-500 mb-4">warning</span>
                        <h3 class="font-bold mb-2 text-red-500 text-xl tracking-tighter">ELIMINACIÓN TOTAL</h3>
                        <p class="text-sm text-slate-400 mb-6 font-bold uppercase tracking-tight">Reinicia el sistema
                            eliminando ABSOLUTAMENTE TODO (Ventas, Compras, Clientes, Productos, Proveedores, etc).</p>
                    </div>
                    <form method="POST"
                        onsubmit="return confirm('¡ÚLTIMA ADVERTENCIA! Se borrará TODA la base de datos. ¿Desea continuar?')">
                        <input type="hidden" name="action" value="delete_all">
                        <button
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-xl font-black text-sm transition-all uppercase tracking-[0.2em] shadow-xl shadow-red-500/20">REINICIAR
                            TODA LA BASE DE DATOS</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>