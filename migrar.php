<?php
include("connection.php");

// Obtener usuarios con contraseñas sin hash
$result = $database->query("SELECT id, contraseña FROM usuario WHERE contraseña NOT LIKE '$2y$%'");
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $plain = $row['contraseña'];
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $database->prepare("UPDATE usuario SET contraseña = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    $stmt->execute();
    $stmt->close();
}
echo "✅ Migración de contraseñas completada.";
?>