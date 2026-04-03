<?php
/**
 * VS System ERP - Secure Login
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

use Vsys\Lib\User;

$userAuth = new User();
$error = '';

// Redirect if already logged in
if ($userAuth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($userAuth->login($username, $password)) {
        $db = Vsys\Lib\Database::getInstance();
        $userId = $_SESSION['user_id'];

        // Si el usuario tiene una entidad asociada, buscamos su perfil para redirigir al catálogo correcto
        $stmt = $db->prepare("SELECT e.client_profile FROM entities e JOIN users u ON u.entity_id = e.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetchColumn();

        if ($profile === 'GREMIO') {
            header('Location: catalogo.php');
        } elseif ($profile === 'WEB') {
            header('Location: catalogo_web.php');
        } elseif ($profile === 'PUBLICO') {
            header('Location: catalogo_publico.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VS System ERP</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #020617;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .login-card h2 {
            color: #fff;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
        }

        .error {
            color: #ef4444;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <img src="src/img/VSLogo_v2.jpg" alt="VS System" class="logo-large"
                style="max-height: 80px; width: auto; margin: 0 auto;">
        </div>
        <h2>Acceso VS System</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; color:#94a3b8; margin-bottom:0.5rem; font-size:0.875rem;">Usuario</label>
                <input type="text" name="username" required autofocus
                    style="width:100%; padding:0.75rem 1rem; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff; outline:none;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label
                    style="display:block; color:#94a3b8; margin-bottom:0.5rem; font-size:0.875rem;">Contraseña</label>
                <input type="password" name="password" required
                    style="width:100%; padding:0.75rem 1rem; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff; outline:none;">
            </div>
            <button type="submit" class="btn-login">INGRESAR</button>
        </form>

        <div style="margin-top:20px; text-align:center; font-size:0.85rem;">
            <a href="recover_password.php" style="color:#94a3b8; text-decoration:none;">¿Olvidó su clave?</a>
            <span style="color:#475569; margin: 0 10px;">|</span>
            <a href="registro.php" style="color:#8b5cf6; text-decoration:none; font-weight:700;">Crear cuenta nueva</a>
        </div>

        <a href="db_migrate_users.php"
            style="display:block; text-align:center; margin-top:2rem; color:#64748b; text-decoration:none; font-size:0.8rem;">¿Primera
            vez? Iniciar base de datos de usuarios</a>
    </div>
</body>

</html>