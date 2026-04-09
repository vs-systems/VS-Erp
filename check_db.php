<?php
/**
 * Script de Diagnóstico de Base de Datos
 * Muestra el esquema y los valores exactos requeridos por las columnas ENUM
 * para evitar errores de Data Truncated.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

try {
    $db = Database::getInstance();
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>Diagnóstico DB</title></head>";
    echo "<body style='font-family: monospace; background: #111; color: #0f0; padding: 20px;'>";
    echo "<h1>Diagnóstico de Esquema - Vecino Seguro</h1>";

    $tables = ['entities', 'products', 'users', 'quotations'];

    foreach ($tables as $table) {
        echo "<h2>Tabla: $table</h2>";
        
        try {
            $cols = $db->query("SHOW COLUMNS FROM $table")->fetchAll();
            echo "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse: collapse; border-color: #333;'>";
            echo "<tr style='background: #222;'><th>Columna</th><th>Tipo de Dato</th><th>Nulo</th><th>Default</th></tr>";

            foreach ($cols as $col) {
                // Highlight ENUMs or VARCHARs to easily spot truncation
                $type = htmlspecialchars($col['Type']);
                if (strpos($type, 'enum') !== false) {
                    $type = "<strong style='color: #fd0;'>$type</strong>";
                }
                
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$type}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";

        } catch (Exception $e) {
            echo "<p style='color: red;'>Error al leer tabla $table: " . $e->getMessage() . "</p>";
        }
    }

    echo "</body></html>";

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}
