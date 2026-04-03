<?php
/**
 * VS System ERP - Automatic Exchange Rate Fix (BNA)
 * Actualiza la cotización consultando la API oficial (dolarapi.com)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';

use Vsys\Lib\Database;
use Vsys\Lib\BCRAClient;

try {
    $db = Database::getInstance();
    $currency = new BCRAClient();
    
    // Obtener cotización oficial (BNA Venta)
    $rate = $currency->getCurrentRate('oficial');
    
    if (!$rate) {
        throw new Exception("No se pudo obtener la cotización de la API.");
    }

    // Insertar en la tabla exchange_rates con currency_to = 'ARS' para compatibilidad con dashboard y listas
    $stmt = $db->prepare("INSERT INTO exchange_rates (rate, source, currency_to) VALUES (?, 'BNA', 'ARS')");
    $stmt->execute([$rate]);
    
    // También actualizar cualquier entrada anterior que no tenga currency_to si es necesario, 
    // pero el insert con 'ARS' es lo que buscan dashboard.php y listas_precios.php
    
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'rate' => $rate]);
        exit;
    }

    echo "<h1>Cotización Actualizada</h1>";
    echo "<p>Se ha establecido el dólar en <strong>ARS $rate</strong> (Fuente: BNA).</p>";
    echo "<a href='listas_precios.php'>Volver a Listas de Precios</a>";
} catch (Exception $e) {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    echo "Error: " . $e->getMessage();
}
?>