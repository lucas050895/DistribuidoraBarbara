<?php
require_once '../auth/session_check.php';
require_once '../../config/cache.php';
require_once '../../config/conexion.php';

// Escapar variables de sesión
$idPersona = $_SESSION['idPersona'];
$usuario   = htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES, 'UTF-8');
$nombre    = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$apellido  = htmlspecialchars($_SESSION['apellido'] ?? '', ENT_QUOTES, 'UTF-8');

$productos = [];
$nombreLista = '';

try {
    $sql = "SELECT 
                i.nombre AS producto,
                i.descripcion,
                p.precio,
                l.nombre AS nombreLista,
                r.nombre AS rubro
            FROM baseRol br
            INNER JOIN invTipoListaPrecio l ON br.idListaPrecio = l.id
            INNER JOIN invPrecioItem p ON l.id = p.idTipoListaPrecio
            INNER JOIN invItem i ON i.id = p.idItem
            INNER JOIN invRubro r ON i.idRubro = r.id
            WHERE br.idPersona = :idPersona AND CAST(i.estado AS INT) = 1
            ORDER BY r.nombre, i.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':idPersona', $idPersona, PDO::PARAM_STR);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as &$p) {
        $p['producto']    = htmlspecialchars($p['producto'], ENT_QUOTES, 'UTF-8');
        $p['descripcion'] = htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
        $p['precio']      = htmlspecialchars($p['precio'] ?? '', ENT_QUOTES, 'UTF-8');
        $p['nombreLista'] = htmlspecialchars($p['nombreLista'] ?? '', ENT_QUOTES, 'UTF-8');
        $p['rubro']       = htmlspecialchars($p['rubro'] ?? 'Sin rubro', ENT_QUOTES, 'UTF-8');
    }
    unset($p);

} catch (Exception $e) {
    error_log("Error obteniendo productos: " . $e->getMessage());
    $productos = [];
}

// Agrupar por rubro y capturar nombre de lista
$rubros = [];
foreach ($productos as $producto) {
    $rubro = $producto['rubro'] ?? 'Sin rubro';
    $rubros[$rubro][] = $producto;
    if (empty($nombreLista) && !empty($producto['nombreLista'])) {
        $nombreLista = $producto['nombreLista'];
    }
}

// ---------- Config imágenes ----------
$carpetaImagenesUrl = "../../assets/img/";         // para el navegador
$carpetaImagenesFS  = __DIR__ . "/../../assets/img/"; // para file_exists / scandir
$imagenDefault      = "default.jpg";

// Normalizador: a snake_case sin tildes ni símbolos
function normalizar_nombre($texto) {
    $t = trim((string)$texto);

    // Pasar a ASCII (quita tildes). Si iconv no está, se usa el original.
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        if ($conv !== false) { $t = $conv; }
    }

    $t = mb_strtolower($t, 'UTF-8');
    // Reemplazar cualquier cosa no alfanumérica por guión bajo
    $t = preg_replace('/[^a-z0-9]+/u', '_', $t);
    // Colapsar múltiples guiones bajos
    $t = preg_replace('/_+/', '_', $t);
    // Quitar guiones bajos al inicio/fin
    $t = trim($t, '_');

    return $t;
}

// Construir un índice de archivos existentes -> clave normalizada
$extPermitidas = ['jpg','jpeg','png','webp','gif','JPG','JPEG','PNG','WEBP','GIF'];
$indiceImagenes = []; // clave normalizada => nombre real del archivo (basename)

if (is_dir($carpetaImagenesFS)) {
    $archivos = scandir($carpetaImagenesFS);
    if ($archivos !== false) {
        foreach ($archivos as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $carpetaImagenesFS . $f;
            if (!is_file($path)) continue;

            $ext = pathinfo($f, PATHINFO_EXTENSION);
            if (!in_array($ext, $extPermitidas, true)) continue;

            $base = pathinfo($f, PATHINFO_FILENAME);
            $clave = normalizar_nombre($base);
            if ($clave === '') continue;

            // Primer match gana (evita sobreescrituras).
            if (!isset($indiceImagenes[$clave])) {
                $indiceImagenes[$clave] = $f; // guardar basename con extensión original
            }
        }
    }
}

// Extraer nombres de rubro ya luego de tener datos
$rubroNombres = array_keys($rubros);

require_once '../../config/tiempo_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include("layout/meta.php"); ?>
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo filemtime('../../assets/css/dashboard.css'); ?>">
    <?php include("layout/iconos.php"); ?>
</head>
<body>
<main>
    <?php include("layout/titulo.php"); ?>

    <h2><?php echo htmlspecialchars($nombreLista, ENT_QUOTES, 'UTF-8'); ?></h2>

    <?php if (!empty($productos)): ?>
        <div class="buscador">
            <input type="text" id="buscador" placeholder="Buscar producto...">
            <select id="filtroRubro">
                <option value="todos">TODOS LOS RUBROS</option>
                <?php foreach ($rubroNombres as $rubro): ?>
                    <option value="<?= htmlspecialchars($rubro, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($rubro, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php foreach ($rubros as $rubroNombre => $productosRubro): ?>
            <h3 class="rubro-titulo" data-rubro='<?= htmlspecialchars($rubroNombre, ENT_QUOTES, 'UTF-8') ?>'>
                <?= htmlspecialchars($rubroNombre, ENT_QUOTES, 'UTF-8') ?>
            </h3>
            <div class='grupo-rubro' data-rubro='<?= htmlspecialchars($rubroNombre, ENT_QUOTES, 'UTF-8') ?>'>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>Img</th>
                            <th>Producto</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($productosRubro as $p): ?>
                        <?php
                        // 1) Normalizar el nombre del producto
                        $nombreProd   = $p['producto'];
                        $clave        = normalizar_nombre($nombreProd);

                        // 2) Preferencia: exacto .jpg
                        $archivoPreferido = $clave . '.jpg';
                        $rutaFSPreferida  = $carpetaImagenesFS . $archivoPreferido;

                        if (is_file($rutaFSPreferida)) {
                            $archivoElegido = $archivoPreferido; // existe exacto .jpg
                        } elseif (isset($indiceImagenes[$clave])) {
                            // 3) Si no existe el .jpg exacto, tomar cualquier archivo que coincida normalizado
                            $archivoElegido = $indiceImagenes[$clave];
                        } else {
                            // 4) Fallback
                            $archivoElegido = $imagenDefault;
                        }

                        $rutaImagen = $carpetaImagenesUrl . $archivoElegido;
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($rutaImagen, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($p['producto'], ENT_QUOTES, 'UTF-8') ?>"
                                     style="width:60px;height:auto;object-fit:contain;">
                            </td>
                            <td><?= htmlspecialchars($p['producto'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No hay productos disponibles para mostrar.</p>
    <?php endif; ?>


    <?php include("layout/footer.php") ?>
</main>

<!-- JS de expiración sincronizado -->
<script>
    const TIEMPO_SESION = <?php echo TIEMPO_SESION; ?>;
    let tiempoRestante = TIEMPO_SESION;

    const temporizador = setInterval(() => {
        tiempoRestante--;
        if (tiempoRestante <= 0) {
            clearInterval(temporizador);
            alert("Su sesión ha expirado.");
            window.location.href = "../../index.php?error=2";
        }
    }, 1000);
</script>

<script src="../../assets/js/filtrado.js?v=3"></script>

</body>
</html>
