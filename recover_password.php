<?php
/**
 * recover_password.php — Recuperación de Contraseña sin SMTP
 * Vecino Seguro ERP
 *
 * Flujo:
 *  1. El usuario ingresa email + CUIT/DNI + fecha de nacimiento
 *  2. Si los 3 datos coinciden → se genera nueva clave VS{año}{6chars}
 *  3. Se muestra en pantalla (NO se envía por email)
 *  4. Se registra en client_credentials_log con action='reset'
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

use Vsys\Lib\Database;

$db      = Database::getInstance();
$step    = 'form';   // 'form' | 'success'
$message = '';
$status  = '';
$newPass = '';
$clientName = '';

// ──────────────────────────────────────────────────────────────────
// PROCESAR FORMULARIO
// ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = strtolower(trim($_POST['email']          ?? ''));
    $taxId      = preg_replace('/[^0-9]/', '', $_POST['tax_id'] ?? '');  // Solo dígitos
    $docNumber  = preg_replace('/[^0-9]/', '', $_POST['document_number'] ?? '');
    $birthDate  = trim($_POST['birth_date'] ?? '');

    // Validaciones básicas
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El email ingresado no es válido.';
        $status  = 'error';
    } elseif (empty($birthDate)) {
        $message = 'La fecha de nacimiento es obligatoria.';
        $status  = 'error';
    } elseif (empty($taxId) && empty($docNumber)) {
        $message = 'Debés ingresar tu CUIT o DNI para verificar tu identidad.';
        $status  = 'error';
    } else {
        // Buscar entidad por email + fecha de nacimiento
        $stmt = $db->prepare(
            "SELECT e.*, u.id as user_id
             FROM entities e
             LEFT JOIN users u ON u.entity_id = e.id
             WHERE LOWER(e.email) = ?
               AND e.birth_date = ?
               AND e.type = 'client'
               AND e.is_verified = 1
             LIMIT 1"
        );
        $stmt->execute([$email, $birthDate]);
        $entity = $stmt->fetch();

        if (!$entity) {
            $message = 'No encontramos una cuenta verificada con esos datos. Revisá el email y la fecha de nacimiento.';
            $status  = 'error';
        } else {
            // Verificar CUIT o DNI
            $dbCuit = preg_replace('/[^0-9]/', '', $entity['tax_id'] ?? '');
            $dbDoc  = preg_replace('/[^0-9]/', '', $entity['document_number'] ?? '');

            $cuitMatch = $taxId    && $dbCuit && $taxId    === $dbCuit;
            $docMatch  = $docNumber && $dbDoc  && $docNumber === $dbDoc;

            if (!$cuitMatch && !$docMatch) {
                $message = 'El CUIT o DNI no coincide con nuestra base de datos.';
                $status  = 'error';
            } elseif (!$entity['user_id']) {
                $message = 'Tu cuenta existe pero aún no tiene usuario activo. Por favor contactanos por WhatsApp.';
                $status  = 'error';
            } else {
                // ── Generar nueva contraseña ──────────────────────
                $chars  = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // Sin 0,O,I,1,L
                $suffix = '';
                for ($i = 0; $i < 6; $i++) {
                    $suffix .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $newPass = 'VS' . date('Y') . $suffix;

                // ── Actualizar hash en users ───────────────────────
                $db->prepare(
                    "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?"
                )->execute([password_hash($newPass, PASSWORD_DEFAULT), $entity['user_id']]);

                // ── Log ───────────────────────────────────────────
                try {
                    $db->prepare(
                        "INSERT INTO client_credentials_log (entity_id, email, cuit, document, birth_date, action)
                         VALUES (?, ?, ?, ?, ?, 'reset')"
                    )->execute([
                        $entity['id'],
                        $entity['email'],
                        $entity['tax_id']          ?? null,
                        $entity['document_number'] ?? null,
                        $entity['birth_date']      ?? null,
                    ]);
                } catch (\Exception $e) { /* log table may not exist yet */ }

                $clientName = $entity['fantasy_name'] ?: $entity['name'];
                $step   = 'success';
                $status = 'success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso — Vecino Seguro</title>
    <meta name="description" content="Recuperá tu clave de acceso al catálogo de precios Vecino Seguro.">
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
            --cyan:     #06b6d4;
            --green:    #10b981;
            --text:     rgba(255,255,255,.85);
            --muted:    rgba(255,255,255,.45);
            --input-bg: rgba(255,255,255,.04);
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 80% 60% at 20% -10%, rgba(26,110,245,.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 110%, rgba(6,182,212,.10) 0%, transparent 55%),
                var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: var(--text);
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 32px 80px -20px rgba(0,0,0,.6);
        }

        /* Header */
        .card-header { text-align: center; margin-bottom: 32px; }

        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(26,110,245,.12);
            border: 1px solid rgba(26,110,245,.25);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #7ab3ff;
            letter-spacing: .08em;
            margin-bottom: 16px;
        }
        .card-header h1 {
            font-size: 22px; font-weight: 800;
            color: #fff; margin-bottom: 8px; letter-spacing: -.4px;
        }
        .card-header p { font-size: 13px; color: var(--muted); line-height: 1.6; }

        /* Form */
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }

        label {
            font-size: 12px; font-weight: 600;
            color: var(--muted); letter-spacing: .04em;
            display: flex; align-items: center; gap: 5px;
        }
        label .req { color: #f87171; }

        input, select {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
        }
        input::placeholder { color: rgba(255,255,255,.2); }
        input:focus {
            border-color: rgba(26,110,245,.5);
            box-shadow: 0 0 0 3px rgba(26,110,245,.12);
        }

        .divider {
            height: 1px; background: var(--border); margin: 20px 0;
        }

        .hint {
            font-size: 11px; color: var(--blue); font-weight: 500;
            display: flex; align-items: center; gap: 4px; margin-top: -8px;
        }
        .hint .material-symbols-outlined { font-size: 13px; }

        .opt { font-size: 10px; font-weight: 700; color: rgba(255,255,255,.3);
               background: rgba(255,255,255,.05); border-radius: 4px; padding: 1px 6px; }

        /* Alert */
        .alert {
            padding: 14px 16px; border-radius: 12px;
            font-size: 13px; line-height: 1.6;
            margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert.error   { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); color: #f87171; }
        .alert.success { background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); color: #34d399; }
        .alert .material-symbols-outlined { font-size: 20px; flex-shrink: 0; margin-top: 1px; }

        /* Button */
        .btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--blue) 0%, #0f4fc9 100%);
            color: white; border: none; border-radius: 12px;
            font-family: inherit; font-size: 15px; font-weight: 700;
            cursor: pointer; margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 8px 24px -8px rgba(26,110,245,.6);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px -8px rgba(26,110,245,.7); }

        /* Password box */
        .pass-box {
            background: rgba(59,130,246,.05);
            border: 1px solid rgba(59,130,246,.2);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .pass-label {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,.4);
            text-transform: uppercase; letter-spacing: .1em; margin-bottom: 12px;
        }
        .pass-value {
            font-size: 28px; font-weight: 900;
            font-family: 'Courier New', monospace;
            color: #60a5fa; letter-spacing: 3px;
            margin-bottom: 16px;
        }
        .btn-copy {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.25);
            color: #60a5fa; border-radius: 8px; padding: 7px 16px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            font-family: inherit; transition: background .2s;
        }
        .btn-copy:hover { background: rgba(59,130,246,.2); }
        .btn-copy .material-symbols-outlined { font-size: 15px; }

        .warn-box {
            background: rgba(245,158,11,.05);
            border: 1px solid rgba(245,158,11,.2);
            border-radius: 12px; padding: 14px 16px;
            display: flex; align-items: flex-start; gap: 10px;
            margin-bottom: 20px;
        }
        .warn-box .material-symbols-outlined { color: #f59e0b; font-size: 18px; flex-shrink: 0; }
        .warn-box p { font-size: 12px; color: rgba(255,255,255,.6); line-height: 1.6; }

        /* Footer */
        .form-footer {
            margin-top: 24px; text-align: center;
            font-size: 13px; color: var(--muted);
        }
        .form-footer a { color: var(--blue); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        @media (max-width: 520px) {
            .card { padding: 32px 22px; }
            .pass-value { font-size: 22px; letter-spacing: 2px; }
        }
    </style>
</head>
<body>

<div class="card">

    <div class="card-header">
        <div class="logo-badge">
            <span class="material-symbols-outlined" style="font-size:13px;">shield</span>
            VECINO SEGURO
        </div>
        <h1>
            <?php if ($step === 'success'): ?>
                Nueva clave generada
            <?php else: ?>
                Recuperar acceso
            <?php endif; ?>
        </h1>
        <p>
            <?php if ($step === 'success'): ?>
                Tu nueva clave de acceso está lista. Anotala o cópiala.
            <?php else: ?>
                Ingresá tus datos de registro para verificar tu identidad<br>
                y generar una nueva contraseña.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($message && $status === 'error'): ?>
        <div class="alert error">
            <span class="material-symbols-outlined">error</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- ════════════ ÉXITO: mostrar contraseña ════════════ -->
    <?php if ($step === 'success'): ?>

        <div class="pass-box">
            <p class="pass-label">Tu nueva contraseña</p>
            <p class="pass-value" id="new-pass"><?= htmlspecialchars($newPass) ?></p>
            <button class="btn-copy" onclick="copyNewPass()">
                <span class="material-symbols-outlined">content_copy</span>
                <span id="copy-label">Copiar contraseña</span>
            </button>
        </div>

        <div class="warn-box">
            <span class="material-symbols-outlined">info</span>
            <p>
                Tu usuario sigue siendo tu <strong>email</strong>.<br>
                Esta contraseña es temporal. Una vez que ingreses podés cambiarla desde tu perfil.
            </p>
        </div>

        <a href="login.php"
            style="display:flex;align-items:center;justify-content:center;gap:8px;
                   width:100%;padding:14px;border-radius:12px;font-size:15px;font-weight:700;
                   color:white;text-decoration:none;
                   background:linear-gradient(135deg,#10b981,#059669);
                   box-shadow: 0 8px 24px -8px rgba(16,185,129,.5);
                   transition: transform .2s;"
            onmouseover="this.style.transform='translateY(-2px)'"
            onmouseout="this.style.transform=''">
            <span class="material-symbols-outlined">login</span>
            Ir al inicio de sesión
        </a>

    <!-- ════════════ FORMULARIO ════════════ -->
    <?php else: ?>

    <form method="POST" novalidate id="recovery-form">

        <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" required
                   placeholder="tu@email.com" autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="divider"></div>
        <p style="font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.08em;margin-bottom:14px;">
            VERIFICACIÓN DE IDENTIDAD
        </p>

        <div class="form-group">
            <label for="birth_date">Fecha de Nacimiento <span class="req">*</span></label>
            <input type="date" id="birth_date" name="birth_date" required
                   value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="tax_id">CUIT <span class="opt">al menos uno</span></label>
            <input type="text" id="tax_id" name="tax_id" placeholder="XX-XXXXXXXX-X"
                   maxlength="13" inputmode="numeric"
                   value="<?= htmlspecialchars($_POST['tax_id'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="document_number">DNI <span class="opt">al menos uno</span></label>
            <input type="text" id="document_number" name="document_number" placeholder="XXXXXXXX"
                   maxlength="9" inputmode="numeric"
                   value="<?= htmlspecialchars($_POST['document_number'] ?? '') ?>">
        </div>
        <p class="hint" style="margin-bottom:16px;margin-top:-8px;">
            <span class="material-symbols-outlined">lock</span>
            Ingresá al menos CUIT o DNI para verificar tu identidad.
        </p>

        <button type="submit" class="btn" id="btn-recover">
            <span class="material-symbols-outlined">key</span>
            Verificar y obtener nueva clave
        </button>
    </form>

    <?php endif; ?>

    <div class="form-footer">
        ¿Recordaste tu clave? <a href="login.php">Ingresá aquí</a>
        &nbsp;·&nbsp;
        <a href="index.php">← Volver</a>
    </div>

</div>

<script>
function copyNewPass() {
    const pass = document.getElementById('new-pass')?.textContent;
    if (!pass) return;
    navigator.clipboard.writeText(pass).then(() => {
        const lbl = document.getElementById('copy-label');
        lbl.textContent = '¡Copiado!';
        setTimeout(() => lbl.textContent = 'Copiar contraseña', 2500);
    });
}

// Formateo CUIT
document.getElementById('tax_id')?.addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 2 && v.length <= 10)   v = v.slice(0,2) + '-' + v.slice(2);
    else if (v.length > 10)               v = v.slice(0,2) + '-' + v.slice(2,10) + '-' + v.slice(10);
    this.value = v;
});

// Spinner al enviar
document.getElementById('recovery-form')?.addEventListener('submit', function () {
    const btn = document.getElementById('btn-recover');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite">refresh</span> Verificando...';
});
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

</body>
</html>