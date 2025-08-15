<?php include_once("config/cache.php") ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- META -->
    <?php include("php/pages/layout/meta.php"); ?>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">

    <!-- ICONOS -->
    <?php include("php/pages/layout/iconos.php"); ?>
</head>
<body>
    <main>
        <?php include("php/pages/layout/titulo.php"); ?>
        
        <form action="php/auth/check.php" method="post">
            <fieldset>
                <legend>Iniciar sesión</legend>

                <div>
                    <label for="email"><i class="fas fa-user"></i></label>
                    <input type="email" id="email" name="email" required placeholder="E-mail">
                </div>

                <div>
                    <label for="clave"><i class="fas fa-lock"></i></label>
                    <input type="password" id="password" name="movil" required placeholder="Celular">
                </div>

                <input type="submit" id="entrar" name="entrar" value="Entrar">
            </fieldset>
        </form>
    </main>

</body>
</html>