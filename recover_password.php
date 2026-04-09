<?php
/**
 * Recuperar Contraseña — Vecinos Seguros
 * Bloque 9: flujo por email (username = email), diseño unificado
 *
 * Paso 1: El usuario ingresa su email → se genera token y se envía link
 * Paso 2: El usuario abre el link → ingresa nueva contraseña
 * (Si SMTP no está configurado → muestra contraseña temporal al operador)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Mailer.php';

use Vsys\Lib\Database;
use Vsys\Lib\Mailer;

$db      = Database::getInstance();
$step    = 'request';   // 'request' | 'reset' | 'done'
$message = '';
$status  = '';

// ──────────────────────────────────────────────────────────────────
// PASO 2: El usuario llega con el token desde el email
// ──────────────────────────────────────────────────────────────────
$token = trim($_GET['token'] ?? '');
if ($token) {
    $step = 'reset';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $newPass  = $_POST['new_password']  ?? '';
        $newPass2 = $_POST['new_password2'] ?? '';
        $postTok  = $_POST['token']         ?? '';

        if ($postTok !== $token) {
            $message = 'Token inválido. Volvé a solicitar el link.';
            $status  = 'error';
        } elseif (strlen($newPass) < 8) {
            $message = 'La contraseña debe tener al menos 8 caracteres.';
            $status  = 'error';
        } elseif ($newPass !== $newPass2) {
            $message = 'Las contraseñas no coinciden.';
            $status  = 'error';
        } else {
            // Verificar token válido y no vencido (1 hora)
            $stmt = $db->prepare(
                "SELECT id FROM users
                 WHERE reset_token = ?
                   AND reset_token_expires > NOW()
                   AND status = 'Active'"
            );
            $stmt->execute([$token]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                $message = 'El link expiró o ya fue utilizado. Solicitá uno nuevo.';
                $status  = 'error';
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $db->prepare(
                    "UPDATE users
                     SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL
                     WHERE id = ?"
                )->execute([$hash, $userId]);

                $step    = 'done';
                $message = '¡Contraseña actualizada! Ya podés ingresar con tu nueva clave.';
                $status  = 'success';
            }
        }
    }
}

// ──────────────────────────────────────────────────────────────────
// PASO 1: El usuario solicita el link por email
// ──────────────────────────────────────────────────────────────────
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Ingresá un email válido.';
        $status  = 'error';
    } else {
        // Buscar usuario por username (= email en el nuevo sistema)
        $stmt = $db->prepare(
            "SELECT u.id, u.username, e.email AS entity_email, e.name AS entity_name
             FROM users u
             LEFT JOIN entities e ON u.entity_id = e.id
             WHERE LOWER(u.username) = ?
               AND u.status = 'Active'"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Respuesta genérica para no revelar si el email existe o no
        $message = 'Si el email está registrado, recibirás las instrucciones en breve.';
        $status  = 'success';

        if ($user) {
            // Generar token único (32 bytes → 64 chars hex)
            $resetToken   = bin2hex(random_bytes(32));
            $tokenExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Guardar token (agregar columnas si no existen — lo hace el SQL B9)
            try {
                $db->prepare(
                    "UPDATE users
                     SET reset_token = ?, reset_token_expires = ?
                     WHERE id = ?"
                )->execute([$resetToken, $tokenExpires, $user['id']]);
            } catch (\Exception $e) {
                // Si las columnas no existen aún → fallback con contraseña temporal
                $tempPass = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 10);
                $db->prepare(
                    "UPDATE users SET password_hash = ? WHERE id = ?"
                )->execute([password_hash($tempPass, PASSWORD_DEFAULT), $user['id']]);

                $message = 'Se generó una contraseña temporal. Por favor consultá con el administrador.';
                error_log("[RecoverPass fallback] Usuario {$user['id']} — temp: $tempPass");
                goto endRequest;
            }

            // Construir link de reseteo
            $host     = $_SERVER['HTTP_HOST'] ?? 'vecinoseguro.com.ar';
            $resetUrl = "https://$host/recover_password.php?token=$resetToken";
            $destEmail = $user['entity_email'] ?: $user['username'];
            $name      = $user['entity_name']  ?: $user['username'];

            $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="background:#0d1117;font-family:'Helvetica Neue',Arial,sans-serif;margin:0;padding:0;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;margin:0 auto;padding:40px 16px;">
    <tr><td>
      <table width="100%" style="background:#111827;border:1px solid #233348;border-radius:16px;padding:32px;">
        <tr><td>
          <div style="background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.25);border-radius:10px;display:inline-block;padding:6px 14px;font-size:11px;font-weight:700;color:#60a5fa;letter-spacing:.06em;margin-bottom:20px;">🔑 RECUPERAR CONTRASEÑA</div>
          <h2 style="color:#fff;font-size:20px;font-weight:800;margin:0 0 10px;">Restablecé tu contraseña</h2>
          <p style="color:#94a3b8;font-size:14px;margin:0 0 24px;line-height:1.6;">Hola <strong style="color:#fff;">{$name}</strong> — recibimos una solicitud para restablecer tu contraseña en Vecinos Seguros.</p>

          <a href="{$resetUrl}"
             style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:700;font-size:14px;margin-bottom:20px;">
             Crear nueva contraseña →
          </a>

          <p style="color:#64748b;font-size:12px;margin:0;line-height:1.7;">
            Este link expira en <strong style="color:#94a3b8;">1 hora</strong>.<br>
            Si no solicitaste el cambio, podés ignorar este email. Tu contraseña no será modificada.
          </p>
        </td></tr>
      </table>
      <p style="text-align:center;color:#374151;font-size:11px;margin-top:16px;">Vecinos Seguros · No respondas este email</p>
    </td></tr>
  </table>
</body>
</html>
HTML;

            try {
                $mailer = new Mailer();
                $mailer->send($destEmail, 'Restablecé tu contraseña — Vecinos Seguros', $emailBody);
            } catch (\Exception $e) {
                error_log("[RecoverPass] Error enviando email a $destEmail: " . $e->getMessage());
                // El mensaje al usuario sigue siendo el genérico (no revelar error)
            }
        }
    }
    endRequest:;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Vecinos Seguros</title>
    <meta name="description" content="Restablecé tu contraseña de acceso al catálogo de Vecinos Seguros.">
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
            --green:    #4ade80;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 70% 50% at 20% -5%, rgba(26,110,245,.15) 0%, transparent 55%),
                radial-gradient(ellipse 50% 40% at 80% 110%, rgba(6,182,212,.1) 0%, transparent 50%),
                var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: var(--text);
        }

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

        .rec-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 32px 80px -20px rgba(0,0,0,.65);
        }

        .card-top {
            text-align: center;
            margin-bottom: 30px;
        }
        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            background: rgba(26,110,245,.1);
            border: 1px solid rgba(26,110,245,.2);
            border-radius: 16px;
            color: var(--blue);
            margin-bottom: 16px;
        }
        .icon-wrap .material-symbols-outlined { font-size: 26px; }
        .card-top h1 { font-size: 21px; font-weight: 800; color: #fff; letter-spacing: -.4px; margin-bottom: 6px; }
        .card-top p  { font-size: 13px; color: var(--muted); line-height: 1.55; }

        .form-group { margin-bottom: 18px; }
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

        /* Strength meter */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            background: var(--border);
            margin-top: 6px;
            overflow: hidden;
        }
        .strength-fill { height: 100%; width: 0; transition: width .3s, background .3s; border-radius: 2px; }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .alert.success { background: rgba(74,222,128,.08); border: 1px solid rgba(74,222,128,.2); color: var(--green); }
        .alert.error   { background: rgba(248,113,113,.08); border: 1px solid rgba(248,113,113,.2); color: var(--red); }
        .alert .material-symbols-outlined { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .btn-submit {
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
            box-shadow: 0 8px 24px -8px rgba(26,110,245,.5);
            margin-top: 8px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 32px -8px rgba(26,110,245,.6); }

        .card-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }
        .card-footer a { color: var(--blue); text-decoration: none; font-weight: 600; }
        .card-footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) { .rec-card { padding: 32px 22px; } }
    </style>
</head>

<body>

    <a href="login.php" class="back-link">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver al login
    </a>

    <div class="rec-card">

        <!-- ── PASO COMPLETADO ── -->
        <?php if ($step === 'done'): ?>
            <div class="card-top">
                <div class="icon-wrap" style="background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.2);color:#4ade80;">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <h1>¡Contraseña actualizada!</h1>
                <p>Ya podés ingresar al sistema con tu nueva contraseña.</p>
            </div>
            <a href="login.php" class="btn-submit" style="text-decoration:none;margin-top:0;">
                <span class="material-symbols-outlined">login</span>
                Ir al login
            </a>

        <!-- ── PASO 2: NUEVA CONTRASEÑA ── -->
        <?php elseif ($step === 'reset'): ?>
            <div class="card-top">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">lock_reset</span>
                </div>
                <h1>Nueva contraseña</h1>
                <p>Ingresá tu nueva contraseña. Mínimo 8 caracteres.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $status ?>">
                    <span class="material-symbols-outlined"><?= $status === 'success' ? 'check_circle' : 'error' ?></span>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($status !== 'success'): ?>
            <form method="POST" id="resetForm" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Mínimo 8 caracteres"
                        required
                        minlength="8"
                        autofocus
                        autocomplete="new-password"
                        oninput="calcStrength(this.value)"
                    >
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                </div>
                <div class="form-group">
                    <label for="new_password2">Repetir contraseña</label>
                    <input
                        type="password"
                        id="new_password2"
                        name="new_password2"
                        placeholder="Confirmá la contraseña"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    <span class="material-symbols-outlined">lock</span>
                    Guardar nueva contraseña
                </button>
            </form>
            <script>
                function calcStrength(v) {
                    let s = 0;
                    if (v.length >= 8)   s += 30;
                    if (v.length >= 12)  s += 20;
                    if (/[A-Z]/.test(v)) s += 15;
                    if (/[0-9]/.test(v)) s += 15;
                    if (/[^a-zA-Z0-9]/.test(v)) s += 20;
                    const fill = document.getElementById('strengthFill');
                    fill.style.width = s + '%';
                    fill.style.background = s < 40 ? '#f87171' : s < 70 ? '#fbbf24' : '#4ade80';
                }
                document.getElementById('resetForm').addEventListener('submit', function(e) {
                    const p1 = document.getElementById('new_password').value;
                    const p2 = document.getElementById('new_password2').value;
                    if (p1 !== p2) {
                        e.preventDefault();
                        alert('Las contraseñas no coinciden.');
                    }
                });
            </script>
            <?php endif; ?>

        <!-- ── PASO 1: SOLICITAR LINK ── -->
        <?php else: ?>
            <div class="card-top">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">key</span>
                </div>
                <h1>Recuperar contraseña</h1>
                <p>Ingresá el email con el que te registraste y te enviamos el link para crear una nueva clave.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $status ?>">
                    <span class="material-symbols-outlined"><?= $status === 'success' ? 'mark_email_read' : 'error' ?></span>
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php if ($status === 'success'): ?>
                    <div class="card-footer">
                        <a href="login.php">Volver al login</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($status !== 'success'): ?>
            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="email">
                        Tu email / usuario de acceso
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nombre@tuempresa.com"
                        required
                        autofocus
                        autocomplete="email"
                        inputmode="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>
                <button type="submit" class="btn-submit">
                    <span class="material-symbols-outlined">send</span>
                    Enviar link de recuperación
                </button>
            </form>
            <?php endif; ?>

            <div class="card-footer" style="margin-top:20px;">
                ¿Recordaste la clave? <a href="login.php">Ingresá aquí</a>
                <span style="margin:0 6px;color:rgba(255,255,255,.1);">·</span>
                <a href="catalogo_web.php">Ver catálogo</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>