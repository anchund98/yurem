<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Evitar eliminar si tiene relaciones activas (opcional)
    $stmt = $database->prepare("SELECT COUNT(*) AS total FROM horario WHERE colaborador_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    if ($total > 0) {
        // Redirigir con error si tiene horarios asociados
        header("location: colaboradores.php?action=drop&id=$id&error=linked");
        exit;
    }

    // Eliminar colaborador
    $stmt = $database->prepare("DELETE FROM usuario WHERE id = ? AND rol = 'colaborador'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("location: colaboradores.php");
exit;
?>