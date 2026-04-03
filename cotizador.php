<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';

use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Lib\BCRAClient;

$cot = new Cotizador();
$currency = new BCRAClient();

$editId = $_GET['id'] ?? null;
$existingQuote = null;
$existingItems = [];
if ($editId) {
    $existingQuote = $cot->getQuotation($editId);
    $existingItems = $cot->getQuotationItems($editId);
    $vData = $cot->createNewVersion($editId);
    if ($vData) {
        $quoteNumber = $vData['number'];
        $version = $vData['version'];
    } else {
        $quoteNumber = $existingQuote['quote_number'];
        $version = $existingQuote['version'];
    }
} else {
    $quoteNumber = $cot->generateQuoteNumber(1);
    $version = 1;
}

$currentRate = $currency->getCurrentRate('oficial') ?? 850.00; // Default if API fails
if ($existingQuote)
    $currentRate = $existingQuote['exchange_rate_usd'];

$today = date('d/m/y');
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Presupuesto - VS System</title>
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

        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-top: 4px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 250px;
            overflow-y: auto;
        }

        .dark .search-dropdown {
            background: #16202e;
            border-color: #233348;
            box-shadow: none;
        }

        .search-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .search-item {
            border-bottom-color: #233348;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:hover {
            background: #f8fafc;
        }

        .dark .search-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .form-input-vsys {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary transition-all;
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
                        <span class="material-symbols-outlined text-2xl">description</span>
                    </div>
                    <div class="flex flex-col">
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight leading-none">
                            Nuevo Presupuesto</h2>
                        <span class="text-[10px] text-primary font-bold tracking-widest uppercase mt-1">Nº
                            <?php echo $quoteNumber; ?> (v<?php echo $version; ?>)</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-primary/10 px-4 py-2 rounded-xl border border-primary/20">
                        <span
                            class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest block">Dólar
                            BNA (Venta)</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-primary">$</span>
                            <input type="number" step="0.01" id="bcra-reference" value="<?php echo $currentRate; ?>"
                                class="w-16 bg-transparent border-none p-0 text-sm font-bold focus:ring-0 text-primary">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
                <div class="max-w-[1400px] mx-auto space-y-6">

                    <!-- Client & Settings Selection -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <!-- Search -->
                            <div class="md:col-span-12 lg:col-span-4 relative">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Buscar
                                    Cliente (Nombre o CUIT)</label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                                    <input type="text" id="client-search" placeholder="Escriba para buscar..."
                                        value="<?php echo htmlspecialchars($existingQuote['client_name'] ?? ''); ?>"
                                        autocomplete="off" class="form-input-vsys pl-10 h-12">
                                    <input type="hidden" id="selected-client-id"
                                        value="<?php echo $existingQuote['client_id'] ?? 1; ?>">
                                    <div id="client-results" class="search-dropdown" style="display: none;"></div>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="md:col-span-6 lg:col-span-4">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nombre
                                    / Razón Social</label>
                                <input type="text" id="client-name-display" readonly
                                    class="form-input-vsys h-12 bg-slate-100/50 dark:bg-[#101822]/50 font-bold"
                                    value="<?php echo htmlspecialchars($existingQuote['client_name'] ?? ''); ?>">
                            </div>

                            <div class="md:col-span-6 lg:col-span-4">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">CUIT
                                    / CUIL</label>
                                <input type="text" id="client-tax-display" readonly
                                    class="form-input-vsys h-12 bg-slate-100/50 dark:bg-[#101822]/50 font-mono"
                                    value="<?php echo htmlspecialchars($existingQuote['tax_id'] ?? ''); ?>">
                            </div>

                            <div
                                class="md:col-span-12 lg:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-slate-100 dark:border-[#233348]">
                                <div class="space-y-4">
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Opciones
                                        de Cotización</label>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" id="is-bank"
                                                class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary shadow-sm transition-all bg-white dark:bg-[#101822]">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold dark:text-white text-slate-800">Transf.
                                                    Bancaria</span>
                                                <span
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">(+3%)</span>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" id="is-retention"
                                                class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary shadow-sm transition-all bg-white dark:bg-[#101822]">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold dark:text-white text-slate-800">Agente
                                                    Retención</span>
                                                <span
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">(+7%)</span>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" id="with-iva" checked
                                                class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary shadow-sm transition-all bg-white dark:bg-[#101822]">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold dark:text-white text-slate-800">Venta con
                                                    IVA</span>
                                                <span
                                                    class="text-[10px] text-primary font-bold uppercase tracking-tighter">Discriminado</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="relative">
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Dirección
                                        de Entrega</label>
                                    <input type="text" id="client-address-display" readonly
                                        class="form-input-vsys h-12 bg-slate-100/50 dark:bg-[#101822]/50"
                                        value="<?php echo htmlspecialchars($existingQuote['address'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Search -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none transition-colors relative h-fit sticky top-0 z-20">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="material-symbols-outlined text-primary">add_shopping_cart</span>
                            <h3 class="font-bold text-sm dark:text-white text-slate-800 uppercase tracking-tight">
                                Agregar Productos</h3>
                        </div>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">inventory_2</span>
                            <input type="text" id="product-search"
                                placeholder="SKU, Nombre, Marca o Descripción del producto..." autocomplete="off"
                                class="form-input-vsys pl-10 h-12">
                            <div id="search-results" class="search-dropdown" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div
                        class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-xl dark:shadow-none transition-colors">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="quote-table">
                                <thead
                                    class="bg-slate-50 dark:bg-[#101822]/50 border-b border-slate-200 dark:border-[#233348]">
                                    <tr class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                        <th class="px-6 py-4 w-24 text-center">Cant.</th>
                                        <th class="px-6 py-4">Producto</th>
                                        <th class="px-6 py-4 text-right">Unit. USD</th>
                                        <th class="px-6 py-4 text-right">Unit. ARS</th>
                                        <th class="px-6 py-4 text-center">IVA</th>
                                        <th class="px-6 py-4 text-right">Total USD</th>
                                        <th class="px-6 py-4 text-center w-16"></th>
                                    </tr>
                                </thead>
                                <tbody id="quote-items" class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <!-- Items injection -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="empty-state" class="p-12 flex flex-col items-center justify-center text-center">
                            <div
                                class="w-16 h-16 bg-slate-100 dark:bg-white/5 rounded-full flex items-center justify-center text-slate-400 mb-4">
                                <span class="material-symbols-outlined text-3xl">shopping_basket</span>
                            </div>
                            <h4 class="text-sm font-bold dark:text-slate-400 text-slate-500 uppercase tracking-widest">
                                Presupuesto Vacío</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Busque productos arriba para comenzar a cotizar.
                            </p>
                        </div>
                    </div>

                    <!-- Bottom Controls & Summary -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Observations -->
                        <div class="lg:col-span-7">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none h-full">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">notes</span> Observaciones /
                                    Referencias Internas
                                </label>
                                <textarea id="quote-observations"
                                    placeholder="Ej: Referencia Orden de Compra #1234, Entrega pactada para el viernes..."
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary transition-all p-4 h-32 resize-none"></textarea>
                                <p class="text-[10px] text-slate-400 mt-3 italic leading-relaxed">
                                    Leyenda final: Cotización válida por 48hs sujeto a cambio de cotización y stock.
                                </p>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="lg:col-span-5">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-xl dark:shadow-none h-full flex flex-col">
                                <h3
                                    class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-[#233348] pb-4">
                                    Resumen de Totales</h3>

                                <div class="space-y-4 flex-1">
                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <span class="text-slate-500">Subtotal Neto:</span>
                                        <div class="flex items-center gap-1.5 font-mono">
                                            <span class="text-slate-400">USD</span>
                                            <span id="total-neto-usd" class="dark:text-white text-slate-800">0.00</span>
                                        </div>
                                    </div>

                                    <!-- IVA 10.5% -->
                                    <div id="row-iva-105"
                                        class="flex justify-between items-center text-sm font-medium hidden text-slate-500">
                                        <span>I.V.A. (10.5%):</span>
                                        <div class="flex items-center gap-1.5 font-mono">
                                            <span class="text-slate-400">USD</span>
                                            <span id="total-iva-105-usd">0.00</span>
                                        </div>
                                    </div>

                                    <!-- IVA 21% -->
                                    <div id="row-iva-21"
                                        class="flex justify-between items-center text-sm font-medium hidden text-slate-500">
                                        <span>I.V.A. (21%):</span>
                                        <div class="flex items-center gap-1.5 font-mono">
                                            <span class="text-slate-400">USD</span>
                                            <span id="total-iva-21-usd">0.00</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <span class="text-slate-500 font-bold">Total I.V.A.:</span>
                                        <div class="flex items-center gap-1.5 font-mono">
                                            <span class="text-slate-400">USD</span>
                                            <span id="total-iva-usd"
                                                class="dark:text-white text-slate-800 font-bold">0.00</span>
                                        </div>
                                    </div>

                                    <!-- DOLLAR RATE REFERENCE -->
                                    <div
                                        class="flex justify-between items-center text-[10px] font-bold text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-[#233348]">
                                        <span>Cotización Ref. (BNA):</span>
                                        <div class="flex items-center gap-1">
                                            <span>$</span>
                                            <span
                                                id="summary-rate-display"><?php echo number_format($currentRate, 2); ?></span>
                                        </div>
                                    </div>

                                    <div
                                        class="pt-4 mt-2 border-t border-slate-100 dark:border-[#233348] flex justify-between items-end">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-bold text-primary uppercase tracking-widest">Total
                                                General</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-lg font-mono font-bold text-primary">USD</span>
                                                <span id="total-general-usd"
                                                    class="text-3xl font-mono font-black text-primary tracking-tighter">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-green-500/10 border border-green-500/20 p-4 rounded-xl flex justify-between items-center mt-4">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-green-500 p-1.5 rounded-lg text-white">
                                                <span class="material-symbols-outlined text-lg">payments</span>
                                            </div>
                                            <span
                                                class="text-xs font-bold text-green-600 dark:text-green-400 tracking-tight">Equivalente
                                                Pesos</span>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                class="text-[10px] block font-bold text-green-500/70 uppercase tracking-tighter">ARS</span>
                                            <span id="total-general-ars"
                                                class="text-xl font-mono font-black text-green-600 dark:text-green-400 tracking-tighter">0,00</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-8">
                                    <button onclick="saveQuotation()"
                                        class="flex items-center justify-center gap-2 bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 transition-all active:scale-95 group">
                                        <span
                                            class="material-symbols-outlined text-sm group-hover:rotate-12 transition-transform">picture_as_pdf</span>
                                        GRABAR Y PDF
                                    </button>
                                    <button onclick="sendWhatsApp()"
                                        class="flex items-center justify-center gap-2 bg-[#25d366] hover:bg-[#1ebe57] text-white font-bold py-4 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-green-500/20 transition-all active:scale-95 group">
                                        <span
                                            class="material-symbols-outlined text-sm group-hover:scale-110 transition-transform">chat_bubble</span>
                                        WHATSAPP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JS Logic Re-integrated -->
    <script>
        // GLOBAL TEXT SANITIZATION (Uppercase + No Accents)
        document.addEventListener('input', function (e) {
            if (e.target.tagName === 'INPUT' && e.target.type === 'text' || e.target.tagName === 'TEXTAREA') {
                const original = e.target.value;
                const upper = original.toUpperCase();
                // Normalize NFD to separate accents, remove diacritics, then uppercase
                const clean = upper.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                if (original !== clean) {
                    const start = e.target.selectionStart;
                    const end = e.target.selectionEnd;
                    e.target.value = clean;
                    e.target.setSelectionRange(start, end);
                }
            }
        });

        let bnaRate = <?php echo $currentRate; ?>;
        let items = <?php echo json_encode(array_map(function ($i) {
            return [
                'id' => $i['product_id'],
                'sku' => $i['sku'],
                'desc' => $i['description'],
                'price' => (float) $i['unit_price_usd'],
                'iva' => (float) $i['iva_rate'],
                'qty' => (int) $i['quantity']
            ];
        }, $existingItems)); ?>;
        let searchTimeout;
        let selectedClientProfile = 'Mostrador'; // Default

        const clientSearch = document.getElementById('client-search');
        const clientResults = document.getElementById('client-results');
        const productSearch = document.getElementById('product-search');
        const productResults = document.getElementById('search-results');

        // Client Search Logic
        clientSearch.addEventListener('input', function () {
            const query = this.value;
            if (query.length < 2) {
                clientResults.style.display = 'none';
                return;
            }

            fetch(`ajax_search_clients.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    clientResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(client => {
                            const div = document.createElement('div');
                            div.className = 'search-item';
                            div.innerHTML = `
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-sm dark:text-white text-slate-800">${client.name}</span>
                                    <span class="text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded uppercase">${client.tax_id || 'S/D'}</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1 italic">${client.contact_person || ''}</div>
                            `;
                            div.onclick = () => selectClient(client);
                            clientResults.appendChild(div);
                        });
                        clientResults.style.display = 'block';
                    } else {
                        clientResults.style.display = 'none';
                    }
                });
        });

        function selectClient(client) {
            document.getElementById('selected-client-id').value = client.id;
            document.getElementById('client-search').value = client.name;
            document.getElementById('client-name-display').value = client.name;
            document.getElementById('client-tax-display').value = client.tax_id;
            document.getElementById('client-address-display').value = client.address;

            document.getElementById('is-retention').checked = (client.is_retention_agent == 1);
            const pref = (client.preferred_payment_method || '').toLowerCase();
            document.getElementById('is-bank').checked = (pref.includes('transferencia') || pref.includes('banco') || pref.includes('deposito'));

            selectedClientProfile = client.client_profile || 'Mostrador';

            const profileBadge = document.createElement('span');
            profileBadge.id = 'profile-badge';
            profileBadge.className = "text-[10px] font-bold bg-primary text-white px-2 py-0.5 rounded ml-2 uppercase";
            profileBadge.innerText = selectedClientProfile;

            const clientNameDiv = document.getElementById('client-name-display').parentElement;
            const existingBadge = document.getElementById('profile-badge');
            if (existingBadge) existingBadge.remove();
            clientNameDiv.appendChild(profileBadge);

            clientResults.style.display = 'none';
            renderTable();
        }

        // Product Search Logic
        productSearch.addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            if (query.length < 2) {
                productResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`ajax_search_products.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        productResults.innerHTML = '';
                        if (data.length > 0) {
                            productResults.style.display = 'block';
                            data.forEach(prod => {
                                const div = document.createElement('div');
                                div.className = 'search-item';
                                const priceARS = (parseFloat(prod.unit_price_usd) * bnaRate).toLocaleString('es-AR', { minimumFractionDigits: 2 });
                                div.innerHTML = `
                                    <div class="flex justify-between">
                                        <span class="font-bold text-sm dark:text-white text-slate-800">${prod.sku}</span>
                                        <span class="font-bold text-primary font-mono text-sm">USD ${prod.unit_price_usd}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">${prod.description} (${prod.brand})</div>
                                `;
                                div.onclick = ((p) => {
                                    return () => addItem(p);
                                })(prod);
                                productResults.appendChild(div);
                            });
                        } else {
                            productResults.style.display = 'none';
                        }
                    });
            }, 300);
        });

        function addItem(prod) {
            const existing = items.find(i => i.id === prod.id);
            if (existing) {
                existing.qty++;
            } else {
                let unitPrice = parseFloat(prod.unit_price_usd);
                const profilePrices = prod.prices_by_name || prod.prices;
                if (profilePrices && profilePrices[selectedClientProfile]) {
                    unitPrice = parseFloat(profilePrices[selectedClientProfile]);
                }

                let taxRate = parseFloat(prod.iva_rate || 21);
                if (prod.sku === 'HD1TB-P-I') taxRate = 10.5;

                items.push({
                    id: prod.id,
                    sku: prod.sku,
                    desc: prod.description,
                    price: unitPrice,
                    iva: taxRate,
                    qty: 1
                });
            }
            productSearch.value = '';
            productResults.style.display = 'none';
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('quote-items');
            const emptyState = document.getElementById('empty-state');
            tbody.innerHTML = '';

            if (items.length === 0) {
                emptyState.style.display = 'flex';
                calculateTotals();
                return;
            }
            emptyState.style.display = 'none';

            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;

            items.forEach((item, index) => {
                let adjustedUnitPrice = item.price;
                if (isRetention) adjustedUnitPrice *= 1.07;
                if (isBank) adjustedUnitPrice *= 1.03;

                const tr = document.createElement('tr');
                tr.className = "hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors";
                tr.innerHTML = `
                    <td class="px-6 py-4 text-center">
                        <input type="number" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)" 
                            class="w-16 h-10 text-center bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-sm dark:text-white font-bold focus:ring-primary/30">
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-sm dark:text-white text-slate-800">${item.sku}</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 max-w-xs uppercase font-medium mt-1">${item.desc}</div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <input type="number" step="0.01" value="${adjustedUnitPrice.toFixed(2)}" 
                            onchange="updatePrice(${index}, this.value, 'usd')" 
                            class="w-24 h-10 text-right bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-sm text-primary font-mono font-bold focus:ring-primary/30">
                    </td>
                    <td class="px-6 py-4 text-right">
                        <input type="number" step="0.01" value="${(adjustedUnitPrice * bnaRate).toFixed(2)}" 
                            onchange="updatePrice(${index}, this.value, 'ars')" 
                            class="w-24 h-10 text-right bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-lg text-sm text-green-600 dark:text-green-400 font-mono font-bold focus:ring-green-500/30">
                    </td>
                    <td class="px-6 py-4 text-center font-bold text-slate-400 text-[11px] uppercase">${item.iva}%</td>
                    <td class="px-6 py-4 text-right">
                        <div class="text-sm font-bold dark:text-white text-slate-800 font-mono">$ ${(adjustedUnitPrice * item.qty).toFixed(2)}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="removeItem(${index})" class="p-2 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-500/10 transition-all">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            calculateTotals();
        }

        function updateQty(index, val) {
            items[index].qty = parseInt(val) || 1;
            renderTable();
        }

        function updatePrice(index, val, unit) {
            let enteredPrice = parseFloat(val) || 0;
            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;

            if (unit === 'ars') enteredPrice = enteredPrice / bnaRate;

            let basePrice = enteredPrice;
            if (isBank) basePrice /= 1.03;
            if (isRetention) basePrice /= 1.07;

            items[index].price = basePrice;
            renderTable();
        }

        function removeItem(index) {
            if (!confirm('¿Seguro que deseas quitar este producto del presupuesto?')) return;
            items.splice(index, 1);
            renderTable();
        }

        function calculateTotals() {
            let subtotal = 0;
            let totalIva105 = 0;
            let totalIva21 = 0;

            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;
            const withIva = document.getElementById('with-iva').checked;

            items.forEach(item => {
                let adjustedPrice = parseFloat(item.price) || 0;
                if (isRetention) adjustedPrice *= 1.07;
                if (isBank) adjustedPrice *= 1.03;

                let lineTotal = adjustedPrice * (parseInt(item.qty) || 1);
                subtotal += lineTotal;

                if (withIva) {
                    let rate = parseFloat(item.iva) || 21;
                    let ivaAmount = lineTotal * (rate / 100);

                    if (Math.abs(rate - 10.5) < 0.1) {
                        totalIva105 += ivaAmount;
                    } else {
                        totalIva21 += ivaAmount;
                    }
                }
            });

            document.getElementById('total-neto-usd').innerText = subtotal.toFixed(2);

            document.getElementById('total-iva-105-usd').innerText = totalIva105.toFixed(2);
            document.getElementById('row-iva-105').classList.toggle('hidden', totalIva105 === 0);

            document.getElementById('total-iva-21-usd').innerText = totalIva21.toFixed(2);
            document.getElementById('row-iva-21').classList.toggle('hidden', totalIva21 === 0);

            const totalIva = totalIva105 + totalIva21;
            document.getElementById('total-iva-usd').innerText = totalIva.toFixed(2);

            const totalGeneral = subtotal + totalIva;
            document.getElementById('total-general-usd').innerText = totalGeneral.toFixed(2);
            document.getElementById('total-general-ars').innerText = (totalGeneral * bnaRate).toLocaleString('es-AR', { minimumFractionDigits: 2 });
        }

        document.getElementById('is-retention').addEventListener('change', renderTable);
        document.getElementById('is-bank').addEventListener('change', renderTable);
        document.getElementById('with-iva').addEventListener('change', renderTable);
        document.getElementById('bcra-reference').addEventListener('change', function () {
            bnaRate = parseFloat(this.value) || 0;
            document.getElementById('summary-rate-display').innerText = bnaRate.toFixed(2);
            renderTable();
        });

        document.addEventListener('click', function (e) {
            if (e.target !== clientSearch && !clientResults.contains(e.target)) clientResults.style.display = 'none';
            if (e.target !== productSearch && !productResults.contains(e.target)) productResults.style.display = 'none';
        });

        function saveQuotation() {
            if (items.length === 0) {
                alert('Agregue al menos un producto.');
                return;
            }

            const data = {
                quote_number: '<?php echo $quoteNumber; ?>',
                version: <?php echo $version; ?>,
                client_id: document.getElementById('selected-client-id').value,
                payment_method: document.getElementById('is-bank').checked ? 'bank' : 'cash',
                is_retention: document.getElementById('is-retention').checked,
                is_bank: document.getElementById('is-bank').checked,
                with_iva: document.getElementById('with-iva').checked,
                exchange_rate_usd: bnaRate,
                subtotal_usd: parseFloat(document.getElementById('total-neto-usd').innerText),
                total_usd: parseFloat(document.getElementById('total-general-usd').innerText),
                total_ars: parseFloat(document.getElementById('total-general-ars').innerText.replace(/[^\d.,]/g, '').replace(/\./g, '').replace(',', '.')),
                observations: document.getElementById('quote-observations').value,
                items: items
            };

            fetch('ajax_save_quotation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        logCrmInteraction(data.client_id, 'Email/PDF', `Generó presupuesto ${data.quote_number}`);

                        if (res.public_url) {
                            navigator.clipboard.writeText(res.public_url).then(() => {
                                alert('Presupuesto Guardado.\nEnlace público copiado al portapapeles!');
                            });
                        }

                        window.open('imprimir_cotizacion.php?id=' + res.id, '_blank');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        }

        function sendWhatsApp() {
            if (items.length === 0) { alert('Agregue productos primero.'); return; }
            const clientName = document.getElementById('client-name-display').value || 'Cliente';
            const clientId = document.getElementById('selected-client-id').value;
            const quoteNo = '<?php echo $quoteNumber; ?>';
            const totalUSD = document.getElementById('total-general-usd').innerText;
            const totalARS = document.getElementById('total-general-ars').innerText;

            let text = `*Presupuesto VS System - ${quoteNo}*\n`;
            text += `Hola *${clientName}*, aquí tienes la cotización solicitada:\n\n`;
            items.forEach(i => {
                const isRetention = document.getElementById('is-retention').checked;
                const isBank = document.getElementById('is-bank').checked;
                let p = i.price;
                if (isRetention) p *= 1.07;
                if (isBank) p *= 1.03;
                text += `- ${i.qty}x ${i.desc} (*$${p.toFixed(2)}*)\n`;
            });
            text += `\n*TOTAL USD: $${totalUSD}*\n*TOTAL ARS: $${totalARS}*\n\n_Cotización BNA: ${bnaRate}_`;
            logCrmInteraction(clientId, 'WhatsApp', `Envió presupuesto ${quoteNo} por WhatsApp`);
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }

        function logCrmInteraction(entityId, type, desc) {
            if (!entityId || entityId == "1") return;
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entity_id: entityId, type: type, description: desc })
            });
        }

        renderTable();
    </script>
</body>

</html>