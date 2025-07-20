<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

if ($_POST) {
    $nombre      = $_POST['name'];
    $cedula      = $_POST['cedula'];
    $password    = $_POST['password'];
    $cpassword   = $_POST['cpassword'];

    if ($password !== $cpassword) {
        $error = '2';
    } else {
        // Verificar si la cédula ya existe
        $stmt = $database->prepare("SELECT * FROM usuario WHERE cedula = ?");
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = '1';
        } else {
            // Insertar nuevo colaborador
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $database->prepare("INSERT INTO usuario (cedula, nombre, contraseña, rol, estado) VALUES (?, ?, ?, 'colaborador', 1)");
            $stmt->bind_param("sss", $cedula, $nombre, $hashed);
            $stmt->execute();
            $error = '4';
        }
        $stmt->close();
    }
} else {
    $error = '3'; // acceso directo sin POST
}

header("location: colaboradores.php?action=add&error=" . $error);
exit;
?>