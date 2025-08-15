<?php
require_once __DIR__ . '/../../config/conexion.php'; // Ajusta a tu conexión

// 🔑 API Key y CX que me diste
$apiKey = "AIzaSyBIEkX6N-lAJWFX0ylJfZcwS3oItL667Zc";
$cx = "467ea294488bb4874";

// Carpeta de destino
$destino = __DIR__ . '/../../assets/img/';

// Consulta de productos
$sql = "SELECT nombre FROM invItem";
$stmt = $conn->prepare($sql);
$stmt->execute();

// Función para buscar y guardar imagen
function descargarImagen($producto, $destino, $apiKey, $cx) {
    $query = urlencode($producto);
    $url = "https://www.googleapis.com/customsearch/v1?q={$query}&cx={$cx}&searchType=image&num=1&key={$apiKey}";

    $json = @file_get_contents($url);
    if ($json === false) {
        echo "⚠ Error al conectar con la API para: $producto\n";
        return false;
    }

    $data = json_decode($json, true);

    if (!empty($data['items'][0]['link'])) {
        $imgUrl = $data['items'][0]['link'];
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9-_]/', '_', $producto) . ".jpg";
        file_put_contents($destino . $nombreArchivo, file_get_contents($imgUrl));
        echo "✅ Imagen descargada: $nombreArchivo\n";
        return true;
    } else {
        echo "⚠ No se encontró imagen para: $producto\n";
        return false;
    }
}

// Procesar cada producto
$contador = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nombreProducto = trim($row['nombre']);
    $archivoDestino = $destino . preg_replace('/[^a-zA-Z0-9-_]/', '_', $nombreProducto) . ".jpg";

    if (file_exists($archivoDestino)) {
        echo "✅ Imagen ya existe: $nombreProducto\n";
        continue;
    }

    // Evitar pasar el límite de 100 búsquedas/día
    if ($contador >= 100) {
        echo "⏹ Límite de 100 búsquedas alcanzado por hoy.\n";
        break;
    }

    echo "📷 Buscando imagen para: $nombreProducto\n";
    descargarImagen($nombreProducto, $destino, $apiKey, $cx);
    $contador++;
    sleep(2); // pausa de 2 segundos para evitar bloqueo
}

$conn = null; // cerrar conexión
?>
