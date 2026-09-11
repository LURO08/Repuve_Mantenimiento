<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];

/* DATOS DEL ARCO */
$stmt = $pdo->prepare("
    SELECT a.*, u.nombre AS ubicacion
    FROM arcos a
    JOIN ubicaciones u ON a.ubicacion_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$arco = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$arco) {
    die("Arco no encontrado");
}

/* DATOS DE BITÁCORA */
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


/* MATERIALES DEL ARCO */
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
$nombreArchivoPdf = "Bitacora_{$safeArc}_{$id}.pdf";
$urlDescargaServidor = "pdf_controller.php?action=bitacora_pdf&id={$id}&download=1";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Bitácora de Instalación - <?= htmlspecialchars($arco['nombre']) ?></title>
    <link rel="stylesheet" href="../css/bitacora_arco.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>

    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button type="button" class="btn-download" onclick="descargarFormatoPDF()">📥 Descargar PDF</button>
    </div>

    <div class="Diseño">
        <div class="hoja">

            <h2 class="titulo">BITÁCORA</h2>

            <!-- I DATOS DEL SERVICIO -->
            <div class="seccion">
                <div class="titulo-seccion">I. DATOS DEL SERVICIO</div>

                <table class="tabla-servicio">
                    <tr>
                        <td colspan="2">
                            <strong>Nombre del Arco:</strong>
                            <span><?= htmlspecialchars($arco['nombre']) ?></span>
                        </td>
                        <td style="width:180px;">
                            <strong>Fecha:</strong>
                            <span><?= date("d") ?></span> /
                            <span><?= date("m") ?></span> /
                            <span><?= date("Y") ?></span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <strong>Ubicación:</strong>
                            <span><?= htmlspecialchars($arco['ubicacion']) ?></span>
                        </td>
                        <td>
                            <strong>Hora:</strong>
                            <span><?= date("H") ?></span> :
                            <span><?= date("i") ?></span>
                            <span><?= date("A") ?></span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <strong>Técnico Responsable:</strong>
                            <span><?= htmlspecialchars($bitacora['encargado'] ?? '') ?></span>
                        </td>
                        <td>
                            <strong>Coordenadas:</strong>
                            <span><?= !empty($arco['lat']) ? htmlspecialchars($arco['lat']) : '' ?>, <?= !empty($arco['lng']) ? htmlspecialchars($arco['lng']) : '' ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- II COMPONENTES -->
            <div class="seccion">
                <div class="titulo-seccion">
                    II. COMPONENTES INSTALADOS EN EL ARCO
                </div>

                <table class="tabla-componentes">
                    <thead>
                        <tr>
                            <th style="width:7%; text-align:center;">#</th>
                            <th style="width:73%; text-align:left; padding-left:10px;">COMPONENTE / ESPECIFICACIÓN</th>
                            <th style="width:20%; text-align:center;">CANTIDAD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($materiales) > 0): ?>
                            <?php foreach ($materiales as $index => $m): ?>
                                <?php
                                $datosTecnicos = [];
                                if (!empty(trim((string)($m['serie'] ?? '')))) {
                                    $datosTecnicos[] = '<strong>Serie:</strong> ' . htmlspecialchars(trim($m['serie']));
                                }
                                if (!empty(trim((string)($m['ip'] ?? '')))) {
                                    $datosTecnicos[] = '<strong>IP:</strong> ' . htmlspecialchars(trim($m['ip']));
                                }
                                if (!empty(trim((string)($m['mac'] ?? '')))) {
                                    $datosTecnicos[] = '<strong>MAC:</strong> ' . htmlspecialchars(trim($m['mac']));
                                }
                                ?>
                                <tr>
                                    <td style="text-align:center; color:#555; font-weight:bold;"><?= $index + 1 ?></td>
                                    <td style="padding-left:10px;">
                                        <div style="font-weight:bold; font-size:11px; color:#111;">
                                            <?= htmlspecialchars($m['material']) ?>
                                        </div>
                                        <?php if (!empty($datosTecnicos)): ?>
                                            <div class="datos-tecnicos">
                                                <?= implode(' &nbsp;•&nbsp; ', $datosTecnicos) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center; font-weight:bold; font-size:11px; white-space:nowrap;">
                                        <?= htmlspecialchars($m['cantidad']) ?> <?= htmlspecialchars($m['medida'] === 'm' ? 'm' : ($m['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding:12px; color:#666;">
                                    No hay materiales registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- III CHECKLIST -->
            <div class="seccion">
                <div class="titulo-seccion">
                    III. CHECKLIST DE INSTALACIÓN Y PRUEBAS
                </div>

                <?php
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
                            <td><?= htmlspecialchars($columna1[$i]['nombre'] ?? '') ?></td>
                            <td style="text-align:center; font-size:16px;">
                                <?= !empty($columna1[$i]['realizado']) ? '☑' : '☐' ?>
                            </td>
                            <td><?= htmlspecialchars($columna2[$i]['nombre'] ?? '') ?></td>
                            <td style="text-align:center; font-size:16px;">
                                <?= !empty($columna2[$i]['realizado']) ? '☑' : '☐' ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table>
            </div>

            <!-- OBSERVACIONES -->
            <div class="observaciones">
                <strong>OBSERVACIONES:</strong>

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
                    <small>FIRMA Y NOMBRE DEL TÉCNICO RESPONSABLE</small>
                </div>

                <div class="firma">
                    <div class="linea-firma"></div>
                    <small>FIRMA Y NOMBRE DEL COORDINADOR OPERATIVO</small>
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
