<?php
/**
 * VS System ERP - Logout Script
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session
$_SESSION = array();
session_destroy();

// Redirect to root index
header("Location: index.php");
exit;
?>