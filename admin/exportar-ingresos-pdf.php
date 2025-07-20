<?php
// Sin espacios ni saltos de línea antes de este tag
ob_start(); // ← importante: limpia cualquier salida previa

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connection.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    die('Acceso denegado');
}

$modo  = $_GET['modo'] ?? 'diario';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

switch ($modo) {
    case 'semanal':
        [$inicio, $fin] = explode(' to ', $fecha);
        if (!$fin) {
            $inicio = date('Y-m-d', strtotime('monday this week', strtotime($inicio)));
            $fin    = date('Y-m-d', strtotime('sunday this week', strtotime($inicio)));
        }
        break;
    case 'mensual':
        $inicio = $fecha . '-01';
        $fin    = date('Y-m-t', strtotime($inicio));
        break;
    default:
        $inicio = $fecha;
        $fin    = $fecha;
}

$sql = "SELECT i.fecha, i.monto, i.descripcion, p.nombre AS paciente, u.nombre AS colaborador
        FROM ingreso i
        JOIN paciente p ON i.paciente_id = p.id
        JOIN usuario u  ON i.colaborador_id = u.id
        WHERE i.fecha BETWEEN ? AND ?
        ORDER BY i.fecha DESC";
$stmt = $database->prepare($sql);
$stmt->bind_param("ss", $inicio, $fin);
$stmt->execute();
$res = $stmt->get_result();

$total = 0;
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $total += $r['monto'];
}

// PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Yurem');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Reporte de Ingresos');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Datos del administrador
$stmtAdmin = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ?");
$stmtAdmin->bind_param("s", $_SESSION['usuario']);
$stmtAdmin->execute();
$stmtAdmin->bind_result($adminNombre);
$stmtAdmin->fetch();
$stmtAdmin->close();

// Logo
// Logo fijo a la izquierda
$logo = __DIR__ . '/../img/logo.jpg';
if (file_exists($logo)) {
    $pdf->Image($logo, 10, 10, 30, 0, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Encabezado alineado a la derecha del logo
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetY(10); // Alineación vertical con logo
$pdf->SetX(50); // Espacio después del logo
$pdf->Cell(0, 10, 'Reporte de Ingresos', 0, 1, 'L');

$pdf->SetFont('helvetica', 'I', 12);
$pdf->SetX(50);
$pdf->Cell(0, 6, ucfirst($modo) . ' - ' . date('d/m/Y', strtotime($inicio)) . ' al ' . date('d/m/Y', strtotime($fin)), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetX(50);
$pdf->Cell(0, 6, 'Administrador: ' . htmlspecialchars($adminNombre) . ' (' . $_SESSION['usuario'] . ')', 0, 1, 'L');
$pdf->SetX(50);
$pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i'), 0, 1, 'L');

$pdf->Ln(15); // Espacio antes de la tabla

// Tabla mejorada
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->SetTextColor(0, 0, 0);
$header = ['Fecha', 'Paciente', 'Monto', 'Descripción', 'Colaborador'];
$w = [25, 45, 20, 60, 40];
foreach ($header as $k => $v) $pdf->Cell($w[$k], 8, $v, 1, 0, 'C', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);
foreach ($rows as $row) {
    $pdf->Cell($w[0], 7, date('d/m/Y', strtotime($row['fecha'])), 1);
    $pdf->Cell($w[1], 7, $row['paciente'], 1);
    $pdf->Cell($w[2], 7, '$' . number_format($row['monto'], 2), 1, 0, 'R');
    $pdf->Cell($w[3], 7, $row['descripcion'], 1);
    $pdf->Cell($w[4], 7, $row['colaborador'], 1);
    $pdf->Ln();
}
// nuevo filtro de colaborador
$idColaborador = $_GET['colaborador'] ?? '';
if ($idColaborador) {
    $sql   .= " AND i.colaborador_id = ?";
    $params[] = $idColaborador;
    $types   .= "i";
}

// Total destacado
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(220, 240, 220);
$pdf->Cell(0, 10, 'Total Ingresos: $' . number_format($total, 2), 0, 1, 'R', 1);

ob_end_clean();
$pdf->Output('ingresos_' . date('Ymd') . '.pdf', 'D');