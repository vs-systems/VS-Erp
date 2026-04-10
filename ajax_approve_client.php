<?php
/**
 * AJAX — Aprobar cliente registrado desde el portal público
 * Bloque 7: crea usuario, envía credenciales, actualiza is_verified
 *
 * POST JSON: { entity_id: int, tipo_cliente: 'gremio'|'partner'|'publico' }
 * Respuesta: { success: bool, message: string }
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'auth_check.php';   // Solo admins/operadores internos
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';
require_once __DIR__ . '/src/lib/Mailer.php';

use Vsys\Lib\Database;
use Vsys\Lib\User;
use Vsys\Lib\Mailer;

$db = Database::getInstance();

// ── INPUT ────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

// También acepta POST form (por si se llama desde un form)
if (!$input) {
    $input = $_POST;
}

$entityId    = (int)($input['entity_id']    ?? 0);
$tipoCliente = $input['tipo_cliente']        ?? 'gremio';
$adminNotes  = trim($input['notes']          ?? '');

if (!$entityId) {
    echo json_encode(['success' => false, 'message' => 'ID de entidad inválido.']);
    exit;
}

$validTipos = ['partner', 'gremio', 'publico'];
if (!in_array($tipoCliente, $validTipos)) {
    $tipoCliente = 'gremio';
}

try {
    $db->beginTransaction();

    // ── 1. OBTENER ENTIDAD ───────────────────────────────────────
    $stmt = $db->prepare("SELECT * FROM entities WHERE id = ? AND type = 'client'");
    $stmt->execute([$entityId]);
    $entity = $stmt->fetch();

    if (!$entity) {
        throw new \Exception('No se encontró el cliente solicitado.');
    }

    $email = trim($entity['email'] ?? '');
    $name  = $entity['fantasy_name'] ?: $entity['name'];

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('El cliente no tiene un email válido. Corregí el dato antes de aprobar.');
    }

    // ── 2. VERIFICAR QUE NO TENGA USUARIO YA ────────────────────
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR entity_id = ?");
    $stmtCheck->execute([$email, $entityId]);
    $existingUser = $stmtCheck->fetch();

    if ($existingUser) {
        // Solo actualizar is_verified y tipo_cliente, no crear otro usuario
        $db->prepare("UPDATE entities SET is_verified = 1, tipo_cliente = ? WHERE id = ?")
           ->execute([$tipoCliente, $entityId]);
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => "Cliente ya tenía usuario. Se actualizó la verificación y lista de precios a «$tipoCliente».",
            'already_existed' => true
        ]);
        exit;
    }

    // ── 3. GENERAR CONTRASEÑA TEMPORAL ──────────────────────────
    // Formato: VS{AÑO}{6 chars aleatorios sin caracteres confusos}
    $chars    = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // Sin 0,O,I,1,L
    $suffix   = '';
    for ($i = 0; $i < 6; $i++) {
        $suffix .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $tempPass = 'VS' . date('Y') . $suffix; // Ej: VS2026AB3X7K

    // ── 4. CREAR USUARIO ─────────────────────────────────────────
    $userModule = new User();
    $created = $userModule->createUser([
        'username'  => $email,
        'password'  => $tempPass,
        'role'      => 'client',
        'entity_id' => $entityId,
        'status'    => 'Active',
        'full_name' => $name
    ]);

    if (!$created) {
        throw new \Exception('No se pudo crear el usuario en la base de datos.');
    }

    // ── 5. ACTUALIZAR ENTIDAD ────────────────────────────────────
    // Valores aceptados por el ENUM client_profile: 'Gremio','Web','ML','Otro'
    $clientProfile = match($tipoCliente) {
        'partner' => 'Otro',   // No existe 'Partner' en ENUM, mapear a 'Otro'
        'gremio'  => 'Gremio', // Valor exacto del ENUM
        default   => 'Otro',
    };

    $db->prepare(
        "UPDATE entities
         SET is_verified   = 1,
             tipo_cliente  = ?,
             client_profile = ?
         WHERE id = ?"
    )->execute([$tipoCliente, $clientProfile, $entityId]);

    // ── 6. REGISTRAR EN LOG DE CREDENCIALES ─────────────────────
    try {
        $db->prepare(
            "INSERT INTO client_credentials_log (entity_id, email, cuit, document, birth_date, action)
             VALUES (?, ?, ?, ?, ?, 'created')"
        )->execute([
            $entityId,
            $entity['email'],
            $entity['tax_id']         ?? null,
            $entity['document_number']?? null,
            $entity['birth_date']     ?? null,
        ]);
    } catch (\Exception $logEx) {
        // Si la tabla aún no existe, no bloquear el flujo
    }

    // ── 6. ENVIAR EMAIL CON CREDENCIALES ─────────────────────────
    $tipoLabel = ['partner' => 'Partner', 'gremio' => 'Gremio', 'publico' => 'Público'][$tipoCliente];
    $loginUrl  = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'vecinoseguro.com.ar') . '/login.php';
    $catUrl    = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'vecinoseguro.com.ar') . '/catalogo_web.php';

    $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="background:#0d1117;font-family:'Helvetica Neue',Arial,sans-serif;margin:0;padding:0;color:#cbd5e1;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;padding:32px 16px;">
    <tr><td>

      <!-- Header -->
      <table width="100%" style="background:#111827;border-radius:16px 16px 0 0;border:1px solid #233348;border-bottom:none;padding:28px 32px;">
        <tr>
          <td>
            <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:16px;">
              <span style="background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);border-radius:10px;padding:7px 14px;font-size:12px;font-weight:700;color:#60a5fa;letter-spacing:.06em;">
                ✓ CUENTA ACTIVADA
              </span>
            </div>
            <h1 style="font-size:22px;font-weight:800;color:#fff;margin:0 0 6px;">¡Tu cuenta fue aprobada!</h1>
            <p style="font-size:14px;color:#64748b;margin:0;">Hola <strong style="color:#94a3b8;">{$name}</strong> — ya podés acceder al catálogo con tus precios de <strong style="color:#60a5fa;">{$tipoLabel}</strong>.</p>
          </td>
        </tr>
      </table>

      <!-- Credenciales -->
      <table width="100%" style="background:#16202e;border:1px solid #233348;border-top:none;border-bottom:none;padding:28px 32px;">
        <tr>
          <td>
            <p style="font-size:11px;font-weight:700;color:#475569;letter-spacing:.1em;text-transform:uppercase;margin:0 0 16px;">Tus credenciales de acceso</p>
            <table width="100%" style="background:#0d1117;border:1px solid #233348;border-radius:12px;padding:20px;margin-bottom:20px;">
              <tr>
                <td style="padding:8px 0;border-bottom:1px solid #233348;">
                  <span style="font-size:11px;color:#475569;display:block;margin-bottom:3px;">USUARIO (Email)</span>
                  <span style="font-size:15px;font-weight:700;color:#fff;">{$email}</span>
                </td>
              </tr>
              <tr>
                <td style="padding:8px 0;">
                  <span style="font-size:11px;color:#475569;display:block;margin-top:8px;margin-bottom:3px;">CONTRASEÑA TEMPORAL</span>
                  <span style="font-size:22px;font-weight:800;color:#3b82f6;font-family:'Courier New',monospace;letter-spacing:2px;">{$tempPass}</span>
                </td>
              </tr>
            </table>
            <p style="font-size:12px;color:#475569;margin:0 0 20px;line-height:1.7;">
              ⚠️ Esta es una contraseña temporal. Una vez que ingreses, te recomendamos cambiarla desde tu perfil.<br>
              Tu lista de precios activa es: <strong style="color:#60a5fa;">{$tipoLabel}</strong>
            </p>
            <a href="{$loginUrl}"
               style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;text-decoration:none;padding:13px 28px;border-radius:10px;font-weight:700;font-size:14px;">
               Ingresar al catálogo →
            </a>
          </td>
        </tr>
      </table>

      <!-- Footer -->
      <table width="100%" style="background:#111827;border:1px solid #233348;border-top:none;border-radius:0 0 16px 16px;padding:20px 32px;">
        <tr>
          <td style="text-align:center;">
            <p style="font-size:11px;color:#475569;margin:0;">
              Vecino Seguro · <a href="{$catUrl}" style="color:#3b82f6;text-decoration:none;">Ver catálogo</a><br>
              No respondas este email. Para consultas escribinos por WhatsApp.
            </p>
          </td>
        </tr>
      </table>

    </td></tr>
  </table>
</body>
</html>
HTML;

    $mailSent = false;
    $mailError = '';
    try {
        $mailer = new Mailer();
        $mailer->send($email, '✓ Tu cuenta en Vecinos Seguros fue activada', $emailBody);
        $mailSent = true;
    } catch (\Exception $e) {
        $mailError = $e->getMessage();
        // No revertir la transacción por fallo de email — el usuario ya fue creado
        error_log("[Aprobación cliente] Fallo envío email a $email: $mailError");
    }

    // ── 7. LOG INTERNO ──────────────────────────────────────────
    $adminId = $_SESSION['user_id'] ?? 1;
    $logNote  = "Aprobado por admin ID $adminId. Tipo: $tipoCliente. Email enviado: " . ($mailSent ? 'SÍ' : "NO ($mailError)");
    if ($adminNotes) $logNote .= " — Nota: $adminNotes";

    // Intentar guardar nota en CRM si existe la tabla
    try {
        $db->prepare(
            "INSERT IGNORE INTO crm_leads (entity_id, status, notes, created_at)
             VALUES (?, 'Aprobado', ?, NOW())"
        )->execute([$entityId, $logNote]);
    } catch (\Exception $e) { /* tabla opcional */ }

    $db->commit();

    echo json_encode([
        'success'    => true,
        'message'    => $mailSent
            ? "Cliente aprobado y credenciales enviadas a <strong>$email</strong>. También podés compartir la clave directamente."
            : "Cliente aprobado. El email no pudo enviarse — <strong>compartí la clave al cliente por WhatsApp</strong>.",
        'mail_sent'  => $mailSent,
        'temp_pass'  => $tempPass,   // Siempre visible para el admin
        'client_name'=> $name,
        'client_email'=> $email,
        'tipo'       => $tipoCliente,
    ]);

} catch (\Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
