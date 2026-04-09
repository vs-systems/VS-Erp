<?php
/**
 * Catálogo Web — Vecinos Seguros
 * Bloque 4: precio según tipo de sesión, filtros anidados, paginado 18 por página
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/BCRAClient.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

use Vsys\Modules\Catalogo\Catalog;

$catalog = new Catalog();
$db      = Vsys\Lib\Database::getInstance();

// ── TIPO DE CLIENTE Y PRECIO ────────────────────────────────────
$tipoCliente = 'publico';
$isLoggedIn  = isset($_SESSION['user_id']);
$me          = ['name' => '', 'email' => '']; // datos del usuario para checkout JS

if ($isLoggedIn) {
    // Cargar tipo_cliente y datos de perfil
    if (!isset($_SESSION['tipo_cliente']) && isset($_SESSION['entity_id'])) {
        $stmt = $db->prepare("SELECT tipo_cliente FROM entities WHERE id = ?");
        $stmt->execute([$_SESSION['entity_id']]);
        $ent = $stmt->fetch();
        if ($ent) {
            $_SESSION['tipo_cliente'] = $ent['tipo_cliente'];
        }
    }
    $tipoCliente = $_SESSION['tipo_cliente'] ?? 'publico';

    // Datos del usuario para checkout
    $stmtMe = $db->prepare(
        "SELECT e.name, e.email FROM users u
         LEFT JOIN entities e ON u.entity_id = e.id
         WHERE u.id = ?"
    );
    $stmtMe->execute([$_SESSION['user_id']]);
    $me = $stmtMe->fetch() ?: ['name' => '', 'email' => ''];
}

// Columna de precio según tipo
$priceColMap = [
    'partner' => 'price_partner',
    'gremio'  => 'price_gremio',
    'publico' => 'price_pvp',
];
$priceCol = $priceColMap[$tipoCliente];

// Label visible
$precioLabel = [
    'partner' => 'Precio Partner',
    'gremio'  => 'Precio Gremio',
    'publico' => 'Precio público',
][$tipoCliente];

// ── TIPO DE CAMBIO ───────────────────────────────────────────────
$bcra = new \Vsys\Lib\BCRAClient();
$dolar = $bcra->getCurrentRate('oficial') ?? 1425.00;

// ── FILTROS ─────────────────────────────────────────────────────
$filterCat    = trim($_GET['cat']    ?? '');
$filterSub    = trim($_GET['sub']    ?? '');
$filterBrand  = trim($_GET['brand']  ?? '');
$filterSearch = trim($_GET['q']      ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 18;

// ── TODOS LOS PRODUCTOS (para construir filtros) ─────────────────
$allProds = $catalog->getAllProducts();

// Construir árbol categoría→subcategorías y lista de marcas
$catTree = [];
$brands  = [];
foreach ($allProds as $p) {
    $c = $p['category']    ?? '';
    $s = $p['subcategory'] ?? '';
    $b = $p['brand']       ?? '';
    if ($c) {
        if (!isset($catTree[$c])) $catTree[$c] = [];
        if ($s && !in_array($s, $catTree[$c])) $catTree[$c][] = $s;
    }
    if ($b && !in_array($b, $brands)) $brands[] = $b;
}
ksort($catTree);
sort($brands);

// ── FILTRAR ──────────────────────────────────────────────────────
$filtered = array_filter($allProds, function ($p) use ($filterCat, $filterSub, $filterBrand, $filterSearch) {
    $cat   = strtolower($p['category']    ?? '');
    $sub   = strtolower($p['subcategory'] ?? '');
    $brand = strtolower($p['brand']       ?? '');
    $desc  = strtolower($p['description'] ?? '');
    $sku   = strtolower($p['sku']         ?? '');

    if ($filterCat    && $cat   !== strtolower($filterCat))   return false;
    if ($filterSub    && $sub   !== strtolower($filterSub))   return false;
    if ($filterBrand  && $brand !== strtolower($filterBrand)) return false;
    if ($filterSearch && !str_contains($desc, strtolower($filterSearch)) && !str_contains($sku, strtolower($filterSearch)) && !str_contains($brand, strtolower($filterSearch))) return false;
    return true;
});

// Ordenar: con stock primero, luego por descripción
usort($filtered, function ($a, $b) {
    $sA = (int)($a['stock_current'] ?? 0);
    $sB = (int)($b['stock_current'] ?? 0);
    if ($sA > 0 && $sB <= 0) return -1;
    if ($sA <= 0 && $sB > 0) return 1;
    return strcmp($a['description'] ?? '', $b['description'] ?? '');
});
$filtered = array_values($filtered);

// Paginación
$totalProds = count($filtered);
$totalPages = max(1, ceil($totalProds / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;
$pageProds  = array_slice($filtered, $offset, $perPage);

// Helper precio
function getPrecio($prod, $col) {
    $v = $prod[$col] ?? null;
    return ($v !== null && $v > 0) ? (float)$v : null;
}
function fmtArs($v) {
    return '$\u00a0' . number_format($v, 0, ',', '.');
}

$waNumber = '5492235772165';
$hoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de productos Vecinos Seguros</title>
    <meta name="description" content="Catálogo completo de sistemas de seguridad electrónica, alarmas, CCTV, control de acceso e iluminación inteligente. Envíos a todo el país.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0d1117;
            --bg2:      #111827;
            --card:     #16202e;
            --border:   #233348;
            --blue:     #3b82f6;
            --blue2:    #1d4ed8;
            --cyan:     #06b6d4;
            --text:     rgba(255,255,255,.9);
            --muted:    rgba(255,255,255,.45);
            --sidebar-w: 260px;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── HEADER ── */
        .cat-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(13,17,23,.85);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(59,130,246,.18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .cat-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .cat-logo .shield {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(59,130,246,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--blue);
        }
        .cat-logo .brand-text span:first-child {
            display: block;
            font-size: 15px;
            font-weight: 800;
            color: #fff;
        }
        .cat-logo .brand-text span:last-child {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: var(--blue);
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .header-search {
            flex: 1;
            max-width: 420px;
            position: relative;
        }
        .header-search .material-symbols-outlined {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: rgba(255,255,255,.3);
            pointer-events: none;
        }
        .header-search input {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 9px 14px 9px 40px;
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .header-search input::placeholder { color: rgba(255,255,255,.25); }
        .header-search input:focus {
            border-color: rgba(59,130,246,.5);
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-sm {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all .2s;
            cursor: pointer;
            border: none;
        }
        .btn-ghost {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            color: rgba(255,255,255,.7);
        }
        .btn-ghost:hover { background: rgba(255,255,255,.1); color: #fff; }

        .btn-cart {
            position: relative;
            background: rgba(59,130,246,.15);
            border: 1px solid rgba(59,130,246,.3);
            color: var(--blue);
        }
        .btn-cart:hover { background: rgba(59,130,246,.25); }
        .cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: var(--blue);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--bg);
        }

        /* ── HERO BANNER ── */
        .hero-banner {
            background: linear-gradient(135deg, rgba(29,78,216,.3) 0%, rgba(13,17,23,0) 60%),
                        linear-gradient(to right, rgba(6,182,212,.08) 0%, transparent 50%);
            border-bottom: 1px solid var(--border);
            padding: 36px 28px 28px;
            text-align: center;
        }
        .hero-banner h1 {
            font-size: clamp(20px, 3vw, 32px);
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }
        .hero-banner .hl { color: var(--blue); }

        .notice-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
        }
        .notice-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
        }
        .notice-pill .material-symbols-outlined { font-size: 14px; }
        .notice-pill.green { color: #4ade80; border-color: rgba(74,222,128,.2); background: rgba(74,222,128,.06); }
        .notice-pill.blue  { color: #60a5fa; border-color: rgba(96,165,250,.2); background: rgba(96,165,250,.06); }

        /* Precio badge según tipo */
        .precio-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .precio-badge.publico  { background: rgba(100,116,139,.12); color: #94a3b8; border: 1px solid rgba(100,116,139,.2); }
        .precio-badge.gremio   { background: rgba(245,158,11,.12);  color: #fbbf24; border: 1px solid rgba(245,158,11,.25); }
        .precio-badge.partner  { background: rgba(168,85,247,.12);  color: #c084fc; border: 1px solid rgba(168,85,247,.25); }

        /* ── LAYOUT: SIDEBAR + GRID ── */
        .catalog-layout {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 16px;
            gap: 24px;
            align-items: flex-start;
        }

        /* ── SIDEBAR FILTROS ── */
        .filter-sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            position: sticky;
            top: 76px;
        }

        .filter-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
        }

        .filter-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-title .material-symbols-outlined { font-size: 14px; }

        .filter-divider { height: 1px; background: var(--border); margin: 16px 0; }

        /* Categorías anidadas */
        .cat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            transition: all .15s;
            text-decoration: none;
        }
        .cat-item:hover, .cat-item.active {
            background: rgba(59,130,246,.1);
            color: var(--blue);
        }
        .cat-item .material-symbols-outlined { font-size: 15px; }

        .sub-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px 4px 28px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,.35);
            transition: all .15s;
            text-decoration: none;
        }
        .sub-item:hover, .sub-item.active {
            background: rgba(59,130,246,.08);
            color: rgba(59,130,246,.9);
        }
        .sub-item::before {
            content: '';
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        /* Select marcas */
        .filter-select {
            width: 100%;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 12px;
            color: var(--text);
            font-family: inherit;
            font-size: 12px;
            outline: none;
        }

        .btn-clear {
            width: 100%;
            padding: 8px;
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 10px;
            color: #f87171;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-clear:hover { background: rgba(239,68,68,.14); }

        /* ── GRID ── */
        .catalog-main { flex: 1; min-width: 0; }

        .catalog-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .catalog-count {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }
        .catalog-count strong { color: var(--text); }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }

        /* ── TARJETA PRODUCTO ── */
        .product-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform .25s, box-shadow .25s, border-color .25s;
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: rgba(59,130,246,.45);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,.6), 0 0 0 1px rgba(59,130,246,.15);
        }
        .product-card.sin-stock { opacity: .55; filter: grayscale(.7); }

        .card-img {
            aspect-ratio: 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            overflow: hidden;
        }
        .card-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform .4s;
        }
        .product-card:hover .card-img img { transform: scale(1.07); }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 14px 16px 16px;
            gap: 6px;
        }

        .card-brand {
            font-size: 10px;
            font-weight: 800;
            color: var(--blue);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .card-desc {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .card-sku {
            font-size: 10px;
            color: rgba(255,255,255,.3);
            font-family: 'Courier New', monospace;
        }

        .stock-bar {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 4px;
        }
        .stock-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .stock-dot.verde  { background: #4ade80; box-shadow: 0 0 6px rgba(74,222,128,.5); }
        .stock-dot.ambar  { background: #fbbf24; box-shadow: 0 0 6px rgba(251,191,36,.5); }
        .stock-dot.rojo   { background: #f87171; }
        .stock-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,.4); }

        .card-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
        }

        .price-block .price-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255,255,255,.35);
            margin-bottom: 2px;
        }
        .price-block .price-value {
            font-size: 18px;
            font-weight: 800;
            color: var(--blue);
            line-height: 1;
        }
        .price-block .price-notice {
            font-size: 9px;
            color: rgba(255,255,255,.25);
            margin-top: 2px;
        }
        .price-block .price-nodisp {
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,.3);
        }

        .btn-add {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--blue);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            transition: all .2s;
        }
        .btn-add:hover { background: var(--blue2); transform: scale(1.08); }
        .btn-add .material-symbols-outlined { font-size: 20px; }
        .btn-add:disabled { opacity: .4; cursor: not-allowed; }

        /* ── PAGINACIÓN ── */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 36px;
            flex-wrap: wrap;
        }
        .page-btn {
            min-width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all .15s;
            padding: 0 8px;
        }
        .page-btn:hover     { border-color: rgba(59,130,246,.4); color: var(--blue); }
        .page-btn.current   { background: var(--blue); border-color: var(--blue); color: #fff; }
        .page-btn.disabled  { opacity: .3; pointer-events: none; }

        /* ── CARRITO PANEL ── */
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            backdrop-filter: blur(4px);
            z-index: 80;
            display: none;
        }
        .cart-panel {
            position: fixed;
            right: 0;
            top: 0;
            height: 100%;
            width: 100%;
            max-width: 420px;
            background: var(--bg2);
            border-left: 1px solid var(--border);
            z-index: 90;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform .35s cubic-bezier(.4,0,.2,1);
        }
        .cart-panel.open  { transform: translateX(0); }

        .cart-hd {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-hd h3 { font-size: 16px; font-weight: 800; }
        .btn-close-cart {
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            display: flex;
        }
        .btn-close-cart:hover { color: #fff; }

        .cart-items { flex: 1; overflow-y: auto; padding: 16px 24px; display: flex; flex-direction: column; gap: 12px; }
        .cart-empty { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: var(--muted); }
        .cart-empty .material-symbols-outlined { font-size: 48px; }

        .cart-item {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            display: flex;
            gap: 12px;
        }
        .cart-item-img {
            width: 52px;
            height: 52px;
            background: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            flex-shrink: 0;
        }
        .cart-item-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-desc { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cart-item-price { font-size: 12px; font-weight: 700; color: var(--blue); margin-top: 2px; }
        .cart-item-sku { font-size: 10px; color: var(--muted); font-family: monospace; }
        .cart-qty-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
        .qty-btn { width: 22px; height: 22px; background: rgba(255,255,255,.07); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; color: #fff; }
        .qty-btn:hover { background: rgba(59,130,246,.2); }
        .qty-val { font-size: 13px; font-weight: 700; min-width: 20px; text-align: center; }
        .del-btn { margin-left: auto; background: none; border: none; color: rgba(248,113,113,.6); cursor: pointer; display: flex; }
        .del-btn:hover { color: #f87171; }

        .cart-ft {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            background: rgba(0,0,0,.3);
        }
        .cart-totals { margin-bottom: 14px; }
        .cart-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 13px; color: var(--muted); }
        .cart-total-row strong { color: var(--text); }
        .cart-total-final { font-size: 20px; font-weight: 800; color: var(--blue); }
        .cart-dolar { font-size: 11px; color: rgba(255,255,255,.3); margin-top: 4px; }

        .btn-wa {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border: none;
            border-radius: 14px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-wa:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -6px rgba(37,211,102,.5); }
        .btn-wa .material-symbols-outlined { font-size: 20px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .filter-sidebar { display: none; }
            .hero-banner { padding: 24px 16px 20px; }
            .catalog-layout { padding: 16px; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        }
    </style>
</head>

<body>

<!-- ── HEADER ── -->
<header class="cat-header">
    <a href="index.php" class="cat-logo">
        <div class="shield">
            <span class="material-symbols-outlined">shield</span>
        </div>
        <div class="brand-text">
            <span>Vecinos Seguros</span>
            <span>Catálogo Web</span>
        </div>
    </a>

    <div class="header-search">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="searchInput" placeholder="Buscar por marca, SKU o descripción…"
               value="<?= htmlspecialchars($filterSearch) ?>"
               onkeydown="if(event.key==='Enter')applySearch()">
    </div>

    <div class="header-actions">
        <?php if ($isLoggedIn): ?>
            <div class="precio-badge <?= $tipoCliente ?>">
                <span class="material-symbols-outlined" style="font-size:14px;">label</span>
                <?= htmlspecialchars($precioLabel) ?>
            </div>
            <a href="mi_cuenta.php" class="btn-sm btn-ghost" title="Mi cuenta">
                <span class="material-symbols-outlined" style="font-size:16px;">manage_accounts</span>
                Mi cuenta
            </a>
            <a href="logout.php" class="btn-sm btn-ghost">
                <span class="material-symbols-outlined" style="font-size:16px;">logout</span>
                Salir
            </a>
        <?php else: ?>
            <a href="login.php" class="btn-sm btn-ghost">
                <span class="material-symbols-outlined" style="font-size:16px;">login</span>
                Ingresar
            </a>
        <?php endif; ?>
        <button class="btn-sm btn-cart" onclick="toggleCart()" id="cartBtn">
            <span class="material-symbols-outlined" style="font-size:18px;">shopping_bag</span>
            <span id="cartBadge" class="cart-badge" style="display:none;">0</span>
        </button>
    </div>
</header>

<!-- ── HERO ── -->
<section class="hero-banner">
    <h1>Catálogo de productos <span class="hl">Vecinos Seguros</span></h1>
    <div class="notice-bar">
        <span class="notice-pill">
            <span class="material-symbols-outlined">info</span>
            El stock y los precios pueden variar sin previo aviso. Consultar antes de confirmar sus presupuestos.
        </span>
        <span class="notice-pill green">
            <span class="material-symbols-outlined">local_shipping</span>
            Envíos Gratis a todo el país mediante Drop Shipping
        </span>
        <span class="notice-pill blue">
            <span class="material-symbols-outlined">currency_exchange</span>
            Dólar oficial hoy: $<?= number_format($dolar, 2, ',', '.') ?>
        </span>
        <?php if (!$isLoggedIn): ?>
        <a href="login.php" class="notice-pill" style="color:#fbbf24;border-color:rgba(251,191,36,.2);background:rgba(251,191,36,.06);text-decoration:none;">
            <span class="material-symbols-outlined">lock_open</span>
            Iniciá sesión para ver precio Gremio o Partner
        </a>
        <?php endif; ?>
    </div>
</section>

<!-- ── LAYOUT ── -->
<div class="catalog-layout">

    <!-- SIDEBAR FILTROS -->
    <aside class="filter-sidebar">
        <div class="filter-card">
            <div class="filter-title">
                <span class="material-symbols-outlined">filter_list</span>
                Filtros
            </div>

            <!-- Categorías anidadas -->
            <div class="filter-title" style="margin-bottom:8px;">Categorías</div>
            <nav>
                <a href="<?= '?' . http_build_query(array_merge($_GET, ['cat'=>'','sub'=>'','page'=>1])) ?>"
                   class="cat-item <?= (!$filterCat) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">apps</span>
                    Todas
                </a>
                <?php foreach ($catTree as $cat => $subs): ?>
                    <a href="<?= '?' . http_build_query(array_merge($_GET, ['cat'=>$cat,'sub'=>'','page'=>1])) ?>"
                       class="cat-item <?= (strtolower($filterCat) === strtolower($cat) && !$filterSub) ? 'active' : '' ?>">
                        <span class="material-symbols-outlined">folder</span>
                        <?= htmlspecialchars($cat) ?>
                    </a>
                    <?php foreach ($subs as $sub): ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['cat'=>$cat,'sub'=>$sub,'page'=>1])) ?>"
                           class="sub-item <?= (strtolower($filterCat)===strtolower($cat) && strtolower($filterSub)===strtolower($sub)) ? 'active' : '' ?>">
                            <?= htmlspecialchars($sub) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>

            <div class="filter-divider"></div>

            <!-- Marca -->
            <div class="filter-title" style="margin-bottom:8px;">Marca</div>
            <select class="filter-select" id="brandSelect" onchange="applyBrand(this.value)">
                <option value="">Todas las marcas</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>"
                        <?= (strtolower($filterBrand) === strtolower($b)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($filterCat || $filterSub || $filterBrand || $filterSearch): ?>
                <a href="catalogo_web.php" class="btn-clear">
                    <span class="material-symbols-outlined" style="font-size:14px;">close</span>
                    Limpiar filtros
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- MAIN GRID -->
    <div class="catalog-main">

        <div class="catalog-toolbar">
            <p class="catalog-count">
                Mostrando <strong><?= count($pageProds) ?></strong> de <strong><?= $totalProds ?></strong>
                productos
                <?php if ($filterCat): ?>
                    en <strong><?= htmlspecialchars($filterCat) ?><?= $filterSub ? ' › ' . htmlspecialchars($filterSub) : '' ?></strong>
                <?php endif; ?>
            </p>
            <span class="precio-badge <?= $tipoCliente ?>" style="font-size:10px;">
                <?= htmlspecialchars($precioLabel) ?> · No incluye impuestos
            </span>
        </div>

        <?php if (empty($pageProds)): ?>
            <div style="padding:60px;text-align:center;color:var(--muted);">
                <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:12px;">search_off</span>
                <p style="font-weight:700;">No se encontraron productos con los filtros seleccionados.</p>
                <a href="catalogo_web.php" style="color:var(--blue);font-size:13px;margin-top:8px;display:inline-block;">Limpiar filtros</a>
            </div>
        <?php else: ?>

        <div class="products-grid" id="productGrid">
            <?php foreach ($pageProds as $p):
                $stock  = (int)($p['stock_current'] ?? 0);
                $precio = getPrecio($p, $priceCol);
                $imgUrl = !empty($p['image_url']) ? $p['image_url'] : 'src/img/VSLogo_v2.jpg';

                // Semáforo stock
                if ($stock <= 0)        { $dotClass = 'rojo';  $stockTxt = 'Sin stock'; }
                elseif ($stock <= 5)    { $dotClass = 'ambar'; $stockTxt = "Stock: $stock ud."; }
                else                    { $dotClass = 'verde'; $stockTxt = "Stock: $stock ud."; }

                // Datos para carrito (precio ARS sin IVA — el IVA se discrimina en el carrito)
                $cartData = json_encode([
                    'sku'         => $p['sku'],
                    'description' => $p['description'],
                    'image'       => $imgUrl,
                    'price'       => $precio,
                    'iva'         => (float)($p['iva_rate'] ?? 21),
                    'brand'       => $p['brand'] ?? '',
                ]);
            ?>
                <div class="product-card <?= $stock <= 0 ? 'sin-stock' : '' ?>">
                    <div class="card-img">
                        <img src="<?= htmlspecialchars($imgUrl) ?>"
                             alt="<?= htmlspecialchars($p['description']) ?>"
                             loading="lazy"
                             onerror="this.src='src/img/VSLogo_v2.jpg'">
                    </div>
                    <div class="card-body">
                        <div class="card-brand"><?= htmlspecialchars($p['brand'] ?? '') ?></div>
                        <div class="card-desc"><?= htmlspecialchars($p['description']) ?></div>
                        <div class="card-sku"><?= htmlspecialchars($p['sku']) ?></div>

                        <div class="stock-bar">
                            <div class="stock-dot <?= $dotClass ?>"></div>
                            <span class="stock-label"><?= $stockTxt ?></span>
                        </div>

                        <div class="card-footer">
                            <div class="price-block">
                                <div class="price-label"><?= htmlspecialchars($precioLabel) ?></div>
                                <?php if ($precio !== null): ?>
                                    <div class="price-value"><?= fmtArs($precio) ?></div>
                                    <div class="price-notice">No incluye impuestos</div>
                                <?php else: ?>
                                    <div class="price-nodisp">Consultar precio</div>
                                <?php endif; ?>
                            </div>
                            <button class="btn-add"
                                    onclick='addToCart(<?= $cartData ?>)'
                                    <?= ($precio === null) ? 'disabled title="Precio no disponible"' : '' ?>>
                                <span class="material-symbols-outlined">add_shopping_cart</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPages > 1):
            $qBase = array_merge($_GET, []);
            unset($qBase['page']);
        ?>
        <div class="pagination">
            <?php
            $prevPage = $page - 1;
            $nextPage = $page + 1;
            ?>
            <a href="?<?= http_build_query(array_merge($qBase, ['page' => $prevPage])) ?>"
               class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
            </a>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1) echo '<span class="page-btn disabled">…</span>';
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?<?= http_build_query(array_merge($qBase, ['page' => $i])) ?>"
                   class="page-btn <?= ($i === $page) ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor;
            if ($end < $totalPages) echo '<span class="page-btn disabled">…</span>';
            ?>
            <a href="?<?= http_build_query(array_merge($qBase, ['page' => $nextPage])) ?>"
               class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
            </a>
        </div>
        <?php endif; ?>

        <?php endif; // empty products ?>
    </div><!-- end catalog-main -->
</div><!-- end catalog-layout -->

<!-- ── PANEL CARRITO ── -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<aside class="cart-panel" id="cartPanel">
    <div class="cart-hd">
        <h3><span class="material-symbols-outlined" style="vertical-align:middle;font-size:18px;color:var(--blue);margin-right:6px;">shopping_basket</span>Carrito</h3>
        <button class="btn-close-cart" onclick="toggleCart()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty">
            <span class="material-symbols-outlined">shopping_cart_off</span>
            <p style="font-size:13px;font-weight:600;">El carrito está vacío</p>
        </div>
    </div>
    <div class="cart-ft" id="cartFt" style="display:none;">
        <div class="cart-totals" id="cartTotals"></div>
        <a href="#" class="btn-wa" id="waBtn" onclick="consultarPorWA(event)">
            <span class="material-symbols-outlined">chat</span>
            Consultar por WhatsApp
        </a>
        <p style="font-size:10px;color:var(--muted);text-align:center;margin-top:8px;">
            El precio final puede variar. Aguardar confirmación.
        </p>
    </div>
</aside>

<script>
// ── DATOS DEL SERVIDOR ──────────────────────────────────────────
const DOLAR     = <?= $dolar ?>;
const WA_NUMBER = '<?= $waNumber ?>';
const HOY       = '<?= $hoy ?>';

// ── CARRITO ────────────────────────────────────────────────────
let cart = JSON.parse(localStorage.getItem('vs_cart') || '[]');

function saveCart() {
    localStorage.setItem('vs_cart', JSON.stringify(cart));
}

function addToCart(p) {
    if (p.price === null) return;
    const idx = cart.findIndex(i => i.sku === p.sku);
    if (idx >= 0) cart[idx].qty++;
    else cart.push({ ...p, qty: 1 });
    saveCart();
    renderCart();
    // Abrir panel
    document.getElementById('cartPanel').classList.add('open');
    document.getElementById('cartOverlay').style.display = 'block';
}

function changeQty(sku, delta) {
    const idx = cart.findIndex(i => i.sku === sku);
    if (idx < 0) return;
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
    saveCart();
    renderCart();
}

function removeItem(sku) {
    cart = cart.filter(i => i.sku !== sku);
    saveCart();
    renderCart();
}

function renderCart() {
    const items    = document.getElementById('cartItems');
    const ft       = document.getElementById('cartFt');
    const totals   = document.getElementById('cartTotals');
    const badge    = document.getElementById('cartBadge');
    const totalQty = cart.reduce((a, i) => a + i.qty, 0);

    // Badge
    badge.textContent = totalQty;
    badge.style.display = totalQty > 0 ? 'flex' : 'none';

    if (cart.length === 0) {
        items.innerHTML = `<div class="cart-empty"><span class="material-symbols-outlined">shopping_cart_off</span><p style="font-size:13px;font-weight:600;">El carrito está vacío</p></div>`;
        ft.style.display = 'none';
        return;
    }

    // Items
    items.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-img">
                <img src="${item.image}" onerror="this.src='src/img/VSLogo_v2.jpg'">
            </div>
            <div class="cart-item-info">
                <div class="cart-item-desc">${item.description}</div>
                <div class="cart-item-sku">${item.sku}</div>
                <div class="cart-item-price">$ ${fmtNum(item.price * item.qty)} <small style="font-size:9px;color:rgba(255,255,255,.3)">sin IVA</small></div>
                <div class="cart-qty-row">
                    <button class="qty-btn" onclick="changeQty('${item.sku}', -1)">−</button>
                    <span class="qty-val">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty('${item.sku}', 1)">+</button>
                    <button class="del-btn" onclick="removeItem('${item.sku}')">
                        <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    // Totales con IVA discriminado
    const ivaGrupos = {};
    let totalSinIva = 0;
    let totalConIva = 0;

    cart.forEach(item => {
        const base = item.price * item.qty;
        const ivaRate = item.iva || 21;
        const ivaAmt  = base * (ivaRate / 100);
        totalSinIva += base;
        totalConIva += base + ivaAmt;
        if (!ivaGrupos[ivaRate]) ivaGrupos[ivaRate] = 0;
        ivaGrupos[ivaRate] += ivaAmt;
    });

    let ivaHTML = Object.entries(ivaGrupos).map(([rate, amt]) =>
        `<div class="cart-total-row"><span>IVA ${rate}%</span><strong>$ ${fmtNum(amt)}</strong></div>`
    ).join('');

    totals.innerHTML = `
        <div class="cart-total-row"><span>Subtotal</span><strong>$ ${fmtNum(totalSinIva)}</strong></div>
        ${ivaHTML}
        <div class="cart-total-row" style="padding-top:8px;border-top:1px solid var(--border);margin-top:8px;">
            <span style="font-weight:700;color:var(--text);">Total ARS</span>
            <span class="cart-total-final">$ ${fmtNum(totalConIva)}</span>
        </div>
        <div class="cart-dolar">≈ USD ${(totalConIva / DOLAR).toFixed(2)} · Dólar oficial $ ${fmtNum(DOLAR)}</div>
    `;
    ft.style.display = 'block';
}

function fmtNum(n) {
    return Math.round(n).toLocaleString('es-AR');
}

function toggleCart() {
    const panel   = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');
    const open    = panel.classList.toggle('open');
    overlay.style.display = open ? 'block' : 'none';
}

// ── CONSULTAR POR WHATSAPP + GUARDAR EN SISTEMA ───────────────
async function consultarPorWA(e) {
    e.preventDefault();
    if (cart.length === 0) return;

    const btn = document.getElementById('waBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Registrando consulta...`;
    btn.style.pointerEvents = 'none';

    let quoteNumber = null;

    // 1. Guardar en el sistema
    try {
        const payload = {
            items:       cart.map(i => ({ ...i })),
            dolar:       DOLAR,
            tipo_cliente: document.querySelector('.precio-badge')?.classList[1] || 'publico',
            client: {
                logged: <?= $isLoggedIn ? 'true' : 'false' ?>,
                name:   '<?= addslashes($me['name'] ?? '') ?>',
                email:  '<?= addslashes($me['email'] ?? '') ?>',
            }
        };
        const res  = await fetch('ajax_catalog_checkout.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success && data.quote_number) {
            quoteNumber = data.quote_number;
        }
    } catch (err) {
        // No bloquear el WA por error de red
        console.warn('Checkout DB error:', err);
    }

    btn.innerHTML = originalContent;
    btn.style.pointerEvents = '';

    // 2. Construir mensaje WhatsApp
    let totalSinIva = 0;
    cart.forEach(i => totalSinIva += i.price * i.qty);
    const totalConIva = totalSinIva + cart.reduce((a,i) => a + i.price*i.qty*(i.iva||21)/100, 0);

    let msg = `*CONSULTA DE PEDIDO — Vecinos Seguros*\n`;
    if (quoteNumber) msg += `N° Consulta: *#${quoteNumber}*\n`;
    msg += `Fecha: ${HOY} | Dólar oficial: $${fmtNum(DOLAR)}\n\n`;
    msg += `*Detalle:*\n`;

    cart.forEach(item => {
        const sub = item.price * item.qty;
        const iva = sub * ((item.iva || 21) / 100);
        msg += `• ${item.sku} | ${item.description}\n`;
        msg += `  Cant: ${item.qty} × $${fmtNum(item.price)} = $${fmtNum(sub)} + IVA $${fmtNum(iva)}\n`;
    });

    msg += `\n*Total ARS (con IVA): $${fmtNum(totalConIva)}*\n`;
    msg += `≈ USD ${(totalConIva / DOLAR).toFixed(2)}\n`;
    msg += `\n_Precios sujetos a verificación de stock. Aguardar confirmación._`;

    window.open(`https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(msg)}`, '_blank');

    // 3. Toast informativo
    if (quoteNumber) {
        const toast = document.createElement('div');
        toast.innerHTML = `<strong>Consulta #${quoteNumber} registrada</strong><br><small>Podés verla en "Mi cuenta" una vez que iniciés sesión.</small>`;
        toast.style.cssText = `
            position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;
            background:#16202e;border:1px solid rgba(59,130,246,.35);color:#93c5fd;
            padding:14px 22px;border-radius:14px;font-size:13px;font-weight:600;
            text-align:center;line-height:1.5;box-shadow:0 20px 40px rgba(0,0,0,.6);
            transition:opacity .4s;max-width:320px;width:90%;
        `;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 5000);
    }
}

// ── FILTROS ────────────────────────────────────────────────────
function applySearch() {
    const q = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    if (q) url.searchParams.set('q', q);
    else url.searchParams.delete('q');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function applyBrand(val) {
    const url = new URL(window.location.href);
    if (val) url.searchParams.set('brand', val);
    else url.searchParams.delete('brand');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

document.getElementById('searchInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') applySearch();
});

// Init
renderCart();
</script>

</body>
</html>