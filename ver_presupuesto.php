<?php
/**
 * VS System ERP - Public Web Quotation
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$hash = $_GET['h'] ?? '';

if (empty($hash)) {
    die("Enlace inválido.");
}

$db = Vsys\Lib\Database::getInstance();

// Fetch quote by hash
$stmt = $db->prepare("SELECT q.*, e.name as client_name, e.tax_id, e.address, e.contact_person, e.email, u.full_name as seller_name, u.email as seller_email 
                      FROM quotations q 
                      JOIN entities e ON q.client_id = e.id 
                      JOIN users u ON q.user_id = u.id 
                      WHERE q.public_hash = ?");
$stmt->execute([$hash]);
$quote = $stmt->fetch();

if (!$quote) {
    die("Presupuesto no encontrado o expirado.");
}

// Fetch items
$stmtItems = $db->prepare("SELECT qi.*, p.sku, p.description, p.image_url 
                           FROM quotation_items qi 
                           JOIN products p ON qi.product_id = p.id 
                           WHERE qi.quotation_id = ?");
$stmtItems->execute([$quote['id']]);
$items = $stmtItems->fetchAll();

// Company Info
$company = [
    'name' => 'Vecinos Seguros',
    'address' => 'Saavedra 3091, Mar del Plata',
    'phone' => '+54 9 223 669-2708',
    'web' => 'www.vecinoseguro.com',
    'logo' => 'logo_display.php?v=2' // Helper to serve logo
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto #<?php echo $quote['quote_number']; ?> - Vecinos Seguros</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: "#5d2fc1",
                        dark: "#0f172a",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .shadow-xl { shadow: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen py-10 print:py-0 print:bg-white">

    <!-- Floating Actions DO NOT PRINT -->
    <div class="fixed bottom-6 right-6 flex flex-col gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-slate-800 text-white p-4 rounded-full shadow-xl hover:scale-105 transition-transform flex items-center justify-center" title="Imprimir / Guardar PDF">
            <span class="material-symbols-outlined">print</span>
        </button>
        <a href="https://wa.me/?text=Hola, te comparto el presupuesto: <?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" target="_blank" class="bg-[#25D366] text-white p-4 rounded-full shadow-xl hover:scale-105 transition-transform flex items-center justify-center" title="Compartir por WhatsApp">
            <i class="fa-brands fa-whatsapp font-bold text-xl">WA</i>
        </a>
    </div>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden print:shadow-none print:rounded-none">
        
        <!-- Header -->
        <header class="bg-brand text-white p-10 print:p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full -ml-10 -mb-10 pointer-events-none"></div>
            
            <div class="flex justify-between items-start relative z-10">
                <div>
                   <!-- <img src="<?php echo $company['logo']; ?>" alt="Logo" class="h-12 mb-6 bg-white/90 rounded p-1 filter drop-shadow opacity-95"> -->
                    <h1 class="text-3xl font-black tracking-tight uppercase">Presupuesto</h1>
                    <p class="opacity-80 font-mono mt-1 text-lg">#<?php echo $quote['quote_number']; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm uppercase tracking-widest opacity-70 font-bold mb-1">Fecha Emisión</p>
                    <p class="font-bold text-xl"><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></p>
                    <p class="text-sm mt-4 opacity-70">Válido hasta: <span class="font-bold text-white"><?php echo date('d/m/Y', strtotime($quote['valid_until'])); ?></span></p>
                </div>
            </div>
        </header>

        <!-- Entities Info -->
        <div class="grid grid-cols-2 gap-10 p-10 border-b border-slate-100 print:p-8">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">De</h3>
                <h2 class="text-xl font-bold text-brand mb-1"><?php echo $company['name']; ?></h2>
                <div class="text-slate-500 text-sm space-y-1">
                    <p class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">location_on</span> <?php echo $company['address']; ?></p>
                    <p class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">call</span> <?php echo $company['phone']; ?></p>
                    <p class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">language</span> <?php echo $company['web']; ?></p>
                </div>
            </div>
            <div class="text-right">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Para</h3>
                <h2 class="text-xl font-bold text-slate-800 mb-1"><?php echo $quote['client_name']; ?></h2>
                <div class="text-slate-500 text-sm space-y-1 ml-auto inline-block text-right">
                     <?php if($quote['contact_person']): ?><p><?php echo $quote['contact_person']; ?></p><?php endif; ?>
                     <?php if($quote['tax_id']): ?><p>CUIT: <?php echo $quote['tax_id']; ?></p><?php endif; ?>
                     <?php if($quote['address']): ?><p><?php echo $quote['address']; ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="p-10 print:p-8">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-[10px] uppercase tracking-widest text-slate-500 font-bold border-b-2 border-slate-100">
                        <th class="pb-3 w-16 text-center">Cant.</th>
                        <th class="pb-3 pl-4">Descripción</th>
                        <th class="pb-3 text-right">Precio Unit.</th>
                        <th class="pb-3 text-right">IVA</th>
                        <th class="pb-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-50">
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="py-4 text-center font-bold text-slate-400"><?php echo $item['quantity']; ?></td>
                        <td class="py-4 pl-4">
                            <p class="font-bold text-slate-700"><?php echo $item['sku']; ?></p>
                            <p class="text-slate-500 text-xs mt-0.5"><?php echo $item['description']; ?></p>
                        </td>
                        <td class="py-4 text-right font-mono text-slate-600">USD <?php echo number_format($item['unit_price_usd'], 2); ?></td>
                        <td class="py-4 text-right text-xs text-slate-400"><?php echo number_format($item['iva_rate'], 1); ?>%</td>
                        <td class="py-4 text-right font-mono font-bold text-slate-800">USD <?php echo number_format($item['subtotal_usd'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="bg-slate-50 p-10 print:bg-white print:pt-4 border-t border-slate-200">
            <div class="flex flex-col md:flex-row justify-between items-end gap-10">
                <div class="w-full md:w-1/2 text-sm text-slate-500">
                    <h4 class="font-bold text-slate-700 mb-2 uppercase text-xs tracking-widest">Observaciones</h4>
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm print:border-none print:shadow-none print:p-0">
                        <?php echo nl2br($quote['observations']) ?: 'Sin observaciones.'; ?>
                    </div>
                </div>
                
                <div class="w-full md:w-1/2 max-w-sm space-y-3">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal USD</span>
                        <span class="font-mono font-bold">USD <?php echo number_format($quote['subtotal_usd'], 2); ?></span>
                    </div>
                    
                    <?php if ($quote['with_iva']): ?>
                        <?php if ($quote['total_iva_105'] > 0): ?>
                        <div class="flex justify-between text-slate-500 text-sm">
                            <span>IVA (10.5%)</span>
                            <span class="font-mono">USD <?php echo number_format($quote['total_iva_105'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($quote['total_iva_21'] > 0): ?>
                        <div class="flex justify-between text-slate-500 text-sm">
                            <span>IVA (21%)</span>
                            <span class="font-mono">USD <?php echo number_format($quote['total_iva_21'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="flex justify-between text-brand font-bold text-lg pt-4 border-t border-slate-200">
                        <span>TOTAL USD</span>
                        <span class="font-mono">USD <?php echo number_format($quote['total_usd'], 2); ?></span>
                    </div>

                    <div class="mt-6 pt-4 border-t-2 border-brand/10">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="text-xs uppercase font-bold text-slate-400">Cotización Dólar</span>
                            <span class="font-mono text-sm">ARS $<?php echo number_format($quote['exchange_rate_usd'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center text-emerald-600 font-black text-2xl bg-emerald-50 p-3 rounded-xl border border-emerald-100 print:bg-transparent print:border-none print:p-0">
                            <span>TOTAL ARS</span>
                            <span>$ <?php echo number_format($quote['total_ars'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center p-6 text-xs text-slate-400 border-t border-slate-100 bg-white">
            <p>Los precios en pesos están sujetos a modificaciones según la cotización del dólar BNA al momento del pago.
            <br>Validez de la oferta: 48 horas.</p>
        </footer>

    </main>

</body>
</html>
