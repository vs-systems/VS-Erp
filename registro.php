<?php
/**
 * VS System ERP - Public Registration (Gremio)
 * username = email del solicitante
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';
require_once __DIR__ . '/src/lib/Mailer.php';

use Vsys\Lib\Database;
use Vsys\Modules\Clientes\Client;
use Vsys\Lib\Mailer;

$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db           = Database::getInstance();
    $clientModule = new Client();

    $email  = trim(strtolower($_POST['email'] ?? ''));
    $name   = trim($_POST['name'] ?? '');
    $tax_id = trim($_POST['tax_id'] ?? '');       // CUIT/DNI — opcional ahora
    $doc_number = trim($_POST['document_number'] ?? '');

    // Validar email real
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El email ingresado no tiene un formato válido.';
        $status  = 'error';
    }
    // Verificar que el email no esté ya registrado en entities
    elseif ($db->query("SELECT id FROM entities WHERE email = " . $db->quote($email))->fetch()) {
        $message = 'Ya existe una cuenta registrada con ese email. Si olvidaste tu clave, contactanos por WhatsApp.';
        $status  = 'error';
    }
    // Verificar email duplicado en users (username)
    elseif ($db->query("SELECT id FROM users WHERE username = " . $db->quote($email))->fetch()) {
        $message = 'Ya existe una cuenta registrada con ese email.';
        $status  = 'error';
    }
    else {
        $data = [
            'id'                => null,
            'type'              => 'client',
            'tax_id'            => $tax_id,
            'document_number'   => $doc_number,
            'name'              => $name,
            'fantasy_name'      => $_POST['fantasy_name'] ?? '',
            'contact'           => $_POST['contact'] ?? $name,
            'email'             => $email,
            'phone'             => $_POST['phone'] ?? '',
            'mobile'            => $_POST['mobile'] ?? '',
            'address'           => $_POST['address'] ?? '',
            'delivery_address'  => '',
            'default_voucher'   => 'Factura',
            'tax_category'      => $_POST['tax_category'] ?? 'No Aplica',
            'is_enabled'        => 1,
            'retention'         => 0,
            'payment_condition' => 'Contado',
            'payment_method'    => 'Transferencia',
            'seller_id'         => null,
            'client_profile'    => 'Gremio',
            'is_verified'       => 0,
            'is_transport'      => 0,
            'tipo_cliente'      => 'gremio',
            'city'              => '',
            'lat'               => null,
            'lng'               => null,
            'transport'         => null,
            'birth_year'        => !empty($_POST['birth_year']) ? (int)$_POST['birth_year'] : null,
        ];

        if ($clientModule->saveClient($data)) {
            // Sincronizar con CRM
            try {
                require_once __DIR__ . '/src/modules/crm/CRM.php';
                $crm = new \Vsys\Modules\CRM\CRM();
                $crm->saveLead([
                    'name'           => $name,
                    'contact_person' => $_POST['contact'] ?? $name,
                    'email'          => $email,
                    'phone'          => $_POST['mobile'] ?? '',
                    'status'         => 'Nuevo',
                    'notes'          => 'Solicitud de alta Gremio desde Portal Web. Pendiente aprobación.'
                ]);
            } catch (Exception $e) { /* silencioso */ }

            $message = 'Solicitud enviada con éxito. Un asesor verificará tus datos y te contactará mediante WhatsApp para darte acceso al sistema.';
            $status  = 'success';

            // Notificación interna
            try {
                $mailer = new Mailer();
                $mailer->send(
                    'vecinoseguro0@gmail.com',
                    "Nuevo Registro Gremio: $name",
                    "Se registró un nuevo cliente Gremio: $name ($email). Pendiente de verificación."
                );
            } catch (Exception $e) { /* silencioso */ }
        } else {
            $message = 'Ocurrió un error al guardar el registro. Por favor intentá nuevamente.';
            $status  = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Gremio — Vecinos Seguros</title>
    <meta name="description" content="Registrate como Gremio en Vecinos Seguros para acceder a la lista de precios exclusiva.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:     #050d1a;
            --card:   #0b1628;
            --border: rgba(255,255,255,.08);
            --blue:   #1a6ef5;
            --cyan:   #06b6d4;
            --text:   rgba(255,255,255,.85);
            --muted:  rgba(255,255,255,.45);
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

        /* ── CARD ── */
        .reg-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 560px;
            box-shadow: 0 32px 80px -20px rgba(0,0,0,.6);
        }

        /* ── HEADER ── */
        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .card-header .logo-badge {
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
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.5px;
            color: #fff;
            margin-bottom: 8px;
        }

        .card-header p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ── FORM ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full { grid-column: 1 / -1; }

        label {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .04em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        label .req { color: #f87171; }

        /* ── TOOLTIP ICONO ── */
        .tooltip-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .tooltip-wrap .material-symbols-outlined {
            font-size: 15px;
            color: var(--blue);
            cursor: help;
        }

        .tooltip-wrap::after {
            content: attr(data-tip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #0f2040;
            border: 1px solid rgba(26,110,245,.4);
            color: #a8c8ff;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.5;
            padding: 8px 12px;
            border-radius: 10px;
            white-space: nowrap;
            max-width: 260px;
            white-space: normal;
            text-align: center;
            pointer-events: none;
            opacity: 0;
            transition: opacity .2s;
            z-index: 10;
            box-shadow: 0 8px 24px rgba(0,0,0,.4);
        }

        .tooltip-wrap:hover::after { opacity: 1; }

        /* ── INPUTS ── */
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

        input:focus, select:focus {
            border-color: rgba(26,110,245,.5);
            box-shadow: 0 0 0 3px rgba(26,110,245,.12);
        }

        /* Email destacado */
        input[type="email"] {
            border-color: rgba(26,110,245,.25);
        }

        input[type="email"]:focus {
            border-color: var(--blue);
        }

        /* Helper text debajo del email */
        .field-hint {
            font-size: 11px;
            color: var(--blue);
            font-weight: 500;
            margin-top: -4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .field-hint .material-symbols-outlined { font-size: 13px; }

        /* ── DIVIDER ── */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 20px 0;
        }

        /* ── OPCIONAL BADGE ── */
        .opt-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,.3);
            background: rgba(255,255,255,.05);
            border-radius: 4px;
            padding: 1px 6px;
            letter-spacing: .04em;
            vertical-align: middle;
        }

        /* ── BOTÓN ── */
        .btn-reg {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--blue) 0%, #0f4fc9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 8px 24px -8px rgba(26,110,245,.6);
            letter-spacing: .02em;
        }

        .btn-reg:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px -8px rgba(26,110,245,.7);
        }

        /* ── ALERT ── */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert.success {
            background: rgba(16,185,129,.08);
            border: 1px solid rgba(16,185,129,.2);
            color: #34d399;
        }

        .alert.error {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            color: #f87171;
        }

        .alert .material-symbols-outlined { font-size: 20px; flex-shrink: 0; margin-top: 1px; }

        /* ── FOOTER LINKS ── */
        .form-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }

        .form-footer a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover { text-decoration: underline; }

        /* ── RESPONSIVE ── */
        @media (max-width: 520px) {
            .reg-card { padding: 32px 22px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: 1; }
        }
    </style>
</head>

<body>

    <div class="reg-card">

        <div class="card-header">
            <div class="logo-badge">
                <span class="material-symbols-outlined" style="font-size:13px;">shield</span>
                VECINO SEGURO
            </div>
            <h1>Registro como Gremio</h1>
            <p>Completá el formulario y un asesor validará tu cuenta.<br>
               Te contactaremos por WhatsApp para darte acceso al catálogo de precios.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $status ?>">
                <span class="material-symbols-outlined">
                    <?= $status === 'success' ? 'check_circle' : 'error' ?>
                </span>
                <span><?= $message ?></span>
            </div>
            <?php if ($status === 'success'): ?>
                <div class="form-footer">
                    <a href="index.php">← Volver al inicio</a>
                    &nbsp;·&nbsp;
                    <a href="catalogo_web.php">Ver catálogo</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($status !== 'success'): ?>
        <form method="POST" novalidate id="form-registro">
            <div class="form-grid">

                <!-- EMAIL (USERNAME) — PRIMERO Y DESTACADO -->
                <div class="form-group full">
                    <label for="email">
                        Email <span class="req">*</span>
                        <span class="tooltip-wrap"
                              data-tip="Tu email será tu usuario de acceso al sistema. Ingresá uno real para poder recibir tu clave, novedades de precios y comunicaciones importantes.">
                            <span class="material-symbols-outlined">info</span>
                        </span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nombre@tuempresa.com"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                    <p class="field-hint">
                        <span class="material-symbols-outlined">key</span>
                        Este email será tu usuario de acceso. Debe ser real para recibir información importante.
                    </p>
                </div>

                <!-- NOMBRE -->
                <div class="form-group full">
                    <label for="name">Nombre / Razón Social <span class="req">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Ej: Juan García o Instalaciones García SRL"
                        required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    >
                </div>

                <!-- WHATSAPP -->
                <div class="form-group">
                    <label for="mobile">WhatsApp / Celular <span class="req">*</span></label>
                    <input
                        type="tel"
                        id="mobile"
                        name="mobile"
                        placeholder="2235001234"
                        required
                        inputmode="numeric"
                        value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                    >
                </div>

                <!-- LOCALIDAD -->
                <div class="form-group">
                    <label for="address">Localidad <span class="req">*</span></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="Ej: Mar del Plata, Buenos Aires"
                        required
                        value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                    >
                </div>

            </div>

            <div class="divider"></div>

            <!-- DATOS OPCIONALES -->
            <p style="font-size:11px; font-weight:700; color:var(--muted); letter-spacing:.08em; margin-bottom:12px;">
                DATOS OPCIONALES
            </p>

            <div class="form-grid">

                <div class="form-group">
                    <label for="tax_id">
                        CUIT <span class="opt-badge">OPCIONAL</span>
                    </label>
                    <input
                        type="text"
                        id="tax_id"
                        name="tax_id"
                        placeholder="XX-XXXXXXXX-X"
                        maxlength="13"
                        value="<?= htmlspecialchars($_POST['tax_id'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="document_number">
                        DNI <span class="opt-badge">OPCIONAL</span>
                    </label>
                    <input
                        type="text"
                        id="document_number"
                        name="document_number"
                        placeholder="XXXXXXXX"
                        maxlength="9"
                        inputmode="numeric"
                        value="<?= htmlspecialchars($_POST['document_number'] ?? '') ?>"
                    >
                </div>

                <!-- AÑO DE NACIMIENTO -->
                <div class="form-group full">
                    <label for="birth_year">
                        Año de Nacimiento <span class="req">*</span>
                        <span class="tooltip-wrap"
                              data-tip="Necesario para recuperar tu clave si la olvidás. Las 3 preguntas que pediremos: email + CUIT/DNI + año de nacimiento.">
                            <span class="material-symbols-outlined">info</span>
                        </span>
                    </label>
                    <input
                        type="number"
                        id="birth_year"
                        name="birth_year"
                        required
                        min="1900"
                        max="<?= date('Y') - 18 ?>"
                        placeholder="Ej: 1980"
                        value="<?= htmlspecialchars($_POST['birth_year'] ?? '') ?>"
                    >
                    <p class="field-hint">
                        <span class="material-symbols-outlined">lock</span>
                        Usado solo para verificar tu identidad si olvidás tu clave.
                    </p>
                </div>

            </div>

            <button type="submit" class="btn-reg" id="btn-enviar">
                <span class="material-symbols-outlined">how_to_reg</span>
                Solicitar Alta como Gremio
            </button>
        </form>

        <div class="form-footer">
            ¿Ya tenés cuenta? <a href="login.php">Ingresá aquí</a>
            &nbsp;·&nbsp;
            <a href="index.php">← Volver</a>
        </div>
        <?php endif; ?>

    </div>

    <script>
        // Formateo automático del CUIT al tipear
        document.getElementById('tax_id')?.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 11);
            if (v.length > 2 && v.length <= 10)      v = v.slice(0, 2) + '-' + v.slice(2);
            else if (v.length > 10)                   v = v.slice(0, 2) + '-' + v.slice(2, 10) + '-' + v.slice(10);
            this.value = v;
        });

        // Validación básica de WhatsApp (mínimo 10 dígitos)
        document.getElementById('form-registro')?.addEventListener('submit', function (e) {
            const mobile = document.getElementById('mobile').value.replace(/\D/g, '');
            if (mobile.length < 10) {
                e.preventDefault();
                alert('Ingresá un WhatsApp válido (mínimo 10 dígitos).');
                document.getElementById('mobile').focus();
            }
        });
    </script>

</body>
</html>