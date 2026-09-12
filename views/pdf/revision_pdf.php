<?php
require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];

$tipo = $_GET['tipo'] ?? '';
$revision = null;
$materiales = [];

if ($tipo === 'infra') {
    $stmt = $pdo->prepare("
        SELECT ir.*, t.nombre AS tecnico_responsable, n.nombre AS arco, n.tipo AS tipo_infra, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento
        FROM infraestructura_revisiones ir
        JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
        LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
        LEFT JOIN tecnicos t ON t.id = ir.tecnico_id
        WHERE ir.id = ?
    ");
    $stmt->execute([$id]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$revision) {
        die("Mantenimiento de sitio no encontrado");
    }

    $matStmt = $pdo->prepare("
        SELECT irm.*, m.nombre AS material, m.medida
        FROM infraestructura_revision_material irm
        JOIN materiales m ON irm.material_id = m.id
        WHERE irm.revision_id = ?
    ");
    $matStmt->execute([$id]);
    $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, t.nombre AS tecnico_responsable, a.nombre AS arco, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento
        FROM revisiones r
        JOIN arcos a ON r.arco_id = a.id
        JOIN ubicaciones u ON a.ubicacion_id = u.id
        LEFT JOIN tecnicos t ON t.id = r.tecnico_id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$revision) {
        $stmt = $pdo->prepare("
            SELECT ir.*, t.nombre AS tecnico_responsable, n.nombre AS arco, n.tipo AS tipo_infra, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento
            FROM infraestructura_revisiones ir
            JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
            LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
            LEFT JOIN tecnicos t ON t.id = ir.tecnico_id
            WHERE ir.id = ?
        ");
        $stmt->execute([$id]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$revision) {
            die("Revisión no encontrada");
        }
        $tipo = 'infra';
        $matStmt = $pdo->prepare("
            SELECT irm.*, m.nombre AS material, m.medida
            FROM infraestructura_revision_material irm
            JOIN materiales m ON irm.material_id = m.id
            WHERE irm.revision_id = ?
        ");
        $matStmt->execute([$id]);
        $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $matStmt = $pdo->prepare("
            SELECT rm.*, m.nombre AS material, m.medida
            FROM revision_material rm
            JOIN materiales m ON rm.material_id = m.id
            WHERE rm.revision_id = ?
        ");
        $matStmt->execute([$id]);
        $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$fechaMantenimiento = $revision ? date("d/m/Y", strtotime($revision['fecha_mantenimiento'])) : '';

$logoPath = '../../assets/LOGO INNOVATEC.png';
date_default_timezone_set('America/Mexico_City');
$fechaFormato = date("d M Y");
$codigoFormato = 'INN-FOR-002';

$safeArc = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($revision['arco'] ?? 'arco'));
$nombreArchivoPdf = "Diagnostico_Inicial_{$safeArc}_{$fechaMantenimiento}.pdf";
$urlDescargaServidor = "../../controllers/pdf_controller.php?action=mantenimiento&id={$id}" . ($tipo === 'infra' ? '&tipo=infra' : '') . "&download=1";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Mantenimiento - <?= htmlspecialchars($revision['arco']) ?></title>
    <link rel="stylesheet" href="../../css/bitacora_arco.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>

    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button type="button" class="btn-download" onclick="descargarFormatoPDF()">📥 Descargar PDF</button>
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
                </table>
            </div>

            <!-- II MATERIALES -->
            <div class="seccion">
                <div class="titulo-seccion">II. MATERIALES CAMBIADOS / AGREGADOS / RETIRADOS</div>

                <?php if (count($materiales) === 1): ?>
                    <?php
                    $m = $materiales[0];
                    $esRetiro = ($m['accion'] ?? 'cambio') === 'retiro';
                    $esAgregado = ($m['accion'] ?? 'cambio') === 'agregado';
                    $datosTec = [];
                    if (!empty(trim((string)($m['serie'] ?? '')))) {
                        $datosTec[] = '<strong>Serie:</strong> ' . htmlspecialchars(trim($m['serie']));
                    }
                    if (!empty(trim((string)($m['ip'] ?? '')))) {
                        $datosTec[] = '<strong>IP:</strong> ' . htmlspecialchars(trim($m['ip']));
                    }
                    if (!empty(trim((string)($m['mac'] ?? '')))) {
                        $datosTec[] = '<strong>MAC:</strong> ' . htmlspecialchars(trim($m['mac']));
                    }
                    ?>
                    <table class="tabla-componentes">
                        <thead>
                            <tr>
                                <th style="width:80%; text-align:left; padding-left:10px;">COMPONENTE / ESPECIFICACIÓN</th>
                                <th style="width:20%; text-align:center;">CANTIDAD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:6px 10px; vertical-align:middle;">
                                    <div style="font-weight:bold; font-size:11px; color:#111; line-height:1.2;">
                                        <?php if ($esRetiro): ?>
                                            <span style="color:#b02a37; font-weight:bold;">[RETIRADO]</span>
                                        <?php elseif ($esAgregado): ?>
                                            <span style="color:#0d6efd; font-weight:bold;">[AGREGADO]</span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($m['material']) ?>
                                    </div>
                                    <?php if (!empty($datosTec)): ?>
                                        <div class="datos-tecnicos" style="margin-top:2px; font-size:9.5px; color:#444; line-height:1.25;">
                                            <?= implode(' &nbsp;•&nbsp; ', $datosTec) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center; font-weight:bold; font-size:11px; white-space:nowrap; vertical-align:middle;">
                                    <?= htmlspecialchars($m['cantidad']) ?> <?= htmlspecialchars($m['medida'] === 'm' ? 'm' : ($m['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php elseif (count($materiales) > 1): ?>
                    <?php
                    $mitadMat = ceil(count($materiales) / 2);
                    $matCol1 = array_slice($materiales, 0, $mitadMat);
                    $matCol2 = array_slice($materiales, $mitadMat);
                    ?>
                    <table class="tabla-componentes tabla-componentes--dos-columnas">
                        <thead>
                            <tr>
                                <th style="width:38%; text-align:left; padding-left:8px;">COMPONENTE / ESPECIFICACIÓN</th>
                                <th style="width:12%; text-align:center;">CANT.</th>
                                <th style="width:38%; text-align:left; padding-left:8px;">COMPONENTE / ESPECIFICACIÓN</th>
                                <th style="width:12%; text-align:center;">CANT.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < $mitadMat; $i++): ?>
                                <?php
                                $m1 = $matCol1[$i] ?? null;
                                $m2 = $matCol2[$i] ?? null;
                                ?>
                                <tr>
                                    <!-- Columna 1 -->
                                    <?php if ($m1): ?>
                                        <?php
                                        $esRetiro1 = ($m1['accion'] ?? 'cambio') === 'retiro';
                                        $esAgregado1 = ($m1['accion'] ?? 'cambio') === 'agregado';
                                        $datosTec1 = [];
                                        if (!empty(trim((string)($m1['serie'] ?? '')))) {
                                            $datosTec1[] = '<strong>Serie:</strong> ' . htmlspecialchars(trim($m1['serie']));
                                        }
                                        if (!empty(trim((string)($m1['ip'] ?? '')))) {
                                            $datosTec1[] = '<strong>IP:</strong> ' . htmlspecialchars(trim($m1['ip']));
                                        }
                                        if (!empty(trim((string)($m1['mac'] ?? '')))) {
                                            $datosTec1[] = '<strong>MAC:</strong> ' . htmlspecialchars(trim($m1['mac']));
                                        }
                                        ?>
                                        <td style="padding:4px 6px; vertical-align:middle;">
                                            <div style="font-weight:bold; font-size:10px; color:#111; line-height:1.2;">
                                                <?php if ($esRetiro1): ?>
                                                    <span style="color:#b02a37; font-weight:bold;">[RETIRADO]</span>
                                                <?php elseif ($esAgregado1): ?>
                                                    <span style="color:#0d6efd; font-weight:bold;">[AGREGADO]</span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($m1['material']) ?>
                                            </div>
                                            <?php if (!empty($datosTec1)): ?>
                                                <div class="datos-tecnicos" style="margin-top:1px; font-size:8.5px; color:#444; line-height:1.15;">
                                                    <?= implode(' &nbsp;•&nbsp; ', $datosTec1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; font-weight:bold; font-size:10px; white-space:nowrap; vertical-align:middle; padding:4px 2px;">
                                            <?= htmlspecialchars($m1['cantidad']) ?> <?= htmlspecialchars($m1['medida'] === 'm' ? 'm' : ($m1['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                                        </td>
                                    <?php else: ?>
                                        <td style="border:1px solid #000; background:#fff;">&nbsp;</td>
                                        <td style="border:1px solid #000; background:#fff;">&nbsp;</td>
                                    <?php endif; ?>

                                    <!-- Columna 2 -->
                                    <?php if ($m2): ?>
                                        <?php
                                        $esRetiro2 = ($m2['accion'] ?? 'cambio') === 'retiro';
                                        $esAgregado2 = ($m2['accion'] ?? 'cambio') === 'agregado';
                                        $datosTec2 = [];
                                        if (!empty(trim((string)($m2['serie'] ?? '')))) {
                                            $datosTec2[] = '<strong>Serie:</strong> ' . htmlspecialchars(trim($m2['serie']));
                                        }
                                        if (!empty(trim((string)($m2['ip'] ?? '')))) {
                                            $datosTec2[] = '<strong>IP:</strong> ' . htmlspecialchars(trim($m2['ip']));
                                        }
                                        if (!empty(trim((string)($m2['mac'] ?? '')))) {
                                            $datosTec2[] = '<strong>MAC:</strong> ' . htmlspecialchars(trim($m2['mac']));
                                        }
                                        ?>
                                        <td style="padding:4px 6px; vertical-align:middle;">
                                            <div style="font-weight:bold; font-size:10px; color:#111; line-height:1.2;">
                                                <?php if ($esRetiro2): ?>
                                                    <span style="color:#b02a37; font-weight:bold;">[RETIRADO]</span>
                                                <?php elseif ($esAgregado2): ?>
                                                    <span style="color:#0d6efd; font-weight:bold;">[AGREGADO]</span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($m2['material']) ?>
                                            </div>
                                            <?php if (!empty($datosTec2)): ?>
                                                <div class="datos-tecnicos" style="margin-top:1px; font-size:8.5px; color:#444; line-height:1.15;">
                                                    <?= implode(' &nbsp;•&nbsp; ', $datosTec2) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; font-weight:bold; font-size:10px; white-space:nowrap; vertical-align:middle; padding:4px 2px;">
                                            <?= htmlspecialchars($m2['cantidad']) ?> <?= htmlspecialchars($m2['medida'] === 'm' ? 'm' : ($m2['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                                        </td>
                                    <?php else: ?>
                                        <td style="border:1px solid #000; background:#fff;">&nbsp;</td>
                                        <td style="border:1px solid #000; background:#fff;">&nbsp;</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <table class="tabla-componentes">
                        <tr>
                            <td style="text-align:center; padding:10px; color:#666;">
                                No hay materiales registrados
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
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

    <script>
    function descargarFormatoPDF() {
        const elemento = document.querySelector('.hoja');
        const btnDescarga = document.querySelector('.btn-download');
        const textoOriginal = btnDescarga ? btnDescarga.innerHTML : '';
        if (btnDescarga) {
            btnDescarga.disabled = true;
            btnDescarga.innerHTML = '⏳ Descargando...';
        }

        const opt = {
            margin:       0,
            filename:     '<?= $nombreArchivoPdf ?>',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        if (typeof html2pdf !== 'undefined') {
            html2pdf().set(opt).from(elemento).save().then(() => {
                if (btnDescarga) {
                    btnDescarga.disabled = false;
                    btnDescarga.innerHTML = textoOriginal;
                }
            }).catch(err => {
                console.error('Error al generar PDF con html2pdf:', err);
                window.location.href = '<?= $urlDescargaServidor ?>';
                if (btnDescarga) {
                    btnDescarga.disabled = false;
                    btnDescarga.innerHTML = textoOriginal;
                }
            });
        } else {
            window.location.href = '<?= $urlDescargaServidor ?>';
            if (btnDescarga) {
                btnDescarga.disabled = false;
                btnDescarga.innerHTML = textoOriginal;
            }
        }
    }
    </script>
</body>

</html>
