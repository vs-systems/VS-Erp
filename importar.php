<?php
require_once 'auth_check.php';
/**
 * VS System ERP — Importación de Precios
 * Bloque 3: Nuevo formato CSV
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$message = '';
$status  = '';
$count   = 0;
$catalog = new Catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type       = $_POST['import_type'] ?? 'product';
    $providerId = $_POST['provider_id'] ?? null;

    $targetDir = __DIR__ . '/data/uploads/';
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $targetFile = $targetDir . time() . '_' . basename($_FILES['csv_file']['name']);

    if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $targetFile)) {
        try {
            if ($type === 'product') {
                $count = $catalog->importProductsFromCsv($targetFile, $providerId);
            } elseif ($type === 'product_legacy') {
                $count = $catalog->importProductsFromCsvLegacy($targetFile, $providerId);
            } elseif ($type === 'client' || $type === 'supplier') {
                $count = $catalog->importEntitiesFromCsv($targetFile, $type);
            }

            if ($count !== false) {
                $message = "¡Éxito! Se procesaron <strong>$count registros</strong> correctamente.";
                $status  = 'success';
            } else {
                $message = 'Error al procesar el archivo CSV. Verificá el formato.';
                $status  = 'error';
            }
        } catch (\Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $status  = 'error';
        }
    } else {
        $message = 'No se pudo subir el archivo. Verificá permisos del servidor.';
        $status  = 'error';
    }
}

$providers = $catalog->getProviders();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importación de Precios — VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { "primary": "#136dec" } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        .drop-zone {
            border: 2px dashed;
            transition: all .25s;
        }
        .drop-zone.dragover {
            border-color: #136dec;
            background: rgba(19,109,236,.06);
        }

        /* Columnas del formato */
        .col-chip {
            display: inline-flex;
            align-items: center;
            background: rgba(19,109,236,.12);
            color: #60a5fa;
            border: 1px solid rgba(19,109,236,.25);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>

<body class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">

            <!-- Header -->
            <header class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                    </div>
                    <div>
                        <h2 class="dark:text-white text-slate-800 font-bold text-lg leading-none">Importación de Precios</h2>
                        <p class="text-slate-400 text-[10px] font-bold tracking-widest uppercase mt-0.5">CSV → Catálogo de Productos</p>
                    </div>
                </div>
            </header>

            <!-- Contenido -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto space-y-6">

                    <?php if ($message): ?>
                        <div class="flex items-start gap-3 p-4 rounded-2xl border <?= $status === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400' ?>">
                            <span class="material-symbols-outlined flex-shrink-0"><?= $status === 'success' ? 'check_circle' : 'error' ?></span>
                            <span class="text-sm font-semibold"><?= $message ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de importación -->
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-primary mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-3">
                            <span class="material-symbols-outlined text-lg">tune</span>
                            Configuración de importación
                        </h3>

                        <form method="POST" enctype="multipart/form-data" id="import-form">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Tipo de importación -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">¿Qué vas a importar?</label>
                                    <select name="import_type" id="import_type" onchange="onTypeChange()"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary">
                                        <option value="product">Catálogo de Productos (Formato Nuevo)</option>
                                        <option value="product_legacy">Catálogo de Productos (Formato Antiguo)</option>
                                        <option value="client">Base de Clientes</option>
                                        <option value="supplier">Base de Proveedores</option>
                                    </select>
                                </div>

                                <!-- Proveedor (solo productos legacy) -->
                                <div id="provider-selector">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Proveedor <span class="text-slate-400 font-normal normal-case">(solo formato antiguo)</span></label>
                                    <select name="provider_id"
                                        class="w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary">
                                        <option value="">— Sin proveedor (Costo Base) —</option>
                                        <?php foreach ($providers as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Drop Zone -->
                            <div class="drop-zone border-slate-200 dark:border-[#233348] rounded-2xl p-10 text-center cursor-pointer mb-6 relative"
                                id="drop-zone" onclick="document.getElementById('csv_file').click()">
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required class="hidden"
                                    onchange="onFileChange(this)">
                                <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3 block">upload_file</span>
                                <p class="font-bold text-slate-500 dark:text-slate-400" id="drop-label">Arrastrá o hacé clic para seleccionar un archivo CSV</p>
                                <p class="text-xs text-slate-400 dark:text-slate-600 mt-1">Formatos aceptados: .csv, .txt · Separador: ; o , (autodetectado)</p>
                            </div>

                            <button type="submit" id="btn-import"
                                class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-primary/20 transition-all active:scale-95">
                                <span class="material-symbols-outlined">play_circle</span>
                                INICIAR PROCESAMIENTO
                            </button>
                        </form>
                    </div>

                    <!-- Referencia de formatos -->
                    <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-primary mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-3">
                            <span class="material-symbols-outlined text-lg">info</span>
                            Referencia de Formatos CSV
                        </h3>

                        <!-- Formato Nuevo -->
                        <div id="hint-product" class="space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-primary/10 text-primary border border-primary/20 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest">Formato Nuevo</span>
                                <span class="text-xs text-slate-400">Precios en ARS directos · Recomendado</span>
                            </div>

                            <!-- Tabla de columnas -->
                            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-[#233348]">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 dark:bg-[#101822]/50">
                                        <tr class="text-slate-500 font-bold uppercase tracking-widest">
                                            <th class="px-4 py-3">#</th>
                                            <th class="px-4 py-3">Columna</th>
                                            <th class="px-4 py-3">Descripción</th>
                                            <th class="px-4 py-3">Ejemplo</th>
                                            <th class="px-4 py-3 text-center">Requerido</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-[#233348]">
                                        <?php
                                        $cols = [
                                            ['SKU',         'Código único del producto. Clave primaria.',       'BD-12345',     true],
                                            ['DESCRIPCION', 'Descripción completa del producto.',               'Sensor PIR Dual',  true],
                                            ['MARCA',       'Marca o fabricante.',                              'Paradox',      true],
                                            ['PARTNER',     'Precio ARS lista Partner. Vacío = NULL.',         '12500',        false],
                                            ['GREMIO',      'Precio ARS lista Gremio. Vacío = NULL.',          '15000',        false],
                                            ['PVP',         'Precio ARS al público. Vacío = NULL.',            '18500',        false],
                                            ['IVA%',        'Alícuota de IVA. Por defecto 21 si se omite.',   '21',           false],
                                            ['STOCK',       'Unidades en stock. Por defecto 0.',               '10',           false],
                                            ['CATEGORIA',   'Categoría principal (texto libre).',              'Alarmas',      false],
                                            ['SUBCATEGORIA','Subcategoría (texto libre).',                     'Sensores',     false],
                                        ];
                                        foreach ($cols as $i => [$name, $desc, $ex, $req]):
                                        ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02]">
                                                <td class="px-4 py-2.5 text-slate-400">col <?= $i + 1 ?></td>
                                                <td class="px-4 py-2.5"><span class="col-chip"><?= $name ?></span></td>
                                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400"><?= $desc ?></td>
                                                <td class="px-4 py-2.5 font-mono text-slate-700 dark:text-slate-300 text-[11px]"><?= $ex ?></td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <?= $req
                                                        ? '<span class="text-[10px] font-bold text-red-400">Sí</span>'
                                                        : '<span class="text-[10px] font-bold text-slate-400">No</span>'
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ejemplo de fila -->
                            <div class="bg-slate-50 dark:bg-[#101822] rounded-xl p-4 mt-2">
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Ejemplo de fila (separador ;)</p>
                                <code class="text-[11px] font-mono text-primary break-all">
                                    SKU;DESCRIPCION;MARCA;PARTNER;GREMIO;PVP;IVA%;STOCK;CATEGORIA;SUBCATEGORIA<br>
                                    BD-PIR-200;Sensor PIR Doble Cortina;Paradox;12500;15000;18500;21;10;Alarmas;Sensores
                                </code>
                            </div>

                            <div class="flex gap-3 mt-2">
                                <div class="text-xs text-slate-500 flex items-start gap-2">
                                    <span class="material-symbols-outlined text-amber-400 text-base flex-shrink-0">warning</span>
                                    <span>La primera fila se descarta como cabecera. Podés usar punto o coma como separador decimal en los precios. BOM UTF-8 es tolerado.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Formato Antiguo (oculto por defecto) -->
                        <div id="hint-product-legacy" class="hidden space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest">Formato Antiguo</span>
                                <span class="text-xs text-slate-400">Columnas en desuso · Precios calculados por margen</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-[#101822] rounded-xl p-4">
                                <code class="text-[11px] font-mono text-amber-400 break-all">
                                    SKU;DESCRIPCION;MARCA;COSTO_USD;IVA%;CATEGORIA;SUBCATEGORIA;PROVEEDOR;STOCK
                                </code>
                            </div>
                        </div>

                        <!-- Formato Clientes (oculto por defecto) -->
                        <div id="hint-client" class="hidden space-y-3">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-green-500/10 text-green-400 border border-green-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest">Clientes / Proveedores</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-[#101822] rounded-xl p-4">
                                <code class="text-[11px] font-mono text-green-400 break-all">
                                    RAZON SOCIAL;NOMBRE FANTASIA;CUIT;DNI;EMAIL;TELEFONO;CELULAR;CONTACTO;DIRECCION;DOMICILIO ENTREGA
                                </code>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function onTypeChange() {
            const type = document.getElementById('import_type').value;
            document.getElementById('hint-product').classList.toggle('hidden',        type !== 'product');
            document.getElementById('hint-product-legacy').classList.toggle('hidden', type !== 'product_legacy');
            document.getElementById('hint-client').classList.toggle('hidden',         type !== 'client' && type !== 'supplier');
            document.getElementById('provider-selector').style.opacity = (type === 'product_legacy') ? '1' : '0.4';
        }

        function onFileChange(input) {
            const label = document.getElementById('drop-label');
            if (input.files.length > 0) {
                label.textContent = input.files[0].name;
                document.getElementById('drop-zone').style.borderColor = '#136dec';
            }
        }

        // Drag & Drop
        const zone = document.getElementById('drop-zone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) {
                const dt = new DataTransfer();
                dt.items.add(file);
                document.getElementById('csv_file').files = dt.files;
                document.getElementById('drop-label').textContent = file.name;
                zone.style.borderColor = '#136dec';
            }
        });

        // Init
        onTypeChange();
    </script>
</body>
</html>