<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

$cedula = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT id FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$stmt->bind_result($colaborador_id);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = (int)$_POST['paciente_id'];
    $concepto = $_POST['concepto'];
    $monto_total = (float)$_POST['monto_total'];
    $monto_pagado = (float)$_POST['monto_pagado'];
    $tipo = $_POST['tipo'];
    $metodo_pago = $_POST['metodo_pago'];
    $referencia = $_POST['referencia'] ?? null;

    $stmt = $database->prepare("INSERT INTO pagos (paciente_id, colaborador_id, concepto, monto_total, monto_pagado, tipo, metodo_pago, referencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddsss", $paciente_id, $colaborador_id, $concepto, $monto_total, $monto_pagado, $tipo, $metodo_pago, $referencia);
    $stmt->execute();
    $stmt->close();

    header("Location: registrar_pago.php?success=1");
    exit;
}
?>