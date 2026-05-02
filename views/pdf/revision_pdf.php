<?php
include('../../config/db.php');

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = $_GET['id'];

/* =========================
   DATOS DE LA REVISIÓN
========================= */
$stmt = $pdo->prepare("
    SELECT r.*, a.nombre AS arco, u.nombre AS ubicacion
    FROM revisiones r
    JOIN arcos a ON r.arco_id = a.id
    JOIN ubicaciones u ON a.ubicacion_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$revision = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$revision) {
    die("Revisión no encontrada");
}

/* =========================
   MATERIALES CAMBIADOS
========================= */
$matStmt = $pdo->prepare("
    SELECT rm.*, m.nombre AS material, m.medida
    FROM revision_material rm
    JOIN materiales m ON rm.material_id = m.id
    WHERE rm.revision_id = ?
");
$matStmt->execute([$id]);
$materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Mantenimiento</title>
<link rel="stylesheet" href="../../css/bitacora_arco.css">
</head>

<body>

<div class="no-print">
    <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="Diseño">
<div class="hoja">

<h2 class="titulo">REPORTE DE MANTENIMIENTO</h2>

<!-- I DATOS -->
<div class="seccion">
<div class="titulo-seccion">I. DATOS DEL SERVICIO</div>

<table class="tabla-servicio">
<tr>
    <td colspan="2">
        <strong>Nombre del Arco:</strong>
        <span><?= htmlspecialchars($revision['arco']) ?></span>
    </td>
    <td>
        <strong>Fecha:</strong>
        <span><?= date("d/m/Y", strtotime($revision['fecha_mantenimiento'])) ?></span>
    </td>
</tr>

<tr>
    <td colspan="2">
        <strong>Ubicación:</strong>
        <span><?= htmlspecialchars($revision['ubicacion']) ?></span>
    </td>
    <td>
        <strong>Hora:</strong>
        <span><?= date("H:i") ?></span>
    </td>
</tr>

<tr>
    <td colspan="3">
        <strong>Técnico Responsable:</strong>
        <span><?= htmlspecialchars($revision['tecnico_responsable'] ?? 'N/A') ?></span>
    </td>
</tr>
</table>
</div>

<!-- II MATERIALES -->
<div class="seccion">
<div class="titulo-seccion">II. MATERIALES CAMBIADOS</div>

<table class="tabla-componentes">
<tr>
    <th style="width:70%;">MATERIAL</th>
    <th style="width:30%;">SERIE</th>
</tr>

<?php if (count($materiales) > 0): ?>
    <?php foreach ($materiales as $m): ?>
    <tr>
        <td>
            <?= htmlspecialchars($m['material']) ?>
            (<?= $m['cantidad'] ?> 
            <?= $m['medida'] == 'm' ? 'Metros' : 'Piezas' ?>)
        </td>
        <td style="text-align:center;">
            <?= $m['serie'] ?: 'N/A' ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="2" style="text-align:center;">
        No hay materiales registrados
    </td>
</tr>
<?php endif; ?>
</table>
</div>

<!-- III OBSERVACIONES -->
<div class="observaciones">
<strong>OBSERVACIONES:</strong>

<div class="observaciones-box">
<?= !empty($revision['observaciones']) 
    ? nl2br(htmlspecialchars($revision['observaciones'])) 
    : '&nbsp;' ?>
</div>
</div>

<!-- FIRMAS -->
<div class="firmas">
<div class="firma">
    <div class="linea-firma"></div>
    <small>FIRMA DEL TÉCNICO</small>
</div>

<div class="firma">
    <div class="linea-firma"></div>
    <small>FIRMA DEL SUPERVISOR</small>
</div>
</div>

</div>
</div>

</body>
</html>
