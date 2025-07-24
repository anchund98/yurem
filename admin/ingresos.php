<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

// Datos del admin
$cedulaAdmin = $_SESSION['usuario'];
$stmt = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ?");
$stmt->bind_param("s", $cedulaAdmin);
$stmt->execute();
$stmt->bind_result($adminName);
$stmt->fetch();
$stmt->close();

// Filtros
$modo          = $_GET['modo']  ?? 'diario';
$fecha         = $_GET['fecha'] ?? date('Y-m-d');
$idColaborador = $_GET['colaborador'] ?? '';

switch ($modo) {
    case 'semanal':
        $inicio = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
        $fin    = date('Y-m-d', strtotime('sunday this week', strtotime($fecha)));
        break;
    case 'mensual':
        $inicio = date('Y-m-01', strtotime($fecha));
        $fin    = date('Y-m-t', strtotime($fecha));
        break;
    default:
        $inicio = $fin = $fecha;
}

// Consulta con filtros
$sql = "SELECT i.id, i.fecha, i.monto, i.descripcion, p.nombre AS paciente, u.nombre AS colaborador
        FROM ingresos i
        JOIN paciente p ON i.paciente_id = p.id
        JOIN usuario u  ON i.colaborador_id = u.id
        WHERE i.fecha BETWEEN ? AND ?";

$params = [$inicio, $fin];
$types  = "ss";

if (!empty($idColaborador)) {
    $sql   .= " AND i.colaborador_id = ?";
    $params[] = $idColaborador;
    $types   .= "i";
}

$sql .= " ORDER BY i.fecha DESC";

$stmt = $database->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Total
$totalSql = "SELECT SUM(monto) FROM ingresos WHERE fecha BETWEEN ? AND ?";
$totalParams = [$inicio, $fin];
$totalTypes  = "ss";

if (!empty($idColaborador)) {
    $totalSql   .= " AND colaborador_id = ?";
    $totalParams[] = $idColaborador;
    $totalTypes   .= "i";
}

$stmt2 = $database->prepare($totalSql);
$stmt2->bind_param($totalTypes, ...$totalParams);
$stmt2->execute();
$stmt2->bind_result($total);
$stmt2->fetch();
$stmt2->close();

// Colaboradores activos
$colaboradores = $database->query("SELECT DISTINCT u.id, u.nombre 
                                   FROM usuario u 
                                   JOIN ingresos i ON u.id = i.colaborador_id
                                   WHERE u.rol='colaborador' AND u.estado=1
                                   ORDER BY u.nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresos - Yurem</title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .popup,.sub-table{animation:transitionIn-Y-bottom .5s}
    </style>
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
            <tr><td class="menu-btn menu-icon-patient"><a href="pacientes.php" class="non-style-link-menu"><div><p class="menu-text">Pacientes</p></div></a></td></tr>
            <tr><td class="menu-btn menu-icon-appoinment menu-active menu-icon-appoinment-active"><a href="ingresos.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Ingresos</p></div></a></td></tr>
        </table>
    </div>

    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing:0;margin-top:25px">
            <tr>
                <td width="13%"><a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back">Volver</button></a></td>
                <td colspan="2">
                    <form action="" method="get" class="header-search" style="display:flex;align-items:center;gap:5px;">
                        <select name="modo" id="modo" class="box" onchange="toggleFecha()">
                            <option value="diario"  <?= $modo==='diario'  ?'selected':'' ?>>Diario</option>
                            <option value="semanal" <?= $modo==='semanal' ?'selected':'' ?>>Semanal</option>
                            <option value="mensual" <?= $modo==='mensual' ?'selected':'' ?>>Mensual</option>
                        </select>
                        <input type="text" name="fecha" id="fecha" class="input-text" placeholder="Seleccione..." value="<?= htmlspecialchars($fecha) ?>">
                        <select name="colaborador" class="box" style="min-width:150px;">
                            <option value="">Todos los colaboradores</option>
                            <?php while($c = $colaboradores->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" <?= $idColaborador==$c['id'] ?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <input type="submit" value="Filtrar" class="login-btn btn-primary btn">
                        <a href="exportar-ingresos-pdf.php?modo=<?= urlencode($modo) ?>&fecha=<?= urlencode($fecha) ?>&colaborador=<?= urlencode($idColaborador) ?>" class="login-btn btn-primary btn">Exportar PDF</a>
                    </form>
                </td>
                <td width="15%">
                    <p style="font-size:14px;color:#777;text-align:right;">Total <?= ucfirst($modo) ?></p>
                    <p class="heading-sub12">$ <?= number_format($total, 2) ?></p>
                </td>
                <td width="10%"><button class="btn-label"><img src="../img/calendar.svg" width="100%"></button></td>
            </tr>

            <tr>
                <td colspan="4" style="padding-top:10px;">
                    <p class="heading-main12" style="margin-left:45px;font-size:18px;">Ingresos registrados (<?= $res->num_rows ?>)</p>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <center>
                        <div class="abc scroll">
                            <table width="93%" class="sub-table scrolldown" border="0">
                                <thead>
                                <tr>
                                    <th class="table-headin">Fecha</th>
                                    <th class="table-headin">Paciente</th>
                                    <th class="table-headin">Monto</th>
                                    <th class="table-headin">Descripción</th>
                                    <th class="table-headin">Colaborador</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($res->num_rows === 0): ?>
                                    <tr><td colspan="5" style="text-align:center;padding:30px;">
                                        <img src="../img/notfound.svg" width="25%">
                                        <p>No hay ingresos en este rango</p>
                                    </td></tr>
                                <?php else: ?>
                                    <?php while ($row = $res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['fecha'] ?></td>
                                            <td><?= htmlspecialchars($row['paciente']) ?></td>
                                            <td>$<?= number_format($row['monto'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                            <td><?= htmlspecialchars($row['colaborador']) ?></td>
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

<script>
function toggleFecha() {
    const modo = document.getElementById('modo').value;
    flatpickr("#fecha", {
        locale: "es",
        dateFormat: modo === "mensual" ? "Y-m" : "Y-m-d",
        mode: modo === "semanal" ? "range" : "single",
        altFormat: modo === "mensual" ? "F Y" : "d/m/Y"
    });
}
toggleFecha();
</script>
</body>
</html>