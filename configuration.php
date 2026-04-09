<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/lib/User.php';
require_once __DIR__ . '/src/modules/cleanup/Cleanup.php';

use Vsys\Lib\Database;
use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;
use Vsys\Lib\User;
use Vsys\Modules\Cleanup\Cleanup;

$db = Database::getInstance();
$catalog = new Catalog();
$priceListModule = new PriceList();
$userLib = new User();

// Routing - Robust Default
$currentSection = (isset($_GET['section']) && trim($_GET['section']) !== '') ? trim($_GET['section']) : 'main';
$action = $_POST['action'] ?? '';
// ...
// (Find and replace all $section usages below with $currentSection in similar chunks)

$message = '';
$status = '';

// Check admin role
$isAdmin = ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'admin');

// --- HELPER FUNCTIONS ---
function loadCompanyConfig()
{
    $file = __DIR__ . '/config_company.json';
    if (file_exists($file))
        return json_decode(file_get_contents($file), true);
    return [
        'company_name' => 'Mi Empresa',
        'fantasy_name' => 'VS System',
        'tax_id' => '',
        'address' => '',
        'email' => '',
        'phone' => '',
        'logo_url' => 'logo_v2.jpg'
    ];
}

function saveCompanyConfig($data)
{
    unset($data['action']);
    file_put_contents(__DIR__ . '/config_company.json', json_encode($data, JSON_PRETTY_PRINT));
}

// --- ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save Company Data
    if ($action === 'save_company' && $isAdmin) {
        saveCompanyConfig($_POST);
        $message = "Datos de empresa guardados correctamente.";
        $status = 'success';
    }

    // Save Budget Config
    if ($action === 'save_budget_config' && $isAdmin) {
        $config = [
            'validity_hours' => $_POST['validity_hours'] ?? 48,
            'legal_notes' => $_POST['legal_notes'] ?? ''
        ];
        file_put_contents(__DIR__ . '/config_budget.json', json_encode($config, JSON_PRETTY_PRINT));
        $message = "Configuración de presupuestos actualizada.";
        $status = 'success';
    }

    // ABM Users
    if ($action === 'create_user' && $isAdmin) {
        try {
            $sql = "INSERT INTO users (username, full_name, email, role, password_hash, status) VALUES (?, ?, ?, ?, ?, 'Active')";
            $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare($sql);
            $stmt->execute([$_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['role'], $passHash]);
            $message = "Usuario creado con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'update_user' && $isAdmin) {
        try {
            $id = $_POST['id'];
            $role = $_POST['role'];
            $statusVal = $_POST['status']; // Active/Inactive

            $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $params = [$_POST['full_name'], $_POST['email'], $role, $statusVal, $id];

            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, password_hash = ? WHERE id = ?";
                $params = [$_POST['full_name'], $_POST['email'], $role, $statusVal, password_hash($_POST['password'], PASSWORD_DEFAULT), $id];
            }

            $db->prepare($sql)->execute($params);
            $message = "Usuario actualizado con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error al actualizar: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'delete_user' && $isAdmin) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['id']]);
        $message = "Usuario eliminado correctamente.";
        $status = 'success';
    }

    // ABM Brands
    if ($action === 'create_brand') {
        try {
            $db->prepare("INSERT INTO brands (name) VALUES (?)")->execute([$_POST['name']]);
            $message = "Marca agregada con éxito.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Error al crear marca.";
            $status = 'error';
        }
    }

    if ($action === 'delete_brand' && $isAdmin) {
        $db->prepare("DELETE FROM brands WHERE id = ?")->execute([$_POST['id']]);
        $message = "Marca eliminada correctamente.";
        $status = 'success';
    }

    // Save Price Config
    if ($action === 'save_price_config' && $isAdmin) {
        $priceConfig = [
            'gremio' => floatval($_POST['gremio']),
            'web' => floatval($_POST['web']),
            'mostrador' => floatval($_POST['mostrador'])
        ];
        file_put_contents(__DIR__ . '/config_prices.json', json_encode($priceConfig));
        $message = 'Porcentajes de precios actualizados.';
        $status = 'success';
    }

    // Save Catalog Config (Maintenance Mode)
    if ($action === 'save_catalog_config' && $isAdmin) {
        $config = [
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        file_put_contents(__DIR__ . '/config_catalogs.json', json_encode($config));
        $message = 'Configuración de catálogos actualizada.';
        $status = 'success';
    }

    // ABM Products
    if ($action === 'save_product' && $isAdmin) {
        try {
            $productData = [
                'sku' => $_POST['sku'],
                'description' => $_POST['description'],
                'brand' => $_POST['brand'] ?? '',
                'category' => $_POST['category'] ?? '',
                'subcategory' => $_POST['subcategory'] ?? '',
                'unit_cost_usd' => floatval($_POST['unit_cost_usd']),
                'iva_rate' => floatval($_POST['iva_rate'] ?? 21.00),
                'image_url' => $_POST['image_url'] ?? '',
                'stock_current' => intval($_POST['stock_current'] ?? 0),
                'supplier_id' => $_POST['supplier_id'] ?: null
            ];

            $res = $catalog->addProduct($productData);
            if ($res) {
                $message = "Producto guardado con éxito.";
                $status = 'success';
            } else {
                throw new Exception("Error al guardar en base de datos.");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = 'error';
        }
    }

    if ($action === 'delete_product' && $isAdmin) {
        if ($catalog->deleteProduct($_POST['id'])) {
            $message = "Producto eliminado correctamente.";
            $status = 'success';
        } else {
            $message = "Error al eliminar producto.";
            $status = 'error';
        }
    }

    // Delete Entity
    if ($action === 'delete_entity' && $isAdmin) {
        $db->prepare("DELETE FROM entities WHERE id = ?")->execute([$_POST['id']]);
        $message = "Entidad eliminada correctamente.";
        $status = 'success';
    }

    // ZONA PELIGROSA - Delete Actions
    if ($isAdmin && in_array($action, ['clean_prices', 'clean_users', 'clean_brands', 'clean_products', 'clean_categories', 'clean_clients', 'clean_suppliers'])) {
        try {
            switch ($action) {
                case 'clean_prices':
                    Cleanup::cleanPriceLists();
                    $message = "Listas de precios eliminadas/reseteadas.";
                    break;
                case 'clean_users':
                    Cleanup::cleanUsers();
                    $message = "Usuarios secundarios eliminados.";
                    break;
                case 'clean_brands':
                    Cleanup::cleanBrands();
                    $message = "Todas las marcas eliminadas.";
                    break;
                case 'clean_categories':
                    Cleanup::cleanCategories();
                    $message = "Todas las categorías eliminadas.";
                    break;
                case 'clean_clients':
                    Cleanup::cleanClients();
                    $message = "Todos los clientes eliminados.";
                    break;
                case 'clean_suppliers':
                    Cleanup::cleanSuppliers();
                    $message = "Todos los proveedores eliminados.";
                    break;
                case 'clean_products':
                    $doBackup = isset($_POST['backup_products']) && $_POST['backup_products'] === '1';
                    $count = Cleanup::cleanAllProducts($doBackup);
                    $message = "Se eliminaron {$count} productos correctamente.";
                    break;
            }
            $status = 'success';
        } catch (\Exception $e) {
            $message = "Error en operación crítica: " . $e->getMessage();
            $status = 'error';
        }
    }

    // Import Data handler
    if ($action === 'import_csv' && $isAdmin && !empty($_FILES['csv_file']['tmp_name'])) {
        $type = $_POST['import_type']; // products, clients, suppliers
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if ($handle) {
            $firstLine = fgets($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            rewind($handle);
            fgetcsv($handle, 1000, $delimiter); // Skip header row

            $imported = 0;
            $updated = 0;

            if ($type === 'products') {
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    if (count($data) < 4)
                        continue;
                    $sku = trim($data[0]);
                    $desc = trim($data[1]);
                    $brand = trim($data[2]);
                    $cost = floatval(str_replace(',', '.', $data[3]));
                    $iva = isset($data[4]) ? floatval(str_replace(',', '.', $data[4])) : 21.00;
                    $cat = $data[5] ?? '';
                    $subcat = $data[6] ?? '';
                    $supplierName = trim($data[7] ?? '');
                    $stock = isset($data[8]) ? intval($data[8]) : 0;

                    // Find or create supplier if name provided
                    $supplierId = null;
                    if ($supplierName) {
                        $stmtS = $db->prepare("SELECT id FROM entities WHERE name = ? AND (type = 'supplier' OR type = 'provider')");
                        $stmtS->execute([$supplierName]);
                        $supplierId = $stmtS->fetchColumn();
                        if (!$supplierId) {
                            $db->prepare("INSERT INTO entities (type, name, is_enabled) VALUES ('supplier', ?, 1)")->execute([$supplierName]);
                            $supplierId = $db->lastInsertId();
                        }
                    }

                    // Multi-supplier logic: check if exists
                    $stmtCheck = $db->prepare("SELECT id FROM products WHERE sku = ?");
                    $stmtCheck->execute([$sku]);
                    $productId = $stmtCheck->fetchColumn();

                    if (!$productId) {
                        // NEW Product
                        $catalog->addProduct([
                            'sku' => $sku,
                            'description' => $desc,
                            'brand' => $brand,
                            'unit_cost_usd' => $cost,
                            'iva_rate' => $iva,
                            'category' => $cat,
                            'subcategory' => $subcat,
                            'supplier_id' => $supplierId,
                            'stock_current' => $stock
                        ]);
                        $imported++;
                    } else {
                        // Product EXISTS
                        if ($supplierId) {
                            // Check if this supplier already has a price for this product
                            $stmtCheckSup = $db->prepare("SELECT id FROM supplier_prices WHERE product_id = ? AND supplier_id = ?");
                            $stmtCheckSup->execute([$productId, $supplierId]);
                            if ($stmtCheckSup->fetchColumn()) {
                                // SAME Supplier: Update price
                                $db->prepare("UPDATE supplier_prices SET cost_usd = ? WHERE product_id = ? AND supplier_id = ?")
                                    ->execute([$cost, $productId, $supplierId]);
                            } else {
                                // NEW Supplier for same SKU: Add as additional price
                                $db->prepare("INSERT INTO supplier_prices (product_id, supplier_id, cost_usd) VALUES (?, ?, ?)")
                                    ->execute([$productId, $supplierId, $cost]);
                            }

                            // Update stock for existing product
                            $db->prepare("UPDATE products SET stock_current = ? WHERE id = ?")
                                ->execute([$stock, $productId]);

                            // Always recalculate minimum cost on main product
                            $db->prepare("UPDATE products SET unit_cost_usd = (SELECT MIN(cost_usd) FROM supplier_prices WHERE product_id = ?) WHERE id = ?")
                                ->execute([$productId, $productId]);
                        } else {
                            // If no supplier provided, still update stock if available
                            $db->prepare("UPDATE products SET stock_current = ? WHERE id = ?")
                                ->execute([$stock, $productId]);
                        }
                        $updated++;
                    }
                }
            } elseif ($type === 'clients' || $type === 'suppliers') {
                $entityType = ($type === 'clients') ? 'client' : 'supplier';
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    if (count($data) < 2)
                        continue;
                    $name = trim($data[0]);
                    $taxId = trim($data[1]);
                    $email = trim($data[2] ?? '');
                    $phone = trim($data[3] ?? '');

                    $sqlEnt = "INSERT INTO entities (type, name, tax_id, email, phone, is_enabled) VALUES (?, ?, ?, ?, ?, 1)
                               ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), phone = VALUES(phone)";
                    $db->prepare($sqlEnt)->execute([$entityType, $name, $taxId, $email, $phone]);
                    $imported++;
                }
            }
            fclose($handle);
            $message = "Importación finalizada. Procesados: " . ($imported + $updated);
            $status = 'success';
        }
    }

}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-[#101822] text-slate-800 dark:text-white antialiased">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur">
                <div class="flex items-center gap-3">
                    <?php if ($currentSection !== 'main'): ?>
                        <a href="configuration.php?section=main"
                            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </a>
                    <?php endif; ?>
                    <h2 class="font-bold text-lg uppercase tracking-tight">Centro de Configuración</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto space-y-6">

                    <?php if ($message): ?>
                        <div
                            class="p-4 rounded-xl flex items-center gap-3 <?php echo $status === 'success' ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'; ?> border">
                            <span
                                class="material-symbols-outlined"><?php echo $status === 'success' ? 'check_circle' : 'error'; ?></span>
                            <span class="text-sm font-bold uppercase"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($currentSection === 'main'): ?>
                        <!-- MAIN GRID LAYOUT -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                            <!-- Datos Empresa -->
                            <a href="?section=company"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">domain</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Datos Empresa</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Razón social, logo, dirección y
                                    contacto.</p>
                            </a>

                            <!-- ABM Presupuestos -->
                            <a href="?section=budget"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">request_quote</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Presupuestos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración, validez y notas
                                    legales.</p>
                            </a>

                            <!-- 
                            Módulos ocultos a pedido
                            <a href="abm_billing.php"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">receipt_long</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Facturación</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Eliminar o corregir facturas emitidas.
                                </p>
                            </a>

                            <a href="abm_cuentas_corrientes.php"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Ctas. Corrientes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Corregir movimientos y saldos
                                    históricos.</p>
                            </a>
                            -->

                            <!-- ABM Clientes -->
                            <a href="config_entities.php?type=client"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-green-500/10 text-green-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">groups</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Clientes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de base de datos de clientes.
                                </p>
                            </a>

                            <!-- ABM Usuarios -->
                            <a href="?section=users"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Usuarios</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Administrar usuarios y permisos.</p>
                            </a>

                            <!-- ABM CRM -->
                            <a href="?section=crm"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">filter_alt</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM CRM</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pipelines, estados y orígenes.</p>
                            </a>

                            <!-- ABM Proveedores -->
                            <a href="config_entities.php?type=supplier"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Proveedores</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de proveedores.</p>
                            </a>

                            <!-- ABM Marcas -->
                            <a href="?section=brands"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">sell</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Marcas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de marcas de productos.</p>
                            </a>

                            <!-- ABM Transportes (NUEVO) -->
                            <a href="config_transports.php"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-emerald-500/50 transition-all hover:shadow-lg hover:shadow-emerald-500/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">local_shipping</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Transportes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Gestión de empresas de transporte.</p>
                            </a>

                            <!-- Listas de Precios (NUEVO) -->
                            <a href="?section=prices"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-yellow-500/10 text-yellow-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">price_change</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Listas de Precios</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configurar porcentajes Gremio, Web,
                                    Mostrador.</p>
                            </a>

                            <!-- Informes -->
                            <a href="?section=reports"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">bar_chart</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Informes</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Configuración de reportes del sistema.
                                </p>
                            </a>

                            <!-- ABM PRODUCTOS (NUEVO) -->
                            <a href="?section=products"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">inventory</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Productos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Carga manual y edición de productos.
                                </p>
                            </a>

                            <!-- Importación (NUEVO) -->
                            <a href="?section=import"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">upload_file</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Importar Datos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Productos, Clientes y Proveedores
                                    (CSV).</p>
                            </a>

                            <!-- ABM Catalogos (NUEVO) -->
                            <a href="?section=catalogs"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">language</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">ABM Catálogos</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Activar/Desactivar catálogo online.
                                </p>
                            </a>

                            <!-- Zona Peligrosa (NUEVO) -->
                            <?php if ($isAdmin): ?>
                                <a href="?section=dangerous_zone"
                                    class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-red-200 dark:border-red-900/30 hover:border-red-500/50 transition-all hover:shadow-lg hover:shadow-red-500/10">
                                    <div
                                        class="w-12 h-12 rounded-xl bg-red-500/10 text-red-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <span class="material-symbols-outlined text-3xl">warning</span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-1 text-red-600">ZONA PELIGROSA</h3>
                                    <p class="text-xs text-red-500/70">Limpieza masiva de datos y reseteo.</p>
                                </a>
                            <?php endif; ?>

                            <!-- Diagnóstico de Despliegue (NUEVO) -->
                            <a href="debug_deploy.php"
                                class="group bg-white dark:bg-[#16202e] p-6 rounded-2xl border border-slate-200 dark:border-[#233348] hover:border-amber-500/50 transition-all hover:shadow-lg hover:shadow-amber-500/10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">terminal</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1">Diagnóstico de Despliegue</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Verificar rutas y estados del
                                    servidor.</p>
                            </a>
                        </div>

                    <?php elseif ($currentSection === 'company'):
                        $company = loadCompanyConfig();
                        ?>
                        <!-- DATOS EMPRESA -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                            <h3 class="text-xl font-bold mb-6">Datos de la Empresa</h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="save_company">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nombre
                                            Fantasía</label>
                                        <input type="text" name="fantasy_name"
                                            value="<?php echo $company['fantasy_name']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Razón
                                            Social</label>
                                        <input type="text" name="company_name"
                                            value="<?php echo $company['company_name']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">CUIT</label>
                                        <input type="text" name="tax_id" value="<?php echo $company['tax_id']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email
                                            Sistema</label>
                                        <input type="email" name="email" value="<?php echo $company['email']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                    <div class="col-span-2">
                                        <label
                                            class="block text-xs font-bold uppercase text-slate-500 mb-1">Dirección</label>
                                        <input type="text" name="address" value="<?php echo $company['address']; ?>"
                                            class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                    </div>
                                </div>
                                <div class="pt-4">
                                    <button
                                        class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Cambios</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($currentSection === 'budget'):
                        $budgetConfig = json_decode(file_get_contents(__DIR__ . '/config_budget.json') ?: '{}', true);
                        ?>
                        <!-- ABM PRESUPUESTOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                            <h3 class="text-xl font-bold mb-6">Configuración de Presupuestos</h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="save_budget_config">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Validez por defecto
                                        (Horas)</label>
                                    <input type="number" name="validity_hours"
                                        value="<?php echo $budgetConfig['validity_hours'] ?? 48; ?>"
                                        class="w-32 bg-slate-50 dark:bg-[#101822] rounded-lg border-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Notas Legales /
                                        Términos</label>
                                    <textarea name="legal_notes" rows="5"
                                        class="w-full bg-slate-50 dark:bg-[#101822] rounded-lg border-none"><?php echo $budgetConfig['legal_notes'] ?? ''; ?></textarea>
                                </div>
                                <div class="pt-4">
                                    <button
                                        class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Configuración</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($currentSection === 'users'):
                        $users = $db->query("SELECT * FROM users ORDER BY username")->fetchAll();

                        // Check for Edit Mode
                        $editUser = null;
                        if (isset($_GET['edit'])) {
                            foreach ($users as $u) {
                                if ($u['id'] == $_GET['edit']) {
                                    $editUser = $u;
                                    break;
                                }
                            }
                        }
                        ?>
                        <!-- ABM USUARIOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348]">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">manage_accounts</span>
                                Gestión de Usuarios
                            </h3>

                            <!-- User Form (Create/Edit) -->
                            <form method="POST"
                                class="bg-slate-50 dark:bg-[#101822] p-6 rounded-xl mb-8 border border-slate-100 dark:border-white/5">
                                <input type="hidden" name="action"
                                    value="<?php echo $editUser ? 'update_user' : 'create_user'; ?>">
                                <?php if ($editUser): ?>
                                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="text-sm font-bold uppercase text-primary">Editando Usuario:
                                            <?php echo $editUser['username']; ?>
                                        </h4>
                                        <a href="configuration.php?section=users"
                                            class="text-xs bg-slate-200 dark:bg-slate-700 px-3 py-1 rounded-lg">Cancelar
                                            Edición</a>
                                    </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Usuario
                                            (Login)</label>
                                        <input type="text" name="username"
                                            value="<?php echo $editUser['username'] ?? ''; ?>" <?php echo $editUser ? 'readonly class="bg-slate-200 dark:bg-slate-800 text-slate-500"' : 'required'; ?> class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div class="">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Nombre
                                            Completo</label>
                                        <input type="text" name="full_name"
                                            value="<?php echo $editUser['full_name'] ?? ''; ?>" required
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div class="">
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Email</label>
                                        <input type="email" name="email" value="<?php echo $editUser['email'] ?? ''; ?>"
                                            required
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div class="">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Rol
                                            (Permisos)</label>
                                        <select name="role"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                            <option value="vendedor" <?php echo ($editUser['role'] ?? '') === 'vendedor' ? 'selected' : ''; ?>>Vendedor (Limitado)</option>
                                            <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador (Total)</option>
                                            <option value="logistica" <?php echo ($editUser['role'] ?? '') === 'logistica' ? 'selected' : ''; ?>>Logística (Envíos)</option>
                                        </select>
                                    </div>
                                    <div class="">
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Estado</label>
                                        <select name="status"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                            <option value="Active" <?php echo ($editUser['status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="Inactive" <?php echo ($editUser['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactivo (Bloqueado)</option>
                                        </select>
                                    </div>
                                    <div class="">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Contraseña
                                            <?php echo $editUser ? '(Dejar en blanco para mantener)' : ''; ?></label>
                                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?>
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                </div>
                                <div class="mt-4 text-right">
                                    <button
                                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase shadow hover:scale-105 transition-transform">
                                        <?php echo $editUser ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                                    </button>
                                </div>
                            </form>

                            <table class="w-full text-left">
                                <thead
                                    class="border-b border-slate-200 dark:border-white/10 uppercase text-xs font-bold text-slate-500">
                                    <tr>
                                        <th class="pb-3 px-4">Usuario</th>
                                        <th class="pb-3 px-4">Nombre</th>
                                        <th class="pb-3 px-4">Rol</th>
                                        <th class="pb-3 px-4">Estado</th>
                                        <th class="pb-3 px-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <?php foreach ($users as $u): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                            <td class="py-3 px-4 text-sm font-bold"><?php echo $u['username']; ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo $u['full_name']; ?></td>
                                            <td class="py-3 px-4"><span
                                                    class="px-2 py-1 bg-primary/10 text-primary rounded text-[10px] font-bold uppercase"><?php echo $u['role']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span
                                                    class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $u['status'] === 'Active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                    <?php echo $u['status']; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <a href="?section=users&edit=<?php echo $u['id']; ?>"
                                                    class="text-primary font-bold text-xs uppercase hover:underline mr-3">Editar</a>
                                                <?php if ($isAdmin): ?>
                                                    <form method="POST" class="inline"
                                                        onsubmit="return confirm('¿Eliminar usuario definitivamente?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                        <button
                                                            class="text-red-500 font-bold text-xs uppercase hover:underline">Eliminar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($currentSection === 'dangerous_zone' && $isAdmin): ?>
                        <!-- ZONA PELIGROSA -->
                        <div class="bg-[#2a1111] dark:bg-[#1a0b0b] p-8 rounded-2xl border border-red-500/30 relative overflow-hidden">
                            <!-- Background warning pattern -->
                            <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: repeating-linear-gradient(45deg, #ef4444 0, #ef4444 10px, transparent 10px, transparent 20px);"></div>
                            
                            <h3 class="text-2xl font-black mb-2 text-red-500 flex items-center gap-3">
                                <span class="material-symbols-outlined text-4xl">warning</span>
                                ZONA PELIGROSA
                            </h3>
                            <p class="text-red-400 mb-8 max-w-2xl text-sm leading-relaxed">
                                Las acciones en esta sección son destructivas y no se pueden deshacer (a menos que haya un backup habilitado). Por favor, proceda con extrema precaución.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                                
                                <!-- Borrar Productos -->
                                <div class="bg-black/20 p-6 rounded-xl border border-red-500/20 hover:border-red-500/50 transition-colors">
                                    <h4 class="font-bold text-red-400 mb-2">Borrar TODOS los Productos</h4>
                                    <p class="text-xs text-red-300/70 mb-4 h-8">Elimina el catálogo completo incluyendo precios de proveedores.</p>
                                    <button onclick="confirmDangerousAction('clean_products', true)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-full transition-colors flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[16px]">delete_forever</span> Ejecutar
                                    </button>
                                </div>

                                <!-- Borrar Usuarios -->
                                <div class="bg-black/20 p-6 rounded-xl border border-red-500/20 hover:border-red-500/50 transition-colors">
                                    <h4 class="font-bold text-red-400 mb-2">Borrar Usuarios</h4>
                                    <p class="text-xs text-red-300/70 mb-4 h-8">Elimina todos los usuarios del sistema EXCEPTO los administradores.</p>
                                    <button onclick="confirmDangerousAction('clean_users')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-full transition-colors flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[16px]">delete_forever</span> Ejecutar
                                    </button>
                                </div>
                                
                                <!-- Borrar Marcas / Categorias -->
                                <div class="bg-black/20 p-6 rounded-xl border border-red-500/20 hover:border-red-500/50 transition-colors">
                                    <h4 class="font-bold text-red-400 mb-2">Borrar Marcas y Categorías</h4>
                                    <p class="text-xs text-red-300/70 mb-4 h-8">Limpia las tablas de marcas y categorías.</p>
                                    <div class="flex gap-2">
                                        <button onclick="confirmDangerousAction('clean_brands')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-1/2 transition-colors">Marcas</button>
                                        <button onclick="confirmDangerousAction('clean_categories')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-1/2 transition-colors">Categorías</button>
                                    </div>
                                </div>
                                
                                <!-- Borrar Clientes/Proveedores -->
                                <div class="bg-black/20 p-6 rounded-xl border border-red-500/20 hover:border-red-500/50 transition-colors">
                                    <h4 class="font-bold text-red-400 mb-2">Entidades</h4>
                                    <p class="text-xs text-red-300/70 mb-4 h-8">Elimina todos los clientes o todos los proveedores.</p>
                                    <div class="flex gap-2">
                                        <button onclick="confirmDangerousAction('clean_clients')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-1/2 transition-colors">Clientes</button>
                                        <button onclick="confirmDangerousAction('clean_suppliers')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider w-1/2 transition-colors">Prov.</button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Modal Confirmacion -->
                        <div id="dangerModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
                            <div class="bg-[#101822] border border-red-500 rounded-2xl p-8 max-w-sm w-full relative shadow-2xl shadow-red-900/50">
                                <h3 class="text-xl font-bold text-red-500 mb-4 text-center">CONFIRMACIÓN REQUERIDA</h3>
                                <p class="text-sm text-slate-300 text-center mb-6">Para proceder, escriba la palabra <strong class="text-white bg-red-500/20 px-2 py-0.5 rounded">CONFIRMAR</strong> a continuación.</p>
                                
                                <form method="POST" id="dangerForm">
                                    <input type="hidden" name="action" id="dangerActionInput" value="">
                                    
                                    <div id="backupCheckboxDiv" class="mb-4 hidden flex items-center gap-2 bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                                        <input type="checkbox" name="backup_products" value="1" id="backupProducts" class="rounded border-slate-600 text-primary focus:ring-primary bg-slate-900" checked>
                                        <label for="backupProducts" class="text-xs text-slate-300 cursor-pointer">Realizar backup antes de borrar</label>
                                    </div>

                                    <input type="text" id="confirmWord" autocomplete="off" class="w-full bg-black/50 border border-slate-700 rounded-lg text-center font-bold text-red-400 uppercase tracking-widest mb-4" placeholder="...">
                                    
                                    <div class="flex gap-3">
                                        <button type="button" onclick="closeDangerModal()" class="w-1/2 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors">Cancelar</button>
                                        <button type="submit" id="dangerSubmitBtn" class="w-1/2 bg-red-600/50 text-red-200 cursor-not-allowed font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all" disabled>Destruir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            const confirmInput = document.getElementById('confirmWord');
                            const submitBtn = document.getElementById('dangerSubmitBtn');

                            if (confirmInput) {
                                confirmInput.addEventListener('input', function() {
                                    if(this.value.trim().toUpperCase() === 'CONFIRMAR') {
                                        submitBtn.disabled = false;
                                        submitBtn.classList.remove('bg-red-600/50', 'text-red-200', 'cursor-not-allowed');
                                        submitBtn.classList.add('bg-red-600', 'hover:bg-red-500', 'text-white', 'shadow-[0_0_15px_rgba(220,38,38,0.5)]');
                                    } else {
                                        submitBtn.disabled = true;
                                        submitBtn.classList.add('bg-red-600/50', 'text-red-200', 'cursor-not-allowed');
                                        submitBtn.classList.remove('bg-red-600', 'hover:bg-red-500', 'text-white', 'shadow-[0_0_15px_rgba(220,38,38,0.5)]');
                                    }
                                });
                            }

                            function confirmDangerousAction(action, showBackup = false) {
                                document.getElementById('dangerActionInput').value = action;
                                document.getElementById('confirmWord').value = '';
                                confirmInput.dispatchEvent(new Event('input')); // reset btn state
                                document.getElementById('dangerModal').classList.remove('hidden');
                                
                                if(showBackup) {
                                    document.getElementById('backupCheckboxDiv').classList.remove('hidden');
                                } else {
                                    document.getElementById('backupCheckboxDiv').classList.add('hidden');
                                }
                                
                                setTimeout(() => document.getElementById('confirmWord').focus(), 100);
                            }

                            function closeDangerModal() {
                                document.getElementById('dangerModal').classList.add('hidden');
                            }
                        </script>

                    <?php elseif ($currentSection === 'brands'):
                        $brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();
                        ?>
                        <!-- ABM MARCAS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-2xl">
                            <h3 class="text-xl font-bold mb-6">Gestión de Marcas</h3>

                            <form method="POST" class="flex gap-4 mb-8">
                                <input type="hidden" name="action" value="create_brand">
                                <input type="text" name="name" placeholder="Nueva Marca..." required
                                    class="flex-1 bg-slate-50 dark:bg-[#101822] rounded-xl border-none">
                                <button
                                    class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow">Agregar</button>
                            </form>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($brands as $b): ?>
                                    <div
                                        class="bg-slate-50 dark:bg-white/5 p-3 rounded-lg flex items-center justify-between group">
                                        <span class="font-bold text-sm"><?php echo $b['name']; ?></span>
                                        <?php if ($isAdmin): ?>
                                            <form method="POST" onsubmit="return confirm('¿Borrar?');">
                                                <input type="hidden" name="action" value="delete_brand">
                                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                                <button
                                                    class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><span
                                                        class="material-symbols-outlined text-lg">delete</span></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php elseif ($currentSection === 'clients'):
                        $entityType = 'client';
                        ?>
                        <!-- ABM CLIENTES (Redirect or Include) -->
                        <?php include 'config_entities_partial.php'; ?>

                    <?php elseif ($currentSection === 'reports'): ?>
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] text-center py-20">
                            <div class="inline-flex bg-cyan-500/10 p-4 rounded-full text-cyan-500 mb-4">
                                <span class="material-symbols-outlined text-4xl">bar_chart</span>
                            </div>
                            <h3 class="text-xl font-bold uppercase mb-2">Informes del Sistema</h3>
                            <p class="text-slate-500 text-sm max-w-md mx-auto">Selecciona un informe desde el panel lateral
                                "Contabilidad > Informes" para ver las estadísticas detalladas.</p>
                        </div>

                    <?php elseif ($currentSection === 'prices'):
                        // Load Price Config
                        $priceConfig = json_decode(file_get_contents(__DIR__ . '/config_prices.json') ?: '{"gremio": 25, "web": 40, "mostrador": 55}', true);
                        ?>
                        <!-- CONFIGURACION PRECIOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-3xl">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-yellow-500">price_change</span>
                                Configuración de Listas de Precios
                            </h3>

                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="save_price_config">

                                <div
                                    class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/20 text-xs text-blue-600 dark:text-blue-400 mb-4">
                                    <strong>Referencia:</strong> Los precios se calculan automáticamente sumando el
                                    porcentaje configurado al <strong>Costo de Compra</strong> del producto.
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Gremio
                                            (%)</label>
                                        <div class="relative">
                                            <input type="number" name="gremio" step="0.1"
                                                value="<?php echo $priceConfig['gremio']; ?>"
                                                class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                            <span class="absolute right-3 top-2 text-slate-400">%</span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1">Costo + Margen</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Web
                                            (%)</label>
                                        <div class="relative">
                                            <input type="number" name="web" step="0.1"
                                                value="<?php echo $priceConfig['web']; ?>"
                                                class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                            <span class="absolute right-3 top-2 text-slate-400">%</span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1">Transf. / Efectivo</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Lista Mostrador
                                            (%)</label>
                                        <div class="relative">
                                            <input type="number" name="mostrador" step="0.1"
                                                value="<?php echo $priceConfig['mostrador']; ?>"
                                                class="w-full pl-4 pr-8 py-2 bg-slate-50 dark:bg-[#101822] rounded-lg border-none font-bold">
                                            <span class="absolute right-3 top-2 text-slate-400">%</span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1">Público General</p>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 dark:border-white/5 flex justify-end">
                                    <button
                                        class="bg-primary text-white px-6 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Porcentajes</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($currentSection === 'products'):
                        $allProducts = $catalog->getAllProducts();
                        $suppliersListForSelect = $catalog->getProviders();

                        $editProductSelected = null;
                        if (isset($_GET['edit'])) {
                            $idToEdit = intval($_GET['edit']);
                            $editProductSelected = $catalog->getProductById($idToEdit);
                        }
                        ?>
                        <!-- ABM PRODUCTOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348]">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-violet-500">inventory</span>
                                Gestión de Productos
                            </h3>

                            <!-- Product Form -->
                            <form method="POST"
                                class="bg-slate-50 dark:bg-[#101822] p-6 rounded-xl mb-8 border border-slate-100 dark:border-white/5">
                                <input type="hidden" name="action" value="save_product">
                                <?php if ($editProductSelected): ?>
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="text-sm font-bold uppercase text-primary">Editando:
                                            <?php echo htmlspecialchars($editProductSelected['sku']); ?>
                                        </h4>
                                        <a href="configuration.php?section=products"
                                            class="text-xs bg-slate-200 dark:bg-slate-700 px-3 py-1 rounded-lg">Nueva Carga</a>
                                    </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="col-span-1">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">SKU</label>
                                        <input type="text" name="sku"
                                            value="<?php echo $editProductSelected['sku'] ?? ''; ?>" required
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div class="lg:col-span-2 col-span-1">
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Descripción</label>
                                        <input type="text" name="description"
                                            value="<?php echo $editProductSelected['description'] ?? ''; ?>" required
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div class="col-span-1">
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Marca</label>
                                        <input type="text" name="brand"
                                            value="<?php echo $editProductSelected['brand'] ?? ''; ?>"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>

                                    <div>
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Categoría</label>
                                        <input type="text" name="category"
                                            value="<?php echo $editProductSelected['category'] ?? ''; ?>"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Subcategoría</label>
                                        <input type="text" name="subcategory"
                                            value="<?php echo $editProductSelected['subcategory'] ?? ''; ?>"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Costo
                                            USD</label>
                                        <input type="number" step="0.01" name="unit_cost_usd"
                                            value="<?php echo $editProductSelected['unit_cost_usd'] ?? ''; ?>" required
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">IVA
                                            (%)</label>
                                        <select name="iva_rate"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                            <option value="21.00" <?php echo ($editProductSelected['iva_rate'] ?? '21.00') == '21.00' ? 'selected' : ''; ?>>21%</option>
                                            <option value="10.50" <?php echo ($editProductSelected['iva_rate'] ?? '') == '10.50' ? 'selected' : ''; ?>>10.5%</option>
                                            <option value="0.00" <?php echo ($editProductSelected['iva_rate'] ?? '') == '0.00' ? 'selected' : ''; ?>>Exento</option>
                                        </select>
                                    </div>

                                    <div class="lg:col-span-2 col-span-1">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">URL
                                            Imagen</label>
                                        <input type="text" name="image_url"
                                            value="<?php echo $editProductSelected['image_url'] ?? ''; ?>"
                                            placeholder="https://..."
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Stock
                                            Inicial/Actual</label>
                                        <input type="number" name="stock_current"
                                            value="<?php echo $editProductSelected['stock_current'] ?? ''; ?>"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block">Proveedor
                                            Principal</label>
                                        <select name="supplier_id"
                                            class="w-full rounded-lg text-xs border-none bg-white dark:bg-[#16202e]">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($suppliersListForSelect as $sItm): ?>
                                                <option value="<?php echo $sItm['id']; ?>" <?php echo ($editProductSelected['supplier_id'] ?? '') == $sItm['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sItm['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button
                                        class="bg-primary text-white px-8 py-3 rounded-xl font-bold text-xs uppercase shadow hover:scale-105 transition-transform">
                                        <?php echo $editProductSelected ? 'Actualizar Producto' : 'Añadir Producto'; ?>
                                    </button>
                                </div>
                            </form>

                            <!-- Product List -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead
                                        class="border-b border-slate-200 dark:border-white/10 uppercase text-[10px] font-bold text-slate-500">
                                        <tr>
                                            <th class="pb-3 px-2">Imagen</th>
                                            <th class="pb-3 px-2">SKU</th>
                                            <th class="pb-3 px-2">Descripción</th>
                                            <th class="pb-3 px-2">Marca</th>
                                            <th class="pb-3 px-2">Costo USD</th>
                                            <th class="pb-3 px-2">Stock</th>
                                            <th class="pb-3 px-2 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <?php foreach ($allProducts as $pObj): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                <td class="py-2 px-2">
                                                    <div
                                                        class="w-10 h-10 rounded border border-slate-200 dark:border-white/10 overflow-hidden flex items-center justify-center bg-white">
                                                        <?php if ($pObj['image_url']): ?>
                                                            <img src="<?php echo htmlspecialchars($pObj['image_url']); ?>"
                                                                class="max-w-full max-h-full object-contain">
                                                        <?php else: ?>
                                                            <img src="https://www.vecinoseguro.com/src/img/VSLogo_v2.jpg"
                                                                class="max-w-full max-h-full object-contain opacity-30 grayscale">
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-2 text-xs font-bold">
                                                    <?php echo htmlspecialchars($pObj['sku']); ?>
                                                </td>
                                                <td class="py-2 px-2 text-xs max-w-[200px] truncate">
                                                    <?php echo htmlspecialchars($pObj['description']); ?>
                                                </td>
                                                <td class="py-2 px-2 text-xs"><?php echo htmlspecialchars($pObj['brand']); ?>
                                                </td>
                                                <td class="py-2 px-2 text-xs font-bold">
                                                    $<?php echo number_format($pObj['unit_cost_usd'], 2); ?></td>
                                                <td class="py-2 px-2">
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold <?php echo $pObj['stock_current'] > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                        <?php echo $pObj['stock_current']; ?>
                                                    </span>
                                                </td>
                                                <td class="py-2 px-2 text-right">
                                                    <a href="?section=products&edit=<?php echo $pObj['id']; ?>"
                                                        class="text-primary hover:underline text-xs font-bold uppercase mr-3">Editar</a>
                                                    <form method="POST" class="inline"
                                                        onsubmit="return confirm('¿Eliminar producto?');">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="id" value="<?php echo $pObj['id']; ?>">
                                                        <button
                                                            class="text-red-500 hover:underline text-xs font-bold uppercase">Borrar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php elseif ($currentSection === 'transports'):
                        $entityType = 'transport';
                        ?>
                        <!-- ABM TRANSPORTES -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348]">
                            <?php include 'config_entities_partial.php'; ?>
                        </div>

                    <?php elseif ($currentSection === 'crm'): ?>
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] text-center py-20">
                            <h3 class="text-xl font-bold uppercase text-slate-400">ABM CRM - Próximamente</h3>
                        </div>

                    <?php elseif ($currentSection === 'purchases'):
                        $entityType = 'supplier';
                        ?>
                        <!-- ABM PROVEEDORES -->
                        <?php include 'config_entities_partial.php'; ?>

                    <?php elseif ($currentSection === 'import'): ?>
                        <!-- IMPORTACION DE DATOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-2xl">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-rose-500">upload_file</span>
                                Importación masiva (CSV)
                            </h3>

                            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                                <input type="hidden" name="action" value="import_csv">

                                <div class="space-y-2">
                                    <label class="block text-xs font-bold uppercase text-slate-500">Tipo de Datos</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="import_type" value="products" checked
                                                class="hidden peer">
                                            <div
                                                class="p-3 text-center border border-slate-200 dark:border-white/5 rounded-xl peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary transition-all">
                                                <span class="material-symbols-outlined block mb-1">inventory_2</span>
                                                <span class="text-[10px] font-bold uppercase">Productos</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="import_type" value="clients" class="hidden peer">
                                            <div
                                                class="p-3 text-center border border-slate-200 dark:border-white/5 rounded-xl peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary transition-all">
                                                <span class="material-symbols-outlined block mb-1">groups</span>
                                                <span class="text-[10px] font-bold uppercase">Clientes</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="import_type" value="suppliers" class="hidden peer">
                                            <div
                                                class="p-3 text-center border border-slate-200 dark:border-white/5 rounded-xl peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary transition-all">
                                                <span class="material-symbols-outlined block mb-1">local_shipping</span>
                                                <span class="text-[10px] font-bold uppercase">Proveedores</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div
                                    class="p-6 border-2 border-dashed border-slate-200 dark:border-white/10 rounded-2xl text-center">
                                    <label class="cursor-pointer block">
                                        <input type="file" name="csv_file" accept=".csv" class="hidden" required>
                                        <div class="text-slate-500">
                                            <span class="material-symbols-outlined text-4xl mb-2">cloud_upload</span>
                                            <p class="text-sm">Haz clic para seleccionar el archivo CSV</p>
                                            <p class="text-[10px] uppercase font-bold mt-1">Formato:
                                                SKU;Descripción;Marca;Costo;IVA;Cat;Subcat;Proveedor;Stock</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="bg-blue-500/5 p-4 rounded-xl border border-blue-500/10">
                                    <p class="text-xs text-blue-500 leading-relaxed">
                                        <strong>Nota sobre productos:</strong> Si el SKU ya existe y el proveedor es
                                        distinto, se agregará como precio adicional para comparación. Si es el mismo
                                        proveedor, se actualizará el precio actual.
                                    </p>
                                </div>

                                <div class="pt-4 flex justify-end">
                                    <button
                                        class="bg-primary text-white px-8 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Comenzar
                                        Importación</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($currentSection === 'catalogs'):
                        $catConfig = json_decode(file_get_contents(__DIR__ . '/config_catalogs.json') ?: '{"maintenance_mode": 0}', true);
                        ?>
                        <!-- ABM CATALOGOS -->
                        <div
                            class="bg-white dark:bg-[#16202e] p-8 rounded-2xl border border-slate-200 dark:border-[#233348] max-w-2xl">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-violet-500">language</span>
                                Gestión de Catálogos Online
                            </h3>

                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="save_catalog_config">

                                <div
                                    class="flex items-center justify-between p-4 bg-slate-50 dark:bg-[#101822] rounded-xl border border-slate-200 dark:border-[#233348]">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm">Modo Mantenimiento</span>
                                        <span class="text-xs text-slate-500">Si se activa, los catálogos públicos mostrarán
                                            un aviso y no serán accesibles.</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="maintenance_mode" value="1" <?php echo ($catConfig['maintenance_mode'] ?? 0) ? 'checked' : ''; ?> class="sr-only peer">
                                        <div
                                            class="w-11 h-6 bg-slate-200 peer-focus:outline-none dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary">
                                        </div>
                                    </label>
                                </div>

                                <?php if ($catConfig['maintenance_mode'] ?? 0): ?>
                                    <div
                                        class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center gap-3">
                                        <span class="material-symbols-outlined text-amber-500">warning</span>
                                        <span class="text-xs text-amber-500 font-bold uppercase tracking-tight">El catálogo está
                                            fuera de línea actualmente.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="pt-4 flex justify-end">
                                    <button
                                        class="bg-primary text-white px-8 py-3 rounded-xl font-bold uppercase text-sm shadow-lg hover:scale-105 transition-transform">Guardar
                                        Cambios</button>
                                </div>
                            </form>
                        </div>

                    <?php else: ?>
                        <!-- DEFAULT / FALLBACK -->
                        <div class="text-center py-20">
                            <h3 class="text-xl text-slate-400 font-bold uppercase">Sección no encontrada:
                                <?php echo htmlspecialchars($currentSection); ?>
                            </h3>
                            <a href="configuration.php?section=main" class="text-primary mt-4 inline-block font-bold">Volver
                                al Centro de Configuración</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>

</html>