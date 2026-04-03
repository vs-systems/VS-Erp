<?php
/**
 * VS System ERP - Scraper Automático de Stock y Costos (Big Dipper)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos de tiempo de ejecución
ini_set('memory_limit', '256M');

// Parámetros de paginación para evitar timeouts
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100; // Procesar de a 100 productos por defecto

// Configuración de Big Dipper
define('BD_USER', 'javier@gozzi.ar');
define('BD_PASS', 'Milla6397@@');
define('BD_LOGIN_URL', 'https://www2.bigdipper.com.ar/api/AccountApi/Login');
define('BD_LIST_URL', 'https://www2.bigdipper.com.ar/api/Products/List');

$db = Vsys\Lib\Database::getInstance();

$logFile = __DIR__ . '/bigdipper_scraper.log';

function log_message($msg)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
    echo $msg . "<br>";
}

log_message("=== Iniciando Scraper de Big Dipper (Offset: $offset, Limit: $limit) ===");

// 1. Obtener Token
$loginData = ['User' => BD_USER, 'Password' => BD_PASS];
$ch = curl_init(BD_LOGIN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$loginResponse = curl_exec($ch);
$loginInfo = json_decode($loginResponse, true);
curl_close($ch);

if (!isset($loginInfo['Token'])) {
    log_message("ERROR: No se pudo obtener el token de Big Dipper. Respuesta: " . $loginResponse);
    exit;
}

$token = $loginInfo['Token'];
log_message("Login exitoso. Token obtenido.");

// 2. Obtener lote de productos
$stmt = $db->prepare("SELECT id, sku FROM products WHERE sku IS NOT NULL AND sku != '' LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$productsBatch = $stmt->fetchAll();

if (count($productsBatch) === 0) {
    log_message("No hay más productos para procesar.");
    echo "<br><a href='productos.php' style='padding:10px; background:#666; color:white; text-decoration:none; border-radius:5px;'>Volver a Productos</a>";
    exit;
}

$updatedCount = 0;
$errorCount = 0;

// 3. Procesar Lote
foreach ($productsBatch as $p) {
    $sku = $p['sku'];

    // Consultar Producto en Big Dipper
    $searchParams = [
        "Description" => $sku,
        "Page" => 0,
        "PageSize" => 1
    ];

    $ch = curl_init(BD_LIST_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($searchParams));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    curl_close($ch);

    if (isset($data['Products']) && count($data['Products']) > 0) {
        $bdProduct = null;
        foreach ($data['Products'] as $prod) {
            if (trim($prod['Code']) === trim($sku)) {
                $bdProduct = $prod;
                break;
            }
        }

        if ($bdProduct) {
            $stock = (int) $bdProduct['Stock'];
            $price = (float) $bdProduct['Price'];

            $sql = "UPDATE products SET stock_current = ?, unit_cost_usd = ? WHERE id = ?";
            $db->prepare($sql)->execute([$stock, $price, $p['id']]);

            log_message("OK: $sku -> Stock: $stock, Price USD: $price");
            $updatedCount++;
        } else {
            log_message("SKIP: $sku (No encontrado)");
            $errorCount++;
        }
    } else {
        log_message("NOT FOUND: $sku");
        $errorCount++;
    }

    // Pequeña pausa para evitar rate-limit
    usleep(150000); // 150ms
}

$nextOffset = $offset + $limit;
log_message("=== Batch Finalizado ===");
log_message("Resumen: $updatedCount actualizados, $errorCount omitidos.");

echo "<br><br><a href='?offset=$nextOffset&limit=$limit' style='display:inline-block; padding:15px 30px; background:#136dec; color:white; border-radius:10px; text-decoration:none; font-weight:bold; box-shadow: 0 4px 6px rgba(19,109,236,0.3);'>PROCESAR SIGUIENTES $limit PRODUCTOS</a>";
echo "<br><br><a href='productos.php'>Volver al Catálogo</a>";
?>