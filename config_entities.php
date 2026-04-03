<?php
require_once 'auth_check.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;
$clientModule = new Client();
$db = Vsys\Lib\Database::getInstance();

$type = $_GET['type'] ?? 'client'; // 'client' or 'supplier'
$id = $_GET['id'] ?? $_GET['edit'] ?? null;
$message = '';
$status = '';

// Data for editing
$editData = [
    'id' => '',
    'name' => '',
    'fantasy_name' => '',
    'tax_id' => '',
    'document_number' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'mobile' => '',
    'address' => '',
    'delivery_address' => '',
    'tax_category' => ($type == 'client' ? 'Consumidor Final' : 'No Aplica'),
    'default_voucher_type' => 'Factura',
    'payment_condition' => 'Contado',
    'preferred_payment_method' => 'Transferencia',
    'is_enabled' => 1,
    'is_retention_agent' => 0,
    'seller_id' => null,
    'client_profile' => 'Otro',
    'is_verified' => 0,
    'city' => '',
    'lat' => '',
    'lng' => '',
    'transport' => '',
    'is_transport' => 0
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM entities WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res)
        $editData = $res;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? null,
        'type' => $type,
        'tax_id' => $_POST['tax_id'],
        'document_number' => $_POST['document_number'],
        'name' => $_POST['name'],
        'fantasy_name' => $_POST['fantasy_name'],
        'contact' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'mobile' => $_POST['mobile'],
        'address' => $_POST['address'],
        'delivery_address' => $_POST['delivery_address'],
        'default_voucher' => $_POST['default_voucher_type'],
        'tax_category' => $_POST['tax_category'],
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention_agent']) ? 1 : 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method'],
        'seller_id' => !empty($_POST['seller_id']) ? $_POST['seller_id'] : null,
        'client_profile' => $_POST['client_profile'] ?? 'Otro',
        'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
        'city' => $_POST['city'] ?? null,
        'lat' => $_POST['lat'] ?? null,
        'lng' => $_POST['lng'] ?? null,
        'transport' => $_POST['transport'] ?? null,
        'is_transport' => isset($_POST['is_transport']) ? 1 : 0
    ];

    if ($clientModule->saveClient($data)) {
        header("Location: configuration.php?tab=entidades&success=1");
        exit;
    } else {
        $message = "Error al guardar.";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $id ? 'Editar' : 'Nuevo'; ?>
        <?php
        if ($type == 'client')
            echo 'Cliente';
        elseif ($type == 'supplier')
            echo 'Proveedor';
        else
            echo 'Transporte';
        ?> - VS System
    </title>
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
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .form-input-vsys {
            @apply w-full bg-slate-50 dark:bg-[#101822] border-slate-200 dark:border-[#233348] rounded-xl text-sm dark:text-white text-slate-800 focus:ring-primary/50 focus:border-primary transition-all;
        }

        .form-label-vsys {
            @apply block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1;
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
                        <span class="material-symbols-outlined text-2xl">
                            <?php echo $type == 'client' ? 'person_add' : 'domain_add'; ?>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <h2
                            class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight leading-none">
                            <?php echo $id ? 'Editar' : 'Nuevo'; ?>
                            <?php
                            if ($type == 'client')
                                echo ' Cliente';
                            elseif ($type == 'supplier')
                                echo ' Proveedor';
                            else
                                echo ' Transporte';
                            ?>
                        </h2>
                        <span class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1">
                            Gestión de Entidades
                        </span>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

                        <!-- Main Info Card -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-sm">
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-primary mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-lg">badge</span> Información Principal
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="form-label-vsys">Razón Social / Nombre</label>
                                    <input type="text" name="name" value="<?php echo $editData['name']; ?>" required
                                        class="form-input-vsys">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Nombre de Fantasía</label>
                                    <input type="text" name="fantasy_name"
                                        value="<?php echo $editData['fantasy_name']; ?>" class="form-input-vsys">
                                </div>
                                <div>
                                    <label class="form-label-vsys">CUIT/CUIL</label>
                                    <input type="text" name="tax_id" value="<?php echo $editData['tax_id']; ?>"
                                        placeholder="00-00000000-0" class="form-input-vsys font-mono">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Categoría Fiscal</label>
                                    <select name="tax_category" class="form-input-vsys">
                                        <?php if ($type == 'client'): ?>
                                            <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>
                                                Responsable Inscripto</option>
                                            <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                            <option value="Exento" <?php echo $editData['tax_category'] == 'Exento' ? 'selected' : ''; ?>>Exento</option>
                                            <option value="Consumidor Final" <?php echo $editData['tax_category'] == 'Consumidor Final' ? 'selected' : ''; ?>>
                                                Consumidor Final</option>
                                        <?php else: ?>
                                            <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>
                                                Responsable Inscripto</option>
                                            <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                            <option value="No Aplica" <?php echo $editData['tax_category'] == 'No Aplica' ? 'selected' : ''; ?>>No Aplica</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-vsys">DNI / Documento</label>
                                    <input type="text" name="document_number"
                                        value="<?php echo $editData['document_number']; ?>"
                                        class="form-input-vsys font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info Card -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-sm">
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-primary mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-lg">contact_phone</span> Contacto y
                                Ubicación
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="form-label-vsys">Persona de Contacto</label>
                                    <input type="text" name="contact_person"
                                        value="<?php echo $editData['contact_person']; ?>" class="form-input-vsys">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Email</label>
                                    <input type="email" name="email" value="<?php echo $editData['email']; ?>"
                                        class="form-input-vsys">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Teléfono / Móviles</label>
                                    <input type="text" name="phone" value="<?php echo $editData['phone']; ?>"
                                        placeholder="Teléfono Fijo" class="form-input-vsys mb-2">
                                    <input type="text" name="mobile" value="<?php echo $editData['mobile']; ?>"
                                        placeholder="Móvil / WhatsApp" class="form-input-vsys">
                                </div>
                                <div class="md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="relative group">
                                        <label class="form-label-vsys">Domicilio Fiscal</label>
                                        <div class="flex gap-2">
                                            <textarea name="address" id="geo_address" rows="2"
                                                class="form-input-vsys"><?php echo $editData['address']; ?></textarea>
                                            <button type="button" onclick="geocodeAddress('address')"
                                                class="bg-slate-700 text-white px-3 rounded-xl hover:bg-primary transition-all flex items-center justify-center h-auto"
                                                title="Buscar por dirección">
                                                <span class="material-symbols-outlined text-lg">home_pin</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="relative group">
                                        <label class="form-label-vsys">Lugar de Entrega</label>
                                        <div class="flex gap-2">
                                            <textarea name="delivery_address" rows="2"
                                                class="form-input-vsys"><?php echo $editData['delivery_address']; ?></textarea>
                                            <button type="button" onclick="geocodeAddress('delivery')"
                                                class="bg-slate-700 text-white px-3 rounded-xl hover:bg-primary transition-all flex items-center justify-center h-auto"
                                                title="Buscar por lugar de entrega">
                                                <span class="material-symbols-outlined text-lg">local_shipping</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label-vsys">Ciudad / Localidad</label>
                                    <div class="flex gap-2">
                                        <input type="text" id="geo_city" name="city"
                                            value="<?php echo $editData['city']; ?>" class="form-input-vsys">
                                        <button type="button" onclick="geocodeAddress('city')"
                                            class="bg-slate-700 text-white p-2 rounded-xl hover:bg-primary transition-all"
                                            title="Buscar por ciudad">
                                            <span class="material-symbols-outlined text-lg">location_city</span>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label-vsys">Latitud</label>
                                    <input type="text" id="geo_lat" name="lat" value="<?php echo $editData['lat']; ?>"
                                        class="form-input-vsys font-mono text-xs">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Longitud</label>
                                    <input type="text" id="geo_lng" name="lng" value="<?php echo $editData['lng']; ?>"
                                        class="form-input-vsys font-mono text-xs">
                                </div>
                                <div>
                                    <label class="form-label-vsys">Transporte</label>
                                    <input type="text" name="transport" value="<?php echo $editData['transport']; ?>"
                                        placeholder="Empresa de transporte" class="form-input-vsys">
                                </div>
                            </div>
                        </div>

                        <!-- Commercial Settings -->
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-2xl p-8 shadow-sm">
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-primary mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-[#233348] pb-2">
                                <span class="material-symbols-outlined text-lg">payments</span> Condiciones Comerciales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <label class="form-label-vsys">Comprobante Defecto</label>
                                    <select name="default_voucher_type" class="form-input-vsys">
                                        <option value="Factura" <?php echo $editData['default_voucher_type'] == 'Factura' ? 'selected' : ''; ?>>Factura</option>
                                        <option value="Remito" <?php echo $editData['default_voucher_type'] == 'Remito' ? 'selected' : ''; ?>>Remito</option>
                                        <option value="Ninguno" <?php echo $editData['default_voucher_type'] == 'Ninguno' ? 'selected' : ''; ?>>Ninguno</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-vsys">Condición Pago</label>
                                    <select name="payment_condition" class="form-input-vsys">
                                        <option value="Contado" <?php echo $editData['payment_condition'] == 'Contado' ? 'selected' : ''; ?>>Contado</option>
                                        <option value="Cta Cte" <?php echo strpos($editData['payment_condition'], 'Cta Cte') !== false ? 'selected' : ''; ?>>Cta Cte</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-vsys">Forma Pago Pref.</label>
                                    <select name="payment_method" class="form-input-vsys">
                                        <option value="Transferencia" <?php echo $editData['preferred_payment_method'] == 'Transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                        <option value="Efectivo" <?php echo $editData['preferred_payment_method'] == 'Efectivo' ? 'selected' : ''; ?>>
                                            Efectivo</option>
                                        <option value="Mercado Pago" <?php echo $editData['preferred_payment_method'] == 'Mercado Pago' ? 'selected' : ''; ?>>
                                            Mercado Pago</option>
                                    </select>
                                </div>

                                <?php if ($type == 'client'): ?>
                                    <div>
                                        <label class="form-label-vsys">Vendedor Asignado</label>
                                        <select name="seller_id" class="form-input-vsys">
                                            <option value="">-- Sin Vendedor --</option>
                                            <?php
                                            $sellers = $db->query("SELECT id, username FROM users WHERE role = 'Vendedor'")->fetchAll();
                                            foreach ($sellers as $s):
                                                ?>
                                                <option value="<?php echo $s['id']; ?>" <?php echo $editData['seller_id'] == $s['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $s['username']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label-vsys">Perfil Cliente</label>
                                        <select name="client_profile" class="form-input-vsys">
                                            <option value="OTRO" <?php echo $editData['client_profile'] == 'OTRO' ? 'selected' : ''; ?>>Otro</option>
                                            <option value="GREMIO" <?php echo $editData['client_profile'] == 'GREMIO' ? 'selected' : ''; ?>>Gremio</option>
                                            <option value="WEB" <?php echo $editData['client_profile'] == 'WEB' ? 'selected' : ''; ?>>Web</option>
                                            <option value="PUBLICO" <?php echo $editData['client_profile'] == 'PUBLICO' ? 'selected' : ''; ?>>Público</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-8 flex gap-6 border-t border-slate-100 dark:border-[#233348] pt-6">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="is_enabled" <?php echo $editData['is_enabled'] ? 'checked' : ''; ?>
                                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary shadow-sm transition-all bg-white dark:bg-[#101822]">
                                    <span class="text-sm font-bold dark:text-white text-slate-800">Habilitado</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="is_transport" <?php echo $editData['is_transport'] ? 'checked' : ''; ?>
                                        class="w-5 h-5 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500 shadow-sm transition-all bg-white dark:bg-[#101822]">
                                    <span class="text-sm font-bold dark:text-white text-slate-800">¿Es
                                        Transporte?</span>
                                </label>

                                <?php if ($type == 'client'): ?>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="is_retention_agent" <?php echo $editData['is_retention_agent'] ? 'checked' : ''; ?>
                                            class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary shadow-sm transition-all bg-white dark:bg-[#101822]">
                                        <span class="text-sm font-bold dark:text-white text-slate-800">Agente
                                            Retención</span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4 pt-4">
                            <button type="submit"
                                class="bg-primary text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform flex items-center gap-2">
                                <span class="material-symbols-outlined">save</span> GUARDAR CAMBIOS
                            </button>
                            <a href="<?php echo $type == 'client' ? 'configuration.php?tab=entidades' : 'configuration.php?tab=entidades'; ?>"
                                class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-sm px-4">
                                CANCELAR
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function geocodeAddress(mode) {
            const city = document.getElementById('geo_city').value;
            const address = document.getElementById('geo_address').value;
            const deliveryAddress = document.getElementsByName('delivery_address')[0].value;

            let queryStr = "";
            let btn = event.currentTarget;

            if (mode === 'address' && address) {
                queryStr = address + (city ? ", " + city : "");
            } else if (mode === 'delivery' && deliveryAddress) {
                queryStr = deliveryAddress + (city ? ", " + city : "");
            } else if (mode === 'city' && city) {
                queryStr = city;
            } else {
                alert('Por favor, complete el campo correspondiente para buscar.');
                return;
            }

            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-lg">sync</span>';
            btn.disabled = true;

            try {
                // Combine with Argentina for better results
                const query = encodeURIComponent(queryStr + ", Argentina");
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1&addressdetails=1`);

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();

                if (data && data.length > 0) {
                    const result = data[0];
                    document.getElementById('geo_lat').value = parseFloat(result.lat).toFixed(6);
                    document.getElementById('geo_lng').value = parseFloat(result.lon).toFixed(6);

                    // Optional: Update city if it was empty and we found it
                    if (!city && result.address) {
                        const foundCity = result.address.city || result.address.town || result.address.village || result.address.state;
                        if (foundCity) document.getElementById('geo_city').value = foundCity;
                    }

                    // Visual feedback
                    ['geo_lat', 'geo_lng'].forEach(id => {
                        const el = document.getElementById(id);
                        el.classList.add('ring-2', 'ring-green-500', 'bg-green-500/10');
                        setTimeout(() => el.classList.remove('ring-2', 'ring-green-500', 'bg-green-500/10'), 3000);
                    });
                } else {
                    alert('No se encontraron coordenadas para: ' + queryStr);
                }
            } catch (error) {
                console.error('Error geocoding:', error);
                alert('Error al conectar con el servicio de geolocalización. Verifique su conexión o intente más tarde.');
            } finally {
                btn.innerHTML = originalIcon;
                btn.disabled = false;
            }
        }

        // CUIT Mask and Validation
        document.querySelector('input[name="tax_id"]').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,8})(\d{0,1})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>

</html>