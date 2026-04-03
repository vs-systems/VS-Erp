<?php
/**
 * AJAX Handler - Reports Data
 */
header('Content-Type: application/json');
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = \Vsys\Lib\Database::getInstance();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'locality_stats':
            // Clients by Locality
            $clients = $db->query("SELECT city as locality, COUNT(*) as count FROM entities WHERE type = 'client' AND is_transport = 0 AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC LIMIT 10")->fetchAll();

            // Suppliers by Locality (excluding transports)
            $suppliers = $db->query("SELECT city as locality, COUNT(*) as count FROM entities WHERE type IN ('supplier', 'provider') AND is_transport = 0 AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC LIMIT 10")->fetchAll();

            // Transports by Locality (new unified logic)
            $transports = $db->query("SELECT city as locality, COUNT(*) as count FROM entities WHERE is_transport = 1 AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC LIMIT 10")->fetchAll();

            echo json_encode(['clients' => $clients, 'suppliers' => $suppliers, 'transports' => $transports]);
            break;

        case 'map_entities':
            // Geolocation markers - include is_transport
            $entities = $db->query("SELECT id, name, type, lat, lng, address, city, is_transport FROM entities WHERE lat IS NOT NULL AND lng IS NOT NULL")->fetchAll();
            echo json_encode($entities);
            break;

        case 'heatmap_data':
            // Sales density (based on quotation frequency by client)
            $sql = "SELECT e.lat, e.lng, COUNT(q.id) as weight 
                    FROM entities e 
                    JOIN quotations q ON e.id = q.client_id 
                    WHERE e.lat IS NOT NULL AND e.lng IS NOT NULL 
                    GROUP BY e.id";
            $data = $db->query($sql)->fetchAll();
            echo json_encode($data);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
