<?php
/**
 * VS System ERP - Public Registration
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';
require_once __DIR__ . '/src/lib/Mailer.php';

use Vsys\Lib\Database;
use Vsys\Modules\Clientes\Client;
use Vsys\Lib\Mailer;

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    $clientModule = new Client();

    $tax_id = $_POST['tax_id']; // CUIT
    $email = $_POST['email'];
    $name = $_POST['name'];

    // Check if CUIT already exists
    $exists = $db->prepare("SELECT id FROM entities WHERE tax_id = ?")->execute([$tax_id]);
    if ($db->query("SELECT id FROM entities WHERE tax_id = '$tax_id'")->fetch()) {
        $message = "Este CUIT ya se encuentra registrado.";
        $status = "error";
    } else {
        // Create entity as unverified
        $data = [
            'id' => null,
            'type' => 'client',
            'tax_id' => $tax_id,
            'document_number' => $_POST['document_number'] ?? '',
            'name' => $name,
            'fantasy_name' => $_POST['fantasy_name'] ?? '',
            'contact' => $_POST['contact'] ?? $name,
            'email' => $email,
            'phone' => $_POST['phone'] ?? '',
            'mobile' => $_POST['mobile'] ?? '',
            'address' => $_POST['address'] ?? '',
            'delivery_address' => '',
            'default_voucher' => 'Factura',
            'tax_category' => $_POST['tax_category'] ?? 'Consumidor Final',
            'is_enabled' => 1,
            'retention' => 0,
            'payment_condition' => 'Contado',
            'payment_method' => 'Transferencia',
            'seller_id' => null, // Pending verification
            'client_profile' => 'Web',
            'is_verified' => 0
        ];

        if ($clientModule->saveClient($data)) {
            // Sincronizar con CRM
            try {
                require_once __DIR__ . '/src/modules/crm/CRM.php';
                $crm = new \Vsys\Modules\CRM\CRM();
                $crm->saveLead([
                    'name' => $name,
                    'contact_person' => $_POST['contact'] ?? $name,
                    'email' => $email,
                    'phone' => $_POST['mobile'] ?? '',
                    'status' => 'Nuevo',
                    'notes' => 'Solicitud de alta desde Portal Web.'
                ]);
            } catch (Exception $e) {
                // Registro silencioso en logs si falla el CRM
            }

            // Notify admin or sales?
            $message = "Solicitud enviada con éxito. Un vendedor verificará sus datos y recibirá un correo con su clave de acceso.";
            $status = "success";

            // Send internal notification (Optional for now)
            try {
                $mailer = new Mailer();
                $mailer->send('vecinoseguro0@gmail.com', "Nuevo Registro: $name", "Se ha registrado un nuevo cliente ($name) pendiente de verificación.");
            } catch (Exception $e) {
                // Ignore mail errors for now
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <style>
        body {
            background: #020617;
            color: #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .reg-card {
            background: #0f172a;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #1e293b;
            width: 100%;
            max-width: 600px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .form-group input {
            padding: 10px;
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            border-radius: 6px;
        }

        .btn-reg {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #8b5cf6, #d946ef);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="reg-card">
        <h2 style="text-align:center; color:white;">Registro de Nuevo Cliente</h2>
        <?php if ($message): ?>
            <div
                style="padding:15px; border-radius:6px; background:<?php echo $status === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color:<?php echo $status === 'success' ? '#10b981' : '#ef4444'; ?>; margin-bottom:20px;">
                <?php echo $message; ?>
            </div>
            <?php if ($status === 'success'): ?>
                <div style="text-align:center;"><a href="login.php" style="color:#8b5cf6;">Volver al Login</a></div>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Razón Social / Nombre</label><input type="text" name="name" required>
                    </div>
                    <div class="form-group"><label>CUIT (Username)</label><input type="text" name="tax_id"
                            placeholder="00-00000000-0" required></div>
                    <div class="form-group"><label>DNI / Documento</label><input type="text" name="document_number"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>WhatsApp / Celular</label><input type="text" name="mobile"></div>
                    <div class="form-group"><label>Provincia / Localidad</label><input type="text" name="address"></div>
                </div>
                <button type="submit" class="btn-reg">SOLICITAR ALTA</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>