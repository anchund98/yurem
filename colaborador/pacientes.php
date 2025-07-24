<?php
session_start();

// Permitir acceso solo a colaboradores o administradores
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['colaborador', 'administrador'])) {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

// Obtener ID del usuario logueado
$cedulaUsuario = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT id, nombre, rol FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedulaUsuario);
$stmt->execute();
$stmt->bind_result($usuario_id, $nombreUsuario, $rolUsuario);
$stmt->fetch();
$stmt->close();

// Filtro de búsqueda
$search = $_GET['search'] ?? '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (p.nombre LIKE ? OR p.cedula LIKE ?)";
}

// Filtrar pacientes según rol
if ($rolUsuario === 'colaborador') {
    $query = "
        SELECT p.id, p.cedula, p.nombre, p.telefono, p.estado,
               (SELECT COUNT(*) FROM horario h WHERE h.paciente_id = p.id) AS total_citas
        FROM paciente p
        WHERE p.creado_por = ? $searchCondition
        ORDER BY p.nombre ASC
    ";
    $stmt = $database->prepare($query);
    if (!empty($search)) {
        $like = "%$search%";
        $stmt->bind_param("iss", $usuario_id, $like, $like);
    } else {
        $stmt->bind_param("i", $usuario_id);
    }
} else {
    // Administrador ve todos
    $query = "
        SELECT p.id, p.cedula, p.nombre, p.telefono, p.estado,
               (SELECT COUNT(*) FROM horario h WHERE h.paciente_id = p.id) AS total_citas
        FROM paciente p
        WHERE 1=1 $searchCondition
        ORDER BY p.nombre ASC
    ";
    $stmt = $database->prepare($query);
    if (!empty($search)) {
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$pacientes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Modal: ver detalles del paciente + citas
$view_id = $_GET['view_id'] ?? null;
$paciente = null;
$citas = [];
if ($view_id) {
    $stmt = $database->prepare("SELECT * FROM paciente WHERE id = ?");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paciente = $result->fetch_assoc();
    $stmt->close();

    // Obtener citas del paciente
    $stmt = $database->prepare("
        SELECT h.id, h.fecha, h.hora, h.titulo, u.nombre AS colaborador
        FROM horario h
        JOIN usuario u ON h.colaborador_id = u.id
        WHERE h.paciente_id = ?
        ORDER BY h.fecha DESC, h.hora DESC
    ");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pacientes - <?= htmlspecialchars($nombreUsuario) ?></title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .popup{ animation: transitionIn-Y-bottom 0.5s; }
        .sub-table{ animation: transitionIn-Y-bottom 0.5s; }
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
                    <td class="menu-btn menu-icon-patient menu-active menu-icon-patient-active">
                        <a href="pacientes.php" class="non-style-link-menu non-style-link-menu-active"><p class="menu-text">Pacientes</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="tratamientos.php" class="non-style-link-menu"><p class="menu-text">Tratamientos</p></a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="pacientes.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Recargar</button></a>
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
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;">
                            Total de pacientes (<?= count($pacientes) ?>)
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
                                    <th class="table-headin">Nombre</th>
                                    <th class="table-headin">Teléfono</th>
                                    <th class="table-headin">Estado</th>
                                    <th class="table-headin">Citas</th>
                                    <th class="table-headin">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($pacientes)): ?>
                                <tr><td colspan="6"><br><center>
                                    <img src="../img/notfound.svg" width="25%">
                                    <p class="heading-main12">No se encontraron pacientes</p>
                                </center><br></td></tr>
                            <?php else: ?>
                                <?php foreach($pacientes as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['cedula']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['telefono']) ?></td>
                                    <td><span class="status <?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    <td><?= $p['total_citas'] ?></td>
                                    <td>
                                        <div style="display:flex;justify-content:center;">
                                            <a href="?action=view&view_id=<?= $p['id'] ?>" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view">Ver</button></a>
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
    if ($view_id && $paciente):
    ?>
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="pacientes.php">&times;</a>
                <div style="display:flex;justify-content:center;">
                    <div class="abc">
                        <table width="80%" class="sub-table" border="0">
                            <tr><td><p style="font-size:25px;">Detalles del Paciente</p></td></tr>
                            <tr><td class="label-td"><label>Nombre:</label></td></tr>
                            <tr><td class="label-td"><?= htmlspecialchars($paciente['nombre']) ?></td></tr>
                            <tr><td class="label-td"><label>Cédula:</label></td></tr>
                            <tr><td class="label-td"><?= htmlspecialchars($paciente['cedula']) ?></td></tr>
                            <tr><td class="label-td"><label>Teléfono:</label></td></tr>
                            <tr><td class="label-td"><?= htmlspecialchars($paciente['telefono']) ?></td></tr>
                            <tr><td class="label-td"><label>Dirección:</label></td></tr>
                            <tr><td class="label-td"><?= htmlspecialchars($paciente['direccion']) ?></td></tr>
                            <tr><td class="label-td"><label>Estado:</label></td></tr>
                            <tr><td class="label-td"><?= ucfirst($paciente['estado']) ?></td></tr>

                            <tr><td><br><strong>Citas registradas:</strong></td></tr>
                            <?php if (empty($citas)): ?>
                                <tr><td class="label-td">No tiene citas registradas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($citas as $c): ?>
                                    <tr>
                                        <td class="label-td">
                                            <?= date('d/m/Y', strtotime($c['fecha'])) ?> a las <?= date('H:i', strtotime($c['hora'])) ?><br>
                                            <strong><?= htmlspecialchars($c['titulo']) ?></strong><br>
                                            Asignado por: <?= htmlspecialchars($c['colaborador']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <tr><td><br><a href="pacientes.php"><input type="button" value="Cerrar" class="login-btn btn-primary-soft btn"></a></td></tr>
                        </table>
                    </div>
                </div>
            </center>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>