<?php
require_once __DIR__ . '/../../config/db.php';

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
        u.nombre AS ubicacion,
        t.nombre AS tecnico_responsable
    FROM arcos_bajas b
    JOIN arcos a ON a.id = b.arco_id
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN tecnicos t ON t.id = b.tecnico_id
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
date_default_timezone_set('America/Mexico_City');
$fechaFormato = date("d M Y");
$codigoFormato = 'INN-FOR-002';

$safeArc = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($baja['arco'] ?? 'arco'));
$nombreArchivoPdf = "Baja_Arco_{$safeArc}_{$id}.pdf";
$urlDescargaServidor = "../../controllers/pdf_controller.php?action=baja_pdf&id={$id}&download=1";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de Baja de Arco - <?= htmlspecialchars($baja['arco']) ?></title>
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

                <?php if (count($materiales) > 0): ?>
                    <table class="tabla-componentes">
                        <thead>
                            <tr>
                                <th style="width:36%;">COMPONENTE</th>
                                <th style="width:12%;">CANTIDAD</th>
                                <th style="width:18%;">SERIE</th>
                                <th style="width:17%;">IP</th>
                                <th style="width:17%;">MAC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiales as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['material']) ?></td>
                                    <td style="text-align:center;">
                                        <?= htmlspecialchars($m['cantidad']) ?>
                                        <?= htmlspecialchars($m['medida'] === 'm' ? 'm' : 'pz') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?= !empty($m['serie']) ? htmlspecialchars($m['serie']) : 'N/A' ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?= !empty($m['ip']) ? htmlspecialchars($m['ip']) : 'N/A' ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?= !empty($m['mac']) ? htmlspecialchars($m['mac']) : 'N/A' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <table class="tabla-componentes">
                        <tr>
                            <td style="text-align:center;">No hay materiales registrados</td>
                        </tr>
                    </table>
                <?php endif; ?>
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
