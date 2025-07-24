<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Primero eliminar de colaborador
    $stmt = $database->prepare("DELETE FROM colaborador WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Luego eliminar de usuario
    $stmt = $database->prepare("DELETE FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: colaboradores.php");
    exit;
}
?>