<?php
session_start();

// Permitir acceso solo a colaboradores o administradores
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

if ($_POST) {
    $nombre           = $_POST['nombre'];
    $cedula           = $_POST['cedula'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $telefono         = $_POST['telefono'] ?? null;
    $direccion        = $_POST['direccion'] ?? null;

    // Determinar creado_por
    $cedulaUsuario = $_SESSION['usuario'];
    $stmt = $database->prepare("SELECT id FROM usuario WHERE cedula = ?");
    $stmt->bind_param("s", $cedulaUsuario);
    $stmt->execute();
    $stmt->bind_result($creado_por);
    $stmt->fetch();
    $stmt->close();

    // Verificar si la cédula ya existe
    $stmt = $database->prepare("SELECT id FROM paciente WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = '1'; // Duplicado
    } else {
        // Insertar paciente
        $stmt = $database->prepare("INSERT INTO paciente (cedula, nombre, fecha_nacimiento, telefono, direccion, creado_por, estado) VALUES (?, ?, ?, ?, ?, ?, 'nuevo')");
        $stmt->bind_param("sssssi", $cedula, $nombre, $fecha_nacimiento, $telefono, $direccion, $creado_por);
        $stmt->execute();
        $error = '4'; // Éxito
    }
    $stmt->close();
} else {
    $error = '3'; // Acceso directo sin POST
}

// Redirigir con mensaje
header("location: citas.php?action=add_patient&error=" . $error);
exit;
?>