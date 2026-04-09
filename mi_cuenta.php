<?php
/**
 * Mi Cuenta — Portal del Cliente
 * Bloque 6: área privada post-login para clientes externos
 */
require_once 'auth_check_client.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$db     = Database::getInstance();
$userId = $_SESSION['user_id'];

// ── DATOS DEL USUARIO Y ENTIDAD ──────────────────────────────────
$stmt = $db->prepare(
    "SELECT u.username, u.full_name, u.role, u.last_login,
            e.id AS entity_id, e.name, e.fantasy_name, e.email,
            e.phone, e.mobile, e.address, e.tax_id, e.document_number,
            e.tax_category, e.is_verified, e.tipo_cliente, e.client_profile,
            e.payment_condition
     FROM users u
     LEFT JOIN entities e ON u.entity_id = e.id
     WHERE u.id = ?"
);
$stmt->execute([$userId]);
$me = $stmt->fetch();

if (!$me) {
    // Usuario sin entidad asociada → es staff interno, redirigir al panel
    header('Location: dashboard.php');
    exit;
}

$entityId    = $me['entity_id'];
$tipoCliente = $me['tipo_cliente'] ?? 'publico';
$isVerified  = (int)($me['is_verified'] ?? 0);
$displayName = $me['fantasy_name'] ?: $me['name'] ?: $me['username'];

// Labels
$tipoLabels = [
    'partner' => ['label' => 'Partner',  'color' => '#c084fc', 'bg' => 'rgba(168,85,247,.12)',  'desc' => 'Acceso a la lista de precios Partner.'],
    'gremio'  => ['label' => 'Gremio',   'color' => '#fbbf24', 'bg' => 'rgba(245,158,11,.12)',  'desc' => 'Acceso a lista de precios exclusiva para el rubro.'],
    'publico' => ['label' => 'Público',  'color' => '#94a3b8', 'bg' => 'rgba(100,116,139,.12)', 'desc' => 'Precio de venta al público (PVP).'],
];
$tipoInfo = $tipoLabels[$tipoCliente] ?? $tipoLabels['publico'];

// ── MIS ÚLTIMAS COTIZACIONES (hasta 10) ──────────────────────────
$cotizaciones = [];
if ($entityId) {
    $stmt2 = $db->prepare(
        "SELECT q.id, q.quote_number, q.created_at, q.total_ars, q.total_usd,
                q.status, q.valid_until, q.observations,
                COUNT(qi.id) AS items_count
         FROM quotations q
         LEFT JOIN quotation_items qi ON qi.quotation_id = q.id
         WHERE q.client_id = ?
         GROUP BY q.id
         ORDER BY q.created_at DESC
         LIMIT 10"
    );
    $stmt2->execute([$entityId]);
    $cotizaciones = $stmt2->fetchAll();
}

// Status badges
function statusBadge($status) {
    $map = [
        'Pendiente'  => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#fbbf24', 'icon' => 'schedule'],
        'Aprobada'   => ['bg' => 'rgba(34,197,94,.12)',  'color' => '#4ade80', 'icon' => 'check_circle'],
        'Rechazada'  => ['bg' => 'rgba(239,68,68,.12)',  'color' => '#f87171', 'icon' => 'cancel'],
        'Facturada'  => ['bg' => 'rgba(96,165,250,.12)', 'color' => '#60a5fa', 'icon' => 'receipt_long'],
        'Vencida'    => ['bg' => 'rgba(100,116,139,.1)', 'color' => '#64748b', 'icon' => 'timer_off'],
    ];
    $s = $map[$status] ?? $map['Pendiente'];
    return "<span style=\"display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:10px;font-weight:700;background:{$s['bg']};color:{$s['color']};\">"
         . "<span class=\"material-symbols-outlined\" style=\"font-size:12px;\">{$s['icon']}</span>"
         . htmlspecialchars($status)
         . "</span>";
}

$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta — Vecinos Seguros</title>
    <meta name="description" content="Tu área privada en Vecinos Seguros. Consultá tus cotizaciones y datos de cuenta.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:     #0d1117;
            --bg2:    #111827;
            --card:   #16202e;
            --border: #233348;
            --blue:   #3b82f6;
            --text:   rgba(255,255,255,.88);
            --muted:  rgba(255,255,255,.42);
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        .top-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(13,17,23,.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(59,130,246,.14);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 32px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .nav-logo .shield {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: rgba(59,130,246,.12);
            display: flex; align-items: center; justify-content: center;
            color: var(--blue);
        }
        .nav-logo .shield .material-symbols-outlined { font-size: 20px; }
        .nav-logo span { font-size: 15px; font-weight: 800; color: #fff; }

        .nav-actions { display: flex; align-items: center; gap: 10px; }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: all .2s;
        }
        .btn-catalog {
            background: rgba(59,130,246,.12);
            border: 1px solid rgba(59,130,246,.25);
            color: var(--blue);
        }
        .btn-catalog:hover { background: rgba(59,130,246,.22); }

        .btn-logout {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            color: var(--muted);
        }
        .btn-logout:hover { background: rgba(255,255,255,.1); color: var(--text); }

        /* ── PAGE ── */
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 24px 60px;
        }

        /* ── HERO PERFIL ── */
        .profile-hero {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: start;
            gap: 24px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(59,130,246,.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .profile-avatar {
            width: 64px; height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(59,130,246,.25), rgba(6,182,212,.15));
            border: 1px solid rgba(59,130,246,.25);
            display: flex; align-items: center; justify-content: center;
            color: var(--blue);
            margin-bottom: 14px;
            flex-shrink: 0;
        }
        .profile-avatar .material-symbols-outlined { font-size: 32px; }

        .profile-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.4px;
            color: #fff;
            margin-bottom: 5px;
        }
        .profile-email {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .profile-badges { display: flex; flex-wrap: wrap; gap: 8px; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge .material-symbols-outlined { font-size: 13px; }

        /* ── VERIFICACIÓN ── */
        .verification-notice {
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.25);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 28px;
        }
        .verification-notice .material-symbols-outlined { color: #fbbf24; font-size: 22px; flex-shrink: 0; }
        .verification-notice h4 { font-size: 13px; font-weight: 700; color: #fbbf24; margin-bottom: 4px; }
        .verification-notice p { font-size: 12px; color: var(--muted); line-height: 1.6; }

        /* ── SECCIÓN ── */
        .section-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .section-title .material-symbols-outlined { font-size: 15px; }
        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .stat-card .stat-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 4px;
        }
        .stat-icon .material-symbols-outlined { font-size: 18px; }
        .stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
        .stat-value { font-size: 22px; font-weight: 800; color: #fff; line-height: 1; }
        .stat-sub { font-size: 11px; color: var(--muted); }

        /* ── DATOS DE CUENTA ── */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 28px;
        }

        .data-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 22px;
        }
        .data-card.full { grid-column: 1 / -1; }

        .data-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 14px;
        }
        .data-field:last-child { margin-bottom: 0; }
        .data-field-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .data-field-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
        }
        .data-field-value.empty { color: rgba(255,255,255,.2); font-style: italic; font-size: 13px; }

        /* ── TABLA COTIZACIONES ── */
        .quotes-table-wrap {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        thead { background: rgba(0,0,0,.25); }
        th {
            padding: 12px 18px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--muted);
            text-align: left;
            white-space: nowrap;
        }
        td {
            padding: 14px 18px;
            font-size: 13px;
            color: var(--text);
            border-top: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background: rgba(255,255,255,.02); }

        .quote-num {
            font-weight: 700;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: var(--blue);
        }
        .quote-date { font-size: 12px; color: var(--muted); }
        .price-ars { font-weight: 700; color: #fff; }
        .price-usd { font-size: 11px; color: var(--muted); font-family: monospace; }
        .validity-ok   { color: #4ade80; font-size: 11px; }
        .validity-exp  { color: #f87171; font-size: 11px; }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--muted);
        }
        .empty-state .material-symbols-outlined { font-size: 48px; display: block; margin-bottom: 12px; }
        .empty-state p { font-size: 13px; font-weight: 600; margin-bottom: 16px; }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--blue);
            color: #fff;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all .2s;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-primary .material-symbols-outlined { font-size: 16px; }

        /* ── CTA IR AL CATÁLOGO ── */
        .cta-banner {
            background: linear-gradient(135deg, rgba(59,130,246,.12) 0%, rgba(6,182,212,.06) 100%);
            border: 1px solid rgba(59,130,246,.2);
            border-radius: 20px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .cta-banner h3 { font-size: 17px; font-weight: 800; color: #fff; margin-bottom: 5px; }
        .cta-banner p { font-size: 13px; color: var(--muted); }

        @media (max-width: 720px) {
            .profile-hero { grid-template-columns: 1fr; }
            .data-grid { grid-template-columns: 1fr; }
            .data-card.full { grid-column: 1; }
            .top-nav { padding: 10px 16px; }
            .page { padding: 20px 14px 50px; }
            .profile-hero { padding: 20px; }
        }
    </style>
</head>

<body>

<!-- ── NAVBAR ── -->
<nav class="top-nav">
    <a href="index.php" class="nav-logo">
        <div class="shield">
            <span class="material-symbols-outlined">shield</span>
        </div>
        <span>Vecinos Seguros</span>
    </a>
    <div class="nav-actions">
        <a href="catalogo_web.php" class="btn-nav btn-catalog">
            <span class="material-symbols-outlined" style="font-size:16px;">storefront</span>
            Ir al Catálogo
        </a>
        <a href="logout.php" class="btn-nav btn-logout">
            <span class="material-symbols-outlined" style="font-size:16px;">logout</span>
            Salir
        </a>
    </div>
</nav>

<div class="page">

    <!-- ── NOTIFICACIÓN PENDIENTE DE VERIFICACIÓN ── -->
    <?php if (!$isVerified): ?>
    <div class="verification-notice">
        <span class="material-symbols-outlined">pending</span>
        <div>
            <h4>Cuenta pendiente de verificación</h4>
            <p>Tu solicitud de alta fue recibida. Un asesor revisará tus datos y te enviará las credenciales de acceso a <strong><?= htmlspecialchars($me['email'] ?? '') ?></strong> en breve.<br>
               Mientras tanto podés explorar el catálogo con precios públicos. Si tenés urgencia, contactanos por WhatsApp.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PERFIL HERO ── -->
    <div class="profile-hero">
        <div>
            <div class="profile-avatar">
                <span class="material-symbols-outlined">person</span>
            </div>
            <div class="profile-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="profile-email"><?= htmlspecialchars($me['email'] ?? $me['username']) ?></div>
            <div class="profile-badges">
                <!-- Tipo cliente -->
                <span class="badge" style="background:<?= $tipoInfo['bg'] ?>;color:<?= $tipoInfo['color'] ?>;border:1px solid <?= str_replace('.12', '.25', $tipoInfo['bg']) ?>;">
                    <span class="material-symbols-outlined">label</span>
                    <?= $tipoInfo['label'] ?>
                </span>
                <!-- Verificación -->
                <?php if ($isVerified): ?>
                <span class="badge" style="background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.2);">
                    <span class="material-symbols-outlined">verified</span>
                    Cuenta verificada
                </span>
                <?php else: ?>
                <span class="badge" style="background:rgba(245,158,11,.1);color:#fbbf24;border:1px solid rgba(245,158,11,.2);">
                    <span class="material-symbols-outlined">schedule</span>
                    Verificación pendiente
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Precio activo</div>
            <div style="font-size:14px;font-weight:700;color:<?= $tipoInfo['color'] ?>;"><?= $tipoInfo['desc'] ?></div>
            <?php if ($me['last_login']): ?>
            <div style="font-size:11px;color:var(--muted);margin-top:10px;">
                Último acceso: <?= date('d/m/Y H:i', strtotime($me['last_login'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── ESTADOS / STATS ── -->
    <?php
    $totalCots  = count($cotizaciones);
    $totalArs   = array_sum(array_column($cotizaciones, 'total_ars'));
    $pendientes = count(array_filter($cotizaciones, fn($q) => $q['status'] === 'Pendiente'));
    $aprobadas  = count(array_filter($cotizaciones, fn($q) => $q['status'] === 'Aprobada'));
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,.12);color:var(--blue);">
                <span class="material-symbols-outlined">description</span>
            </div>
            <div class="stat-label">Consultas enviadas</div>
            <div class="stat-value"><?= $totalCots ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#fbbf24;">
                <span class="material-symbols-outlined">schedule</span>
            </div>
            <div class="stat-label">Pendientes</div>
            <div class="stat-value"><?= $pendientes ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,.1);color:#4ade80;">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
            <div class="stat-label">Aprobadas</div>
            <div class="stat-value"><?= $aprobadas ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,.1);color:var(--blue);">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <div class="stat-label">Total acumulado</div>
            <div class="stat-value" style="font-size:16px;">
                <?= $totalArs > 0 ? '$&nbsp;' . number_format($totalArs, 0, ',', '.') : '—' ?>
            </div>
            <div class="stat-sub">ARS, todas las consultas</div>
        </div>
    </div>

    <!-- ── MIS DATOS ── -->
    <div class="section-title">
        <span class="material-symbols-outlined">manage_accounts</span>
        Mis datos de cuenta
    </div>

    <div class="data-grid">
        <div class="data-card">
            <div class="data-field">
                <div class="data-field-label">Razón Social / Nombre</div>
                <div class="data-field-value"><?= htmlspecialchars($me['name'] ?: '—') ?></div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Nombre Fantasía</div>
                <div class="data-field-value <?= empty($me['fantasy_name']) ? 'empty' : '' ?>">
                    <?= htmlspecialchars($me['fantasy_name'] ?: 'Sin especificar') ?>
                </div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Categoría fiscal</div>
                <div class="data-field-value"><?= htmlspecialchars($me['tax_category'] ?: '—') ?></div>
            </div>
        </div>

        <div class="data-card">
            <div class="data-field">
                <div class="data-field-label">Email</div>
                <div class="data-field-value"><?= htmlspecialchars($me['email'] ?? '—') ?></div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Celular / WhatsApp</div>
                <div class="data-field-value <?= empty($me['mobile']) ? 'empty' : '' ?>">
                    <?= htmlspecialchars($me['mobile'] ?: 'Sin especificar') ?>
                </div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Teléfono</div>
                <div class="data-field-value <?= empty($me['phone']) ? 'empty' : '' ?>">
                    <?= htmlspecialchars($me['phone'] ?: 'Sin especificar') ?>
                </div>
            </div>
        </div>

        <div class="data-card">
            <div class="data-field">
                <div class="data-field-label">CUIT</div>
                <div class="data-field-value <?= empty($me['tax_id']) ? 'empty' : '' ?>">
                    <?= htmlspecialchars($me['tax_id'] ?: 'Sin especificar') ?>
                </div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Localidad / Dirección</div>
                <div class="data-field-value <?= empty($me['address']) ? 'empty' : '' ?>">
                    <?= htmlspecialchars($me['address'] ?: 'Sin especificar') ?>
                </div>
            </div>
        </div>

        <div class="data-card">
            <div class="data-field">
                <div class="data-field-label">Condición de pago</div>
                <div class="data-field-value"><?= htmlspecialchars($me['payment_condition'] ?: 'Contado') ?></div>
            </div>
            <div class="data-field">
                <div class="data-field-label">Lista de precios</div>
                <div class="data-field-value" style="color:<?= $tipoInfo['color'] ?>;font-weight:700;">
                    <?= $tipoInfo['label'] ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MIS COTIZACIONES ── -->
    <div class="section-title">
        <span class="material-symbols-outlined">receipt_long</span>
        Mis consultas y cotizaciones
    </div>

    <div class="quotes-table-wrap">
        <?php if (empty($cotizaciones)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">inbox</span>
                <p>Todavía no enviaste ninguna consulta.</p>
                <a href="catalogo_web.php" class="btn-primary">
                    <span class="material-symbols-outlined">storefront</span>
                    Explorar el catálogo
                </a>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>N° Consulta</th>
                    <th>Fecha</th>
                    <th>Ítems</th>
                    <th class="text-right">Total ARS</th>
                    <th>Válida hasta</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cotizaciones as $q):
                    $qDate    = $q['created_at'] ? date('d/m/Y', strtotime($q['created_at'])) : '—';
                    $validUntil = $q['valid_until'] ?? null;
                    $isExpired  = $validUntil && $validUntil < $hoy;
                    $validStr   = $validUntil ? date('d/m/Y', strtotime($validUntil)) : '—';
                    $actualStatus = ($isExpired && $q['status'] === 'Pendiente') ? 'Vencida' : $q['status'];
                ?>
                <tr>
                    <td>
                        <div class="quote-num">#<?= htmlspecialchars($q['quote_number']) ?></div>
                    </td>
                    <td>
                        <div class="quote-date"><?= $qDate ?></div>
                    </td>
                    <td>
                        <span style="font-size:12px;font-weight:600;"><?= (int)$q['items_count'] ?> prod.</span>
                    </td>
                    <td>
                        <div class="price-ars">
                            <?= $q['total_ars'] > 0 ? '$ ' . number_format($q['total_ars'], 0, ',', '.') : '—' ?>
                        </div>
                        <?php if ($q['total_usd'] > 0): ?>
                        <div class="price-usd">≈ USD <?= number_format($q['total_usd'], 2) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($validUntil): ?>
                            <span class="<?= $isExpired ? 'validity-exp' : 'validity-ok' ?>">
                                <?= $validStr ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= statusBadge($actualStatus) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── CTA CATÁLOGO ── -->
    <div class="cta-banner">
        <div>
            <h3>¿Necesitás algo que no encontrás?</h3>
            <p>Lo que no está en nuestro catálogo, lo buscamos por vos. Contactanos directamente.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="catalogo_web.php" class="btn-primary">
                <span class="material-symbols-outlined">storefront</span>
                Ver catálogo
            </a>
            <a href="https://wa.me/5492235772165?text=Hola%2C+necesito+consultar+un+producto+que+no+encuentro+en+el+cat%C3%A1logo."
               class="btn-primary" target="_blank"
               style="background:linear-gradient(135deg,#25d366,#128c7e);">
                <span class="material-symbols-outlined">chat</span>
                WhatsApp
            </a>
        </div>
    </div>

</div><!-- /page -->

</body>
</html>
