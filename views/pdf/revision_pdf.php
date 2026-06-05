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

$logoPath = '../../assets/LOGO INNOVATEC.png';
$fechaFormato = '02-Abril-2026';
$codigoFormato = 'INN-FOR-002';

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
            <div class="encabezado-formato">
                <div class="encabezado-logo">
                    <img src="<?= $logoPath ?>" alt="Innovación y Tecnología">
                </div>
                <div class="encabezado-titulo">
                    DIAGNÓSTICO INICIAL
                </div>

                <div class="encabezado-info">
                    <table>
                        <tr>
                            <th>Código:</th>
                            <td><?= htmlspecialchars($codigoFormato) ?></td>
                        </tr>
                        <tr>
                            <th>Fecha:</th>
                            <td><?= htmlspecialchars($fechaFormato) ?></td>
                        </tr>
                        <tr>
                            <th>Página:</th>
                            <td>1 de 1</td>
                        </tr>
                    </table>
                </div>
        </div>


            <!-- I DATOS -->
            <div class="seccion">
                <div class="titulo-seccion">I. DATOS DEL SERVICIO</div>

                <table class="tabla-servicio">
                    <tr>
                        <td colspan="2">
                            <strong>Nombre del Arco:</strong>
                            <span><?= htmlspecialchars($revision['arco']) ?> - <?= htmlspecialchars($revision['ubicacion']) ?></span>
                        </td>
                        <td>
                            <strong>Fecha:</strong>
                            <span><?= date("d/m/Y", strtotime($revision['fecha_mantenimiento'])) ?></span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <strong>Técnico Responsable:</strong>
                            <span><?= htmlspecialchars($revision['tecnico_responsable'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <strong>Hora:</strong>
                            <span><?= date("H:i A", strtotime($revision['fecha_mantenimiento'])) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <?php $tipoMant = $revision['tipo_mantenimiento'] ?? 'Correctivo'; ?>
                            <strong>Tipo de Mantenimiento:</strong>
                            <span>[ <?= $tipoMant === 'Preventivo' ? 'X' : '&nbsp;' ?> ] Preventivo</span>
                            <span style="margin-left: 25px;">[ <?= $tipoMant === 'Correctivo' ? 'X' : '&nbsp;' ?> ] Correctivo</span>
                        </td>
                    </tr>

                    <tr>
                        
                    </tr>
                </table>
            </div>

            <!-- II MATERIALES -->
            <div class="seccion">
                <div class="titulo-seccion">II. MATERIALES CAMBIADOS / RETIRADOS</div>

                <table class="tabla-componentes">
                    <tr>
                        <th style="width:70%;">COMPONENTE</th>
                        <th style="width:30%;">SERIE</th>
                    </tr>

                    <?php if (count($materiales) > 0): ?>
                        <?php foreach ($materiales as $m): ?>
                            <?php $esRetiro = ($m['accion'] ?? 'cambio') === 'retiro'; ?>
                            <tr>
                                <td>
                                    <?= $esRetiro ? '[RETIRADO] ' : '' ?>
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
                <strong class="titulo-seccion">III. OBSERVACIONES:</strong>

                <div class="observaciones-box">
                    <?= !empty($revision['observaciones'])
                        ? nl2br(htmlspecialchars($revision['observaciones']))
                        : '&nbsp;' ?>
                </div>
            </div>

          <!-- FIRMA -->
            <div class="firmas">
                <div class="firma">
                    <div class="nombre-firma">
                        <?= htmlspecialchars($revision['tecnico_responsable'] ?? 'N/A') ?>
                    </div>

                    <small class="texto-firma">
                        NOMBRE Y FIRMA DEL TÉCNICO RESPONSABLE
                    </small>
                </div>
            </div>

            <div class="pie-formato">
                <div class="pie-izquierdo">
                    <div><strong>RFC:</strong> ITC090904G64</div>
                    <div><strong>TEL.</strong> 747 141 5434</div>
                </div>

                <div class="pie-separador"></div>

                <div class="pie-derecho">
                    <div>GONZALO N RAMÍREZ, MANZANA 1</div>
                    <div>LOTE 167, COL. TRIBUNA</div>
                </div>
            </div>

        </div>
    </div>

</body>

</html>
