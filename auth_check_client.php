<?php
/**
 * Guard para páginas de clientes externos.
 * Redirige a login si no hay sesión activa.
 * No aplica el auto-login de red local.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    $back = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header("Location: login.php?dest=catalogo&back=$back");
    exit;
}
