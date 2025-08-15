<?php
    $server = 'DESKTOP-BJCP0I9';
    $database = 'GestionBarbara';
    $username = 'lectorGestion';
    $password = 'ContraseñaSegura123';

    try {
        $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Conexión fallida: " . $e->getMessage());
    }
?>