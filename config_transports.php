<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/logistica/Logistics.php';

use Vsys\Modules\Logistica\Logistics;
$logistics = new Logistics();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? null,
        'name' => $_POST['name'] ?? '',
        'contact_person' => $_POST['contact_person'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    $logistics->saveTransport($data);
    header("Location: config_transports.php?success=1");
    exit;
}

$transports = $logistics->getTransports(false);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Transportes - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .transport-form {
            background: rgba(30, 41, 59, 0.7);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #334155;
        }

        .input-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #94a3b8;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
            border-radius: 6px;
        }

        .is-active-check {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large"style="height: 50px;">
            <div style="color:white; font-weight:700; font-size:1.4rem;">GESTIó“N DE <span>TRANSPORTES</span></div>
        </div>
    </header>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h1>Directorio de Transportes</h1>
                <a href="configuration.php" class="btn-primary" style="background:#475569; text-decoration:none;"><i
                        class="fas fa-arrow-left"></i> Volver a Config</a>
            </div>

            <div class="card">
                <h2>
                    <?php echo isset($_GET['edit']) ? 'Editar Empresa' : 'Nueva Empresa de Transporte'; ?>
                </h2>
                <form action="config_transports.php" method="POST" class="transport-form">
                    <?php
                    $editData = ['id' => '', 'name' => '', 'contact_person' => '', 'phone' => '', 'email' => '', 'is_active' => 1];
                    if (isset($_GET['edit'])) {
                        foreach ($transports as $t)
                            if ($t['id'] == $_GET['edit'])
                                $editData = $t;
                    }
                    ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="input-group"><label>Nombre Comercial</label><input type="text" name="name"
                                value="<?php echo $editData['name']; ?>" required></div>
                        <div class="input-group"><label>Contacto Principal</label><input type="text"
                                name="contact_person" value="<?php echo $editData['contact_person']; ?>"></div>
                        <div class="input-group"><label>Teló©fono de Contacto</label><input type="tel" name="phone"
                                value="<?php echo $editData['phone']; ?>"></div>
                        <div class="input-group"><label>Email de Coordinació³n</label><input type="email" name="email"
                                value="<?php echo $editData['email']; ?>"></div>
                    </div>
                    <div class="is-active-check">
                        <input type="checkbox" name="is_active" id="is_active" <?php echo $editData['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active" style="margin:0; color:white;">Empresa Habilitada</label>
                    </div>
                    <button type="submit" class="btn-primary"
                        style="margin-top:20px; background:var(--accent-violet); border:none; padding:12px 25px; border-radius:8px; font-weight:700; cursor:pointer;">
                        <i class="fas fa-save"></i>
                        <?php echo $editData['id'] ? 'Actualizar Transportista' : 'Registrar Transportista'; ?>
                    </button>
                    <?php if ($editData['id']): ?><a href="config_transports.php"
                            style="color:#94a3b8; margin-left:15px; text-decoration:none;">Cancelar</a>
                    <?php endif; ?>
                </form>

                <h2>Empresas Registradas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Transportista</th>
                            <th>Contacto</th>
                            <th>Teló©fono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transports as $t): ?>
                            <tr>
                                <td style="font-weight:700; color:var(--accent-violet);">
                                    <?php echo $t['name']; ?>
                                </td>
                                <td>
                                    <?php echo $t['contact_person']; ?>
                                </td>
                                <td>
                                    <?php echo $t['phone']; ?>
                                </td>
                                <td><span
                                        class="status-pill <?php echo $t['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $t['is_active'] ? 'HABILITADO' : 'INACTIVO'; ?>
                                    </span></td>
                                <td><a href="config_transports.php?edit=<?php echo $t['id']; ?>" class="btn-primary"
                                        style="padding:6px 12px; font-size:12px; text-decoration:none; background:#1e293b; border:1px solid #334155;"><i
                                            class="fas fa-edit"></i> Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transports)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px; color:#94a3b8;">No se han
                                    configurado empresas de transporte aóºn.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>






