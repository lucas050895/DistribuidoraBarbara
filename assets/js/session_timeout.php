<?php
    header('Content-Type: application/javascript');
    require_once __DIR__ . '/../../config/tiempo_sesion.php';

    // Pasar el tiempo de sesión al JS
    echo "const TIEMPO_SESION = " . (int) TIEMPO_SESION . ";";
?>
