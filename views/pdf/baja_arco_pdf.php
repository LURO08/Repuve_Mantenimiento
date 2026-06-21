<?php
include('../../config/db.php');

if (!isset($_GET['id'])) {
    die("ID de baja no recibido");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT
        b.*,
        a.nombre AS arco,
        a.fecha_instalacion,
        a.lat,
        a.lng,
        a.estado,
        u.nombre AS ubicacion
    FROM arcos_bajas b
    JOIN arcos a ON a.id = b.arco_id
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$baja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$baja) {
    die("Baja de arco no encontrada");
}

$matStmt = $pdo->prepare("
    SELECT
        am.*,
        m.nombre AS material,
        m.medida AS medida
    FROM arco_material am
    JOIN materiales m ON m.id = am.material_id
    WHERE am.arco_id = ?
    ORDER BY am.id ASC
");
$matStmt->execute([$baja['arco_id']]);
$materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);

$logoPath = '../../assets/LOGO INNOVATEC.png';
$fechaFormato = '02-Abril-2026';
$codigoFormato = 'INN-FOR-002';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de Baja de Arco</title>
    <link rel="stylesheet" href="../../css/bitacora_arco.css">
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <div class="Diseño">
        <div class="hoja">
            <div class="encabezado-formato">
                <div class="encabezado-logo">
                    <img src="<?= $logoPath ?>" alt="Innovacion y Tecnologia">
                </div>
                <div class="encabezado-titulo">
                    BAJA DE ARCO
                </div>

                <div class="encabezado-info">
                    <table>
                        <tr>
                            <th>Codigo:</th>
                            <td><?= htmlspecialchars($codigoFormato) ?></td>
                        </tr>
                        <tr>
                            <th>Fecha:</th>
                            <td><?= htmlspecialchars($fechaFormato) ?></td>
                        </tr>
                        <tr>
                            <th>Pagina:</th>
                            <td>1 de 1</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="seccion">
                <div class="titulo-seccion">I. DATOS DEL ARCO</div>

                <table class="tabla-servicio">
                    <tr>
                        <td colspan="2">
                            <strong>Nombre del Arco:</strong>
                            <span><?= htmlspecialchars($baja['arco']) ?></span>
                        </td>
                        <td>
                            <strong>Ubicacion:</strong>
                            <span><?= htmlspecialchars($baja['ubicacion'] ?? 'N/A') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Fecha Instalacion:</strong>
                            <span>
                                <?= !empty($baja['fecha_instalacion'])
                                    ? date("d/m/Y H:i", strtotime($baja['fecha_instalacion']))
                                    : 'N/A' ?>
                            </span>
                        </td>
                        <td>
                            <strong>Latitud:</strong>
                            <span><?= htmlspecialchars($baja['lat'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <strong>Longitud:</strong>
                            <span><?= htmlspecialchars($baja['lng'] ?? 'N/A') ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="seccion">
                <div class="titulo-seccion">II. DATOS DE BAJA</div>

                <table class="tabla-servicio">
                    <tr>
                        <td>
                            <strong>Fecha de Baja:</strong>
                            <span><?= date("d/m/Y", strtotime($baja['fecha_baja'])) ?></span>
                        </td>
                        <td>
                            <strong>Hora:</strong>
                            <span><?= date("H:i A", strtotime($baja['fecha_baja'])) ?></span>
                        </td>
                        <td>
                            <strong>Estado:</strong>
                            <span><?= htmlspecialchars($baja['estado'] ?? 'Baja') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <strong>Motivo:</strong>
                            <span><?= htmlspecialchars($baja['motivo']) ?></span>
                        </td>
                        <td>
                            <strong>Tecnico:</strong>
                            <span><?= htmlspecialchars($baja['tecnico_responsable'] ?? 'N/A') ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="seccion">
                <div class="titulo-seccion">III. COMPONENTES REGISTRADOS</div>

                <table class="tabla-componentes">
                    <tr>
                        <th style="width:55%;">COMPONENTE</th>
                        <th style="width:20%;">CANTIDAD</th>
                        <th style="width:25%;">SERIE</th>
                    </tr>

                    <?php if (count($materiales) > 0): ?>
                        <?php foreach ($materiales as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['material']) ?></td>
                                <td style="text-align:center;">
                                    <?= htmlspecialchars($m['cantidad']) ?>
                                    <?= htmlspecialchars($m['medida'] === 'm' ? 'Metros' : 'Piezas') ?>
                                </td>
                                <td style="text-align:center;">
                                    <?= !empty($m['serie']) ? htmlspecialchars($m['serie']) : 'N/A' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">No hay materiales registrados</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="observaciones">
                <strong class="titulo-seccion">IV. OBSERVACIONES:</strong>

                <div class="observaciones-box">
                    <?= !empty($baja['observaciones'])
                        ? nl2br(htmlspecialchars($baja['observaciones']))
                        : '&nbsp;' ?>
                </div>
            </div>

            <div class="firmas">
                <div class="firma">
                    <div class="nombre-firma">
                        <?= htmlspecialchars($baja['tecnico_responsable'] ?? 'N/A') ?>
                    </div>
                    <small class="texto-firma">
                        NOMBRE Y FIRMA DEL TECNICO RESPONSABLE
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
                    <div>GONZALO N RAMIREZ, MANZANA 1</div>
                    <div>LOTE 167, COL. TRIBUNA</div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
