<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';

use Vsys\Lib\Database;
use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$db = Database::getInstance();
$catalog = new Catalog();
$priceListModule = new PriceList();
$products = $catalog->getAllProducts();

// Sort: In stock first
usort($products, function ($a, $b) {
    $sA = $a['stock_current'] ?? 0;
    $sB = $b['stock_current'] ?? 0;
    if ($sA > 0 && $sB <= 0)
        return -1;
    if ($sA <= 0 && $sB > 0)
        return 1;
    return strcmp($a['sku'], $b['sku']);
});

// Default list to show
$currentList = $_GET['list'] ?? 'gremio';
$validLists = ['gremio', 'web', 'mostrador'];
if (!in_array(strtolower($currentList), $validLists)) {
    $currentList = 'gremio';
}

// Exchange Rate
$currency = new \Vsys\Lib\BCRAClient();
$dolar = $currency->getCurrentRate('oficial') ?? 1425.00;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listas de Precios - VS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
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
                <h2 class="font-bold text-lg uppercase tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-500">price_change</span>
                    Listas de Precios
                </h2>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span id="dolar-display"
                            class="text-xs font-bold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                            Dólar: $<?php echo number_format($dolar, 2); ?>
                        </span>
                        <button onclick="updateDolar()"
                            class="p-1 rounded-full hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 transition-all"
                            title="Actualizar Cotización">
                            <span id="dolar-sync-icon" class="material-symbols-outlined text-sm">sync</span>
                        </button>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <!-- Toolbar -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <label class="text-xs font-bold uppercase text-slate-500">Filtrar:</label>
                        <select id="filter-category"
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-lg py-2 px-3 text-sm focus:ring-1 focus:ring-green-500 outline-none">
                            <option value="">Todas las Categorías</option>
                            <?php
                            $catTree = $catalog->getCategoriesWithSubcategories();
                            foreach ($catTree as $cat => $subs):
                                ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-subcategory"
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-lg py-2 px-3 text-sm focus:ring-1 focus:ring-green-500 outline-none">
                            <option value="">Todas las Subcategorías</option>
                        </select>
                        <input type="text" id="search-input" placeholder="Buscar..."
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-lg py-2 px-3 text-sm focus:ring-1 focus:ring-green-500 outline-none min-w-[200px]">
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="text-xs font-bold uppercase text-slate-500">Seleccionar Lista:</label>
                        <div
                            class="flex bg-white dark:bg-[#16202e] rounded-lg border border-slate-200 dark:border-[#233348] p-1">
                            <a href="?list=gremio"
                                class="px-4 py-2 rounded-md text-sm font-bold transition-colors <?php echo $currentList === 'gremio' ? 'bg-green-500 text-white' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'; ?>">
                                Gremio
                            </a>
                            <a href="?list=web"
                                class="px-4 py-2 rounded-md text-sm font-bold transition-colors <?php echo $currentList === 'web' ? 'bg-blue-500 text-white' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'; ?>">
                                Web
                            </a>
                            <a href="?list=mostrador"
                                class="px-4 py-2 rounded-md text-sm font-bold transition-colors <?php echo $currentList === 'mostrador' ? 'bg-amber-500 text-white' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'; ?>">
                                Mostrador
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div
                    class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-[#233348]">
                                <tr class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                                    <th class="px-6 py-4">SKU</th>
                                    <th class="px-6 py-4">Marca</th>
                                    <th class="px-6 py-4">Descripción</th>
                                    <th class="px-6 py-4 text-right">Unit. USD</th>
                                    <th class="px-6 py-4 text-right">Unit. ARS</th>
                                    <th class="px-6 py-4 text-center">IVA</th>
                                    <th class="px-6 py-4 text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-sm">
                                <?php foreach ($products as $p):
                                    $cost = (float) $p['unit_cost_usd'];
                                    $iva = (float) $p['iva_rate'];
                                    // Calculate Price based on selected list
                                    $priceArs = $priceListModule->getPriceByListName($cost, $iva, $currentList, $dolar, true);

                                    // Calculate Price in USD for reference (List Price / Dollar) ? 
                                    // Actually user asked for "Unitario USD", assuming they mean Cost + Margin in USD (before IVA or after?)
                                    // Let's assume Price in USD (Cost * Margin * IVA)
                                    // Recalculate manually to get USD value
                                    $priceUsd = $priceArs / $dolar;
                                    ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02]"
                                        data-category="<?php echo htmlspecialchars($p['category']); ?>"
                                        data-subcategory="<?php echo htmlspecialchars($p['subcategory'] ?? ''); ?>">
                                        <td class="px-6 py-3 font-mono text-xs text-slate-500">
                                            <?php echo $p['sku']; ?>
                                        </td>
                                        <td class="px-6 py-3 font-bold text-xs uppercase text-primary">
                                            <?php echo $p['brand']; ?>
                                        </td>
                                        <td class="px-6 py-3 font-medium">
                                            <?php echo $p['description']; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                            US$
                                            <?php echo number_format($priceUsd, 2); ?>
                                        </td>
                                        <td class="px-6 py-3 text-right font-bold text-slate-800 dark:text-white">
                                            $
                                            <?php echo number_format($priceArs, 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-3 text-center text-xs text-slate-500">
                                            <?php echo $iva; ?>%
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <?php
                                            $stockVal = $p['stock_current'] ?? 0;
                                            $stockColorClass = ($stockVal > 0) ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500';
                                            $stockDisplay = ($stockVal > 0) ? $stockVal : 'Sin Stock';
                                            ?>
                                            <span
                                                class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $stockColorClass; ?>">
                                                <?php echo $stockDisplay; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const searchInput = document.getElementById('search-input');
        const categorySelect = document.getElementById('filter-category');
        const subcategorySelect = document.getElementById('filter-subcategory');
        const rows = Array.from(document.querySelectorAll('tbody tr'));

        const catTree = <?php echo json_encode($catalog->getCategoriesWithSubcategories()); ?>;

        categorySelect.addEventListener('change', () => {
            const cat = categorySelect.value;
            subcategorySelect.innerHTML = '<option value="">Todas las Subcategorías</option>';
            if (cat && catTree[cat]) {
                catTree[cat].forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub;
                    opt.innerText = sub;
                    subcategorySelect.appendChild(opt);
                });
            }
            filterItems();
        });

        let filterTimeout;
        function debounceFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(filterItems, 300);
        }

        let currentIteration = 0;
        function filterItems() {
            const query = searchInput.value.toLowerCase();
            const selectedCat = categorySelect.value;
            const selectedSub = subcategorySelect.value;

            // Incrementar ID de iteración para cancelar procesos previos si el usuario sigue escribiendo
            const iterationId = ++currentIteration;
            const chunkSize = 150; // Procesar de a 150 filas para no trabar el hilo principal
            let index = 0;

            function processChunk() {
                if (iterationId !== currentIteration) return; // Cancelar si hay una nueva búsqueda

                const end = Math.min(index + chunkSize, rows.length);

                for (; index < end; index++) {
                    const row = rows[index];
                    const sku = row.querySelector('td:nth-child(1)').innerText.toLowerCase();
                    const brand = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
                    const desc = row.querySelector('td:nth-child(3)').innerText.toLowerCase();
                    const cat = row.dataset.category;
                    const sub = row.dataset.subcategory;

                    const matchesSearch = !query || sku.includes(query) || brand.includes(query) || desc.includes(query);
                    const matchesCategory = !selectedCat || cat === selectedCat;
                    const matchesSub = !selectedSub || sub === selectedSub;

                    row.style.display = (matchesSearch && matchesCategory && matchesSub) ? '' : 'none';
                }

                if (index < rows.length) {
                    // Ceder el hilo al navegador antes del siguiente bloque
                    setTimeout(processChunk, 1);
                }
            }

            processChunk();
        }

        searchInput.addEventListener('input', debounceFilter);
        subcategorySelect.addEventListener('change', filterItems);

        function updateDolar() {
            const icon = document.getElementById('dolar-sync-icon');
            const display = document.getElementById('dolar-display');
            icon.classList.add('animate-spin');

            fetch('fix_dolar_bna.php?ajax=1')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        display.innerText = `Dólar: $${parseFloat(data.rate).toFixed(2)}`;
                        // Recargar la página para actualizar todos los precios de la tabla
                        window.location.reload();
                    } else {
                        alert('Error al actualizar: ' + data.error);
                    }
                })
                .catch(err => alert('Error de conexión'))
                .finally(() => icon.classList.remove('animate-spin'));
        }
    </script>

</html>