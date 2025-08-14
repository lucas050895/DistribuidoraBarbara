<?php
session_start();
include("../../config/conexion.php");

$email = strtolower(trim($_POST['email']));
$password = $_POST['movil']; // usado como contraseña

$stmt = $conn->prepare("SELECT id, movil, nombre, apellido, estado FROM basePersona WHERE LOWER(email) = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['estado'] == 1 && $password === trim($user['movil'])) {
    $_SESSION['usuario'] = $email;
    $_SESSION['idPersona'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre'] ?? '';
    $_SESSION['apellido'] = $user['apellido'] ?? '';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    header("Location: ../pages/dashboard.php");
    exit();
}

header("Location: ../../index.php");
exit();
?>