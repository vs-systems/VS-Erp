<?php
/**
 * VS System ERP - Password Recovery
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Mailer.php';

use Vsys\Lib\Database;
use Vsys\Lib\Mailer;

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    $username = $_POST['username']; // For clients, this is usually their CUIT

    $stmt = $db->prepare("SELECT u.*, e.email FROM users u LEFT JOIN entities e ON u.entity_id = e.id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['email']) {
        // In a real scenario, we'd generate a token. For now, we'll send a temporary password or a simple "Check your email"
        $tempPass = bin2hex(random_bytes(4));
        $hash = password_hash($tempPass, PASSWORD_DEFAULT);

        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);

        try {
            $mailer = new Mailer();
            $body = "
                <h2>Recuperación de Contraseña - VS System</h2>
                <p>Hola <strong>{$user['username']}</strong>,</p>
                <p>Tu nueva clave temporal es: <strong style='font-size:1.2rem; background:#f3f4f6; padding:5px 10px;'>$tempPass</strong></p>
                <p>Por favor, ingrese al sistema y cámbiela a la brevedad.</p>
                <br>
                <p>Atentamente,<br>VS Systems</p>
            ";
            $mailer->send($user['email'], "Recuperación de Contraseña", $body);
            $message = "Se ha enviado una clave temporal a su correo registrado.";
            $status = "success";
        } catch (Exception $e) {
            $message = "Error al enviar el correo. Por favor contacte a soporte.";
            $status = "error";
        }
    } else {
        $message = "Usuario no encontrado o sin correo asociado.";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <style>
        body {
            background: #020617;
            color: #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .recovery-card {
            background: #0f172a;
            padding: 2.5rem;
            border-radius: 12px;
            border: 1px solid #1e293b;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .btn-recover {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #8b5cf6, #d946ef);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="recovery-card">
        <h2 style="text-align:center; color:white; margin-bottom:2rem;">Recuperar Clave</h2>
        <?php if ($message): ?>
            <div
                style="padding:15px; border-radius:8px; background:<?php echo $status === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color:<?php echo $status === 'success' ? '#10b981' : '#ef4444'; ?>; margin-bottom:20px; text-align:center;">
                <?php echo $message; ?>
            </div>
            <div style="text-align:center;"><a href="login.php" style="color:#8b5cf6; text-decoration:none;">Volver al
                    Inicio</a></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>CUIT / Usuario</label>
                    <input type="text" name="username" placeholder="Ingrese su CUIT o Usuario" required autofocus>
                </div>
                <button type="submit" class="btn-recover">ENVIAR CLAVE TEMPORAL</button>
                <div style="text-align:center; margin-top:1.5rem;">
                    <a href="login.php" style="color:#64748b; font-size:0.85rem; text-decoration:none;">Cancelar y
                        volver</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>