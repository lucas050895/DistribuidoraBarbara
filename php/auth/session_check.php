<?php
// Configuración segura de cookies de sesión
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1); 
ini_set('session.use_only_cookies', 1);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que esté logueado
if (!isset($_SESSION['idPersona']) || !isset($_SESSION['logged_in'])) {
    header("Location: ../../index.php?error=3");
    exit();
}

// Verificar IP y User-Agent (para prevenir secuestro de sesión)
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}
if (!isset($_SESSION['ip_address'])) {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
}
if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '') ||
    $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_destroy();
    header("Location: ../../index.php?error=4");
    exit();
}

// Regenerar ID de sesión cada 5 minutos
if (!isset($_SESSION['last_regen'])) {
    $_SESSION['last_regen'] = time();
} elseif (time() - $_SESSION['last_regen'] > 300) { // 5 minutos
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// Expiración fija desde el login (NO se renueva con interacción)
require_once __DIR__ . "/../../config/tiempo_sesion.php";
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > TIEMPO_SESION)) {
    session_destroy();
    header("Location: ../../index.php?error=2");
    exit();
}
?>
