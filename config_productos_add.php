<?php
require_once 'auth_check.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$catalog = new Catalog();
$message = '';
$status = '';

// Handle save logic (Keep exactly same backend logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $data = [
        'sku' => $_POST['sku'],
        'barcode' => $_POST['barcode'] ?? null,
        'provider_code' => $_POST['provider_code'] ?? null,
        'description' => $_POST['description'],
        'category' => $_POST['category'] ?? '',
        'subcategory' => $_POST['subcategory'] ?? '',
        'unit_cost_usd' => $_POST['unit_cost_usd'],
        // 'unit_price_usd' removed, calculated automatically
        'iva_rate' => $_POST['iva_rate'],
        'brand' => $_POST['brand'] ?? '',
        'image_url' => $_POST['image_url'] ?? null,
        'has_serial_number' => isset($_POST['has_serial_number']) ? 1 : 0,
        'stock_current' => $_POST['stock_current'] ?? 0,
        'supplier_id' => !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null
    ];

    if ($catalog->addProduct($data)) {
        $message = "Producto guardado correctamente.";
        $status = "success";
        header("Location: config_productos_add.php?msg=success"); // Post-redirect-get pattern
        exit;
    } else {
        $message = "Error al guardar el producto.";
        $status = "error";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $message = "Producto guardado correctamente.";
    $status = "success";
}

$suppliers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Productos - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#136dec",
                        brand: "#5d2fc1",
                        dark: "#0f172a",
                        surface: "#1e293b",
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-white antialiased h-screen flex flex-col md:flex-row overflow-hidden transition-colors duration-300">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative w-full h-full">
        <!-- Header -->
        <header
            class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a]/95 backdrop-blur z-20 sticky top-0 transition-colors duration-300">
            <div class="flex items-center gap-4">
                <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="text-lg font-bold uppercase tracking-tight">Carga Manual de <span
                        class="text-primary">Productos</span></h2>
            </div>
            <div class="flex items-center gap-4">
                <div
                    class="hidden md:flex items-center gap-2 bg-slate-100 dark:bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-300">Sistema Activo</span>
                </div>
            </div>
        </header>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
            <div class="max-w-5xl mx-auto">

                <?php if ($message): ?>
                    <div
                        class="mb-6 p-4 rounded-xl flex items-center gap-3 shadow-lg <?php echo $status === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400'; ?>">
                        <span
                            class="material-symbols-outlined"><?php echo $status === 'success' ? 'check_circle' : 'error'; ?></span>
                        <p class="font-medium text-sm"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <div
                    class="bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-xl transition-colors duration-300">
                    <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">add_box</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Nuevo Producto</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Complete la ficha técnica para agregar
                                al catálogo</p>
                        </div>
                    </div>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <input type="hidden" name="save_product" value="1">

                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">SKU
                                    (Código Interno)</label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">fingerprint</span>
                                    <input type="text" name="sku" required placeholder="Ej: HD1TB-WD"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 pl-10 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600 text-slate-800 dark:text-white">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Descripción</label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">description</span>
                                    <input type="text" name="description" required
                                        placeholder="Ej: Disco Rígido 1TB Western Digital Blue"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 pl-10 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600 text-slate-800 dark:text-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Marca</label>
                                    <input type="text" name="brand" placeholder="Ej: Western Digital"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-800 dark:text-white">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">EAN
                                        (Opcional)</label>
                                    <input type="text" name="barcode" placeholder="Código de Barras"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-800 dark:text-white">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Categoría</label>
                                <input type="text" name="category" placeholder="Ej: Almacenamiento"
                                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-800 dark:text-white">
                            </div>

                            <label
                                class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors">
                                <input type="checkbox" name="has_serial_number"
                                    class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-primary focus:ring-offset-0 focus:ring-primary shadow-sm">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Requiere Número de
                                    Serie (Trazabilidad)</span>
                            </label>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Imagen
                                    URL</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span
                                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">image</span>
                                        <input type="text" name="image_url" id="img-input" placeholder="https://..."
                                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 pl-10 py-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-600 text-slate-800 dark:text-white"
                                            onchange="updatePreview(this.value)">
                                    </div>
                                    <div
                                        class="w-12 h-12 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden">
                                        <img id="img-preview" src="" onerror="this.style.display='none'"
                                            class="w-full h-full object-cover" style="display:none">
                                        <span
                                            class="material-symbols-outlined text-xs text-slate-400 dark:text-slate-600"
                                            id="img-placeholder">image</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Costo
                                        USD</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-xs font-bold">USD</span>
                                        <input type="number" step="0.01" name="unit_cost_usd" required
                                            placeholder="0.00"
                                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 pl-10 py-3 text-sm font-mono text-emerald-600 dark:text-emerald-400 font-bold focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
                                    </div>
                                </div>
                                <!-- Removed Unit Price input -->
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Tasa
                                        IVA</label>
                                    <select name="iva_rate"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary outline-none appearance-none text-slate-800 dark:text-white">
                                        <option value="21">21% (Estándar)</option>
                                        <option value="10.5">10.5% (Reducido)</option>
                                        <option value="0">0% (Exento)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Stock
                                        Inicial</label>
                                    <input type="number" name="stock_current" value="0"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary outline-none transition-all text-slate-800 dark:text-white">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Proveedor
                                    Principal</label>
                                <select name="supplier_id"
                                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:border-primary outline-none text-slate-800 dark:text-white">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>

                        <!-- Actions -->
                        <div
                            class="md:col-span-2 pt-6 mt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                            <a href="configuration.php"
                                class="px-6 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-bold transition-all">CANCELAR</a>
                            <button type="submit"
                                class="px-8 py-3 rounded-xl bg-primary hover:bg-blue-600 text-white text-sm font-bold shadow-lg shadow-primary/25 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined">save</span>
                                GUARDAR PRODUCTO
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        function updatePreview(url) {
            const img = document.getElementById('img-preview');
            const placeholder = document.getElementById('img-placeholder');
            if (url) {
                img.src = url;
                img.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                img.style.display = 'none';
                placeholder.style.display = 'block';
            }
        }
    </script>
</body>

</html>