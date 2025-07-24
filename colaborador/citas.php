<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'colaborador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");
$cedulaColaborador = $_SESSION['usuario'];

// Datos del colaborador
$stmt = $database->prepare("SELECT id, nombre FROM usuario WHERE cedula = ? AND rol = 'colaborador'");
$stmt->bind_param("s", $cedulaColaborador);
$stmt->execute();
$stmt->bind_result($colaborador_id, $nombreColaborador);
$stmt->fetch();
$stmt->close();

// Filtro y búsqueda
$search = $_GET['search'] ?? '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (p.nombre LIKE ? OR p.cedula LIKE ?)";
}

// Obtener pacientes
$query = "
    SELECT p.id, p.cedula, p.nombre, p.telefono, p.estado
    FROM paciente p
    WHERE 1=1 $searchCondition
    ORDER BY p.nombre ASC
";
$stmt = $database->prepare($query);
if (!empty($search)) {
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
}
$stmt->execute();
$result = $stmt->get_result();
$pacientes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar nueva cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_cita'])) {
    $paciente_id = $_POST['paciente_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $titulo = $_POST['titulo'];

    $stmt = $database->prepare("INSERT INTO horario (fecha, hora, titulo, paciente_id, colaborador_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $fecha, $hora, $titulo, $paciente_id, $colaborador_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $database->prepare("UPDATE paciente SET estado = 'atendido' WHERE id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $stmt->close();

    echo '<script>window.location.href="citas.php?success=1";</script>';
    exit;
}

// Modal: ver paciente
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

    $stmt = $database->prepare("SELECT * FROM horario WHERE paciente_id = ? ORDER BY fecha DESC");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Agregar nuevo paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $nombre = $_POST['nombre'];
    $cedula = $_POST['cedula'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $telefono = $_POST['telefono'] ?? null;
    $direccion = $_POST['direccion'] ?? null;

    // Verificar si ya existe
    $stmt = $database->prepare("SELECT id FROM paciente WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo '<script>alert("Ya existe un paciente con esta cédula."); window.location.href="citas.php";</script>';
        exit;
    }
    $stmt->close();

    // Insertar paciente
    $stmt = $database->prepare("INSERT INTO paciente (cedula, nombre, fecha_nacimiento, telefono, direccion, creado_por, estado) VALUES (?, ?, ?, ?, ?, ?, 'nuevo')");
    $stmt->bind_param("sssssi", $cedula, $nombre, $fecha_nacimiento, $telefono, $direccion, $colaborador_id);
    $stmt->execute();
    $stmt->close();

    echo '<script>alert("Paciente agregado correctamente."); window.location.href="citas.php";</script>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citas - <?= htmlspecialchars($nombreColaborador) ?></title>
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
                                    <p class="profile-title"><?= htmlspecialchars($nombreColaborador) ?></p>
                                    <p class="profile-subtitle">Colaborador</p>
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
                    <td class="menu-btn menu-icon-appoinment menu-active menu-icon-appoinment-active">
                        <a href="citas.php" class="non-style-link-menu non-style-link-menu-active"><p class="menu-text">Citas</p></a>
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
            </table>
        </div>

        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="citas.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Recargar</button></a>
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
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;">Agregar Nueva Cita</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add_patient" class="non-style-link">
                            <button class="login-btn btn-primary btn button-icon">Agregar Paciente</button>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;">
                            Todos los pacientes (<?= count($pacientes) ?>)
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
                                    <th class="table-headin">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($pacientes)): ?>
                                <tr><td colspan="5"><br><center>
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
                                    <td>
                                        <div style="display:flex;justify-content:center;">
                                            <a href="?action=view&view_id=<?= $p['id'] ?>" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view">Ver</button></a>&nbsp;&nbsp;
                                            <a href="?action=add&paciente_id=<?= $p['id'] ?>" class="non-style-link"><button class="btn-primary-soft btn button-icon">Cita</button></a>
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
    // Modal: Agregar cita
    if ($_GET['action'] === 'add') {
        $paciente_id = $_GET['paciente_id'] ?? '';
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="citas.php">&times;</a>
                    <div style="display:flex;justify-content:center;">
                        <div class="abc">
                            <table width="80%" class="sub-table" border="0">
                                <tr><td><p style="font-size:25px;">Asignar Nueva Cita</p></td></tr>
                                <form action="citas.php" method="POST">
                                <tr><td class="label-td">Paciente:</td></tr>
                                <tr><td>
                                    <select name="paciente_id" required>';
                                    foreach ($pacientes as $p) {
                                        $selected = $p['id'] == $paciente_id ? 'selected' : '';
                                        echo "<option value='{$p['id']}' $selected>{$p['nombre']} ({$p['cedula']})</option>";
                                    }
        echo '                  </select>
                                </td></tr>
                                <tr><td class="label-td">Fecha:</td></tr>
                                <tr><td><input type="date" name="fecha" required min="'.date('Y-m-d').'"></td></tr>
                                <tr><td class="label-td">Hora:</td></tr>
                                <tr><td><input type="time" name="hora" required></td></tr>
                                <tr><td class="label-td">Título:</td></tr>
                                <tr><td><input type="text" name="titulo" placeholder="Ej: Primera sesión" required></td></tr>
                                <tr><td colspan="2">
                                    <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                    <input type="submit" name="asignar_cita" value="Guardar" class="login-btn btn-primary btn">
                                </td></tr>
                                </form>
                            </table>
                        </div>
                    </div>
                </center>
            </div>
        </div>';
    }

    // Modal: Ver paciente
    if ($view_id && $paciente) {
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="citas.php">&times;</a>
                    <div style="display:flex;justify-content:center;">
                        <div class="abc">
                            <table width="80%" class="sub-table" border="0">
                                <tr><td><p style="font-size:25px;">Detalles del Paciente</p></td></tr>
                                <tr><td class="label-td"><label>Nombre:</label></td></tr>
                                <tr><td class="label-td">'.$paciente['nombre'].'</td></tr>
                                <tr><td class="label-td"><label>Cédula:</label></td></tr>
                                <tr><td class="label-td">'.$paciente['cedula'].'</td></tr>
                                <tr><td class="label-td"><label>Teléfono:</label></td></tr>
                                <tr><td class="label-td">'.$paciente['telefono'].'</td></tr>
                                <tr><td class="label-td"><label>Estado:</label></td></tr>
                                <tr><td class="label-td">'.ucfirst($paciente['estado']).'</td></tr>
                                <tr><td><br><a href="citas.php"><input type="button" value="Cerrar" class="login-btn btn-primary-soft btn"></a></td></tr>
                            </table>
                        </div>
                    </div>
                </center>
            </div>
        </div>';
    }

    // Modal: éxito
    if (isset($_GET['success'])) {
        echo '
        <div id="popup1" class="overlay">
            <div class="popup"><center><br>
                <h2>¡Cita asignada con éxito!</h2>
                <a class="close" href="citas.php">&times;</a>
                <div style="display:flex;justify-content:center;">
                    <a href="citas.php" class="non-style-link"><button class="btn-primary btn">OK</button></a>
                </div><br>
            </center></div>
        </div>';
    }
    // Modal: agregar paciente 
        if ($_GET['action'] === 'add_patient') {
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="citas.php">&times;</a>
                        <div style="display:flex;justify-content:center;">
                            <div class="abc">
                                <table width="80%" class="sub-table" border="0">
                                    <tr><td><p style="font-size:25px;">Agregar Nuevo Paciente</p></td></tr>
                                    <form action="citas.php" method="POST">
                                    <tr><td class="label-td">Nombre:</td></tr>
                                    <tr><td><input type="text" name="nombre" class="input-text" required></td></tr>

                                    <tr><td class="label-td">Cédula:</td></tr>
                                    <tr><td><input type="text" name="cedula" class="input-text" required></td></tr>

                                    <tr><td class="label-td">Fecha de Nacimiento:</td></tr>
                                    <tr><td><input type="date" name="fecha_nacimiento" class="input-text"></td></tr>

                                    <tr><td class="label-td">Teléfono:</td></tr>
                                    <tr><td><input type="text" name="telefono" class="input-text"></td></tr>

                                    <tr><td class="label-td">Dirección:</td></tr>
                                    <tr><td><input type="text" name="direccion" class="input-text"></td></tr>

                                    <tr><td colspan="2">
                                        <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;
                                        <input type="submit" name="add_patient" value="Guardar" class="login-btn btn-primary btn">
                                    </td></tr>
                                    </form>
                                </table>
                            </div>
                        </div>
                    </center>
                </div>
            </div>';
        }
        ?>
</body>
</html>