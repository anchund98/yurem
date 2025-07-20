<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'colaborador') {
    header("Location: ../login.php");
    exit;
}
include("../connection.php");
$cedulaColaborador = $_SESSION['usuario'];

// Datos del colaborador
$stmt = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ? AND rol = 'colaborador'");
$stmt->bind_param("s", $cedulaColaborador);
$stmt->execute();
$stmt->bind_result($nombreColaborador);
$stmt->fetch();
$stmt->close();

// Total pacientes
$stmt = $database->prepare("SELECT COUNT(DISTINCT p.id) 
    FROM paciente p 
    JOIN horario h ON p.id = h.paciente_id 
    WHERE h.colaborador_id = (SELECT id FROM usuario WHERE cedula = ?)");
$stmt->bind_param("s", $cedulaColaborador);
$stmt->execute();
$stmt->bind_result($totalPacientes);
$stmt->fetch();
$stmt->close();

// Datos semanales
$semana = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $stmt = $database->prepare("SELECT COUNT(*) 
        FROM horario 
        WHERE colaborador_id = (SELECT id FROM usuario WHERE cedula = ?) 
        AND fecha = ?");
    $stmt->bind_param("ss", $cedulaColaborador, $fecha);
    $stmt->execute();
    $stmt->bind_result($cantidad);
    $stmt->fetch();
    $semana[$fecha] = $cantidad;
    $stmt->close();
}

date_default_timezone_set('America/Guayaquil');
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - <?= htmlspecialchars($nombreColaborador) ?></title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashbord-tables,.doctor-heade{
            animation: transitionIn-Y-over 0.5s;
        }
        .filter-container{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table,#anim{
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>
</head>
<style>
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .container {
            flex-direction: column;
        }

        .menu {
            width: 100%;
            position: relative;
            height: auto;
            z-index: 1000;
        }

        .menu-container {
            width: 100%;
        }

        .dash-body {
            margin-left: 0;
            margin-top: 0;
            width: 100%;
            padding: 10px;
        }

        .dashboard-container {
            flex-direction: column;
            gap: 10px;
        }

        .dashboard-items {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }

        .filter-container {
            width: 100%;
        }

        .doctor-header {
            padding: 10px;
            text-align: center;
        }

        /* Botón hamburguesa */
        .menu-toggle {
            display: block;
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 18px;
            cursor: pointer;
            z-index: 1001;
        }

        .menu {
            display: none;
        }

        .menu.show {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .menu-toggle {
            display: none;
        }

        .menu {
            display: block !important;
        }
    }
</style>


<body>
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menú</button>
    <div class="container">
        <!-- Sidebar -->
        <div class="menu">
            <table class="menu-container">
                <tr>
                    <td colspan="2">
                        <table class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px">
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title"><?= htmlspecialchars(substr($nombreColaborador, 0, 13)) ?></p>
                                    <p class="profile-subtitle">Colaborador</p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="../login.php"><input type="button" value="Cerrar Sesion" class="logout-btn btn-primary-soft btn"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashbord menu-active menu-icon-dashbord-active">
                        <a href="index.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Desempeño</p></div></a>
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
                    <td class="menu-btn menu-icon-settings">
                        <a href="registrar_pago.php" class="non-style-link-menu"><p class="menu-text">Registrar Pago</p></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-settings">
                        <a href="ajustes.php" class="non-style-link-menu"><p class="menu-text">Ajustes</p></a>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Contenido -->
        <div class="dash-body" style="margin-top: 15px">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;">
                <tr>
                    <td colspan="1" class="nav-bar">
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;margin-left:20px;">Panel de Desempeño</p>
                    </td>
                    <td width="25%"></td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Fecha de hoy
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?= $today ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                            <img src="../img/calendar.svg" width="100%">
                        </button>
                    </td>
                </tr>

                <!-- Banner de bienvenida -->
                <tr>
                    <td colspan="4">
                        <center>
                            <table class="filter-container doctor-header" style="border: none;width:95%" border="0">
                                <tr>
                                    <td>
                                        <h3>¡Bienvenido!</h3>
                                        <h1><?= htmlspecialchars($nombreColaborador) ?>.</h1>
                                        <p>
                                            💡 <em>"Cada terapia es un paso hacia la sanación. Gracias por ser parte del cambio."</em><br><br>
                                            Puedes revisar tus pacientes, registrar pagos y más desde este panel.
                                        </p>
                                        <a href="pacientes.php" class="non-style-link">
                                            <button class="btn-primary btn" style="width:30%">Ver mis pacientes</button>
                                        </a>
                                        <br><br>
                                    </td>
                                </tr>
                            </table>
                        </center>
                    </td>
                </tr>

                <!-- Estadísticas -->
                <tr>
                    <td colspan="4">
                        <table width="100%">
                            <tr>
                                <td width="50%">
                                    <center>
                                        <p style="font-size: 20px;font-weight:600;padding-left: 12px;">Estado</p>
                                        <table class="filter-container" style="border: none;">
                                            <tr>
                                                <td style="width: 50%;">
                                                    <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display: flex;">
                                                        <div>
                                                            <div class="h1-dashboard"><?= $totalPacientes ?></div><br>
                                                            <div class="h3-dashboard">Mis Pacientes</div>
                                                        </div>
                                                        <div class="btn-icon-back dashboard-icons" style="background-image: url('../img/icons/patients-hover.svg');"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </center>
                                </td>
                                <td>
                                    <p id="anim" style="font-size: 20px;font-weight:600;padding-left: 40px;">Pacientes atendidos esta semana</p>
                                    <center>
                                        <canvas id="semanaChart" width="400" height="200"></canvas>
                                    </center>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Gráfico -->
    <script>
        const ctx = document.getElementById('semanaChart').getContext('2d');
        const semanaChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($semana)) ?>,
                datasets: [{
                    label: 'Pacientes atendidos',
                    data: <?= json_encode(array_values($semana)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    <script>
    function toggleMenu() {
        const menu = document.querySelector('.menu');
        menu.classList.toggle('show');
    }
    </script>
</body>
</html>