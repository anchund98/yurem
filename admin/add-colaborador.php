<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['name'];
    $cedula = $_POST['cedula'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $area_trabajo = $_POST['area_trabajo'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        header("Location: colaboradores.php?action=add&error=2");
        exit;
    }

    // Verificar duplicado en usuario
    $stmt = $database->prepare("SELECT id FROM usuario WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: colaboradores.php?action=add&error=1");
        exit;
    }
    $stmt->close();

    // Insertar en usuario
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $database->prepare("INSERT INTO usuario (cedula, nombre, contraseña, rol, estado) VALUES (?, ?, ?, 'colaborador', 1)");
    $stmt->bind_param("sss", $cedula, $nombre, $hashed);
    $stmt->execute();
    $usuario_id = $stmt->insert_id;
    $stmt->close();

    // Insertar en colaborador
    $stmt = $database->prepare("INSERT INTO colaborador (nombre, correo, cedula, telefono, area_trabajo, id_usuario) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $nombre, $correo, $cedula, $telefono, $area_trabajo, $usuario_id);
    $stmt->execute();
    $stmt->close();

    header("Location: colaboradores.php?action=add&error=4");
    exit;
}
?>