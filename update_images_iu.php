<?php
/**
 * VS System ERP - Mass Image Update from IU Argentina
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Increase timeout for scraping
set_time_limit(300);

$db = Vsys\Lib\Database::getInstance();
$catalog = new Vsys\Modules\Catalogo\Catalog();
$products = $catalog->getAllProducts();

echo "<h2>Actualizació³n de Imó¡genes - IU Argentina</h2>";
echo "Este proceso es un poco mó¡s lento porque debemos consultar la web de IU para cada producto...<br><br>";

$updated = 0;
$skipped = 0;
$notFound = 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$count = 0;

foreach ($products as $p) {
    if (!empty($p['image_url'])) {
        $skipped++;
        continue;
    }

    if ($count >= $limit) {
        echo "<br><b>Ló­mite de procesamiento alcanzado ($limit).</b><br>";
        echo "<a href='update_images_iu.php?limit=$limit' class='btn-primary'>Procesar 10 mó¡s</a>";
        break;
    }

    $sku = $p['sku'];
    $searchUrl = "https://www.iuargsa.com/productos.php?q=" . urlencode($sku) . "&b=s";

    // Fetch search results
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $html = @file_get_contents($searchUrl, false, $context);

    if ($html && preg_match('/src="(admin\/productos\/[a-z0-9]+\.jpg)"/i', $html, $matches)) {
        $imageUrl = "https://www.iuargsa.com/" . $matches[1];

        $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")
            ->execute([$imageUrl, $p['id']]);

        echo "âœ… SKU <b>$sku</b>: Imagen encontrada y actualizada.<br>";
        $updated++;
    } else {
        echo "âŒ SKU <b>$sku</b>: No se encontró³ imagen en IU Argentina.<br>";
        $notFound++;
    }

    $count++;
    // Small sleep to be polite
    usleep(200000);
}

echo "<br><b>Resumen del lote:</b><br>";
echo "- Actualizados: $updated <br>";
echo "- No encontrados: $notFound <br>";
echo "- Ya tenó­an imagen: $skipped <br>";

echo "<br><a href='productos.php'>Volver a Productos</a> | <a href='catalogo.php'>Ver Cató¡logo</a>";





