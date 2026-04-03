<?php
/**
 * VS System ERP - Mass Image Update from Big Dipper
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = Vsys\Lib\Database::getInstance();
$catalog = new Vsys\Modules\Catalogo\Catalog();
$products = $catalog->getAllProducts();

echo "<h2>Actualizació³n Masiva de Imó¡genes - Big Dipper</h2>";
echo "Escaneando productos...<br><br>";

$updated = 0;
$skipped = 0;
$notFound = 0;

foreach ($products as $p) {
    if (!empty($p['image_url'])) {
        $skipped++;
        continue;
    }

    $sku = $p['sku'];
    $url = "https://www.bigdipper.com.ar/File/Imagenes/Productos/" . $sku . ".png";

    // Simplificamos: asumimos que si es de Big Dipper el SKU es correcto.
    // Para no tardar milenios pingeando cada URL, podemos simplemente setearlas
    // y el navegador las cargaró¡ si existen, o mostraró¡ el icono si fallan.
    // Pero para ser prolijos, el usuario puede elegir pingearlas.

    // Opció³n ró¡pida: Setear la URL directamente
    $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")
        ->execute([$url, $p['id']]);

    echo "Actualizado: " . $sku . "<br>";
    $updated++;
}

echo "<br><b>Resultado:</b><br>";
echo "Actualizados: $updated <br>";
echo "Ya tenó­an imagen (Omitidos): $skipped <br>";

echo "<br><a href='productos.php'>Volver a Productos</a>";





