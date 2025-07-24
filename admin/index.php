<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Dashboard</title>
    <style>
        .dashbord-tables {
            animation: transitionIn-Y-over 0.5s;
        }
        .filter-container {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 30px;
        }
        .welcome-message {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }
        .date-box {
            text-align: right;
        }
        .date-box p {
            margin: 0;
        }
    </style>
</head>
<body>
<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");

// Obtener nombre del administrador desde la base de datos
$cedulaAdmin = $_SESSION['usuario'];
$adminName = "Administrador"; // valor por defecto

$query = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ?");
$query->bind_param("s", $cedulaAdmin);
$query->execute();
$result = $query->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $adminName = $row['nombre'];
}
$query->close();
?>

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
                            <td>
                                <p class="profile-title"><?php echo htmlspecialchars($adminName); ?></p>
                                <p class="profile-subtitle"><?php echo htmlspecialchars($cedulaAdmin); ?></p>
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
                <td class="menu-btn menu-icon-dashbord menu-active menu-icon-dashbord-active">
                    <a href="index.php" class="non-style-link-menu non-style-link-menu-active">
                        <div><p class="menu-text">Dashboard</p></div>
                    </a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor">
                    <a href="colaboradores.php" class="non-style-link-menu">
                        <div><p class="menu-text">Colaboradores</p></div>
                    </a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-patient">
                    <a href="pacientes.php" class="non-style-link-menu">
                        <div><p class="menu-text">Pacientes</p></div>
                    </a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment">
                    <a href="ingresos.php" class="non-style-link-menu">
                        <div><p class="menu-text">Ingresos</p></div>
                    </a>
                </td>
            </tr>
        </table>
    </div>
    <div class="dash-body" style="margin-top: 15px">

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-message">
                ¡Bienvenido, <strong><?= $adminName ?></strong>!
            </div>
            <div class="date-box">
                <p style="font-size: 14px; color: #777;">Fecha de hoy</p>
                <p class="heading-sub12" style="font-size: 18px; font-weight: bold;">
                    <?php date_default_timezone_set('America/Guayaquil'); echo date('Y-m-d'); ?>
                </p>
            </div>
        </div>

        <?php
        $today = date('Y-m-d');
        $pacienteResult = $database->query("SELECT COUNT(*) AS total FROM paciente");
        $pacientes = $pacienteResult->fetch_assoc()['total'];

        $colaboradorResult = $database->query("SELECT COUNT(*) AS total FROM usuario WHERE rol='colaborador'");
        $colaboradores = $colaboradorResult->fetch_assoc()['total'];

        $ingresoResult = $database->query("SELECT SUM(monto) AS total FROM ingresos");
        $ingresos = $ingresoResult->fetch_assoc()['total'] ?? 0;

        $horarioResult = $database->query("SELECT COUNT(*) AS total FROM horario WHERE fecha='$today'");
        $sesionesHoy = $horarioResult->fetch_assoc()['total'];
        ?>

        <center>
            <table class="filter-container" style="border: none;" border="0">
                <tr><td colspan="4"><p style="font-size: 20px;font-weight:600;padding-left: 12px;">Resumen</p></td></tr>
                <tr>
                    <td style="width: 25%;">
                        <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display: flex">
                            <div>
                                <div class="h1-dashboard"><?= $colaboradores ?></div><br>
                                <div class="h3-dashboard">Colaboradores</div>
                            </div>
                            <div class="btn-icon-back dashboard-icons" style="background-image: url('../img/icons/doctors-hover.svg');"></div>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display: flex">
                            <div>
                                <div class="h1-dashboard"><?= $pacientes ?></div><br>
                                <div class="h3-dashboard">Pacientes</div>
                            </div>
                            <div class="btn-icon-back dashboard-icons" style="background-image: url('../img/icons/patients-hover.svg');"></div>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display: flex">
                            <div>
                                <div class="h1-dashboard">$<?= number_format($ingresos, 2) ?></div><br>
                                <div class="h3-dashboard">Ingresos</div>
                            </div>
                            <div class="btn-icon-back dashboard-icons" style="background-image: url('../img/icons/book-hover.svg');"></div>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display: flex">
                            <div>
                                <div class="h1-dashboard"><?= $sesionesHoy ?></div><br>
                                <div class="h3-dashboard">Sesiones hoy</div>
                            </div>
                            <div class="btn-icon-back dashboard-icons" style="background-image: url('../img/icons/session-iceblue.svg');"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </center>
    </div>
</div>
</body>
</html>
