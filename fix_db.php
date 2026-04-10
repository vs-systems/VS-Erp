<?php
/**
 * fix_db.php — Auto-reparación de Esquema de Base de Datos
 * Vecino Seguro ERP
 *
 * Ejecutar UNA VEZ en el servidor.
 * Detecta y corrige automáticamente los problemas de esquema
 * identificados por check_db.php
 *
 * ACCESO: Solo admins internos (protegido por auth_check.php)
 */
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$db = Database::getInstance();
$results = [];
$errors  = [];

// ──────────────────────────────────────────────────────────────────
// Helper: ejecutar ALTER y registrar resultado
// ──────────────────────────────────────────────────────────────────
function runFix(PDO $db, string $label, string $sql, array &$results, array &$errors): void
{
    try {
        $db->exec($sql);
        $results[] = ['ok', $label];
    } catch (PDOException $e) {
        // Error 1060 = columna ya existe → no es un error real
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
            $results[] = ['skip', "$label (ya existía, sin cambios)"];
        } else {
            $errors[] = [$label, $e->getMessage()];
        }
    }
}

// ══════════════════════════════════════════════════════════════════
// TABLA: users
// Problema detectado: role ENUM no incluye 'client'
//                     full_name es NOT NULL sin default
// ══════════════════════════════════════════════════════════════════

// 1. Ampliar ENUM role para incluir 'client'
runFix($db,
    "users.role — Agregar valor 'client' al ENUM",
    "ALTER TABLE users MODIFY COLUMN role 
     ENUM('admin','vendedor','logistica','client') 
     NULL DEFAULT 'vendedor'",
    $results, $errors
);

// 2. full_name: quitar NOT NULL para evitar error 1364 cuando no se provee
runFix($db,
    "users.full_name — Permitir NULL y poner default vacío",
    "ALTER TABLE users MODIFY COLUMN full_name VARCHAR(100) NULL DEFAULT ''",
    $results, $errors
);

// 3. email: permitir NULL (clients creados sin email interno del ERP)
runFix($db,
    "users.email — Permitir NULL",
    "ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NULL DEFAULT NULL",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: entities — client_profile
// Problema: registros con valores en mayúsculas (GREMIO, PUBLICO, PARTNER)
// que no son válidos en el ENUM original ('Gremio','Web','ML','Otro')
//
// Estrategia correcta:
//  1. Convertir temporalmente a VARCHAR (acepta cualquier valor)
//  2. Normalizar los registros con UPDATE
//  3. Restaurar ENUM con los valores canónicos
// ══════════════════════════════════════════════════════════════════

// Paso 1: Convertir a VARCHAR para poder normalizar datos sin restricción
runFix($db,
    "entities.client_profile — Paso 1/3: Convertir a VARCHAR para normalización",
    "ALTER TABLE entities MODIFY COLUMN client_profile VARCHAR(50) NULL DEFAULT 'Otro'",
    $results, $errors
);

// Paso 2: Normalizar valores con mayúsculas incorrectas
runFix($db,
    "entities.client_profile — Paso 2/3: Normalizar valores (GREMIO→Gremio, PUBLICO/PARTNER→Otro, WEB→Web)",
    "UPDATE entities SET client_profile = CASE
        WHEN UPPER(client_profile) = 'GREMIO'  THEN 'Gremio'
        WHEN UPPER(client_profile) = 'WEB'     THEN 'Web'
        WHEN UPPER(client_profile) = 'ML'      THEN 'ML'
        WHEN UPPER(client_profile) = 'PUBLICO' THEN 'Otro'
        WHEN UPPER(client_profile) = 'PARTNER' THEN 'Otro'
        ELSE 'Otro'
     END
     WHERE client_profile NOT IN ('Gremio','Web','ML','Otro') OR client_profile IS NULL",
    $results, $errors
);

// Paso 3: Restaurar ENUM con los valores canónicos correctos
runFix($db,
    "entities.client_profile — Paso 3/3: Restaurar ENUM canónico",
    "ALTER TABLE entities MODIFY COLUMN client_profile
     ENUM('Gremio','Web','ML','Otro')
     NULL DEFAULT 'Otro'",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: entities — is_verified: columna puede faltar en instalaciones viejas
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "entities.is_verified — Asegurar que existe (ADD COLUMN IF NOT EXISTS emulado)",
    "ALTER TABLE entities ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NULL DEFAULT 0",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: entities — tipo_cliente: columna puede faltar
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "entities.tipo_cliente — Asegurar que existe",
    "ALTER TABLE entities ADD COLUMN IF NOT EXISTS tipo_cliente 
     ENUM('partner','gremio','publico') NOT NULL DEFAULT 'publico'",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: entities — birth_year: para recuperación de contraseña
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "entities.birth_year — Agregar columna de año de nacimiento",
    "ALTER TABLE entities ADD COLUMN IF NOT EXISTS birth_year SMALLINT NULL",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA NUEVA: client_credentials_log
// Registra cuándo se creó o reseteó la contraseña de un cliente
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "client_credentials_log — Crear tabla de log de credenciales",
    "CREATE TABLE IF NOT EXISTS client_credentials_log (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        entity_id  INT NOT NULL,
        email      VARCHAR(100)  NULL,
        cuit       VARCHAR(20)   NULL,
        document   VARCHAR(20)   NULL,
        birth_year SMALLINT      NULL,
        action     ENUM('created','reset') DEFAULT 'created',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_entity (entity_id),
        INDEX idx_email  (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: quotations — columnas de logística que pueden faltar
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "quotations.authorized_dispatch — Asegurar columna",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS authorized_dispatch TINYINT(1) NULL DEFAULT 0",
    $results, $errors
);

runFix($db,
    "quotations.logistics_authorized_by — Asegurar columna",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_by VARCHAR(100) NULL",
    $results, $errors
);

runFix($db,
    "quotations.logistics_authorized_at — Asegurar columna",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS logistics_authorized_at DATETIME NULL",
    $results, $errors
);

runFix($db,
    "quotations.archived_at — Asegurar columna",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL",
    $results, $errors
);

runFix($db,
    "quotations.archive_reason — Asegurar columna",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS archive_reason 
     ENUM('Vendido','Suspendido','Rechazado') NULL",
    $results, $errors
);

// ══════════════════════════════════════════════════════════════════
// TABLA: products — columnas de stock y listas de precios
// ══════════════════════════════════════════════════════════════════
runFix($db,
    "products.stock_current — Asegurar columna",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_current INT(11) NULL DEFAULT 0",
    $results, $errors
);

runFix($db,
    "products.price_partner — Asegurar columna",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS price_partner DECIMAL(15,2) NULL",
    $results, $errors
);

runFix($db,
    "products.price_gremio — Asegurar columna",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS price_gremio DECIMAL(15,2) NULL",
    $results, $errors
);

runFix($db,
    "products.price_pvp — Asegurar columna (precio publico)",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS price_pvp DECIMAL(15,2) NULL",
    $results, $errors
);

// ──────────────────────────────────────────────────────────────────
// OUTPUT HTML
// ──────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Fix DB — Vecino Seguro</title>
<style>
  body { font-family: 'Courier New', monospace; background: #0d1117; color: #c9d1d9; padding: 32px; }
  h1   { color: #58a6ff; font-size: 22px; margin-bottom: 24px; }
  h2   { color: #8b949e; font-size: 14px; margin: 24px 0 8px; border-top: 1px solid #21262d; padding-top: 16px; }
  .ok   { background: #0d2119; border-left: 3px solid #3fb950; margin: 6px 0; padding: 8px 12px; border-radius: 4px; color: #3fb950; font-size: 13px; }
  .skip { background: #1a1d22; border-left: 3px solid #6e7681; margin: 6px 0; padding: 8px 12px; border-radius: 4px; color: #6e7681; font-size: 13px; }
  .err  { background: #2d1117; border-left: 3px solid #f85149; margin: 6px 0; padding: 8px 12px; border-radius: 4px; color: #f85149; font-size: 13px; }
  .icon { margin-right: 8px; }
  .summary { margin-top: 32px; padding: 16px; border-radius: 8px; font-size: 14px; }
  .summary.success { background: #0d2119; border: 1px solid #3fb950; color: #3fb950; }
  .summary.hasErrors { background: #2d1117; border: 1px solid #f85149; color: #f85149; }
  a.back { display: inline-block; margin-top: 24px; color: #58a6ff; text-decoration: none; font-size: 13px; }
  a.back:hover { text-decoration: underline; }
</style>
</head>
<body>

<h1>🔧 Auto-reparación de Esquema — Vecino Seguro ERP</h1>

<h2>Resultados</h2>
<?php foreach ($results as [$type, $msg]): ?>
    <div class="<?php echo $type; ?>">
        <?php if ($type === 'ok'): ?>
            <span class="icon">✓</span><?php echo htmlspecialchars($msg); ?>
        <?php else: ?>
            <span class="icon">–</span><?php echo htmlspecialchars($msg); ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
    <h2>Errores</h2>
    <?php foreach ($errors as [$label, $errMsg]): ?>
        <div class="err">
            <span class="icon">✗</span>
            <strong><?php echo htmlspecialchars($label); ?></strong><br>
            <span style="font-size:11px;opacity:.8;"><?php echo htmlspecialchars($errMsg); ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="summary <?php echo empty($errors) ? 'success' : 'hasErrors'; ?>">
    <?php if (empty($errors)): ?>
        ✅ Reparación completada sin errores críticos.<br>
        <small>
            <?php echo count($results); ?> operaciones realizadas.
            <?php 
            $okCount = count(array_filter($results, fn($r) => $r[0] === 'ok'));
            echo "$okCount cambios aplicados.";
            ?>
        </small>
    <?php else: ?>
        ⚠️ Completado con <?php echo count($errors); ?> error(es). Revisá los detalles arriba.
    <?php endif; ?>
</div>

<a class="back" href="configuration.php">← Volver al Centro de Configuración</a>
<br>
<a class="back" href="check_db.php">→ Verificar esquema post-reparación</a>

</body>
</html>
