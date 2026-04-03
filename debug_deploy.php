<?php
/**
 * VS System ERP - Deployment Diagnostic Tool
 */
require_once __DIR__ . '/auth_check.php';

echo "<h2>Diagnóstico de Despliegue - VS System</h2>";

echo "<h3>Información del Servidor</h3>";
echo "<ul>";
echo "<li><b>CWD:</b> " . getcwd() . "</li>";
echo "<li><b>Real Path:</b> " . realpath(__DIR__) . "</li>";
echo "<li><b>Script Name:</b> " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li><b>Document Root:</b> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

echo "<h3>Verificación de Últimos Cambios</h3>";
$filesToCheck = [
    'index.php',
    '.github/workflows/deploy.yml',
    'update_images_sanyi.php',
    'fix_dolar_bna.php'
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background: #eee;'><th>Archivo</th><th>Ruta Completa</th><th>Última Modificación (Server)</th><th>Estado</th></tr>";

foreach ($filesToCheck as $file) {
    $path = __DIR__ . '/' . $file;
    echo "<tr>";
    echo "<td>$file</td>";
    echo "<td>" . (file_exists($path) ? realpath($path) : "<span style='color:red;'>No existe</span>") . "</td>";
    echo "<td>" . (file_exists($path) ? date("Y-m-d H:i:s", filemtime($path)) : "-") . "</td>";
    echo "<td>" . (file_exists($path) ? "<span style='color:green;'>OK</span>" : "<span style='color:red;'>Faltante</span>") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Estado de Git (Local en Servidor)</h3>";
$gitLog = shell_exec("git log -3 --oneline 2>&1");
echo "<pre style='background: #1e1e1e; color: #d4d4d4; pading: 15px; border-radius: 8px;'>";
echo $gitLog ?: "No se pudo ejecutar git log";
echo "</pre>";

echo "<h3>Estructura de Directorios Cercanos</h3>";
echo "<pre>";
$dirs = glob('..//*', GLOB_ONLYDIR);
echo "Directorios en " . realpath('..') . ":\n";
foreach ($dirs as $dir) {
    echo " - " . basename($dir) . "\n";
}
echo "</pre>";

echo "<br><br><a href='configuration.php'>Volver a Configuración</a>";
