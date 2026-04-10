<?php
/**
 * VS System ERP — Configuración Central
 * Detecta automáticamente el entorno y conecta a la base correcta.
 * Bloque 10: carga config_company.json, define constantes de empresa y SMTP.
 */

// ── 1. REPORTE DE ERRORES ────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── 2. AUTODETECCIÓN DE ENTORNO ──────────────────────────────────
$is_dev = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dev.') !== false);

// ── 3. BASE DE DATOS ─────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'burose');
define('DB_PASS',    'rtM1X2SOCko7');
define('DB_CHARSET', 'utf8mb4');
define('DB_NAME',    $is_dev ? 'vecinoseguro_dev' : 'vecinoseguro');

// ── 4. DATOS DE LA EMPRESA (desde config_company.json) ───────────
$_companyConfig = [];
$_companyFile   = __DIR__ . '/../../config_company.json';

// Intentar desde la raíz del proyecto
if (!file_exists($_companyFile)) {
    $_companyFile = dirname($_companyFile) . '/config_company.json';
}
if (file_exists($_companyFile)) {
    $_companyConfig = json_decode(file_get_contents($_companyFile), true) ?? [];
}

define('COMPANY_NAME',      $_companyConfig['company_name']  ?? 'Vecinos Seguros');
define('COMPANY_FANTASY',   $_companyConfig['fantasy_name']  ?? 'Vecinos Seguros');
define('COMPANY_TAX_ID',    $_companyConfig['tax_id']        ?? '');
define('COMPANY_EMAIL',     $_companyConfig['email']         ?? 'vecinoseguro0@gmail.com');
define('COMPANY_PHONE',     $_companyConfig['phone']         ?? '');
define('COMPANY_WHATSAPP',  $_companyConfig['whatsapp']      ?? '5492235772165');
define('COMPANY_ADDRESS',   $_companyConfig['address']       ?? '');
define('COMPANY_WEBSITE',   $_companyConfig['website']       ?? 'https://vecinoseguro.com.ar');
define('COMPANY_TAGLINE',   $_companyConfig['tagline']       ?? 'Lo que no encontrás en nuestro catálogo, lo buscamos por vos.');

// ── 5. EMAIL (Resend API) ──────────────────────────────────────────
define('RESEND_API_KEY', 're_dHc9Ec6b_31R83VTojF9oQJvrh1tBswrc'); // Tu clave de Resend
define('MAIL_FROM',      'no-reply@vecinoseguro.com.ar');         // Remitente autorizado

// ── 6. TOKENS Y SEGURIDAD ────────────────────────────────────────
define('BCRA_TOKEN', 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTg3MjIwMTksInR5cGUiOiJleHRlcm5hbCIsInVzZXIiOiJqYXZpZXJAdmVjaW5vc2VndXJvLmNvbS5hciJ9.5gGamU2tbfkH1EJusB7a39P4sod-7XAJvcPljaIlDgEapFfGdk95fyhRARGcvy1xSux3jRXFStQnS1kKTxQEBQ');

// ── 7. AJUSTES DEL SISTEMA ───────────────────────────────────────
define('APP_NAME',          'VS System ERP');
define('APP_ENV',           $is_dev ? 'development' : 'production');
define('LOGO_URL_LARGE',    '../src/img/VSLogo.png');
define('LOGO_URL_SMALL',    '../src/img/logo_short.png');
define('CURRENCY_DEFAULT',  'USD');
define('CURRENCY_SECONDARY','ARS');

// ── 8. RUTAS DE CARPETAS ─────────────────────────────────────────
$base_dir = dirname(__FILE__);
if (basename($base_dir) == 'config') {
    define('BASE_PATH', dirname($base_dir, 2));
} else {
    define('BASE_PATH', dirname($base_dir, 1));
}

define('MODULES_PATH', BASE_PATH . '/src/modules');
define('LIB_PATH',     BASE_PATH . '/src/lib');

// ── 9. ZONA HORARIA ──────────────────────────────────────────────
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>