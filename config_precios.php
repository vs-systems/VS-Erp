<?php
require_once 'auth_check.php';
/**
 * Configuració³n de Precios y Mó¡rgenes - VS System ERP
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Config\PriceList;

$priceListModule = new PriceList();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_margins'])) {
            foreach ($_POST['margins'] as $id => $margin) {
                $priceListModule->updateMargin($id, $margin);
            }
            $message = "Mó¡rgenes actualizados correctamente.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$lists = $priceListModule->getAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuració³n de Precios - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large">
            <div style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem;">
                Configuració³n <span style="color: var(--accent-violet);">Precios</span>
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="content">
            <div class="card">
                <h2><i class="fas fa-percentage"></i> Gestión de Mó¡rgenes por Lista</h2>
                <p style="color: #94a3b8; margin-bottom: 2rem;">Defina el porcentaje de ganancia sobre el costo (USD)
                    para cada lista de precios.</p>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Lista de Precios</th>
                                    <th>Margen (%)</th>
                                    <th>Ejemplo (Costo $100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lists as $list): ?>
                                    <tr>
                                        <td><strong>
                                                <?php echo $list['name']; ?>
                                            </strong></td>
                                        <td>
                                            <input type="number" step="0.01" name="margins[<?php echo $list['id']; ?>]"
                                                value="<?php echo $list['margin_percent']; ?>" class="form-control"
                                                style="width: 100px; display: inline-block;"> %
                                        </td>
                                        <td style="color: #10b981;">
                                            $
                                            <?php echo number_format(100 * (1 + $list['margin_percent'] / 100), 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 2rem; text-align: right;">
                        <button type="submit" name="update_margins" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>




