<?php
    session_start();
    require_once '../../config/tiempo_sesion.php';

    if (!isset($_SESSION['login_time'])) {
        session_destroy();
        header("Location: ../../index.php?error=2");
        exit();
    }

    $tiempoTranscurrido = time() - $_SESSION['login_time'];
    $tiempoRestante = TIEMPO_SESION - $tiempoTranscurrido;

    if ($tiempoRestante <= 0) {
        session_destroy();
        header("Location: ../../index.php?error=2");
        exit();
    }

    header("Content-Type: application/javascript");
    ?>
    setTimeout(function() {
        window.location.href = "../../index.php?error=2";
    }, <?= $tiempoRestante * 1000 ?>);
