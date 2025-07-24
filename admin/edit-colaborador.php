<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id00'];
    $nombre = $_POST['name'];
    $cedula = $_POST['cedula'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        header("Location: colaboradores.php?action=edit&id=$id&error=2");
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar usuario
    $stmt = $database->prepare("UPDATE usuario SET nombre = ?, contraseña = ?, cedula = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nombre, $hashed, $cedula, $id);
    $stmt->execute();
    $stmt->close();

    // Actualizar colaborador (solo nombre y cédula por simplicidad)
    $stmt = $database->prepare("UPDATE colaborador SET nombre = ?, cedula = ? WHERE id_usuario = ?");
    $stmt->bind_param("ssi", $nombre, $cedula, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: colaboradores.php?action=edit&id=$id&error=4");
    exit;
}
?>