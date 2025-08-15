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
        $p['descripcion'] = htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8');
        $p['precio']      = htmlspecialchars($p['precio'], ENT_QUOTES, 'UTF-8');
        $p['nombreLista'] = htmlspecialchars($p['nombreLista'], ENT_QUOTES, 'UTF-8');
        $p['rubro']       = htmlspecialchars($p['rubro'], ENT_QUOTES, 'UTF-8');
    }
    unset($p);

} catch (Exception $e) {
    error_log("Error obteniendo productos: " . $e->getMessage());
    $productos = [];
}

$rubros = [];
foreach ($productos as $producto) {
    $rubro = $producto['rubro'] ?? 'Sin rubro';
    $rubros[$rubro][] = $producto;
    if (empty($nombreLista) && !empty($producto['nombreLista'])) {
        $nombreLista = $producto['nombreLista'];
    }
}

$rubroNombres = array_keys($rubros);
$carpetaImagenes = "../../assets/img/";
$imagenDefault = "default.jpg";

require_once '../../config/tiempo_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include("layout/meta.php"); ?>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo filemtime('../../assets/css/style.css'); ?>">
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
                        <th>Img</th>
                        <th>Producto</th>
                        <th>Precio</th>
                    </thead>
                    <?php foreach ($productosRubro as $p): ?>
                        <?php
                        $nombreArchivo = preg_replace('/[^a-z0-9]/i', '_', strtolower($p['producto'])) . ".jpg";
                        $rutaImagen = $carpetaImagenes . $nombreArchivo;
                        if (!file_exists($rutaImagen)) {
                            $rutaImagen = $carpetaImagenes . $imagenDefault;
                        }
                        ?>
                        <tr>
                            <td><img src="<?= $rutaImagen ?>" alt="<?= htmlspecialchars($p['producto'], ENT_QUOTES, 'UTF-8') ?>" style="width:60px;height:auto;"></td>
                            <td><?= htmlspecialchars($p['producto'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No hay productos disponibles para mostrar.</p>
    <?php endif; ?>
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
