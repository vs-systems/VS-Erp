<?php
/**
 * Authentication check script
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\User;

$userAuth = new User();

// Local Network Auto-Login Bypass (Dev only)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = (strpos($clientIp, '192.168.0.') === 0 || $clientIp === '127.0.0.1' || $clientIp === '::1');

if (!$userAuth->isLoggedIn()) {
    if ($isLocal) {
        // Auto-login logic for local development
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $_SESSION['user_id'] = 1; // Assuming ID 1 is the primary admin
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'Admin';

        // Refresh object state
        $userAuth = new User();
    } else {
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit;
        }
    }
}
?>