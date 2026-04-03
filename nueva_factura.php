<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

// Get current exchange rate
$db = Vsys\Lib\Database::getInstance();
$exchangeRate = $db->query("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 1450.00;
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Factura Interna - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
        }

        .normal-case {
            text-transform: none;
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]">
                <div class="flex items-center gap-3">
                    <a href="facturacion.php"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-[#1c2a3b] transition-colors">
                        <span class="material-symbols-outlined text-slate-400">arrow_back</span>
                    </a>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Nueva Factura
                        Interna</h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-6xl mx-auto space-y-6">
                    <form id="invoiceForm" class="space-y-6">
                        <!-- Header Config -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="relative md:col-span-2">
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Cliente</label>
                                    <input type="text" id="clientSearch" placeholder="Escriba nombre del cliente..."
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-2.5 text-sm normal-case focus:ring-2 focus:ring-primary outline-none">
                                    <input type="hidden" name="client_id" id="client_id" required>
                                    <div id="results"
                                        class="absolute left-0 right-0 mt-1 bg-white dark:bg-[#1c2a3b] border border-slate-200 dark:border-[#233348] rounded-xl shadow-2xl z-50 hidden max-h-60 overflow-y-auto">
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Tipo
                                        Comprobante</label>
                                    <select name="invoice_type"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary outline-none">
                                        <option value="A">Factura A</option>
                                        <option value="B">Factura B</option>
                                        <option value="X">Presupuesto / Interno</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl overflow-hidden shadow-sm">
                            <div
                                class="p-4 bg-slate-50 dark:bg-[#1c2a3b]/30 border-b border-slate-200 dark:border-[#233348] flex justify-between items-center">
                                <h3 class="font-bold text-sm tracking-tight text-slate-500">Items de Factura</h3>
                                <button type="button" onclick="addItem()"
                                    class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg font-bold flex items-center gap-1 hover:bg-blue-600 transition-colors">
                                    <span class="material-symbols-outlined text-sm">add</span> AGREGAR ITEM
                                </button>
                            </div>
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="text-[9px] uppercase text-slate-400 font-bold border-b border-slate-100 dark:border-[#233348]">
                                        <th class="px-6 py-3">Descripción</th>
                                        <th class="px-6 py-3 text-center" width="100">Cantidad</th>
                                        <th class="px-6 py-3 text-right" width="150">Precio Unit. USD</th>
                                        <th class="px-6 py-3 text-center" width="100">IVA (%)</th>
                                        <th class="px-6 py-3 text-right" width="150">Subtotal USD</th>
                                        <th class="px-6 py-3" width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody" class="divide-y divide-slate-100 dark:divide-[#233348]">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6">
                                <label
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Observaciones</label>
                                <textarea name="notes" rows="4"
                                    class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl p-4 text-xs normal-case focus:ring-2 focus:ring-primary outline-none"
                                    placeholder="Notas que aparecerán en el comprobante..."></textarea>
                            </div>
                            <div
                                class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-6 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Subtotal
                                            Neto</span>
                                        <span class="font-mono font-bold text-sm" id="subtotalDisplay">USD 0.00</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Total IVA</span>
                                        <span class="font-mono font-bold text-sm" id="ivaDisplay">USD 0.00</span>
                                    </div>
                                    <div
                                        class="border-t border-slate-100 dark:border-[#233348] pt-3 flex justify-between items-center">
                                        <span class="text-xs font-bold text-primary uppercase">Total Factura</span>
                                        <span class="font-mono font-bold text-2xl text-primary" id="totalDisplay">USD
                                            0.00</span>
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full mt-6 bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl text-xs uppercase tracking-widest shadow-lg shadow-blue-500/20 active:scale-95 transition-all">
                                    Emitir Factura y Registrar Deuda
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let items = [];
        const exchangeRate = <?php echo $exchangeRate; ?>;

        function addItem() {
            items.push({ description: '', quantity: 1, unit_price: 0, iva_rate: 21 });
            renderItems();
        }

        function renderItems() {
            const body = document.getElementById('itemsBody');
            body.innerHTML = '';
            let subtotal = 0;
            let ivaTotal = 0;

            items.forEach((item, index) => {
                const lineSub = item.quantity * item.unit_price;
                const lineIva = lineSub * (item.iva_rate / 100);
                subtotal += lineSub;
                ivaTotal += lineIva;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-6 py-3"><input type="text" value="${item.description}" onchange="updateItem(${index}, 'description', this.value)" class="w-full bg-white dark:bg-[#101822] border-slate-100 dark:border-[#233348] rounded-lg text-xs normal-case p-2"></td>
                    <td class="px-6 py-3"><input type="number" value="${item.quantity}" onchange="updateItem(${index}, 'quantity', this.value)" class="w-full bg-white dark:bg-[#101822] border-slate-100 dark:border-[#233348] rounded-lg text-xs text-center p-2 font-mono"></td>
                    <td class="px-6 py-3"><input type="number" step="0.01" value="${item.unit_price}" onchange="updateItem(${index}, 'unit_price', this.value)" class="w-full bg-white dark:bg-[#101822] border-slate-100 dark:border-[#233348] rounded-lg text-xs text-right p-2 font-mono"></td>
                    <td class="px-6 py-3">
                        <select onchange="updateItem(${index}, 'iva_rate', this.value)" class="w-full bg-white dark:bg-[#101822] border-slate-100 dark:border-[#233348] rounded-lg text-xs p-2">
                            <option value="21" ${item.iva_rate == 21 ? 'selected' : ''}>21%</option>
                            <option value="10.5" ${item.iva_rate == 10.5 ? 'selected' : ''}>10.5%</option>
                            <option value="0" ${item.iva_rate == 0 ? 'selected' : ''}>0%</option>
                        </select>
                    </td>
                    <td class="px-6 py-3 text-right font-mono font-bold text-xs text-slate-600 dark:text-slate-300">USD ${lineSub.toFixed(2)}</td>
                    <td class="px-6 py-3 text-center">
                        <button type="button" onclick="items.splice(${index}, 1); renderItems();" class="text-red-400 hover:text-red-500"><span class="material-symbols-outlined text-lg">delete</span></button>
                    </td>
                `;
                body.appendChild(tr);
            });

            document.getElementById('subtotalDisplay').innerText = `USD ${subtotal.toFixed(2)}`;
            document.getElementById('ivaDisplay').innerText = `USD ${ivaTotal.toFixed(2)}`;
            const total = subtotal + ivaTotal;
            document.getElementById('totalDisplay').innerText = `USD ${total.toFixed(2)}`;
        }

        function updateItem(index, field, val) {
            items[index][field] = (field === 'description') ? val : parseFloat(val);
            renderItems();
        }

        // Client Search
        const clientSearch = document.getElementById('clientSearch');
        const resultsDiv = document.getElementById('results');
        const clientIdInput = document.getElementById('client_id');

        clientSearch.addEventListener('input', async (e) => {
            const q = e.target.value;
            if (q.length < 2) { resultsDiv.classList.add('hidden'); return; }

            const res = await fetch(`ajax_search_entities.php?q=${q}`);
            const data = await res.json();

            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-2.5 hover:bg-primary/10 cursor-pointer border-b border-slate-100 dark:border-[#233348] last:border-0';
                    div.innerHTML = `<p class="font-bold text-xs">${c.name}</p><p class="text-[9px] text-slate-500 uppercase">${c.tax_id || 'SIN CUIT'}</p>`;
                    div.onclick = () => {
                        clientSearch.value = c.name;
                        clientIdInput.value = c.id;
                        resultsDiv.classList.add('hidden');
                    };
                    resultsDiv.appendChild(div);
                });
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.classList.add('hidden');
            }
        });

        document.getElementById('invoiceForm').onsubmit = async (e) => {
            e.preventDefault();
            if (!clientIdInput.value) { Swal.fire('Error', 'Seleccione un cliente', 'error'); return; }
            if (items.length === 0) { Swal.fire('Error', 'Agregue al menos un item', 'error'); return; }

            const subtotal = items.reduce((acc, i) => acc + (i.quantity * i.unit_price), 0);
            const iva = items.reduce((acc, i) => acc + (i.quantity * i.unit_price * (i.iva_rate / 100)), 0);
            const totalUSD = subtotal + iva;

            const formData = new FormData(e.target);
            formData.append('action', 'create_invoice');
            formData.append('date', '<?php echo date('Y-m-d'); ?>');
            formData.append('due_date', '<?php echo date('Y-m-d', strtotime('+30 days')); ?>');
            formData.append('total_net', subtotal);
            formData.append('total_iva', iva);
            formData.append('total_amount', totalUSD);
            formData.append('total_amount_ars', totalUSD * exchangeRate); // Send ARS for account movement
            formData.append('items', JSON.stringify(items.map(i => ({ ...i, subtotal: i.quantity * i.unit_price }))));

            try {
                const res = await fetch('ajax_billing.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Éxito', 'Factura generada y cuenta corriente actualizada', 'success').then(() => location.href = 'facturacion.php');
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        };

        // Initial row
        addItem();
    </script>
</body>

</html>