<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tratamiento'])) {
    $id = (int)$_POST['id'];
    $diagnostico = $_POST['diagnostico'];
    $tratamiento = $_POST['tratamiento'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $estado = $_POST['estado'];

    $stmt = $database->prepare("UPDATE tratamiento SET diagnostico=?, tratamiento=?, fecha_inicio=?, fecha_fin=?, estado=? WHERE id=?");
    $stmt->bind_param("sssssi", $diagnostico, $tratamiento, $fecha_inicio, $fecha_fin, $estado, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: tratamientos.php?success=2");
    exit;
}
?>