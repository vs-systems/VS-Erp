<?php
/**
 * VS System — Login Unificado
 * Bloque 5: redirección por tipo_cliente + carga en sesión
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\User;
use Vsys\Lib\Database;

$userAuth = new User();
$error    = '';
$dest     = $_GET['dest'] ?? ''; // destino post-login opcional: 'catalogo'

// Ya logueado
if ($userAuth->isLoggedIn()) {
    header('Location: ' . _redirectByRole());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($userAuth->login($username, $password)) {
        // Cargar tipo_cliente en sesión
        $db     = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $stmt = $db->prepare(
            "SELECT e.id, e.tipo_cliente, e.client_profile, u.role
             FROM users u
             LEFT JOIN entities e ON u.entity_id = e.id
             WHERE u.id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        $role        = $row['role']         ?? $_SESSION['role']   ?? 'client';
        $tipoCliente = $row['tipo_cliente'] ?? 'publico';
        $entityId    = $row['id']           ?? null;

        // Guardar en sesión para que el catálogo lo use directamente
        $_SESSION['tipo_cliente'] = $tipoCliente;
        $_SESSION['entity_id']    = $entityId;

        header('Location: ' . _redirectByRole($role, $tipoCliente, $dest));
        exit;
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}

/**
 * Determina a qué URL redirigir según rol y tipo de cliente.
 */
function _redirectByRole($role = null, $tipo = null, $dest = '') {
    $role = $role ?? $_SESSION['role'] ?? 'client';
    $tipo = $tipo ?? $_SESSION['tipo_cliente'] ?? 'publico';

    // Si se vino desde el catálogo, volver ahí
    if ($dest === 'catalogo') return 'catalogo_web.php';

    // Admins/vendedores → panel interno
    if (in_array($role, ['admin', 'seller', 'operator'])) return 'dashboard.php';

    // Clientes externos → Mi Cuenta (desde ahí pueden ir al catálogo)
    return 'mi_cuenta.php';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — Vecinos Seguros</title>
    <meta name="description" content="Accedé a tu cuenta en Vecinos Seguros para ver tus precios exclusivos de Gremio o Partner.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #050d1a;
            --card:     #0b1628;
            --border:   rgba(255,255,255,.08);
            --blue:     #1a6ef5;
            --text:     rgba(255,255,255,.85);
            --muted:    rgba(255,255,255,.4);
            --input-bg: rgba(255,255,255,.04);
            --red:      #f87171;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 80% 60% at 15% -10%, rgba(26,110,245,.18) 0%, transparent 55%),
                radial-gradient(ellipse 55% 45% at 85% 105%, rgba(6,182,212,.12) 0%, transparent 50%),
                var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: var(--text);
        }

        /* ── BACK LINK ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
            transition: color .2s;
        }
        .back-link:hover { color: var(--text); }
        .back-link .material-symbols-outlined { font-size: 16px; }

        /* ── CARD ── */
        .login-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 32px 80px -20px rgba(0,0,0,.65);
        }

        /* ── LOGO + HEADER ── */
        .card-top {
            text-align: center;
            margin-bottom: 32px;
        }

        .shield-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: rgba(26,110,245,.12);
            border: 1px solid rgba(26,110,245,.25);
            border-radius: 16px;
            margin-bottom: 16px;
            color: var(--blue);
        }
        .shield-icon .material-symbols-outlined { font-size: 28px; }

        .card-top h1 {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.4px;
            margin-bottom: 6px;
        }
        .card-top p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ── FORM ── */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .04em;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input::placeholder { color: rgba(255,255,255,.2); }
        input:focus {
            border-color: rgba(26,110,245,.5);
            box-shadow: 0 0 0 3px rgba(26,110,245,.12);
        }

        /* ── ERROR ── */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            color: var(--red);
            margin-bottom: 20px;
        }
        .alert-error .material-symbols-outlined { font-size: 18px; flex-shrink: 0; }

        /* ── SUBMIT ── */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--blue) 0%, #0f4fc9 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 8px 24px -8px rgba(26,110,245,.55);
            letter-spacing: .02em;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px -8px rgba(26,110,245,.65);
        }
        .btn-login .material-symbols-outlined { font-size: 18px; }

        /* ── DIVIDER ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: rgba(255,255,255,.12);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.06);
        }

        /* ── INFO TIPOS ── */
        .tipos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }
        .tipo-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 10px 6px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            line-height: 1.3;
        }
        .tipo-pill .material-symbols-outlined { font-size: 18px; }
        .tipo-pill.publico { color: #94a3b8; }
        .tipo-pill.gremio  { color: #fbbf24; }
        .tipo-pill.partner { color: #c084fc; }

        /* ── FOOTER ── */
        .card-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }
        .card-footer a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 600;
        }
        .card-footer a:hover { text-decoration: underline; }
        .card-footer .sep { margin: 0 8px; color: rgba(255,255,255,.1); }

        /* ── RECUPERAR ── */
        .forgot-link {
            display: block;
            text-align: right;
            font-size: 12px;
            color: var(--muted);
            text-decoration: none;
            margin-top: -10px;
            margin-bottom: 16px;
            transition: color .2s;
        }
        .forgot-link:hover { color: var(--blue); }

        @media (max-width: 480px) {
            .login-card { padding: 32px 22px; }
            .tipos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <a href="index.php" class="back-link">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver al inicio
    </a>

    <div class="login-card">

        <!-- Header -->
        <div class="card-top">
            <div class="shield-icon">
                <span class="material-symbols-outlined">shield</span>
            </div>
            <h1>Ingresá a tu cuenta</h1>
            <p>Accedé a tu lista de precios exclusiva<br>según tu perfil de cliente.</p>
        </div>

        <!-- Info tipos (solo si viene desde catálogo o sin destino) -->
        <?php if (empty($dest) || $dest === 'catalogo'): ?>
        <div class="tipos-grid">
            <div class="tipo-pill publico">
                <span class="material-symbols-outlined">person</span>
                Público<br>PVP
            </div>
            <div class="tipo-pill gremio">
                <span class="material-symbols-outlined">construction</span>
                Gremio<br>P. Especial
            </div>
            <div class="tipo-pill partner">
                <span class="material-symbols-outlined">handshake</span>
                Partner<br>P. Partner
            </div>
        </div>
        <?php endif; ?>

        <!-- Alerta error -->
        <?php if ($error): ?>
            <div class="alert-error">
                <span class="material-symbols-outlined">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" novalidate id="loginForm">
            <?php if ($dest): ?>
                <input type="hidden" name="dest" value="<?= htmlspecialchars($dest) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Email / Usuario</label>
                <input
                    type="email"
                    id="username"
                    name="username"
                    placeholder="nombre@tuempresa.com"
                    required
                    autofocus
                    autocomplete="email"
                    inputmode="email"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <a href="recover_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>

            <button type="submit" class="btn-login" id="btnLogin">
                <span class="material-symbols-outlined">login</span>
                Ingresar
            </button>
        </form>

        <div class="divider">o</div>

        <div class="card-footer">
            ¿No tenés cuenta?
            <a href="registro.php">Registrate como Gremio</a>
            <span class="sep">·</span>
            <a href="catalogo_web.php">Ver catálogo sin cuenta</a>
        </div>

    </div>

    <script>
        // Mostrar spinner en el botón al enviar
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('btnLogin');
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     style="animation:spin .7s linear infinite">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                Verificando...
            `;
            btn.disabled = true;
        });
    </script>
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

</body>
</html>