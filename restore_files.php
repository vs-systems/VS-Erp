<?php
/**
 * RESTORE FILES v19 - VS System ERP
 * 2026-01-17
 */

$files = [
    'sidebar.php' => <<<'PHP'
<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$menu = [
    [
        'id' => 'index',
        'label' => 'DASHBOARD',
        'icon' => 'fas fa-home',
        'href' => 'index.php'
    ],
    [
        'label' => 'VENTAS',
        'items' => [
            ['id' => 'presupuestos', 'label' => 'Presupuestos', 'icon' => 'fas fa-history', 'href' => 'presupuestos.php'],
            ['id' => 'cotizador', 'label' => 'Cotizador', 'icon' => 'fas fa-file-invoice-dollar', 'href' => 'cotizador.php'],
            ['id' => 'productos', 'label' => 'Productos', 'icon' => 'fas fa-box-open', 'href' => 'productos.php'],
        ]
    ],
    [
        'label' => 'CONTABILIDAD',
        'items' => [
            ['id' => 'compras', 'label' => 'Compras', 'icon' => 'fas fa-cart-arrow-down', 'href' => 'compras.php'],
            ['id' => 'facturacion', 'label' => 'Facturació³n', 'icon' => 'fas fa-file-invoice', 'href' => 'facturacion.php'],
            ['id' => 'analisis', 'label' => 'Análisis OP.', 'icon' => 'fas fa-chart-line', 'href' => 'analisis.php'],
        ]
    ],
    [
        'id' => 'logistica',
        'label' => 'LOGóSTICA',
        'icon' => 'fas fa-truck',
        'href' => 'logistica.php'
    ],
    [
        'id' => 'clientes',
        'label' => 'CLIENTES',
        'icon' => 'fas fa-users',
        'href' => 'clientes.php'
    ],
    [
        'id' => 'proveedores',
        'label' => 'PROVEEDORES',
        'icon' => 'fas fa-truck-loading',
        'href' => 'proveedores.php'
    ],
    [
        'id' => 'crm',
        'label' => 'CRM',
        'icon' => 'fas fa-handshake',
        'href' => 'crm.php'
    ],
    [
        'id' => 'configuration',
        'label' => 'CONFIGURACIó“N',
        'icon' => 'fas fa-cogs',
        'href' => 'configuration.php'
    ]
];
?>
<nav class="sidebar">
    <div class="sidebar-scroll" style="height: 100%; overflow-y: auto; padding-bottom: 2rem;">
        <?php foreach ($menu as $section): ?>
            <?php if (isset($section['items'])): ?>
                <div class="nav-group">
                    <div class="nav-group-label" onclick="this.parentElement.classList.toggle('collapsed')" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                        <span><?php echo $section['label']; ?></span>
                        <i class="fas fa-chevron-down" style="font-size:0.7rem; opacity:0.5;"></i>
                    </div>
                    <?php foreach ($section['items'] as $item): ?>
                        <a href="<?php echo $item['href']; ?>" class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <a href="<?php echo $section['href']; ?>" class="nav-link <?php echo ($currentPage === $section['id']) ? 'active' : ''; ?>">
                    <i class="<?php echo $section['icon']; ?>"></i> <?php echo $section['label']; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div style="margin-top:2rem; padding:10px; border-top:1px solid rgba(255,255,255,0.1);">
            <a href="catalogo.php" target="_blank" class="nav-link" style="background:var(--accent-violet); border-radius:8px;">
                <i class="fas fa-eye"></i> Cató¡logo Póºblico
            </a>
        </div>
    </div>
</nav>

<style>
.nav-group { margin-bottom: 1rem; border-left: 2px solid rgba(139, 92, 246, 0.2); margin-left: 5px; }
.nav-group-label { 
    padding: 10px 15px; 
    font-size: 0.7rem; 
    font-weight: 800; 
    color: #94a3b8; 
    text-transform: uppercase; 
    letter-spacing: 1.5px;
}
.nav-group .nav-link { padding-left: 25px; }
.nav-group.collapsed .nav-link { display: none; }
.nav-group.collapsed i.fa-chevron-down { transform: rotate(-90deg); }
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.3); border-radius: 10px; }
</style>
PHP
    ,
    'facturacion.php' => <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturació³n - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Vecino Seguro <span style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
            </div>
        </div>
    </header>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <div class="card" style="text-align: center; padding: 50px;">
                <i class="fas fa-file-invoice" style="font-size: 5rem; color: var(--accent-violet); margin-bottom: 20px; opacity: 0.5;"></i>
                <h1>Modulo de Facturació³n</h1>
                <p style="color: #94a3b8; font-size: 1.2rem;">Este mó³dulo se encuentra en desarrollo.</p>
                <div style="margin-top: 30px;">
                    <a href="index.php" class="btn-primary" style="text-decoration: none;"><i class="fas fa-home"></i> VOLVER AL INICIO</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
PHP
    ,
    'config_entities.php' => <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

$clientModule = new Client();
$type = $_GET['type'] ?? 'client'; // client or supplier
$entityId = $_GET['edit'] ?? null;
$message = '';
$status = '';

$entity = null;
if ($entityId) {
    // Re-use search or specific fetch if available
    $entities = $clientModule->getClients(); // Returns all
    foreach ($entities as $e) {
        if ($e['id'] == $entityId) {
            $entity = $e;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entity'])) {
    $data = [
        'id' => $_POST['id'] ?? null,
        'type' => $type,
        'name' => $_POST['name'] ?? '',
        'fantasy_name' => $_POST['fantasy_name'] ?? '',
        'tax_id' => $_POST['tax_id'] ?? '',
        'document_number' => $_POST['document_number'] ?? '',
        'tax_category' => $_POST['tax_category'] ?? 'No Aplica',
        'contact_person' => $_POST['contact_person'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'mobile' => $_POST['mobile'] ?? '',
        'address' => $_POST['address'] ?? '',
        'delivery_address' => $_POST['delivery_address'] ?? '',
        'default_voucher_type' => $_POST['default_voucher_type'] ?? 'Factura',
        'payment_condition' => $_POST['payment_condition'] ?? 'Contado',
        'preferred_payment_method' => $_POST['payment_method'] ?? 'Transferencia',
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0
    ];

    if ($clientModule->saveClient($data)) {
        header("Location: " . ($type === 'client' ? 'clientes.php' : 'proveedores.php') . "?status=success");
        exit;
    } else {
        $message = "Error al guardar la entidad.";
        $status = "error";
    }
}

$title = ($type === 'client') ? 'Cliente' : 'Proveedor';
$icon = ($type === 'client') ? 'fa-users' : 'fa-truck-loading';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuració³n de <?php echo $title; ?> - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                VS System <span style="opacity:0.5;">/</span> Configuració³n
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <div class="card">
                <h3><i class="fas <?php echo $icon; ?>"></i> <?php echo ($entityId ? 'Editar' : 'Nuevo'); ?> <?php echo $title; ?></h3>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="save_entity" value="1">
                    <input type="hidden" name="id" value="<?php echo $entity['id'] ?? ''; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Raz&oacute;n Social / Nombre</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($entity['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Nombre de Fantas&iacute;a</label>
                            <input type="text" name="fantasy_name" value="<?php echo htmlspecialchars($entity['fantasy_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>CUIT/CUIL</label>
                            <input type="text" name="tax_id" class="mask-cuit" value="<?php echo htmlspecialchars($entity['tax_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>DNI / Documento</label>
                            <input type="text" name="document_number" class="mask-dni" value="<?php echo htmlspecialchars($entity['document_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Categor&iacute;a Fiscal</label>
                            <select name="tax_category">
                                <option value="Responsable Inscripto" <?php echo ($entity['tax_category'] ?? '') === 'Responsable Inscripto' ? 'selected' : ''; ?>>Responsable Inscripto</option>
                                <option value="Monotributo" <?php echo ($entity['tax_category'] ?? '') === 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                <option value="Exento" <?php echo ($entity['tax_category'] ?? '') === 'Exento' ? 'selected' : ''; ?>>Exento</option>
                                <option value="No Aplica" <?php echo ($entity['tax_category'] ?? 'No Aplica') === 'No Aplica' ? 'selected' : ''; ?>>No Aplica</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Persona de Contacto</label>
                            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($entity['contact_person'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="no-upper" value="<?php echo htmlspecialchars($entity['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tel&eacute;fono</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($entity['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Celular / WhatsApp</label>
                            <input type="text" name="mobile" value="<?php echo htmlspecialchars($entity['mobile'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Condici&oacute;n de Pago</label>
                            <select name="payment_condition">
                                <option value="Contado" <?php echo ($entity['payment_condition'] ?? '') === 'Contado' ? 'selected' : ''; ?>>Contado</option>
                                <option value="7 dó­as" <?php echo ($entity['payment_condition'] ?? '') === '7 dó­as' ? 'selected' : ''; ?>>7 dó­as</option>
                                <option value="15 dó­as" <?php echo ($entity['payment_condition'] ?? '') === '15 dó­as' ? 'selected' : ''; ?>>15 dó­as</option>
                                <option value="30 dó­as" <?php echo ($entity['payment_condition'] ?? '') === '30 dó­as' ? 'selected' : ''; ?>>30 dó­as</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label>Domicilio</label>
                        <textarea name="address" rows="2" style="width:100%;"><?php echo htmlspecialchars($entity['address'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 15px;">
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> GUARDAR</button>
                        <a href="<?php echo ($type === 'client' ? 'clientes.php' : 'proveedores.php'); ?>" class="btn-secondary" style="text-decoration:none; display:inline-block; padding:10px 20px; border-radius:8px; background:rgba(255,255,255,0.05); color:white;">CANCELAR</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
PHP
];

// 1. Process standard file overrides
foreach ($files as $name => $content) {
    file_put_contents(__DIR__ . '/' . $name, $content);
    echo "Restored: $name\n";
}

// 2. Global Sidebar Refactor - Programmatic consistency
$allFiles = glob(__DIR__ . "/*.php");
foreach ($allFiles as $file) {
    if (basename($file) === 'sidebar.php' || basename($file) === 'restore_files.php' || basename($file) === 'imprimir_cotizacion.php')
        continue;

    $content = file_get_contents($file);
    if (strpos($content, '<nav class="sidebar">') !== false) {
        echo "Refactoring sidebar in: " . basename($file) . "\n";
        // Greedy match for the entire sidebar block
        $newContent = preg_replace('/<nav class="sidebar">.*?<\/nav>/s', '<?php include "sidebar.php"; ?>', $content);
        file_put_contents($file, $newContent);
    }
}

// 3. Specific table fix in Logistics.php
$logisticsFile = __DIR__ . '/src/modules/logistica/Logistics.php';
if (file_exists($logisticsFile)) {
    $content = file_get_contents($logisticsFile);
    $content = str_replace("LEFT JOIN clients c ON q.client_id = c.id", "LEFT JOIN entities e ON q.client_id = e.id", $content);
    $content = str_replace("q.authorized_dispatch = TRUE", "q.authorized_dispatch = 1", $content);
    // Fix the client_name reference if needed
    $content = str_replace("c.name as client_name", "e.name as client_name", $content);
    file_put_contents($logisticsFile, $content);
    echo "Fixed: Logistics.php tables\n";
}

echo "SUCCESS: ERP Navigation v19 applied.\n";




