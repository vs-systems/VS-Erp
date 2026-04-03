<?php
/**
 * VS System ERP - BNA Synchronization Script
 * Fetches rate from Banco Nación website
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/BNAClient.php';

use Vsys\Lib\Database;
use Vsys\Lib\BNAClient;

$client = new BNAClient();
$rate = $client->getCurrentRate();

if ($rate) {
    $db = Database::getInstance();

    // Check if the rate has changed or if we need a new entry
    $lastRate = $db->query("SELECT rate FROM exchange_rates ORDER BY created_at DESC LIMIT 1")->fetchColumn();

    if ($lastRate != $rate) {
        $stmt = $db->prepare("INSERT INTO exchange_rates (rate, source, currency_to, fetched_at) VALUES (?, 'BNA', 'ARS', NOW())");
        $stmt->execute([$rate]);
        echo "Successfully updated BNA rate: ARS " . $rate . "\n";
    } else {
        echo "Rate is already up to date: ARS " . $rate . "\n";
    }
} else {
    echo "Error: Could not fetch rate from BNA website.\n";
}
