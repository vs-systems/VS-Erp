<?php
/**
 * Catálogo Público - VS System
 * No Authentication Required - Premium Stitch UI Redesign
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/modules/catalogo/PublicCatalog.php';

use Vsys\Modules\Catalogo\PublicCatalog;

$publicCatalog = new PublicCatalog();
$data = $publicCatalog->getProductsForWeb();
$products = $data['products'];
$exchangeRate = $data['rate'];

// Categorías y Marcas para filtros
$categories = array_unique(array_column($products, 'category'));
$brands = array_unique(array_column($products, 'brand'));
sort($categories);
sort($brands);

// Check Maintenance Mode
$configPath = __DIR__ . '/config_catalogs.json';
$catConfig = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : ['maintenance_mode' => 0];
if (($catConfig['maintenance_mode'] ?? 0) == 1 && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: maintenance.php");
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnología y Seguridad - Vecino Seguro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-dark": "#0a0c10",
                        "surface-dark": "#111827",
                        "card-dark": "#16202e",
                        "border-dark": "#233348",
                        "accent-green": "#10b981",
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: white;
        }

        .glass-header {
            background: rgba(13, 17, 23, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }

        .gradient-text {
            background: linear-gradient(90deg, #136dec, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(35, 51, 72, 0.5);
        }

        .product-card:hover {
            transform: translateY(-8px);
            border-color: #136dec;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0d1117;
        }

        ::-webkit-scrollbar-thumb {
            background: #233348;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #324867;
        }
    </style>
</head>

<body class="antialiased overflow-x-hidden">

    <!-- Navbar -->
    <nav class="glass-header sticky top-0 z-50 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec]">
                <span class="material-symbols-outlined text-2xl">shield</span>
            </div>
            <div class="flex flex-col">
                <span class="text-white text-lg font-bold leading-tight">Vecino Seguro</span>
                <span class="text-xs text-[#136dec] font-medium tracking-tight uppercase">Catálogo Tecnológico</span>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <button class="relative text-white hover:text-[#136dec] transition-colors" onclick="toggleCart()">
                <span class="material-symbols-outlined text-[28px]">shopping_bag</span>
                <span
                    class="absolute -top-1 -right-1 bg-accent-green text-black text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center border-2 border-background-dark"
                    id="cartBadge">0</span>
            </button>
            <a href="login.php"
                class="bg-white/5 hover:bg-white/10 px-4 py-2 rounded-lg text-xs font-bold border border-white/10 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">login</span> ACCESO ERP
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-20 pb-16 px-6 text-center">
        <div class="max-w-4xl mx-auto space-y-4">
            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tighter text-white">Explora nuestra <span
                    class="gradient-text">Tecnología</span></h1>
            <p class="text-slate-400 text-lg md:text-xl font-medium max-w-2xl mx-auto leading-relaxed">
                Equipamiento de seguridad electrónica de alta gama. Cámaras, NVRs y soluciones de videovigilancia
                profesional con respaldo técnico garantizado.
            </p>
        </div>
    </section>

    <!-- Search & Filters -->
    <section class="max-w-7xl mx-auto px-6 mb-12">
        <div
            class="bg-[#16202e]/50 border border-[#233348] p-4 rounded-2xl flex flex-col md:flex-row gap-4 backdrop-blur-sm">
            <div class="relative flex-1">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">search</span>
                <input type="text" id="searchInput" placeholder="Buscar por SKU o descripción..."
                    class="w-full bg-[#0d1117] border-none rounded-xl py-3 pl-11 pr-4 text-sm text-white focus:ring-2 focus:ring-[#136dec] outline-none placeholder:text-slate-600">
            </div>
            <div class="flex gap-4">
                <select id="catFilter"
                    class="bg-[#0d1117] border border-[#233348] rounded-xl py-3 px-4 text-sm text-slate-400 focus:ring-1 focus:ring-[#136dec] outline-none appearance-none min-w-[180px]">
                    <option value="">Todas las Categorías</option>
                    <?php
                    // Build Tree on the fly since we have all products
                    $catTree = [];
                    foreach ($products as $pr) {
                        $c = $pr['category'];
                        $s = $pr['subcategory'] ?? '';
                        if ($c) {
                            if (!isset($catTree[$c]))
                                $catTree[$c] = [];
                            if ($s && !in_array($s, $catTree[$c]))
                                $catTree[$c][] = $s;
                        }
                    }
                    ksort($catTree);

                    foreach ($catTree as $cat => $subs):
                        sort($subs);
                        ?>
                        <option value="<?php echo htmlspecialchars(strtolower($cat)); ?>"
                            class="font-bold bg-slate-800 text-white disabled" disabled>
                            — <?php echo htmlspecialchars($cat); ?> —
                        </option>
                        <option value="<?php echo htmlspecialchars(strtolower($cat)); ?>">
                            Todo en <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php foreach ($subs as $sub): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($cat . '|' . $sub)); ?>">
                                &nbsp;&nbsp;&nbsp;<?php echo htmlspecialchars($sub); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
                <select id="brandFilter"
                    class="bg-[#0d1117] border border-[#233348] rounded-xl py-3 px-4 text-sm text-slate-400 focus:ring-1 focus:ring-[#136dec] outline-none appearance-none min-w-[150px]">
                    <option value="">Todas las Marcas</option>
                    <?php foreach ($brands as $brand)
                        echo "<option value='" . strtolower($brand) . "'>$brand</option>"; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- Product Grid -->
    <main class="max-w-7xl mx-auto px-6 pb-20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8" id="productGrid">
            <?php foreach ($products as $p): ?>
                <div class="product-card bg-[#16202e] rounded-2xl p-4 flex flex-col group cursor-pointer"
                    data-description="<?php echo strtolower($p['description']); ?>"
                    data-sku="<?php echo strtolower($p['sku']); ?>"
                    data-category="<?php echo strtolower($p['category']); ?>"
                    data-subcategory="<?php echo strtolower($p['subcategory'] ?? ''); ?>"
                    data-brand="<?php echo strtolower($p['brand']); ?>">

                    <div
                        class="relative aspect-square rounded-xl bg-white overflow-hidden mb-6 flex items-center justify-center p-4">
                        <img src="<?php echo $p['image_url']; ?>" alt="<?php echo $p['description']; ?>"
                            class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-500">
                    </div>

                    <div class="flex-1 flex flex-col">
                        <span
                            class="text-[10px] font-bold text-[#136dec] uppercase tracking-widest mb-1"><?php echo $p['brand']; ?></span>
                        <h3
                            class="text-white font-bold text-sm leading-snug line-clamp-2 mb-2 group-hover:text-[#136dec] transition-colors">
                            <?php echo $p['description']; ?>
                        </h3>
                        <p class="text-slate-500 text-[10px] mb-4"><?php echo $p['sku']; ?></p>

                        <div class="mt-auto flex items-center justify-between gap-2 pt-4 border-t border-[#233348]">
                            <div>
                                <span class="block text-[10px] text-slate-500 font-bold uppercase">Precio Final</span>
                                <span class="text-lg font-extrabold text-[#10b981]">USD
                                    <?php echo number_format($p['price_final_usd'], 2); ?></span>
                            </div>
                            <button onclick='addToCart(<?php echo json_encode($p); ?>)'
                                class="bg-accent-green hover:bg-emerald-600 text-black px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-emerald-500/10">
                                <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
                                <span class="text-xs font-bold uppercase">Agregar</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Cart Sidebar / Modal -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden" onclick="toggleCart()"></div>
    <div id="cartModal"
        class="fixed right-0 top-0 h-full w-full max-w-md bg-[#111827] border-l border-[#233348] z-[70] translate-x-full transition-transform duration-500 shadow-2xl flex flex-col">
        <div
            class="p-6 border-b border-[#233348] flex items-center justify-between bg-[#111827]/50 backdrop-blur sticky top-0">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#136dec]">shopping_basket</span>
                <h3 class="text-lg font-bold">Resumen de tu Pedido</h3>
            </div>
            <button onclick="toggleCart()" class="text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="cartContent"></div>

        <div class="p-6 border-t border-[#233348] bg-[#0d1117] space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 font-medium">Subtotal estimado</span>
                <span class="text-2xl font-bold gradient-text" id="cartTotal">USD 0.00</span>
            </div>
            <button
                class="w-full bg-[#136dec] hover:bg-blue-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-xl shadow-blue-500/20"
                onclick="showCheckout()">
                REALIZAR PEDIDO <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-[#111827] border border-[#233348] w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-8 border-b border-[#233348] flex justify-between items-center bg-[#16202e]/50">
                <div>
                    <h3 class="text-xl font-bold text-white">Finalizar Pedido</h3>
                    <p class="text-xs text-slate-500 mt-1">Ingresa tus datos para recibir la cotización oficial.</p>
                </div>
                <button onclick="closeCheckout()" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="checkoutForm" class="p-8 space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nombre Completo</label>
                        <input type="text" id="clientName" required
                            class="w-full bg-[#0d1117] border-[#233348] rounded-xl py-3 px-4 text-white focus:ring-2 focus:ring-[#136dec] outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">WhatsApp</label>
                            <input type="tel" id="clientPhone" required placeholder="Ej: 2235..."
                                class="w-full bg-[#0d1117] border-[#233348] rounded-xl py-3 px-4 text-white focus:ring-2 focus:ring-[#136dec] outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                            <input type="email" id="clientEmail" required
                                class="w-full bg-[#0d1117] border-[#233348] rounded-xl py-3 px-4 text-white focus:ring-2 focus:ring-[#136dec] outline-none">
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" id="confirmBtn"
                        class="w-full bg-[#136dec] hover:bg-blue-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-xl shadow-blue-500/20">
                        CONFIRMAR Y GENERAR <span class="material-symbols-outlined">description</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Processing Overlay -->
    <div id="processingOverlay"
        class="fixed inset-0 bg-black/90 z-[200] hidden flex-col items-center justify-center text-center gap-6">
        <div class="w-16 h-16 border-4 border-[#136dec] border-t-transparent rounded-full animate-spin"></div>
        <p class="text-xl font-bold text-white tracking-widest uppercase">Procesando Pedido...</p>
        <p class="text-slate-500 text-sm">Estamos registrando tu interés en el CRM</p>
    </div>

    <!-- Scripting Logic -->
    <script>
        let cart = [];

        function toggleCart() {
            const modal = document.getElementById('cartModal');
            const overlay = document.getElementById('overlay');
            modal.classList.toggle('translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function addToCart(product) {
            const exists = cart.find(i => i.sku === product.sku);
            if (exists) exists.qty++;
            else cart.push({ ...product, qty: 1 });
            updateUI();
            if (!document.getElementById('cartModal').classList.contains('translate-x-full')) return;
            toggleCart();
        }

        function updateUI() {
            const badge = document.getElementById('cartBadge');
            const content = document.getElementById('cartContent');
            const total = document.getElementById('cartTotal');

            badge.innerText = cart.reduce((acc, i) => acc + i.qty, 0);

            content.innerHTML = cart.length === 0 ? '<div class="h-64 flex flex-col items-center justify-center text-slate-500 gap-4"><span class="material-symbols-outlined text-5xl">shopping_cart_off</span><p class="font-medium text-sm">Tu carrito está vacío</p></div>' : '';

            let sum = 0;
            cart.forEach((item, idx) => {
                sum += item.price_final_usd * item.qty;
                content.innerHTML += `
                    <div class="bg-[#16202e] border border-[#233348] p-4 rounded-xl flex gap-4 group">
                        <div class="h-16 w-16 bg-white p-2 rounded-lg flex items-center justify-center shrink-0">
                            <img src="${item.image_url}" class="max-h-full mix-blend-multiply">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white text-xs font-bold truncate">${item.description}</p>
                            <p class="text-[#10b981] text-xs font-bold mt-1">USD ${item.price_final_usd}</p>
                            <div class="flex items-center gap-3 mt-2">
                                <button onclick="changeQty(${idx}, -1)" class="h-6 w-6 flex items-center justify-center bg-[#0d1117] hover:bg-slate-800 rounded border border-[#233348] text-xs">-</button>
                                <span class="text-xs font-bold">${item.qty}</span>
                                <button onclick="changeQty(${idx}, 1)" class="h-6 w-6 flex items-center justify-center bg-[#0d1117] hover:bg-slate-800 rounded border border-[#233348] text-xs">+</button>
                                <button onclick="removeItem(${idx})" class="ml-auto text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><span class="material-symbols-outlined text-lg">delete</span></button>
                            </div>
                        </div>
                    </div>
                `;
            });
            total.innerText = `USD ${sum.toFixed(2)}`;
        }

        function changeQty(idx, delta) {
            cart[idx].qty += delta;
            if (cart[idx].qty <= 0) cart.splice(idx, 1);
            updateUI();
        }

        function removeItem(idx) {
            cart.splice(idx, 1);
            updateUI();
        }

        // Filters Search
        function filterProducts() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const cat = document.getElementById('catFilter').value;
            const brand = document.getElementById('brandFilter').value;
            const cards = document.querySelectorAll('.product-card');

            cards.forEach(card => {
                const text = card.dataset.description + ' ' + card.dataset.sku;
                const matchesText = text.includes(q);

                // Nested Logic
                const cardCat = card.dataset.category;
                const cardSub = card.dataset.subcategory;
                const cardBrand = card.dataset.brand;

                const brandFilter = document.getElementById('brandFilter').value;

                let matchesCategory = true;
                if (cat) {
                    if (cat.includes('|')) {
                        const [selCat, selSub] = cat.split('|');
                        matchesCategory = (cardCat === selCat && cardSub === selSub);
                    } else {
                        matchesCategory = (cardCat === cat);
                    }
                }

                const matchesBrand = !brandFilter || cardBrand === brandFilter;

                if (matchesText && matchesCategory && matchesBrand) card.style.display = 'flex';
                else card.style.display = 'none';
            });
        }

        function showCheckout() {
            if (cart.length === 0) return;
            toggleCart(); // Close sidebar
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        document.getElementById('checkoutForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('confirmBtn');
            btn.disabled = true;
            document.getElementById('processingOverlay').classList.replace('hidden', 'flex');

            const payload = {
                items: cart,
                client: {
                    name: document.getElementById('clientName').value,
                    phone: document.getElementById('clientPhone').value,
                    email: document.getElementById('clientEmail').value
                },
                catalog_type: 'publico'
            };

            fetch('ajax_catalog_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Send WhatsApp with Quote ID
                        let text = `Hola! Soy ${payload.client.name} (Ref: #${data.quote_number}) y quiero realizar un pedido:\n\n`;
                        cart.forEach(item => {
                            text += `- ${item.sku} | ${item.description} (Cant: ${item.qty})\n`;
                        });
                        const total = document.getElementById('cartTotal').innerText;
                        text += `\n*TOTAL ESTIMADO: ${total}*`;

                        window.open(`https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=${encodeURIComponent(text)}`, '_blank');

                        // Success UI
                        cart = [];
                        updateUI();
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo procesar el pedido'));
                        document.getElementById('processingOverlay').classList.replace('flex', 'hidden');
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Ocurrió un error de red');
                    document.getElementById('processingOverlay').classList.replace('flex', 'hidden');
                    btn.disabled = false;
                });
        });

        document.getElementById('searchInput').addEventListener('input', filterProducts);
        document.getElementById('catFilter').addEventListener('change', filterProducts);
        document.getElementById('brandFilter').addEventListener('change', filterProducts);

    </script>
</body>

</html>