<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

// Datos del usuario
$cedulaUsuario = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT id, nombre, rol FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedulaUsuario);
$stmt->execute();
$stmt->bind_result($usuario_id, $nombreUsuario, $rolUsuario);
$stmt->fetch();
$stmt->close();

// Búsqueda
$search = $_GET['search'] ?? '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (pa.nombre LIKE ? OR pa.cedula LIKE ?)";
}

// Filtrar por rol
if ($rolUsuario === 'colaborador') {
    $query = "
        SELECT p.id, pa.nombre AS paciente, pa.cedula, p.concepto, p.monto_total, p.monto_pagado, p.tipo, p.metodo_pago, p.estado
        FROM pagos p
        JOIN paciente pa ON p.paciente_id = pa.id
        WHERE p.colaborador_id = ? $searchCondition
        ORDER BY p.fecha_pago DESC
    ";
    $stmt = $database->prepare($query);
    if (!empty($search)) {
        $like = "%$search%";
        $stmt->bind_param("iss", $usuario_id, $like, $like);
    } else {
        $stmt->bind_param("i", $usuario_id);
    }
} else {
    $query = "
        SELECT p.id, pa.nombre AS paciente, pa.cedula, p.concepto, p.monto_total, p.monto_pagado, p.tipo, p.metodo_pago, p.estado
        FROM pagos p
        JOIN paciente pa ON p.paciente_id = pa.id
        WHERE 1=1 $searchCondition
        ORDER BY p.fecha_pago DESC
    ";
    $stmt = $database->prepare($query);
    if (!empty($search)) {
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$pagos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Modal: ver detalles
$view_id = $_GET['view_id'] ?? null;
$pago = null;
if ($view_id) {
    $stmt = $database->prepare("
        SELECT p.*, pa.nombre AS paciente, pa.cedula, u.nombre AS colaborador
        FROM pagos p
        JOIN paciente pa ON p.paciente_id = pa.id
        JOIN usuario u ON p.colaborador_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pago = $result->fetch_assoc();
    $stmt->close();
}

// Obtener pacientes para selects
$pacientes = [];
$stmt = $database->prepare("SELECT id, nombre, cedula FROM paciente ORDER BY nombre ASC");
$stmt->execute();
$result = $stmt->get_result();
$pacientes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pago - <?= htmlspecialchars($nombreUsuario) ?></title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .popup{ animation: transitionIn-Y-bottom 0.5s; }
        .sub-table{ animation: transitionIn-Y-bottom 0.5s; }
        .btn-view, .btn-edit, .btn-delete {
            width: 32px; height: 32px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; margin: 2px;
        }
        .btn-view { background-color: #e7f3ff; color: #007bff; }
        .btn-edit { background-color: #d1ecf1; color: #0c5460; }
        .btn-delete { background-color: #f8d7da; color: #721c24; }
        .input-text, select, textarea {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <table class="menu-container" border="0">
                <tr>
                    <td style="padding:10px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px">
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title"><?= htmlspecialchars($nombreUsuario) ?></p>
                                    <p class="profile-subtitle"><?= ucfirst($rolUsuario) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="../login.php"><input type="button" value="Cerrar sesión" class="logout-btn btn-primary-soft btn"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashbord">
                        <a href="index.php" class="non-style-link-menu"><p class="menu-text">Desempeño</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="citas.php" class="non-style-link-menu"><p class="menu-text">Citas</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-patient">
                        <a href="pacientes.php" class="non-style-link-menu"><p class="menu-text">Pacientes</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="tratamientos.php" class="non-style-link-menu"><p class="menu-text">Tratamientos</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-settings menu-active menu-icon-settings-active">
                        <a href="registrar_pago.php" class="non-style-link-menu non-style-link-menu-active"><p class="menu-text">Registrar Pago</p></a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="registrar_pago.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Recargar</button></a>
                    </td>
                    <td>
                        <form action="" method="get" class="header-search">
                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Buscar por nombre o cédula" list="pacientes">&nbsp;&nbsp;
                            <input type="Submit" value="Buscar" class="login-btn btn-primary btn">
                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Fecha</p>
                        <p class="heading-sub12"><?= date('Y-m-d') ?></p>
                    </td>
                    <td width="10%">
                        <button class="btn-label"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:30px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;">Registrar Pago</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add" class="non-style-link">
                            <button class="login-btn btn-primary btn button-icon">Agregar</button>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;">
                            Total de pagos registrados (<?= count($pagos) ?>)
                        </p>
                    </td>
                </tr>
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                            <thead>
                                <tr>
                                    <th class="table-headin">Cédula</th>
                                    <th class="table-headin">Paciente</th>
                                    <th class="table-headin">Concepto</th>
                                    <th class="table-headin">Total</th>
                                    <th class="table-headin">Pagado</th>
                                    <th class="table-headin">Estado</th>
                                    <th class="table-headin">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($pagos)): ?>
                                <tr><td colspan="7"><br><center>
                                    <img src="../img/notfound.svg" width="25%">
                                    <p class="heading-main12">No se encontraron pagos</p>
                                </center><br></td></tr>
                            <?php else: ?>
                                <?php foreach($pagos as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['cedula']) ?></td>
                                    <td><?= htmlspecialchars($p['paciente']) ?></td>
                                    <td><?= htmlspecialchars($p['concepto']) ?></td>
                                    <td>$<?= number_format($p['monto_total'], 2) ?></td>
                                    <td>$<?= number_format($p['monto_pagado'], 2) ?></td>
                                    <td><span class="status <?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    <td>
                                        <div style="display:flex;justify-content:center;">
                                            <a href="?action=view&view_id=<?= $p['id'] ?>" class="non-style-link">
                                                <button class="btn-primary-soft btn button-icon btn-view">👁️</button>
                                            </a>&nbsp;&nbsp;
                                            <a href="?action=edit&id=<?= $p['id'] ?>" class="non-style-link">
                                                <button class="btn-primary-soft btn button-icon btn-edit">✏️</button>
                                            </a>&nbsp;&nbsp;
                                            <a href="?action=delete&id=<?= $p['id'] ?>&name=<?= urlencode($p['paciente']) ?>" class="non-style-link">
                                                <button class="btn-primary-soft btn button-icon btn-delete">🗑️</button>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                       </center>
                   </td>
                </tr>
            </table>
        </div>
    </div>

    <?php
    // Modal: Agregar pago
    if ($_GET['action'] === 'add'):
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="registrar_pago.php">&times;</a>
                <div class="abc">
                    <table width="80%" class="sub-table" border="0">
                        <tr><td><p style="font-size:25px;">Registrar Pago</p></td></tr>
                        <form action="add-pago.php" method="POST">
                            <tr><td class="label-td">Paciente:</td></tr>
                            <tr><td>
                                <select name="paciente_id" required>
                                    <option value="">Seleccione un paciente</option>
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['cedula'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Concepto:</td></tr>
                            <tr><td>
                                <select name="concepto" required>
                                    <option value="">Seleccione concepto</option>
                                    <option value="Tratamiento 5 días - $60">Tratamiento 5 días - $60</option>
                                    <option value="Tratamiento 10 días - $120">Tratamiento 10 días - $120</option>
                                    <option value="Tratamiento 15 días - $180">Tratamiento 15 días - $180</option>
                                    <option value="Tratamiento 20 días - $240">Tratamiento 20 días - $240</option>
                                    <option value="Terapia única - $15">Terapia única - $15</option>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Monto Total:</td></tr>
                            <tr><td><input type="number" step="0.01" name="monto_total" required></td></tr>

                            <tr><td class="label-td">Monto Pagado:</td></tr>
                            <tr><td><input type="number" step="0.01" name="monto_pagado" required></td></tr>

                            <tr><td class="label-td">Tipo:</td></tr>
                            <tr><td>
                                <select name="tipo" required>
                                    <option value="abono">Abono</option>
                                    <option value="total">Total</option>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Método de Pago:</td></tr>
                            <tr><td>
                                <select name="metodo_pago" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta de Crédito</option>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Referencia (opcional):</td></tr>
                            <tr><td><input type="text" name="referencia" placeholder="Número de comprobante"></td></tr>

                            <tr><td colspan="2">
                                <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                <input type="submit" name="add_pago" value="Guardar" class="login-btn btn-primary btn">
                            </td></tr>
                        </form>
                    </table>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Modal: Editar pago
    if ($_GET['action'] === 'edit' && isset($_GET['id'])):
        $id = (int)$_GET['id'];
        $stmt = $database->prepare("SELECT * FROM pagos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pagoEdit = $result->fetch_assoc();
        $stmt->close();
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="registrar_pago.php">&times;</a>
                <div class="abc">
                    <table width="80%" class="sub-table" border="0">
                        <tr><td><p style="font-size:25px;">Editar Pago</p></td></tr>
                        <form action="edit-pago.php" method="POST">
                            <input type="hidden" name="id" value="<?= $pagoEdit['id'] ?>">
                            <tr><td class="label-td">Concepto:</td></tr>
                            <tr><td><input type="text" name="concepto" value="<?= htmlspecialchars($pagoEdit['concepto']) ?>" required></td></tr>

                            <tr><td class="label-td">Monto Total:</td></tr>
                            <tr><td><input type="number" step="0.01" name="monto_total" value="<?= $pagoEdit['monto_total'] ?>" required></td></tr>

                            <tr><td class="label-td">Monto Pagado:</td></tr>
                            <tr><td><input type="number" step="0.01" name="monto_pagado" value="<?= $pagoEdit['monto_pagado'] ?>" required></td></tr>

                            <tr><td class="label-td">Tipo:</td></tr>
                            <tr><td>
                                <select name="tipo">
                                    <option value="abono" <?= $pagoEdit['tipo'] === 'abono' ? 'selected' : '' ?>>Abono</option>
                                    <option value="total" <?= $pagoEdit['tipo'] === 'total' ? 'selected' : '' ?>>Total</option>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Método de Pago:</td></tr>
                            <tr><td>
                                <select name="metodo_pago">
                                    <option value="efectivo" <?= $pagoEdit['metodo_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                                    <option value="transferencia" <?= $pagoEdit['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                                    <option value="tarjeta" <?= $pagoEdit['metodo_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Referencia:</td></tr>
                            <tr><td><input type="text" name="referencia" value="<?= htmlspecialchars($pagoEdit['referencia']) ?>"></td></tr>

                            <tr><td class="label-td">Estado:</td></tr>
                            <tr><td>
                                <select name="estado">
                                    <option value="pendiente" <?= $pagoEdit['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="pagado" <?= $pagoEdit['estado'] === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                                    <option value="cancelado" <?= $pagoEdit['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </td></tr>

                            <tr><td colspan="2">
                                <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                <input type="submit" name="edit_pago" value="Actualizar" class="login-btn btn-primary btn">
                            </td></tr>
                        </form>
                    </table>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Modal: Ver pago
    if ($_GET['action'] === 'view' && isset($_GET['view_id'])):
        $stmt = $database->prepare("
            SELECT p.*, pa.nombre AS paciente, pa.cedula, u.nombre AS colaborador
            FROM pagos p
            JOIN paciente pa ON p.paciente_id = pa.id
            JOIN usuario u ON p.colaborador_id = u.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $_GET['view_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $pago = $result->fetch_assoc();
        $stmt->close();
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="registrar_pago.php">&times;</a>
                <div class="abc">
                    <table width="80%" class="sub-table" border="0">
                        <tr><td><p style="font-size:25px;">Detalles del Pago</p></td></tr>
                        <tr><td class="label-td"><label>Paciente:</label></td></tr>
                        <tr><td class="label-td"><?= htmlspecialchars($pago['paciente']) ?> (<?= $pago['cedula'] ?>)</td></tr>
                        <tr><td class="label-td"><label>Concepto:</label></td></tr>
                        <tr><td class="label-td"><?= htmlspecialchars($pago['concepto']) ?></td></tr>
                        <tr><td class="label-td"><label>Monto Total:</label></td></tr>
                        <tr><td class="label-td">$<?= number_format($pago['monto_total'], 2) ?></td></tr>
                        <tr><td class="label-td"><label>Monto Pagado:</label></td></tr>
                        <tr><td class="label-td">$<?= number_format($pago['monto_pagado'], 2) ?></td></tr>
                        <tr><td class="label-td"><label>Tipo:</label></td></tr>
                        <tr><td class="label-td"><?= ucfirst($pago['tipo']) ?></td></tr>
                        <tr><td class="label-td"><label>Método:</label></td></tr>
                        <tr><td class="label-td"><?= ucfirst($pago['metodo_pago']) ?></td></tr>
                        <tr><td class="label-td"><label>Referencia:</label></td></tr>
                        <tr><td class="label-td"><?= htmlspecialchars($pago['referencia']) ?></td></tr>
                        <tr><td class="label-td"><label>Fecha:</label></td></tr>
                        <tr><td class="label-td"><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td></tr>
                        <tr><td><br><a href="registrar_pago.php"><input type="button" value="Cerrar" class="login-btn btn-primary-soft btn"></a></td></tr>
                    </table>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Modal: Eliminar pago
    if ($_GET['action'] === 'delete' && isset($_GET['id'])):
        $id = (int)$_GET['id'];
        $name = $_GET['name'] ?? '';
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <h2>¿Está seguro?</h2>
                <a class="close" href="registrar_pago.php">&times;</a>
                <div class="content">¿Desea eliminar este pago?<br>(<?= htmlspecialchars($name) ?>)</div>
                <div style="display:flex;justify-content:center;">
                    <a href="delete-pago.php?id=<?= $id ?>" class="non-style-link"><button class="btn-primary btn">Sí</button></a>&nbsp;&nbsp;
                    <a href="registrar_pago.php" class="non-style-link"><button class="btn-primary btn">No</button></a>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Mensajes de éxito
    if (isset($_GET['success'])):
        $msg = ['1' => 'Pago registrado', '2' => 'Pago actualizado', '3' => 'Pago eliminado'];
        echo '<script>alert("' . $msg[$_GET['success']] . '"); window.location.href="registrar_pago.php";</script>';
    endif;
    ?>
</body>
</html>