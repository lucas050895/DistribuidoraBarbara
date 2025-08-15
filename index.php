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
                    <label for="password"><i class="fas fa-lock"></i></label>
                    <input type="password" id="password" name="movil" required placeholder="Celular">
                </div>

                <input type="submit" id="entrar" name="entrar" value="Entrar">
            </fieldset>

                <?php
                    // MENSAJES DE ERROR
                    if (!empty($_GET['error'])) {
                        $mensaje = '';
                        switch ($_GET['error']) {
                            case '1':
                                $mensaje = 'Usuario o contraseña incorrectos.';
                                break;
                            case '2':
                                $mensaje = 'Su sesión ha expirado. Vuelva a iniciar sesión.';
                                break;
                            case '3':
                                $mensaje = 'Debe iniciar sesión para acceder a esta página.';
                                break;
                            case '4':
                                $mensaje = 'Su cuenta está inactiva o bloqueada.';
                                break;
                            default:
                                $mensaje = 'Ha ocurrido un error. Inténtelo nuevamente.';
                                break;
                        }

                        echo '<div class="error-message">' . htmlspecialchars($mensaje) . '</div>';
                    }
                ?>
        </form>
    </main>

</body>
</html>