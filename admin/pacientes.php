<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

// Nombre del admin
$cedulaAdmin = $_SESSION['usuario'];
$adminName = "Administrador";
$stmt = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedulaAdmin);
$stmt->execute();
$stmt->bind_result($adminName);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pacientes</title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>.popup,.sub-table{animation:transitionIn-Y-bottom .5s}</style>
</head>
<body>
<div class="container">
    <div class="menu">
        <table class="menu-container">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table class="profile-container">
                        <tr>
                            <td width="30%"><img src="../img/user.png" width="100%" style="border-radius:50%"></td>
                            <td>
                                <p class="profile-title"><?= htmlspecialchars($adminName) ?></p>
                                <p class="profile-subtitle"><?= htmlspecialchars($cedulaAdmin) ?></p>
                            </td>
                        </tr>
                        <tr><td colspan="2"><a href="../logout.php"><input type="button" value="Cerrar sesión" class="logout-btn btn-primary-soft btn"></a></td></tr>
                    </table>
                </td>
            </tr>
            <tr><td class="menu-btn menu-icon-dashbord"><a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Dashboard</p></div></a></td></tr>
            <tr><td class="menu-btn menu-icon-doctor"><a href="colaboradores.php" class="non-style-link-menu"><div><p class="menu-text">Colaboradores</p></div></a></td></tr>
            <tr><td class="menu-btn menu-icon-patient menu-active menu-icon-patient-active"><a href="pacientes.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Pacientes</p></div></a></td></tr>
            <tr><td class="menu-btn menu-icon-appoinment"><a href="ingresos.php" class="non-style-link-menu"><div><p class="menu-text">Ingresos</p></div></a></td></tr>
        </table>
    </div>

    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing:0;margin-top:25px">
            <tr>
                <td width="13%"><a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back">Volver</button></a></td>
                <td>
                    <form action="" method="post" class="header-search">
                        <input type="search" name="search" class="input-text header-searchbar" placeholder="Buscar por nombre o cédula" list="pacientes">&nbsp;&nbsp;
                        <?php
                        echo '<datalist id="pacientes">';
                        $stmt = $database->prepare("SELECT nombre, cedula FROM paciente");
                        $stmt->execute();
                        $stmt->bind_result($n,$c);
                        while($stmt->fetch()){ echo "<option value='$n'><option value='$c'>"; }
                        $stmt->close();
                        ?>
                        <input type="submit" value="Buscar" class="login-btn btn-primary btn">
                    </form>
                </td>
                <td width="15%">
                    <p style="font-size:14px;color:#777;text-align:right;">Fecha actual</p>
                    <p class="heading-sub12"><?= date('Y-m-d') ?></p>
                </td>
                <td width="10%"><button class="btn-label"><img src="../img/calendar.svg" width="100%"></button></td>
            </tr>

            <tr>
                <td colspan="4" style="padding-top:10px;">
                    <p class="heading-main12" style="margin-left:45px;font-size:18px;">
                        Total Pacientes (<?php
                        $stmt = $database->prepare("SELECT COUNT(*) FROM paciente");
                        $stmt->execute();
                        $stmt->bind_result($total);
                        $stmt->fetch();
                        echo $total;
                        $stmt->close();
                        ?>)
                    </p>
                </td>
            </tr>

            <?php
            $sql = "SELECT p.id, p.nombre, p.cedula, p.telefono, p.direccion, p.fecha_nacimiento, u.nombre AS colaborador
                    FROM paciente p
                    LEFT JOIN usuario u ON p.creado_por = u.id";
            if (!empty($_POST['search'])) {
                $keyword = '%' . $_POST['search'] . '%';
                $sql .= " WHERE p.nombre LIKE ? OR p.cedula LIKE ?";
                $stmt = $database->prepare($sql);
                $stmt->bind_param("ss", $keyword, $keyword);
            } else {
                $sql .= " ORDER BY p.id DESC";
                $stmt = $database->prepare($sql);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            ?>

            <tr>
                <td colspan="4">
                    <center>
                        <div class="abc scroll">
                            <table width="93%" class="sub-table scrolldown" border="0">
                                <thead>
                                <tr>
                                    <th class="table-headin">Nombre</th>
                                    <th class="table-headin">Cédula</th>
                                    <th class="table-headin">Teléfono</th>
                                    <th class="table-headin">Fecha nac.</th>
                                    <th class="table-headin">Dirección</th>
                                    <th class="table-headin">Atendido por</th>
                                    <th class="table-headin">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($res->num_rows === 0): ?>
                                    <tr><td colspan="7" style="text-align:center;padding:30px;">
                                        <img src="../img/notfound.svg" width="25%">
                                        <p>No se encontraron pacientes</p>
                                        <a href="pacientes.php"><button class="login-btn btn-primary-soft btn">Mostrar todos</button></a>
                                    </td></tr>
                                <?php else: ?>
                                    <?php while ($row = $res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                                            <td><?= htmlspecialchars($row['cedula']) ?></td>
                                            <td><?= htmlspecialchars($row['telefono']) ?></td>
                                            <td><?= $row['fecha_nacimiento'] ?></td>
                                            <td><?= htmlspecialchars(substr($row['direccion'], 0, 25)) ?></td>
                                            <td><?= htmlspecialchars($row['colaborador'] ?? '—') ?></td>
                                            <td>
                                                <a href="?action=view&id=<?= $row['id'] ?>" class="non-style-link">
                                                    <button class="btn-primary-soft btn button-icon btn-view">Ver</button>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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
// Pop-up vista
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'view'):
    $id = intval($_GET['id']);
    $stmt = $database->prepare("SELECT p.*, u.nombre AS colaborador
                                FROM paciente p
                                LEFT JOIN usuario u ON p.creado_por = u.id
                                WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()):
?>
<div id="popup1" class="overlay">
    <div class="popup">
        <center>
            <a class="close" href="pacientes.php">&times;</a>
            <p style="font-size:25px;">Detalles del paciente</p>
            <table width="80%" class="sub-table">
                <tr><td><strong>Nombre:</strong></td><td><?= htmlspecialchars($row['nombre']) ?></td></tr>
                <tr><td><strong>Cédula:</strong></td><td><?= htmlspecialchars($row['cedula']) ?></td></tr>
                <tr><td><strong>Teléfono:</strong></td><td><?= htmlspecialchars($row['telefono']) ?></td></tr>
                <tr><td><strong>Fecha nac.:</strong></td><td><?= $row['fecha_nacimiento'] ?></td></tr>
                <tr><td><strong>Dirección:</strong></td><td><?= htmlspecialchars($row['direccion']) ?></td></tr>
                <tr><td><strong>Atendido por:</strong></td><td><?= htmlspecialchars($row['colaborador'] ?? '—') ?></td></tr>
                <tr><td colspan="2"><a href="pacientes.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a></td></tr>
            </table>
        </center>
    </div>
</div>
<?php
    endif;
    $stmt->close();
endif;
?>
</body>
</html>