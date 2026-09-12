<?php
require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];

$logoPath = '../../assets/LOGO INNOVATEC.png';
date_default_timezone_set('America/Mexico_City');
$fechaFormato = date("d M Y");
$codigoFormato = 'INN-FOR-001';
$tituloFormato = 'BITÁCORA';

$tipo = $_GET['tipo'] ?? '';
$arco = null;
$bitacora = null;
$materiales = [];

if ($tipo === 'infra') {
    $stmt = $pdo->prepare("
        SELECT n.*, n.nombre AS nombre, n.tipo AS tipo_infra, u.nombre AS ubicacion, CURRENT_TIMESTAMP AS fecha_instalacion
        FROM infraestructura_nodos n
        LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
        WHERE n.id = ?
    ");
    $stmt->execute([$id]);
    $arco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$arco) {
        die("Puente/Sitio no encontrado");
    }

    $bitStmt = $pdo->prepare("
        SELECT 
            b.id,
            t.nombre AS encargado,
            b.observaciones,
            b.fecha_registro
        FROM bitacoras_arco b
        LEFT JOIN tecnicos t ON t.id = b.tecnico_id
        WHERE b.infraestructura_id = ?
        ORDER BY b.fecha_registro DESC
        LIMIT 1
    ");
    $bitStmt->execute([$id]);
    $bitacora = $bitStmt->fetch(PDO::FETCH_ASSOC);

    $matStmt = $pdo->prepare("
        SELECT 
            im.*,
            m.nombre AS material,
            m.medida AS medida
        FROM infraestructura_material im
        JOIN materiales m ON im.material_id = m.id
        WHERE im.infraestructura_id = ?
    ");
    $matStmt->execute([$id]);
    $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nombre AS ubicacion, a.fecha_instalacion AS fecha_instalacion
        FROM arcos a
        JOIN ubicaciones u ON a.ubicacion_id = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $arco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$arco) {
        $stmt = $pdo->prepare("
            SELECT n.*, n.nombre AS nombre, n.tipo AS tipo_infra, u.nombre AS ubicacion, CURRENT_TIMESTAMP AS fecha_instalacion
            FROM infraestructura_nodos n
            LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $arco = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$arco) {
            die("Arco o sitio no encontrado");
        }
        $tipo = 'infra';

        $bitStmt = $pdo->prepare("
            SELECT 
                b.id,
                t.nombre AS encargado,
                b.observaciones,
                b.fecha_registro
            FROM bitacoras_arco b
            LEFT JOIN tecnicos t ON t.id = b.tecnico_id
            WHERE b.infraestructura_id = ?
            ORDER BY b.fecha_registro DESC
            LIMIT 1
        ");
        $bitStmt->execute([$id]);
        $bitacora = $bitStmt->fetch(PDO::FETCH_ASSOC);

        $matStmt = $pdo->prepare("
            SELECT 
                im.*,
                m.nombre AS material,
                m.medida AS medida
            FROM infraestructura_material im
            JOIN materiales m ON im.material_id = m.id
            WHERE im.infraestructura_id = ?
        ");
        $matStmt->execute([$id]);
        $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $bitStmt = $pdo->prepare("
            SELECT 
                b.id,
                t.nombre AS encargado,
                b.observaciones,
                b.fecha_registro
            FROM bitacoras_arco b
            LEFT JOIN tecnicos t ON t.id = b.tecnico_id
            WHERE b.arco_id = ?
            ORDER BY b.fecha_registro DESC
            LIMIT 1
        ");
        $bitStmt->execute([$id]);
        $bitacora = $bitStmt->fetch(PDO::FETCH_ASSOC);

        $matStmt = $pdo->prepare("
            SELECT 
                am.*,
                m.nombre AS material,
                m.medida AS medida
            FROM arco_material am
            JOIN materiales m ON am.material_id = m.id
            WHERE am.arco_id = ?
        ");
        $matStmt->execute([$id]);
        $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$fechaInstalacion = $arco && !empty($arco['fecha_instalacion']) ? date("d/m/Y", strtotime($arco['fecha_instalacion'])) : date("d/m/Y");

$checks = [];

if ($bitacora) {
    $checkStmt = $pdo->prepare("
        SELECT 
            cc.id,
            cc.nombre,
            CASE 
                WHEN bc.realizado = 1 THEN 1
                ELSE 0
            END AS realizado
        FROM checklist_conceptos cc
        LEFT JOIN bitacora_checklist bc
            ON cc.id = bc.concepto_id
            AND bc.bitacora_id = ?
        ORDER BY cc.id ASC
    ");

    $checkStmt->execute([$bitacora['id']]);
    $checks = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
}

$safeArc = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($arco['nombre'] ?? 'arco'));
$nombreArchivoPdf = "Bitacora_{$safeArc}_{$fechaInstalacion}.pdf";
$urlDescargaServidor = "../../controllers/pdf_controller.php?action=bitacora_pdf&id={$id}" . ($tipo === 'infra' ? '&tipo=infra' : '') . "&download=1";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Bitácora de Instalación - <?= htmlspecialchars($arco['nombre']) ?></title>
    <link rel="stylesheet" href="../../css/bitacora_arco.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>

    <style>
        .firmas {
            position: absolute;
            left: 32px;
            right: 32px;
            bottom: 85px;
            display: flex;
            justify-content: center;
        }
    </style>

    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button type="button" class="btn-download" onclick="descargarFormatoPDF()">📥 Descargar PDF</button>
    </div>

    <div class="Diseño">
        <div class="hoja">

            <!-- ENCABEZADO -->
            <div class="encabezado-formato">
                <div class="encabezado-logo">
                    <img src="<?= $logoPath ?>" alt="Innovación y Tecnología">
                </div>

                <div class="encabezado-titulo">
                    <?= htmlspecialchars($tituloFormato) ?>
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

            <!-- I DATOS DEL SERVICIO -->
            <div class="seccion">
                <div class="titulo-seccion">I. DATOS DEL SERVICIO</div>

                <table class="tabla-servicio">
                    <tr>
                        <td colspan="2">
                            <strong>Nombre del Arco:</strong>
                            <span><?= htmlspecialchars($arco['nombre']) ?> - <?= htmlspecialchars($arco['ubicacion']) ?></span>
                        </td>
                        <td>
                            <strong>Fecha Instalación:</strong>
                            <span>
                                <?= !empty($arco['fecha_instalacion'])
                                    ? date("d / m / Y", strtotime($arco['fecha_instalacion']))
                                    : 'N/A' ?>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <strong>Técnico Responsable:</strong>
                            <span><?= htmlspecialchars($bitacora['encargado'] ?? '') ?></span>

                        </td>
                        <td>
                            <strong>Hora:</strong>
                            <span>
                                <?= !empty($bitacora['fecha_registro'])
                                    ? date("h:i A", strtotime($bitacora['fecha_registro']))
                                    : date("h:i A") ?>
                            </span>
                        </td>
                    </tr>

                   <tr>
                        <td colspan="3">
                            <strong>Coordenadas de instalación:</strong>

                            <span>
                                <strong>Latitud:</strong>
                                <?= !empty($arco['lat']) ? htmlspecialchars($arco['lat']) : 'N/A' ?>
                            </span>

                            <span style="margin-left: 15px;">
                                <strong>Longitud:</strong>
                                <?= !empty($arco['lng']) ? htmlspecialchars($arco['lng']) : 'N/A' ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- II COMPONENTES -->
            <div class="seccion">
                <div class="titulo-seccion">
                    II. COMPONENTES INSTALADOS EN EL ARCO
                </div>

                <?php if (count($materiales) === 1): ?>
                    <?php
                    $m = $materiales[0];
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


            <!-- III CHECKLIST -->
            <div class="seccion">
                <div class="titulo-seccion">
                    III. CHECKLIST DE INSTALACIÓN Y PRUEBAS
                </div>

                <?php
                /* solo checks realizados */
                $mitad = ceil(count($checks) / 2);
                $columna1 = array_slice($checks, 0, $mitad);
                $columna2 = array_slice($checks, $mitad);
                ?>

                <table class="tabla-componentes">
                    <tr>
                        <th style="width:40%;">CONCEPTO</th>
                        <th style="width:10%;">✓</th>
                        <th style="width:40%;">CONCEPTO</th>
                        <th style="width:10%;">✓</th>
                    </tr>

                    <?php for ($i = 0; $i < $mitad; $i++): ?>
                        <tr>
                            <!-- izquierda -->
                            <td>
                                <?= htmlspecialchars($columna1[$i]['nombre'] ?? '') ?>
                            </td>
                            <td style="text-align:center; font-size:16px;">
                                <?= !empty($columna1[$i]['realizado']) ? '☑' : '☐' ?>
                            </td>

                            <!-- derecha -->
                            <td>
                                <?= htmlspecialchars($columna2[$i]['nombre'] ?? '') ?>
                            </td>
                            <td style="text-align:center; font-size:16px;">
                                <?= !empty($columna2[$i]['realizado']) ? '☑' : '☐' ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table>
            </div>

            <!-- OBSERVACIONES -->
            <div class="observaciones">
                <strong class="titulo-seccion">IV. OBSERVACIONES:</strong>

                <div class="observaciones-box">
                    <?= !empty($bitacora['observaciones'])
                        ? nl2br(htmlspecialchars($bitacora['observaciones']))
                        : '&nbsp;' ?>
                </div>
            </div>


            <!-- FIRMAS -->
            <div class="firmas">
                <div class="firma">
                    <div class="linea-firma"></div>

                    <small class="texto-firma">
                        FIRMA Y NOMBRE DEL TÉCNICO RESPONSABLE
                    </small>
                </div>

                <div class="firma">
                    <div class="linea-firma"></div>

                    <small class="texto-firma">
                        FIRMA Y NOMBRE DEL COORDINADOR OPERATIVO
                    </small>
                </div>
            </div>

            <!-- PIE DE FORMATO -->
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
