<?php
    session_start();
    require_once '../../config/cache.php';
    require_once '../../config/conexion.php';
    require_once '../../config/tiempo_sesion.php'; // constante de tiempo de sesión

    // Validar que el usuario esté logueado
    if (!isset($_SESSION['idPersona']) || !isset($_SESSION['logged_in'])) {
        header("Location: ../../index.php?error=3");
        exit();
    }

    // Verificar si se pasó el tiempo de sesión
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > TIEMPO_SESION)) {
        session_destroy();
        header("Location: ../../index.php?error=2");
        exit();
    }

    $idPersona = $_SESSION['idPersona'];
    $usuario = $_SESSION['usuario'] ?? '';
    $nombre = $_SESSION['nombre'] ?? '';
    $apellido = $_SESSION['apellido'] ?? '';

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

    // Ruta imágenes
    $carpetaImagenes = "../../assets/img/";
    $imagenDefault = "default.jpg";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include("layout/meta.php"); ?>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <?php include("layout/iconos.php"); ?>
</head>
<body>
<main>
    <?php include("layout/titulo.php"); ?>

    <h2><?php echo htmlspecialchars($nombreLista); ?></h2>

    <?php if (!empty($productos)): ?>
        <div class="buscador">
            <input type="text" id="buscador" placeholder="Buscar producto...">
            <select id="filtroRubro">
                <option value="todos">TODOS LOS RUBROS</option>
                <?php foreach ($rubroNombres as $rubro): ?>
                    <option value="<?= htmlspecialchars($rubro) ?>"><?= htmlspecialchars($rubro) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php foreach ($rubros as $rubroNombre => $productosRubro): ?>
            <h3 class='rubro-titulo' data-rubro='<?= htmlspecialchars($rubroNombre) ?>'>
                <?= htmlspecialchars($rubroNombre) ?>
            </h3>
            <div class='grupo-rubro' data-rubro='<?= htmlspecialchars($rubroNombre) ?>'>
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
                            <td><img src="<?= $rutaImagen ?>" alt="<?= htmlspecialchars($p['producto']) ?>" style="width:60px;height:auto;"></td>
                            <td><?= htmlspecialchars($p['producto']) ?></td>
                            <td>$<?= number_format($p['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No hay productos disponibles para mostrar.</p>
    <?php endif; ?>
</main>

<!-- CIERRE DE SESION AUTOMATICA -->
<script src="../../assets/js/session_timeout.php"></script>

<!-- FILTRADO DE TABLA -->
<script src="../../assets/js/filtrado.js"></script>

</body>
</html>
