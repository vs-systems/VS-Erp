<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$catalog = new Catalog();
$priceListModule = new PriceList();

$products = $catalog->getAllProducts();
$lists = $priceListModule->getAll();

$listsByName = [];
foreach ($lists as $l) {
    $listsByName[$l['name']] = $l['margin_percent'];
}

$gremioMargin = $listsByName['Gremio'] ?? 30;
$webMargin = $listsByName['Web'] ?? 40;
$mlMargin = $listsByName['MercadoLibre'] ?? 50;
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - VS System</title>
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
                        <span class="material-symbols-outlined text-2xl">inventory_2</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Catálogo de
                        Productos</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg group-focus-within:text-primary transition-colors">search</span>
                        <input type="text" id="tableSearch" placeholder="Buscar por SKU o descripción..."
                            class="w-64 lg:w-96 pl-10 bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-[#233348] rounded-xl text-sm focus:ring-primary focus:border-primary transition-all">
                    </div>
                    <a href="config_productos_add.php"
                        class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">add_box</span>
                        CARGA MANUAL
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-[1600px] mx-auto space-y-6">

                    <div class="flex justify-between items-end">
                        <h1 class="text-2xl font-bold dark:text-white text-slate-800 tracking-tight">Inventario Maestro
                        </h1>
                        <div class="flex gap-2">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] px-4 py-2 rounded-xl flex items-center gap-3">
                                <span
                                    class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">Productos</span>
                                <span class="text-lg font-bold text-primary"><?php echo count($products); ?></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4">Información del Producto</th>
                                        <th class="px-6 py-4">Rubro / Marca</th>
                                        <th class="px-6 py-4 text-right bg-primary/5 dark:bg-primary/5 text-primary">
                                            Costo USD</th>
                                        <th class="px-6 py-4 text-right">Gremio (+<?php echo (int) $gremioMargin; ?>%)
                                        </th>
                                        <th class="px-6 py-4 text-right">Web (+<?php echo (int) $webMargin; ?>%)</th>
                                        <th class="px-6 py-4 text-right">ML (+<?php echo (int) $mlMargin; ?>%)</th>
                                        <th class="px-6 py-4 text-center">IVA</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <?php foreach ($products as $p):
                                        $cost = $p['unit_cost_usd'];
                                        $priceGremio = $cost * (1 + ($gremioMargin / 100));
                                        $priceWeb = $cost * (1 + ($webMargin / 100));
                                        $priceML = $cost * (1 + ($mlMargin / 100));
                                        ?>
                                        <tr class="product-row hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group"
                                            data-sku="<?php echo strtolower($p['sku']); ?>"
                                            data-desc="<?php echo strtolower($p['description']); ?>">
                                            <td class="px-6 py-5">
                                                <div
                                                    class="font-bold text-sm dark:text-white text-slate-800 group-hover:text-primary transition-colors">
                                                    <?php echo $p['sku']; ?>
                                                </div>
                                                <div
                                                    class="text-[11px] text-slate-500 font-medium max-w-[250px] truncate group-hover:dark:text-slate-300 transition-colors">
                                                    <?php echo $p['description']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div
                                                    class="flex items-center gap-1 text-[10px] font-bold dark:text-slate-400 text-slate-600 uppercase tracking-widest mb-1">
                                                    <span class="material-symbols-outlined text-[14px]">category</span>
                                                    <?php echo $p['category'] ?: 'General'; ?>
                                                </div>
                                                <div
                                                    class="flex items-center gap-1 text-[10px] font-bold text-primary uppercase tracking-widest">
                                                    <span
                                                        class="material-symbols-outlined text-[14px]">branding_watermark</span>
                                                    <?php echo $p['brand'] ?: 'VS System'; ?>
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-5 text-right font-mono font-bold text-primary bg-primary/5 group-hover:bg-primary/10 transition-colors">
                                                $ <?php echo number_format($cost, 2); ?>
                                            </td>
                                            <td
                                                class="px-6 py-5 text-right font-mono text-xs dark:text-slate-300 text-slate-600">
                                                $ <?php echo number_format($priceGremio, 2); ?>
                                            </td>
                                            <td
                                                class="px-6 py-5 text-right font-mono text-xs dark:text-slate-300 text-slate-600">
                                                $ <?php echo number_format($priceWeb, 2); ?>
                                            </td>
                                            <td
                                                class="px-6 py-5 text-right font-mono text-xs dark:text-slate-300 text-slate-600">
                                                $ <?php echo number_format($priceML, 2); ?>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <span
                                                    class="inline-flex px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-white/10 text-[10px] font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/5">
                                                    <?php echo $p['iva_rate']; ?>%
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex items-center justify-center">
                                                    <a href="config_productos_add.php?sku=<?php echo urlencode($p['sku']); ?>"
                                                        class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all"
                                                        title="Editar Producto">
                                                        <span
                                                            class="material-symbols-outlined text-[18px]">edit_square</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty Results Placeholder -->
                        <div id="noResults"
                            class="hidden py-12 flex flex-col items-center justify-center text-slate-400 dark:text-slate-600">
                            <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
                            <span class="text-sm font-bold uppercase tracking-widest">No se encontraron productos</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('tableSearch').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const sku = row.getAttribute('data-sku');
                const desc = row.getAttribute('data-desc');
                if (sku.includes(q) || desc.includes(q)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('noResults');
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
                document.querySelector('table').classList.add('hidden');
            } else {
                noResults.classList.add('hidden');
                document.querySelector('table').classList.remove('hidden');
            }
        });
    </script>
</body>

</html>