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
$estado = $_GET['estado'] ?? 'nuevo';
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

// Procesar formulario de nueva cita
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

    header("Location: citas.php?estado=$estado&success=1");
    exit;
}

// Vista de detalle
$view_id = $_GET['view_id'] ?? null;
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
        .action-buttons {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .action-buttons button {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .action-buttons button:hover {
            transform: scale(1.1);
        }

        .btn-icon-view {
            background-color: #e7f3ff;
            color: #007bff;
        }

        .btn-icon-edit {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .btn-icon-delete {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.nuevo {
            background-color: #f0ad4e;
            color: white;
        }

        .status.atendido {
            background-color: #5cb85c;
            color: white;
        }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menú</button>
    <div class="container">
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
                                    <a href="../login.php"><input type="button" value="Cerrar Sesión" class="logout-btn btn-primary-soft btn"></a>
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

        <div class="dash-body" style="margin-top: 15px">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;">
                <tr>
                    <td colspan="1" class="nav-bar">
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;margin-left:20px;">Gestión de Citas</p>
                    </td>
                    <td width="25%"></td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Fecha de hoy</p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;"><?= date('Y-m-d') ?></p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                            <img src="../img/calendar.svg" width="100%">
                        </button>
                    </td>
                </tr>

                <!-- Vista de detalle -->
                <?php if ($view_id && $paciente): ?>
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll" style="margin-top: 20px; width: 95%;">
                                <h3>Detalles del Paciente</h3>
                                <table class="sub-table" style="width: 100%;">
                                    <tr>
                                        <td><strong>Cédula:</strong></td>
                                        <td><?= htmlspecialchars($paciente['cedula']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nombre:</strong></td>
                                        <td><?= htmlspecialchars($paciente['nombre']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Teléfono:</strong></td>
                                        <td><?= htmlspecialchars($paciente['telefono']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado:</strong></td>
                                        <td><span class="status <?= $paciente['estado'] ?>"><?= ucfirst($paciente['estado']) ?></span></td>
                                    </tr>
                                </table>
                                <br>
                                <a href="citas.php?estado=<?= $estado ?>" class="non-style-link">
                                    <button class="btn-primary-soft btn">← Regresar</button>
                                </a>
                            </div>
                        </center>
                    </td>
                </tr>
                <?php else: ?>

                <!-- Formulario de nueva cita -->
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll" style="margin-top: 20px; width: 95%;">
                                <h3>Asignar Nueva Cita</h3>
                                <form method="POST" class="container">
                                    <label for="paciente_id">Paciente:</label>
                                    <select name="paciente_id" required>
                                        <option value="">Seleccione un paciente</option>
                                        <?php foreach ($pacientes as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['cedula'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select><br><br>

                                    <label>Fecha:</label>
                                    <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>"><br><br>

                                    <label>Hora:</label>
                                    <input type="time" name="hora" required><br><br>

                                    <label>Título:</label>
                                    <input type="text" name="titulo" placeholder="Ej: Primera sesión" required><br><br>

                                    <button type="submit" name="asignar_cita" class="btn-primary btn">Guardar Cita</button>
                                </form>
                            </div>
                        </center>
                    </td>
                </tr>

                <!-- Filtros -->
                <tr>
                    <td colspan="4" style="padding-left: 20px;">
                        <div class="filter-tabs">
                            <a href="?estado=nuevo"><button class="btn-filter <?= $estado === 'nuevo' ? 'active' : '' ?>">Nuevos</button></a>
                            <a href="?estado=atendido"><button class="btn-filter <?= $estado === 'atendido' ? 'active' : '' ?>">Atendidos</button></a>
                        </div>
                    </td>
                </tr>

                <!-- Búsqueda -->
                <tr>
                    <td colspan="4" style="padding-left: 20px;">
                        <form method="GET">
                            <input type="text" name="search" placeholder="Buscar por nombre o cédula" value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="estado" value="<?= $estado ?>">
                            <button type="submit" class="btn-primary btn">Buscar</button>
                        </form>
                    </td>
                </tr>

                <!-- Tabla de pacientes -->
                <tr>
                    <td colspan="4">
                        <center>
                            <table class="sub-table" style="width: 95%; margin-top: 20px;">
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
                                    <?php foreach ($pacientes as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['cedula']) ?></td>
                                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                                        <td><?= htmlspecialchars($p['telefono']) ?></td>
                                        <td><span class="status <?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="citas.php?view_id=<?= $p['id'] ?>" class="non-style-link">
                                                    <button class="btn-icon-view">👁️</button>
                                                </a>
                                                <?php if ($p['estado'] === 'nuevo'): ?>
                                                    <a href="citas.php?edit_id=<?= $p['id'] ?>" class="non-style-link">
                                                        <button class="btn-icon-edit">✏️</button>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="citas.php?delete_id=<?= $p['id'] ?>" class="non-style-link" onclick="return confirm('¿Eliminar este paciente?')">
                                                    <button class="btn-icon-delete">🗑️</button>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </center>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.menu');
            menu.classList.toggle('show');
        }

        <?php if (isset($_GET['success'])): ?>
            alert("Cita asignada correctamente.");
        <?php endif; ?>
    </script>
</body>
</html>