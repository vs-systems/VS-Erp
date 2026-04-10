<?php
/**
 * check_db.php — Diagnóstico de Esquema de Base de Datos
 * Vecino Seguro ERP
 *
 * EL AMARILLO NO ES UN ERROR — solo resalta columnas de tipo ENUM.
 * Leer la leyenda en pantalla para interpretar correctamente.
 */
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$tablesOk   = [];
$tablesFail = [];

try {
    $db     = Database::getInstance();
    $tables = ['entities', 'users', 'quotations', 'products'];

    ob_start();
    foreach ($tables as $table) {
        try {
            $cols = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll();
            $tablesOk[] = $table;

            echo "<section>";
            echo "<h2 class='tbl-title'><span class='icon-tbl'>▸</span> $table</h2>";
            echo "<table class='schema-table'>";
            echo "<thead><tr>
                    <th>Columna</th>
                    <th>Tipo de Dato</th>
                    <th>Nulo</th>
                    <th>Default</th>
                  </tr></thead><tbody>";

            foreach ($cols as $col) {
                $rawType = $col['Type'];
                $isEnum  = (strpos($rawType, 'enum') !== false);
                $typeHtml = htmlspecialchars($rawType);

                if ($isEnum) {
                    $typeHtml = "<span class='badge-enum' title='Columna ENUM — los valores permitidos están entre paréntesis. El amarillo NO indica error.'>ENUM</span> "
                              . "<span class='enum-vals'>" . htmlspecialchars(substr($rawType, 5)) . "</span>";
                }

                $nullBadge = $col['Null'] === 'YES'
                    ? "<span class='badge-null'>NULL</span>"
                    : "<span class='badge-notnull'>NOT NULL</span>";

                $default = isset($col['Default']) ? htmlspecialchars((string)$col['Default']) : '<span class="muted">—</span>';

                echo "<tr class='" . ($isEnum ? 'row-enum' : '') . "'>";
                echo "<td class='col-field'>{$col['Field']}</td>";
                echo "<td class='col-type'>$typeHtml</td>";
                echo "<td class='col-null'>$nullBadge</td>";
                echo "<td class='col-default'>$default</td>";
                echo "</tr>";
            }
            echo "</tbody></table></section>";

        } catch (Exception $e) {
            $tablesFail[] = $table;
            echo "<section>";
            echo "<h2 class='tbl-title err'>✗ $table — ERROR</h2>";
            echo "<p class='err-msg'>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</section>";
        }
    }
    $content = ob_get_clean();

} catch (Exception $e) {
    die("<pre style='color:red;padding:32px'>Error crítico de conexión: " . htmlspecialchars($e->getMessage()) . "</pre>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnóstico DB — Vecino Seguro</title>
<style>
  :root {
    --bg:      #0d1117;
    --surface: #161b22;
    --border:  #21262d;
    --text:    #c9d1d9;
    --muted:   #6e7681;
    --blue:    #58a6ff;
    --green:   #3fb950;
    --yellow:  #e3b341;
    --red:     #f85149;
    --enum-bg:     #1c1a09;
    --enum-border: #3d3000;
    --enum-text:   #e3b341;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Courier New', monospace;
    background: var(--bg);
    color: var(--text);
    padding: 32px 24px;
    font-size: 13px;
    line-height: 1.6;
    max-width: 1100px;
  }

  /* ── HEADER ─────────────────────────────── */
  header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }
  h1 { color: var(--blue); font-size: 20px; margin-bottom: 4px; }
  .subtitle { color: var(--muted); font-size: 12px; }

  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 40px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
  }
  .status-ok  { background: #0d2119; border: 1px solid var(--green); color: var(--green); }
  .status-err { background: #2d1117; border: 1px solid var(--red);   color: var(--red);   }

  /* ── LEYENDA ─────────────────────────────── */
  .legend {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 28px;
  }
  .legend-title {
    font-size: 10px;
    font-weight: bold;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 12px;
  }
  .legend-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 10px;
  }
  .legend-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 12px;
    color: var(--text);
  }
  .legend-swatch {
    flex-shrink: 0;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    margin-top: 1px;
  }
  .sw-enum    { background: var(--enum-text); }
  .sw-notnull { background: var(--blue); }
  .sw-null    { background: var(--muted); }
  .sw-err     { background: var(--red); }

  .legend-item strong { color: var(--yellow); }
  .legend-item.ok-item strong { color: var(--green); }
  .legend-item.err-item strong { color: var(--red); }

  /* ── TABLES ─────────────────────────────── */
  section { margin-bottom: 32px; }

  .tbl-title {
    font-size: 15px;
    color: var(--blue);
    margin-bottom: 10px;
    font-weight: bold;
  }
  .tbl-title .icon-tbl { color: var(--muted); margin-right: 6px; font-style: normal; }
  .tbl-title.err { color: var(--red); }

  .schema-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
  }
  .schema-table thead tr { background: #1f2937; }
  .schema-table th {
    padding: 8px 14px;
    text-align: left;
    color: var(--muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    border-bottom: 1px solid var(--border);
  }
  .schema-table td {
    padding: 7px 14px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
  }
  .schema-table tbody tr:last-child td { border-bottom: none; }
  .schema-table tbody tr:hover { background: #ffffff07; }

  /* ENUM rows */
  .row-enum { background: var(--enum-bg); }
  .row-enum:hover { background: #25210e !important; }

  .badge-enum {
    display: inline-block;
    background: rgba(227,179,65,.12);
    border: 1px solid var(--enum-border);
    color: var(--enum-text);
    font-size: 9px;
    font-weight: bold;
    padding: 1px 5px;
    border-radius: 4px;
    vertical-align: middle;
  }
  .enum-vals { color: var(--enum-text); font-size: 11px; opacity: .85; }

  .badge-null    { color: var(--muted); font-size: 11px; }
  .badge-notnull { color: var(--blue);  font-size: 11px; font-weight: bold; }

  .col-field   { color: #e6edf3; font-weight: bold; min-width: 160px; }
  .col-type    { max-width: 400px; }
  .col-null    { width: 100px; }
  .col-default { color: var(--muted); }
  .muted       { color: var(--muted); }
  .err-msg     { color: var(--red); padding: 8px 0; }

  /* ── NAV ────────────────────────────────── */
  .nav-links {
    margin-top: 40px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    border-top: 1px solid var(--border);
    padding-top: 24px;
  }
  .nav-links a {
    color: var(--blue);
    text-decoration: none;
    font-size: 12px;
    padding: 7px 16px;
    border: 1px solid var(--border);
    border-radius: 6px;
    transition: border-color .2s, background .2s;
  }
  .nav-links a:hover { border-color: var(--blue); background: #1a2b3c; }
</style>
</head>
<body>

<header>
  <div>
    <h1>🔍 Diagnóstico de Esquema</h1>
    <p class="subtitle">Vecino Seguro ERP — Estructura de tablas principales</p>
  </div>
  <?php if (empty($tablesFail)): ?>
    <span class="status-pill status-ok">
      ✅ Todo OK — <?php echo count($tablesOk); ?> tabla(s) verificada(s)
    </span>
  <?php else: ?>
    <span class="status-pill status-err">
      ✗ <?php echo count($tablesFail); ?> tabla(s) con error
    </span>
  <?php endif; ?>
</header>

<!-- ═══════════════════════ LEYENDA ═══════════════════════ -->
<div class="legend">
  <div class="legend-title">📖 Cómo leer este diagnóstico</div>
  <div class="legend-grid">

    <div class="legend-item">
      <span class="legend-swatch sw-enum"></span>
      <div>
        <strong>Fila / badge amarillo = columna ENUM</strong><br>
        <span style="color:var(--muted);font-size:11px;">
          Solo es resaltado visual para identificar fácilmente las columnas ENUM
          y verificar que sus valores sean correctos.<br>
          <b style="color:var(--yellow)">NO indica ningún error.</b>
        </span>
      </div>
    </div>

    <div class="legend-item">
      <span class="legend-swatch sw-notnull"></span>
      <div>
        <strong style="color:var(--blue)">NOT NULL (azul)</strong><br>
        <span style="color:var(--muted);font-size:11px;">
          La columna es obligatoria. El código siempre debe proveer un valor.
        </span>
      </div>
    </div>

    <div class="legend-item">
      <span class="legend-swatch sw-null"></span>
      <div>
        <strong style="color:var(--muted)">NULL (gris)</strong><br>
        <span style="color:var(--muted);font-size:11px;">
          La columna acepta valores nulos (campo opcional).
        </span>
      </div>
    </div>

    <div class="legend-item err-item">
      <span class="legend-swatch sw-err"></span>
      <div>
        <strong>Error rojo = problema real</strong><br>
        <span style="color:var(--muted);font-size:11px;">
          La tabla no existe o no es accesible. Ejecutar
          <a href="fix_db.php" style="color:var(--blue)">fix_db.php</a> para reparar.
        </span>
      </div>
    </div>

  </div>
</div>
<!-- ════════════════════════════════════════════════════════ -->

<?php echo $content; ?>

<div class="nav-links">
  <a href="fix_db.php">🔧 Ejecutar auto-reparación</a>
  <a href="configuration.php">← Centro de Configuración</a>
  <a href="clientes.php">Clientes</a>
</div>

</body>
</html>
