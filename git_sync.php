<?php
/**
 * GIT SYNC HELPER v2 - VS System ERP
 * Trying multiple execution methods.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function run_git($cmd)
{
    $full_cmd = "git $cmd 2>&1";
    echo "<h3>Sincronizando: $full_cmd</h3>";

    $methods = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open'];
    $success = false;

    foreach ($methods as $method) {
        if (function_exists($method)) {
            echo "<em>Intentando con mó©todo: $method</em><br>";
            try {
                if ($method === 'shell_exec') {
                    $out = shell_exec($full_cmd);
                    echo "<pre>$out</pre>";
                    $success = true;
                } elseif ($method === 'exec') {
                    $out = [];
                    exec($full_cmd, $out);
                    echo "<pre>" . implode("\n", $out) . "</pre>";
                    $success = true;
                } elseif ($method === 'system') {
                    echo "<pre>";
                    system($full_cmd);
                    echo "</pre>";
                    $success = true;
                } elseif ($method === 'passthru') {
                    echo "<pre>";
                    passthru($full_cmd);
                    echo "</pre>";
                    $success = true;
                }

                if ($success) {
                    echo "<span style='color:green;'>Comando ejecutado con ó©xito vó­a $method</span><br>";
                    break;
                }
            } catch (Error $e) {
                echo "<span style='color:orange;'>Error con $method: " . $e->getMessage() . "</span><br>";
            }
        } else {
            echo "<em>Mó©todo deshabilitado: $method</em><br>";
        }
    }

    if (!$success) {
        echo "<b style='color:red;'>No se pudo ejecutar ningóºn comando. Todas las funciones de ejecució³n estó¡n deshabilitadas.</b><br>";
    }
}

echo "<h2>Git Recovery Tools</h2>";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'fix') {
        run_git('fetch --all');
        run_git('reset --hard origin/main');
        run_git('clean -fd');
    }

    if ($_GET['action'] == 'clean_inodes') {
        echo "<h3>Iniciando Limpieza de Inodos...</h3>";
        $patterns = [
            'debug_*.php',
            'test_*.php',
            'check_*.php',
            'diag_*.php',
            'db_migrate_*.php',
            'db_update_*.php',
            'migrate_*.php',
            '*.sql',
            '*.log',
            '*.tmp',
            'run_migration_v5.php',
            'db_check_quotations.php',
            'db_debug.php',
            'db_definitive_fix.php',
            'db_final_polish.php',
            'db_fix_schema.php',
            'db_fix_subtotals.php',
            'db_fix_usernames.php',
            'db_force_import.php',
            'db_mega_fix.php',
            'diff.txt',
            'dump_db.php',
            'files.txt',
            'fix_users_db.php',
            'full_financial_migration.php',
            'full_schema_check.php',
            'git_diff.txt',
            'inspect_db.php',
            'last_commit.txt',
            'logo_display.php',
            'repair.php',
            'reparar_db.php',
            'save_schema.php',
            'strip_bom.php',
            'Claves.txt',
            'vecino_user.txt',
            'config_entities_partial.php',
            'diag.php'
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        echo "Eliminando: $file ... ";
                        if (unlink($file)) {
                            echo "<span style='color:green;'>[OK]</span><br>";
                            $count++;
                        } else {
                            echo "<span style='color:red;'>[FALLÃ“]</span><br>";
                        }
                    }
                }
            }
        }
        echo "<h4>Total archivos eliminados: $count</h4>";
    }
}

echo "<ul>
    <li><a href='?action=fix' style='color: #136dec; font-weight: bold;'>1. REPARAR GIT (Reset & Pull)</a></li>
    <li><a href='?action=clean_inodes' style='color: #ef4444; font-weight: bold;'>2. LIMPIEZA PROFUNDA (Borrar archivos residuales e INODOS)</a></li>
</ul>";





