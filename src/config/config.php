<?php
/**
 * VS System ERP - Configuración Jamaica (Unificada)
 * Detecta automáticamente el entorno y conecta a la base correcta.
 */

// Reporte de errores para la mudanza
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 1. AUTODETECCIÓN DE ENTORNO ---
$is_dev = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dev.') !== false);

// --- 2. BASE DE DATOS (JAMAICA) ---
define('DB_HOST', 'localhost');
define('DB_USER', 'burose');
define('DB_PASS', 'rtM1X2SOCko7');
define('DB_CHARSET', 'utf8mb4');

// Selección automática de base de datos
define('DB_NAME', $is_dev ? 'vecinoseguro_dev' : 'vecinoseguro');

// --- 3. TOKENS Y SEGURIDAD ---
define('BCRA_TOKEN', 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTg3MjIwMTksInR5cGUiOiJleHRlcm5hbCIsInVzZXIiOiJqYXZpZXJAdmVjaW5vc2VndXJvLmNvbS5hciJ9.5gGamU2tbfkH1EJusB7a39P4sod-7XAJvcPljaIlDgEapFfGdk95fyhRARGcvy1xSux3jRXFStQnS1kKTxQEBQ');

// --- 4. AJUSTES DEL SISTEMA ---
define('APP_NAME', 'VS System ERP');
define('LOGO_URL_LARGE', '../src/img/VSLogo.png');
define('LOGO_URL_SMALL', '../src/img/logo_short.png');
define('CURRENCY_DEFAULT', 'USD');
define('CURRENCY_SECONDARY', 'ARS');

// --- 5. RUTAS DE CARPETAS ---
$base_dir = dirname(__FILE__);
if (basename($base_dir) == 'config') {
    define('BASE_PATH', dirname($base_dir, 2));
} else {
    define('BASE_PATH', dirname($base_dir, 1));
}

define('MODULES_PATH', BASE_PATH . '/src/modules');
define('LIB_PATH', BASE_PATH . '/src/lib');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>