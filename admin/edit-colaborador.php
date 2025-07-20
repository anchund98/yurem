<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

if ($_POST) {
    $id       = $_POST['id00'];
    $nombre   = $_POST['name'];
    $cedula   = $_POST['cedula'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        $error = '2';
    } else {
        // Verificar si la nueva cédula ya existe en otro registro
        $stmt = $database->prepare("SELECT * FROM usuario WHERE cedula = ? AND id != ?");
        $stmt->bind_param("si", $cedula, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = '1';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $database->prepare("UPDATE usuario SET nombre = ?, cedula = ?, contraseña = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $cedula, $hashed, $id);
            $stmt->execute();
            $error = '4';
        }
        $stmt->close();
    }
} else {
    $error = '3';
}

header("location: colaboradores.php?action=edit&id=" . $_POST['id00'] . "&error=" . $error);
exit;
?>