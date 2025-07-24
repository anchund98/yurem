<?php
session_start();

// Solo colaboradores o administradores
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

// Obtener ID del colaborador logueado
$cedulaUsuario = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT id FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedulaUsuario);
$stmt->execute();
$stmt->bind_result($colaborador_id);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_cita'])) {
    $paciente_id = (int) $_POST['paciente_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $titulo = $_POST['titulo'];

    // Validar que el paciente existe
    $stmt = $database->prepare("SELECT id FROM paciente WHERE id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        header("Location: citas.php?error=1");
        exit;
    }
    $stmt->close();

    // Insertar cita
    $stmt = $database->prepare("INSERT INTO horario (fecha, hora, titulo, paciente_id, colaborador_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $fecha, $hora, $titulo, $paciente_id, $colaborador_id);
    $stmt->execute();
    $stmt->close();

    // Actualizar estado del paciente
    $stmt = $database->prepare("UPDATE paciente SET estado = 'atendido' WHERE id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $stmt->close();

    // Redirigir con éxito
    header("Location: citas.php?success=1");
    exit;
} else {
    header("Location: citas.php?error=2");
    exit;
}
?>