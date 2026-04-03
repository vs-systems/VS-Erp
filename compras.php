<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';
require_once __DIR__ . '/src/modules/purchases/Purchases.php';

use Vsys\Modules\Purchases\Purchases;

$purchasesModule = new Purchases();
$purchaseNumber = $purchasesModule->generatePurchaseNumber();
// Get current exchange rate
$currency = new \Vsys\Lib\BCRAClient();
$exchangeRate = $currency->getCurrentRate('oficial') ?? 1425.00;

$db = \Vsys\Lib\Database::getInstance();

// Listado de proveedores para el selector opcional
$suppliersList = $db->query("SELECT id, name, fantasy_name FROM entities WHERE type IN ('supplier', 'provider') ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Compras - VS System</title>
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

        .search-dropdown {
            @apply absolute left-0 right-0 top-full mt-1 bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-xl shadow-2xl z-[100] max-height-[300px] overflow-y-auto hidden;
        }

        .search-item {
            @apply px-4 py-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 border-b border-slate-100 dark:border-[#233348] last:border-0 transition-colors;
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
                    <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                        <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Gestión de
                        Compras</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span
                        class="bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-[#233348] px-3 py-1.5 rounded-lg text-xs font-bold dark:text-slate-400 text-slate-500 uppercase tracking-widest">
                        Nro: <span class="text-primary"><?php echo $purchaseNumber; ?></span>
                    </span>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-[1400px] mx-auto space-y-8">

                    <!-- NEW PURCHASE SECTION -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors">
                        <div class="flex items-center gap-2 mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                            <span class="material-symbols-outlined text-primary">add_circle</span>
                            <h3 class="font-bold text-lg dark:text-white text-slate-800">Nueva Orden de Compra</h3>
                        </div>

                        <form id="purchase-form" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Supplier Search -->
                                <div class="relative lg:col-span-2">
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Proveedor</label>
                                    <div class="relative">
                                        <span
                                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                                        <input type="text" id="supplier-search"
                                            placeholder="Escriba el nombre del proveedor..."
                                            class="w-full pl-10 bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors"
                                            autocomplete="off">
                                        <input type="hidden" name="entity_id" id="entity_id" required>
                                    </div>
                                    <div id="supplier-results" class="search-dropdown"></div>
                                </div>

                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Fecha</label>
                                    <input type="date" name="purchase_date" id="purchase_date"
                                        value="<?php echo date('Y-m-d'); ?>"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors"
                                        required>
                                </div>

                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Tasa
                                        de Cambio (TC)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                                        <input type="number" step="0.01" name="exchange_rate_usd" id="exchange_rate_usd"
                                            value="<?php echo $exchangeRate; ?>"
                                            class="w-full pl-7 bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Estado
                                        de Entrega</label>
                                    <select name="status" id="status"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="En Camino">En Camino</option>
                                        <option value="Recibido">Recibido</option>
                                        <option value="Cancelado">Cancelado</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Estado
                                        de Pago</label>
                                    <select name="payment_status" id="payment_status"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary focus:border-primary transition-colors">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Pagado">Pagado</option>
                                    </select>
                                </div>
                                <div class="flex items-end pb-1.5">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input type="checkbox" name="is_confirmed" id="is_confirmed"
                                                class="sr-only peer" checked>
                                            <div
                                                class="w-10 h-6 bg-slate-200 dark:bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500">
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-slate-500 uppercase tracking-widest group-hover:text-primary transition-colors">Orden
                                            Confirmada</span>
                                    </label>
                                </div>
                            </div>

                            <div
                                class="relative bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-100 dark:border-[#233348]">
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">inventory_2</span> Buscar Producto
                                </label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">barcode_scanner</span>
                                    <input type="text" id="product-search" placeholder="Buscar por SKU o descripción..."
                                        class="w-full pl-10 bg-white dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm focus:ring-primary focus:border-primary transition-all"
                                        autocomplete="off">
                                </div>
                                <div id="search-results" class="search-dropdown"></div>
                            </div>

                            <!-- Items Table -->
                            <div
                                class="border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-4 py-3" width="80">Cant.</th>
                                            <th class="px-4 py-3">Referencia SKU</th>
                                            <th class="px-4 py-3">Descripción</th>
                                            <th class="px-4 py-3 text-right" width="130">P. Unit ARS</th>
                                            <th class="px-4 py-3 text-right" width="120">P. Unit USD</th>
                                            <th class="px-4 py-3 text-center" width="90">IVA</th>
                                            <th class="px-4 py-3 text-right" width="130">Subtotal USD</th>
                                            <th class="px-4 py-3" width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody" class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <!-- Items will be injected here via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer Totals & Observations -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-4">
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Observaciones
                                        / Notas internas</label>
                                    <textarea id="purchase-observations"
                                        placeholder="Ej: Referencia OC del proveedor, Notas de envío, etc..."
                                        class="w-full h-24 bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 p-4 focus:ring-primary focus:border-primary transition-colors"></textarea>
                                </div>

                                <div
                                    class="bg-primary/5 dark:bg-[#136dec]/10 border border-primary/20 dark:border-[#233348] rounded-2xl p-6 flex flex-col justify-center">
                                    <div class="space-y-2 mb-4">
                                        <div class="flex justify-between items-center text-xs text-slate-500">
                                            <span class="uppercase font-bold tracking-widest">Subtotal Neto</span>
                                            <span class="font-mono font-bold" id="subtotal-display">USD 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs text-slate-500">
                                            <span class="uppercase font-bold tracking-widest">IVA (21% + 10.5%)</span>
                                            <span class="font-mono font-bold" id="iva-total-display">USD 0.00</span>
                                        </div>
                                    </div>
                                    <div
                                        class="border-t border-primary/20 dark:border-white/10 pt-4 flex justify-between items-end">
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-primary uppercase tracking-widest leading-none mb-1">
                                                Total Bruto</p>
                                            <h2 class="text-3xl font-bold dark:text-white text-slate-800 tracking-tighter"
                                                id="grand-total">USD 0.00</h2>
                                        </div>
                                        <div class="text-right">
                                            <p
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">
                                                Equivalente ARS</p>
                                            <span
                                                class="text-xl font-bold dark:text-slate-300 text-slate-600 tracking-tight"
                                                id="total-ars-display">0.00</span>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex gap-3">
                                        <button type="button" onclick="location.reload()"
                                            class="flex-1 bg-white dark:bg-white/5 border border-slate-200 dark:border-[#233348] text-slate-600 dark:text-slate-400 font-bold py-3 rounded-xl text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-white/10 transition-all">Limpiar</button>
                                        <button type="submit"
                                            class="flex-[2] bg-primary hover:bg-blue-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                                            <span class="material-symbols-outlined text-sm">save</span> GUARDAR COMPRA
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- RECENT HISTORY SECTION -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg dark:text-white text-slate-800 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">history</span> Historial de Compras
                                Recientes
                            </h3>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead
                                        class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                        <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                            <th class="px-6 py-4">Orden</th>
                                            <th class="px-6 py-4">Proveedor</th>
                                            <th class="px-6 py-4 whitespace-nowrap">Fecha</th>
                                            <th class="px-6 py-4 text-right">Total USD</th>
                                            <th class="px-6 py-4 text-right">Total ARS</th>
                                            <th class="px-6 py-4 text-center">Estado</th>
                                            <th class="px-6 py-4 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php
                                        $history = $purchasesModule->getAllPurchases();
                                        foreach ($history as $p):
                                            ?>
                                            <tr
                                                class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                                <td class="px-6 py-5">
                                                    <span
                                                        class="font-bold dark:text-white text-slate-800 group-hover:text-primary transition-colors">
                                                        <?php echo $p['purchase_number']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="text-xs font-semibold dark:text-slate-200 text-slate-700">
                                                        <?php echo $p['supplier_name']; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="text-xs text-slate-500"><?php echo $p['purchase_date']; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5 text-right font-mono font-bold text-primary">
                                                    $<?php echo number_format($p['total_usd'], 2); ?>
                                                </td>
                                                <td class="px-6 py-5 text-right font-mono font-bold text-green-500">
                                                    $<?php echo number_format($p['total_ars'] ?? ($p['total_usd'] * ($p['exchange_rate_usd'] ?? $exchangeRate)), 2); ?>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex items-center justify-center gap-3">
                                                        <!-- Confirm Toggle -->
                                                        <button
                                                            onclick="toggleStatus(<?php echo $p['id']; ?>, 'purchase', 'is_confirmed', <?php echo ($p['is_confirmed'] ?? 0) ? 0 : 1; ?>)"
                                                            class="flex items-center gap-1.5 px-2 py-1 rounded-lg transition-all <?php echo ($p['is_confirmed'] ?? 0) ? 'bg-green-500/10 text-green-500' : 'bg-slate-500/10 text-slate-400'; ?>"
                                                            title="Confirmar/Desmarcar">
                                                            <span
                                                                class="material-symbols-outlined text-sm <?php echo ($p['is_confirmed'] ?? 0) ? 'fill-1' : ''; ?>">verified</span>
                                                            <span
                                                                class="text-[9px] font-bold uppercase"><?php echo ($p['is_confirmed'] ?? 0) ? 'OK' : 'PND'; ?></span>
                                                        </button>

                                                        <!-- Payment Toggle -->
                                                        <button
                                                            onclick="toggleStatus(<?php echo $p['id']; ?>, 'purchase', 'payment_status', '<?php echo ($p['payment_status'] ?? 'Pendiente') === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')"
                                                            class="flex items-center gap-1.5 px-2 py-1 rounded-lg transition-all <?php echo ($p['payment_status'] ?? 'Pendiente') === 'Pagado' ? 'bg-purple-500/10 text-purple-500' : 'bg-amber-500/10 text-amber-500'; ?>"
                                                            title="Cambiar Pago">
                                                            <span
                                                                class="material-symbols-outlined text-sm <?php echo ($p['payment_status'] === 'Pagado') ? 'fill-1' : ''; ?>">payments</span>
                                                            <span
                                                                class="text-[9px] font-bold uppercase"><?php echo $p['payment_status'] ?? 'Pendiente'; ?></span>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <a href="imprimir_compra.php?id=<?php echo $p['id']; ?>"
                                                            target="_blank"
                                                            class="p-2 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-all"
                                                            title="Ver PDF">
                                                            <span
                                                                class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                                        </a>
                                                        <button onclick="deletePurchase(<?php echo $p['id']; ?>)"
                                                            class="p-2 rounded-lg hover:bg-red-500/10 text-slate-400 hover:text-red-500 transition-all"
                                                            title="Eliminar">
                                                            <span
                                                                class="material-symbols-outlined text-[18px]">delete</span>
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
        let items = [];
        let currentExchangeRate = parseFloat(document.getElementById('exchange_rate_usd').value) || 1480;

        document.getElementById('exchange_rate_usd').addEventListener('input', function () {
            currentExchangeRate = parseFloat(this.value) || 0;
            updateStoredItemsRates();
            renderTable();
        });

        function updateStoredItemsRates() {
            items.forEach(item => {
                item.unit_price_ars = item.unit_price_usd * currentExchangeRate;
            });
        }

        // Supplier Search
        const supplierSearch = document.getElementById('supplier-search');
        const supplierResults = document.getElementById('supplier-results');
        const entityIdInput = document.getElementById('entity_id');

        supplierSearch.addEventListener('input', async function () {
            const q = this.value;
            if (q.length < 2) { supplierResults.style.display = 'none'; return; }

            const res = await fetch(`ajax_search_suppliers.php?q=${q}`);
            const data = await res.json();

            supplierResults.innerHTML = '';
            if (data.length > 0) {
                data.forEach(s => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `
                        <div class="flex flex-col">
                            <span class="text-sm font-bold dark:text-white text-slate-800">${s.name}</span>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">${s.fantasy_name || 'Sin nombre fantasía'}</span>
                        </div>
                    `;
                    div.onclick = () => {
                        supplierSearch.value = s.name + (s.fantasy_name ? ` (${s.fantasy_name})` : '');
                        entityIdInput.value = s.id;
                        supplierResults.style.display = 'none';
                    };
                    supplierResults.appendChild(div);
                });
                supplierResults.style.display = 'block';
            } else {
                supplierResults.style.display = 'none';
            }
        });

        // Product Search
        const productSearch = document.getElementById('product-search');
        const productResults = document.getElementById('search-results');

        productSearch.addEventListener('input', async function () {
            const q = this.value;
            if (q.length < 2) { productResults.style.display = 'none'; return; }

            const res = await fetch(`ajax_search_products.php?q=${q}`);
            const data = await res.json();

            productResults.innerHTML = '';
            if (data.length > 0) {
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-primary">${p.sku}</span>
                                <span class="text-[11px] text-slate-500">${p.description}</span>
                            </div>
                            <span class="font-mono text-xs font-bold text-slate-400">$${p.cost_usd} USD</span>
                        </div>
                    `;
                    div.onclick = () => addItem(p);
                    productResults.appendChild(div);
                });
            }

            // Quick Add
            const addDiv = document.createElement('div');
            addDiv.className = 'search-item bg-primary/5 dark:bg-primary/10 border-t border-primary/20';
            addDiv.innerHTML = `
                <div class="flex items-center gap-2 text-primary">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span class="text-xs font-bold uppercase tracking-widest">Crear Nuevo: "${q}"</span>
                </div>
            `;
            addDiv.onclick = () => quickAddProduct(q);
            productResults.appendChild(addDiv);

            productResults.style.display = 'block';
        });

        function addItem(p) {
            const existing = items.find(i => i.sku === p.sku);
            if (existing) {
                existing.qty++;
            } else {
                const costUsd = parseFloat(p.cost_usd) || 0;
                items.push({
                    product_id: p.id,
                    sku: p.sku,
                    description: p.description,
                    qty: 1,
                    unit_price_usd: costUsd,
                    unit_price_ars: costUsd * currentExchangeRate,
                    iva_rate: parseFloat(p.iva_rate) || 21
                });
            }
            productResults.style.display = 'none';
            productSearch.value = '';
            renderTable();
        }

        function quickAddProduct(sku) {
            const desc = prompt("Descripción del nuevo producto:", "");
            if (!desc) return;
            const cost = prompt("Costo unitario USD:", "0.00");
            addItem({
                id: 'new-' + Date.now(),
                sku: sku.toUpperCase(),
                description: desc,
                cost_usd: parseFloat(cost) || 0
            });
        }

        function updateQty(idx, val) { items[idx].qty = Math.max(1, parseInt(val) || 1); renderTable(); }
        function updatePriceUSD(idx, val) {
            const usd = parseFloat(val) || 0;
            items[idx].unit_price_usd = usd;
            items[idx].unit_price_ars = usd * currentExchangeRate;
            renderTable();
        }
        function updatePriceARS(idx, val) {
            const ars = parseFloat(val) || 0;
            items[idx].unit_price_ars = ars;
            items[idx].unit_price_usd = ars / currentExchangeRate;
            renderTable();
        }
        function updateIVA(idx, val) { items[idx].iva_rate = parseFloat(val); renderTable(); }
        function removeItem(idx) {
            if (!confirm('¿Seguro que deseas quitar este producto de la lista?')) return;
            items.splice(idx, 1);
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('items-tbody');
            tbody.innerHTML = '';
            let subtotalUsd = 0;
            let ivaTotalUsd = 0;

            items.forEach((item, idx) => {
                const lineNet = item.qty * item.unit_price_usd;
                const lineIva = lineNet * (item.iva_rate / 100);
                subtotalUsd += lineNet;
                ivaTotalUsd += lineIva;

                const tr = document.createElement('tr');
                tr.className = "hover:bg-slate-50 dark:hover:bg-white/[0.01] transition-colors";
                tr.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="number" value="${item.qty}" onchange="updateQty(${idx}, this.value)" 
                               class="w-16 bg-white dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-xs text-center p-1.5 focus:ring-primary">
                    </td>
                    <td class="px-4 py-3 text-xs font-bold text-primary">${item.sku}</td>
                    <td class="px-4 py-3 text-[11px] text-slate-500 max-w-[200px] truncate">${item.description}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-green-500 font-bold text-[10px]">$</span>
                            <input type="number" step="0.01" value="${item.unit_price_ars.toFixed(2)}" onchange="updatePriceARS(${idx}, this.value)" 
                                   class="w-24 pl-4 bg-white dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-xs text-right p-1.5 text-green-500 font-bold focus:ring-green-500">
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-primary font-bold text-[10px]">$</span>
                            <input type="number" step="0.01" value="${item.unit_price_usd.toFixed(2)}" onchange="updatePriceUSD(${idx}, this.value)" 
                                   class="w-24 pl-4 bg-white dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-xs text-right p-1.5 text-primary font-bold focus:ring-primary">
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <select onchange="updateIVA(${idx}, this.value)" class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-[10px] p-1.5 focus:ring-primary">
                            <option value="21" ${item.iva_rate == 21 ? 'selected' : ''}>21%</option>
                            <option value="10.5" ${item.iva_rate == 10.5 ? 'selected' : ''}>10.5%</option>
                            <option value="0" ${item.iva_rate == 0 ? 'selected' : ''}>0%</option>
                        </select>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-xs font-bold dark:text-white text-slate-800">$${lineNet.toFixed(2)}</td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" onclick="removeItem(${idx})" class="text-red-500/50 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-lg">delete_forever</span>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            const grandTotalUsd = subtotalUsd + ivaTotalUsd;
            document.getElementById('subtotal-display').innerText = `USD ${subtotalUsd.toFixed(2)}`;
            document.getElementById('iva-total-display').innerText = `USD ${ivaTotalUsd.toFixed(2)}`;
            document.getElementById('grand-total').innerText = `USD ${grandTotalUsd.toFixed(2)}`;
            document.getElementById('total-ars-display').innerText = (grandTotalUsd * currentExchangeRate).toLocaleString('es-AR', { minimumFractionDigits: 2 });
        }

        // Save Form
        document.getElementById('purchase-form').onsubmit = async function (e) {
            e.preventDefault();
            if (items.length === 0) { alert('Agregue productos a la compra.'); return; }
            if (!entityIdInput.value) { alert('Seleccione un proveedor.'); return; }

            const subtotal_usd = items.reduce((acc, i) => acc + (i.qty * i.unit_price_usd), 0);
            const total_iva = items.reduce((acc, i) => acc + (i.qty * i.unit_price_usd * (i.iva_rate / 100)), 0);
            const total_usd = subtotal_usd + total_iva;

            const formData = {
                purchase_number: '<?php echo $purchaseNumber; ?>',
                entity_id: entityIdInput.value,
                purchase_date: document.getElementById('purchase_date').value,
                status: document.getElementById('status').value,
                is_confirmed: document.getElementById('is_confirmed').checked ? 1 : 0,
                payment_status: document.getElementById('payment_status').value,
                exchange_rate_usd: currentExchangeRate,
                subtotal_usd: subtotal_usd,
                subtotal_ars: subtotal_usd * currentExchangeRate,
                total_usd: total_usd,
                total_ars: total_usd * currentExchangeRate,
                notes: document.getElementById('purchase-observations').value,
                items: items
            };

            const res = await fetch('ajax_save_purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();
            if (data.success) { alert('Compra guardada.'); location.reload(); }
            else { alert('Error: ' + data.error); }
        };

        async function toggleStatus(id, type, field, val) {
            const res = await fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, type, field, value: val })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error);
        }

        async function deletePurchase(id) {
            if (!confirm('¿Eliminar esta compra?')) return;
            const res = await fetch('ajax_delete_purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error);
        }

        // Close search lists
        document.addEventListener('click', (e) => {
            if (e.target !== supplierSearch) supplierResults.style.display = 'none';
            if (e.target !== productSearch) productResults.style.display = 'none';
        });
    </script>
</body>

</html>