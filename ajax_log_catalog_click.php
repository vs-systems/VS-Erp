<?php
/**
 * VS System ERP - AJAX Log Catalog Click
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['sku'])) {
    exit;
}

$db = Vsys\Lib\Database::getInstance();
$sku = $data['sku'];
$desc = $data['desc'] ?? '';

// Log as a special "Public Interest" interaction or creation of a temporary Lead if we want.
// For now, let's log it as an interaction for a "General Public" entity or just a standalone log.

// Try to find if we have a generic "Public" lead or just log it in a new table for catalog tracking.
// Since we don't have user info (it's public), we'll just log the SKU interest.

try {
    $sql = "INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
            VALUES (0, 'lead', 1, 'Consulta Web', :desc, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([':desc' => "Interó©s póºblico en cató¡logo: $sku - $desc"]);
} catch (Exception $e) {
    // Fail silently for public logs
}





