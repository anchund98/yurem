<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $database->prepare("DELETE FROM pagos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: registrar_pago.php?success=3");
    exit;
}
?>