<?php
session_start();
require_once '../../config/conexion.php';

if (!isset($_SESSION['idPersona']) || !isset($_SESSION['logged_in'])) {
    header("Location: ../../index.php?error=3");
    exit();
}

if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include("layout/meta.php"); ?>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php include("layout/iconos.php"); ?>
</head>
<body>
<main>
    <?php include("layout/titulo.php"); ?>

    <div>
        <h2>Bienvenido <?php echo htmlspecialchars($nombre . ' ' . $apellido); ?></h2>
        <p>Usuario: <?php echo htmlspecialchars($usuario); ?></p>
        <a href="../auth/logout.php">Cerrar Sesión</a>
    </div>

    <?php if (!empty($productos)): ?>
        <div>
            <input type="text" id="buscador" placeholder="Buscar producto...">
            <select id="filtroRubro">
                <option value="todos">Todos los rubros</option>
                <?php foreach ($rubroNombres as $rubro): ?>
                    <option value="<?= htmlspecialchars($rubro) ?>"><?= htmlspecialchars($rubro) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <h2>Lista de precios: <?php echo htmlspecialchars($nombreLista); ?></h2>

        <?php foreach ($rubros as $rubroNombre => $productosRubro): ?>
            <h3 class='rubro-titulo' data-rubro='<?= htmlspecialchars($rubroNombre) ?>'>
                <?= htmlspecialchars($rubroNombre) ?>
            </h3>
            <div class='grupo-rubro' data-rubro='<?= htmlspecialchars($rubroNombre) ?>'>
                <table>
                    <tr>
                        <th>Img</th>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                    </tr>
                    <?php foreach ($productosRubro as $p): ?>
                        <tr>
                            <td></td>
                            <td><?= htmlspecialchars($p['producto']) ?></td>
                            <td><?= htmlspecialchars($p['descripcion'] ?? '') ?></td>
                            <td>$<?= number_format($p['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No hay productos disponibles para mostrar.</p>
        <p>Contacta al administrador si esto es un error.</p>
    <?php endif; ?>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const buscador = document.getElementById("buscador");
    const filtroRubro = document.getElementById("filtroRubro");
    const grupos = document.querySelectorAll(".grupo-rubro");
    const titulosRubros = document.querySelectorAll(".rubro-titulo");

    if (!buscador || !filtroRubro) return;

    function filtrar() {
        const texto = buscador.value.trim().toLowerCase();
        const rubroSeleccionado = filtroRubro.value;

        grupos.forEach(grupo => {
            const rubro = grupo.getAttribute("data-rubro");
            const filas = grupo.querySelectorAll("table tr:not(:first-child)");
            let algunaVisible = false;

            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const coincideTexto = texto === "" || textoFila.includes(texto);
                const coincideRubro = rubroSeleccionado === "todos" || rubroSeleccionado === rubro;

                let mostrar = false;
                if (texto !== "") {
                    mostrar = coincideTexto;
                } else if (rubroSeleccionado !== "todos") {
                    mostrar = coincideRubro;
                } else {
                    mostrar = true;
                }

                fila.style.display = mostrar ? "" : "none";
                if (mostrar) algunaVisible = true;
            });

            grupo.style.display = algunaVisible ? "" : "none";
        });

        titulosRubros.forEach(titulo => {
            const rubro = titulo.getAttribute("data-rubro");
            const grupo = document.querySelector(".grupo-rubro[data-rubro='" + rubro + "']");
            titulo.style.display = grupo && grupo.style.display !== "none" ? "" : "none";
        });
    }

    buscador.addEventListener("input", filtrar);
    filtroRubro.addEventListener("change", function() {
        buscador.value = "";
        filtrar();
    });

    filtrar();
});
</script>
</body>
</html>