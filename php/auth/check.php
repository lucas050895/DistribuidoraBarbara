<?php
session_start();
include("../../config/conexion.php");

// Normalizamos email y clave
$email = strtolower(trim($_POST['email']));
$clave_plana = $_POST['movil'];

// SALT SEGURO
$salt_fija = 'Salt_Segura#123';

// Hashear la clave ingresada
$hash_clave_ingresada = hash('sha256', $salt_fija . $clave_plana);

// Buscar usuario por email
$stmt = $conn->prepare("SELECT id, movil, nombre, apellido, estado FROM basePersona WHERE LOWER(email) = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['estado'] == 1) {
    // Hashear clave que está en BD
    $hash_clave_bd = hash('sha256', $salt_fija . trim($user['movil']));

    if (hash_equals($hash_clave_bd, $hash_clave_ingresada)) {
        // Configurar datos de sesión
        $_SESSION['usuario']     = $email;
        $_SESSION['idPersona']   = $user['id'];
        $_SESSION['nombre']      = $user['nombre'] ?? '';
        $_SESSION['apellido']    = $user['apellido'] ?? '';
        $_SESSION['logged_in']   = true;
        $_SESSION['login_time']  = time(); // 🔹 Tiempo de inicio, no se renueva

        // Seguridad extra
        $_SESSION['user_agent']  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address']  = $_SERVER['REMOTE_ADDR'] ?? '';

        header("Location: ../pages/dashboard.php");
        exit();
    }
}

// Si llega aquí, usuario o clave incorrectos
header("Location: ../../index.php?error=1");
exit();
?>
