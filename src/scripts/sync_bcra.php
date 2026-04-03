<?php
/**
 * VS System ERP - BCRA Synchronization Script
 * Can be run via CLI/Cron
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/BCRAClient.php';

use Vsys\Lib\Database;
use Vsys\Lib\BCRAClient;

$client = new BCRAClient(BCRA_TOKEN);
$rate = $client->getCurrentRate();

if ($rate) {
    $db = Database::getInstance();
    $stmt = $db->prepare("INSERT INTO exchange_rates (rate, source, currency_to, fetched_at) VALUES (?, 'BCRA', 'ARS', NOW())");
    $stmt->execute([$rate]);
    echo "Successfully updated BCRA rate: ARS " . $rate . "\n";
} else {
    echo "Error: Could not fetch rate from BCRA API. Check token or connectivity.\n";
}
?>