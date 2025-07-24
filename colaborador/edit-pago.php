<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pago'])) {
    $id = (int)$_POST['id'];
    $concepto = $_POST['concepto'];
    $monto_total = (float)$_POST['monto_total'];
    $monto_pagado = (float)$_POST['monto_pagado'];
    $tipo = $_POST['tipo'];
    $metodo_pago = $_POST['metodo_pago'];
    $referencia = $_POST['referencia'] ?? '';
    $estado = $_POST['estado'];

    $stmt = $database->prepare("
        UPDATE pagos
        SET concepto = ?, monto_total = ?, monto_pagado = ?, tipo = ?, metodo_pago = ?, referencia = ?, estado = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssdssssi", $concepto, $monto_total, $monto_pagado, $tipo, $metodo_pago, $referencia, $estado, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: registrar_pago.php?success=2");
    exit;
}
?>
