<?php
session_start();

// Solo colaboradores o administradores
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
        SELECT t.id, pa.nombre AS paciente, pa.cedula, t.diagnostico, t.tratamiento, t.fecha_inicio, t.estado
        FROM tratamiento t
        JOIN paciente pa ON t.paciente_id = pa.id
        WHERE t.colaborador_id = ? $searchCondition
        ORDER BY t.fecha_inicio DESC
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
        SELECT t.id, pa.nombre AS paciente, pa.cedula, t.diagnostico, t.tratamiento, t.fecha_inicio, t.estado
        FROM tratamiento t
        JOIN paciente pa ON t.paciente_id = pa.id
        WHERE 1=1 $searchCondition
        ORDER BY t.fecha_inicio DESC
    ";
    $stmt = $database->prepare($query);
    if (!empty($search)) {
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$tratamientos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Modal: ver detalles
$view_id = $_GET['view_id'] ?? null;
$tratamiento = null;
if ($view_id) {
    $stmt = $database->prepare("
        SELECT t.*, pa.nombre AS paciente, pa.cedula, u.nombre AS colaborador
        FROM tratamiento t
        JOIN paciente pa ON t.paciente_id = pa.id
        JOIN usuario u ON t.colaborador_id = u.id
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tratamiento = $result->fetch_assoc();
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
    <title>Tratamientos - <?= htmlspecialchars($nombreUsuario) ?></title>
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
                    <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                        <a href="tratamientos.php" class="non-style-link-menu non-style-link-menu-active"><p class="menu-text">Tratamientos</p></a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="tratamientos.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Recargar</button></a>
                    </td>
                    <td>
                        <form action="" method="get" class="header-search">
                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Buscar por nombre o cédula" list="pacientes">&nbsp;&nbsp;
                            <?php
                                echo '<datalist id="pacientes">';
                                $list = $database->query("SELECT nombre, cedula FROM paciente");
                                while($row=$list->fetch_assoc()){
                                    echo "<option value='".$row["nombre"]."'>";
                                    echo "<option value='".$row["cedula"]."'>";
                                }
                                echo '</datalist>';
                            ?>
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
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;">Agregar Tratamiento</p>
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
                            Total de tratamientos (<?= count($tratamientos) ?>)
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
                                    <th class="table-headin">Diagnóstico</th>
                                    <th class="table-headin">Fecha Inicio</th>
                                    <th class="table-headin">Estado</th>
                                    <th class="table-headin">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($tratamientos)): ?>
                                <tr><td colspan="6"><br><center>
                                    <img src="../img/notfound.svg" width="25%">
                                    <p class="heading-main12">No se encontraron tratamientos</p>
                                </center><br></td></tr>
                            <?php else: ?>
                                <?php foreach($tratamientos as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['cedula']) ?></td>
                                    <td><?= htmlspecialchars($t['paciente']) ?></td>
                                    <td><?= htmlspecialchars(substr($t['diagnostico'], 0, 40)) ?>...</td>
                                    <td><?= date('d/m/Y', strtotime($t['fecha_inicio'])) ?></td>
                                    <td><span class="status <?= $t['estado'] ?>"><?= ucfirst($t['estado']) ?></span></td>
                                    <td>
                                        <div style="display:flex;justify-content:center;">
                                            <a href="?action=view&view_id=<?= $t['id'] ?>" class="non-style-link">
                                                <button class="btn-primary-soft btn button-icon btn-view">👁️</button>
                                            </a>&nbsp;&nbsp;
                                            <a href="?action=edit&id=<?= $t['id'] ?>" class="non-style-link">
                                                <button class="btn-primary-soft btn button-icon btn-edit">✏️</button>
                                            </a>&nbsp;&nbsp;
                                            <a href="?action=delete&id=<?= $t['id'] ?>&name=<?= urlencode($t['paciente']) ?>" class="non-style-link">
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
    // Modal: Agregar tratamiento
    if ($_GET['action'] === 'add'):
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="tratamientos.php">&times;</a>
                <div class="abc">
                    <table width="80%" class="sub-table" border="0">
                        <tr><td><p style="font-size:25px;">Agregar Tratamiento</p></td></tr>
                        <form action="add-tratamiento.php" method="POST">
                            <tr><td class="label-td">Paciente:</td></tr>
                            <tr><td>
                                <select name="paciente_id" required>
                                    <option value="">Seleccione un paciente</option>
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['cedula'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>

                            <tr><td class="label-td">Diagnóstico:</td></tr>
                            <tr><td><textarea name="diagnostico" rows="3" class="input-text" required></textarea></td></tr>

                            <tr><td class="label-td">Tratamiento:</td></tr>
                            <tr><td><textarea name="tratamiento" rows="3" class="input-text" required></textarea></td></tr>

                            <tr><td class="label-td">Fecha Inicio:</td></tr>
                            <tr><td><input type="date" name="fecha_inicio" required></td></tr>

                            <tr><td class="label-td">Fecha Fin (opcional):</td></tr>
                            <tr><td><input type="date" name="fecha_fin"></td></tr>

                            <tr><td colspan="2">
                                <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                <input type="submit" name="add_tratamiento" value="Guardar" class="login-btn btn-primary btn">
                            </td></tr>
                        </form>
                    </table>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Modal: Editar tratamiento
    if ($_GET['action'] === 'edit' && isset($_GET['id'])):
        $id = (int)$_GET['id'];
        $stmt = $database->prepare("SELECT * FROM tratamiento WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tratamientoEdit = $result->fetch_assoc();
        $stmt->close();
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="tratamientos.php">&times;</a>
                <div class="abc">
                    <table width="80%" class="sub-table" border="0">
                        <tr><td><p style="font-size:25px;">Editar Tratamiento</p></td></tr>
                        <form action="edit-tratamiento.php" method="POST">
                            <input type="hidden" name="id" value="<?= $tratamientoEdit['id'] ?>">
                            <tr><td class="label-td">Diagnóstico:</td></tr>
                            <tr><td><textarea name="diagnostico" rows="3" class="input-text" required><?= htmlspecialchars($tratamientoEdit['diagnostico']) ?></textarea></td></tr>

                            <tr><td class="label-td">Tratamiento:</td></tr>
                            <tr><td><textarea name="tratamiento" rows="3" class="input-text" required><?= htmlspecialchars($tratamientoEdit['tratamiento']) ?></textarea></td></tr>

                            <tr><td class="label-td">Fecha Inicio:</td></tr>
                            <tr><td><input type="date" name="fecha_inicio" value="<?= $tratamientoEdit['fecha_inicio'] ?>" required></td></tr>

                            <tr><td class="label-td">Fecha Fin:</td></tr>
                            <tr><td><input type="date" name="fecha_fin" value="<?= $tratamientoEdit['fecha_fin'] ?>"></td></tr>

                            <tr><td class="label-td">Estado:</td></tr>
                            <tr><td>
                                <select name="estado">
                                    <option value="activo" <?= $tratamientoEdit['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="finalizado" <?= $tratamientoEdit['estado'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                    <option value="cancelado" <?= $tratamientoEdit['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </td></tr>

                            <tr><td colspan="2">
                                <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                <input type="submit" name="edit_tratamiento" value="Actualizar" class="login-btn btn-primary btn">
                            </td></tr>
                        </form>
                    </table>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Modal: Eliminar tratamiento
    if ($_GET['action'] === 'delete' && isset($_GET['id'])):
        $id = (int)$_GET['id'];
        $name = $_GET['name'] ?? '';
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <h2>¿Está seguro?</h2>
                <a class="close" href="tratamientos.php">&times;</a>
                <div class="content">¿Desea eliminar este tratamiento?<br>(<?= htmlspecialchars($name) ?>)</div>
                <div style="display:flex;justify-content:center;">
                    <a href="delete-tratamiento.php?id=<?= $id ?>" class="non-style-link"><button class="btn-primary btn">Sí</button></a>&nbsp;&nbsp;
                    <a href="tratamientos.php" class="non-style-link"><button class="btn-primary btn">No</button></a>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Mensajes de éxito
    if (isset($_GET['success'])):
        $msg = ['1' => 'Tratamiento agregado', '2' => 'Tratamiento actualizado', '3' => 'Tratamiento eliminado'];
        echo '<script>alert("' . $msg[$_GET['success']] . '"); window.location.href="tratamientos.php";</script>';
    endif;
    ?>
</body>
</html>