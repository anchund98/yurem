<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

// Obtener colaborador_id
$cedula = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT id FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$stmt->bind_result($colaborador_id);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = (int)$_POST['paciente_id'];
    $diagnostico = $_POST['diagnostico'];
    $tratamiento = $_POST['tratamiento'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'] ?? null;

    $stmt = $database->prepare("INSERT INTO tratamiento (paciente_id, colaborador_id, diagnostico, tratamiento, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $paciente_id, $colaborador_id, $diagnostico, $tratamiento, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $stmt->close();

    header("Location: tratamientos.php?success=1");
    exit;
}
?>