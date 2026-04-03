<?php
/**
 * VS System ERP - Mass Image Update from Sanyi / LaserDreams
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(1200); // 20 minutes

$db = Vsys\Lib\Database::getInstance();

echo "<h2>Sincronización de Imágenes - Sanyi / LaserDreams</h2>";

// 1. Cargar datos de LaserDreams (Fuente rápida)
echo "Cargando repositorio de imágenes de LaserDreams... ";
flush();

$laserDreamsMap = [];
$dataJsUrl = "https://laserdreams.com.ar/js/data.js";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $dataJsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$jsContent = curl_exec($ch);
curl_close($ch);

if ($jsContent) {
    // Extraer pares ID/Image usando regex
    // El formato es "id": "sku", ... "image": "url"
    preg_match_all('/"id":\s*"([^"]+)"[^}]+"image":\s*"([^"]+)"/s', $jsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $idx => $sku) {
            $laserDreamsMap[strtoupper(trim($sku))] = $matches[2][$idx];
        }
    }
    echo "<span style='color:green;'>¡OK! (" . count($laserDreamsMap) . " imágenes encontradas)</span><br>";
} else {
    echo "<span style='color:red;'>FALLÓ (Se usará solo scraping directo)</span><br>";
}
flush();

// 2. Obtener solo productos SANYI de la base de datos
echo "Filtrando productos locales...<br>";
$stmt = $db->prepare("SELECT id, sku, brand, image_url FROM products WHERE UPPER(brand) LIKE '%SANYI%'");
$stmt->execute();
$products = $stmt->fetchAll();

echo "Encontrados " . count($products) . " productos para procesar.<br><br>";
flush();

$updated = 0;
$skipped = 0;
$notFound = 0;

foreach ($products as $p) {
    $sku = strtoupper(trim($p['sku']));

    // Omitir si ya tiene imagen de supabase (ya sincronizado)
    if (!empty($p['image_url']) && strpos($p['image_url'], 'supabase.co') !== false) {
        // echo "Omitido (Ya sincronizado): $sku<br>";
        $skipped++;
        continue;
    }

    echo "Procesando $sku... ";
    flush();

    $imageUrl = null;

    // A. Intentar con el mapa de LaserDreams (Muy rápido)
    if (isset($laserDreamsMap[$sku])) {
        $imageUrl = $laserDreamsMap[$sku];
        echo "<span style='color:blue;'>[LaserDreams] </span>";
    }
    // B. Intentar variaciones del SKU en LaserDreams (ej. sin guiones o con minúsculas)
    else {
        $cleanSku = str_replace('-', '', $sku);
        if (isset($laserDreamsMap[strtolower($sku)])) {
            $imageUrl = $laserDreamsMap[strtolower($sku)];
            echo "<span style='color:blue;'>[LaserDreams-min] </span>";
        } elseif (isset($laserDreamsMap[$cleanSku])) {
            $imageUrl = $laserDreamsMap[$cleanSku];
            echo "<span style='color:blue;'>[LaserDreams-clean] </span>";
        }
    }

    // C. Fallback: Scraping directo a Sanyi Lights
    if (!$imageUrl) {
        $productUrl = "https://sanyilights.com.ar/producto/" . urlencode($p['sku']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $productUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $content) {
            $pattern = '/https:\/\/yxldaywdnpzpfeftctpr\.supabase\.co\/storage\/v1\/object\/public\/product-images\/products\/' . preg_quote($p['sku'], '/') . '\/[^"\'\s>]+/';
            if (preg_match($pattern, $content, $match)) {
                $imageUrl = $match[0];
                echo "<span style='color:purple;'>[Scraping] </span>";
            }
        }
    }

    if ($imageUrl) {
        $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")
            ->execute([$imageUrl, $p['id']]);
        echo "<span style='color:green;'>¡ÉXITO!</span><br>";
        $updated++;
    } else {
        echo "<span style='color:orange;'>No encontrada en ninguna fuente</span><br>";
        $notFound++;
    }

    echo str_repeat(" ", 1024);
    flush();

    // Pequeño delay solo si hicimos scraping
    if (!isset($laserDreamsMap[$sku])) {
        usleep(100000); // 0.1s
    }
}

echo "<br><b>Resultado Final:</b><br>";
echo "Actualizados: $updated <br>";
echo "Omitidos: $skipped <br>";
echo "No encontrados: $notFound <br>";

echo "<br><a href='configuration.php'>Volver a Configuración</a>";
