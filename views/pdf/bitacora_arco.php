<?php
include('../../config/db.php');

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = $_GET['id'];

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
        id,
        encargado,
        observaciones,
        fecha_registro
    FROM bitacoras_arco
    WHERE arco_id = ?
    ORDER BY fecha_registro DESC
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


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Bitácora de Instalación</title>

    <link rel="stylesheet" href="../../css/bitacora_arco.css">
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
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
                        <td colspan="3">
                            <strong>Técnico Responsable:</strong>
                            <span><?= htmlspecialchars($bitacora['encargado'] ?? '') ?></span>

                        </td>
                    </tr>

                    <tr>
                        <td>
                            <strong>Fecha Instalación:</strong>
                            <span>
                                <?= !empty($arco['fecha_instalacion']) 
                                    ? date("d / m / Y", strtotime($arco['fecha_instalacion'])) 
                                    : 'N/A' ?>
                            </span>
                        </td>

                        <td>
                            <strong>Latitud:</strong>
                            <span><?= htmlspecialchars($arco['lat']) ?></span>
                        </td>
                        <td>
                            <strong>Longitud:</strong>
                            <span><?= htmlspecialchars($arco['lng']) ?></span>
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
                    <tr>
                        <th style="width:70%;">COMPONENTE</th>
                        <th style="width:30%;">SERIE</th>
                    </tr>

                    <?php if (count($materiales) > 0): ?>
                        <?php foreach ($materiales as $m): ?>
                            <tr>
                                <td class="componente-cell">
                                    <span class="nombre-material">
                                        <?= htmlspecialchars($m['material']) ?>
                                    </span>
                                    <span class="cantidad-material">
                                        <?= htmlspecialchars($m['cantidad']) ?>
                                        <?= ' ' . strtoupper(htmlspecialchars($m['medida'] === 'm' ? 'Metros' : 'Piezas')) ?>
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <?= !empty($m['serie'])
                                        ? htmlspecialchars($m['serie'])
                                        : 'N/A' ?>
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

                    <?php
                        $filasMinimas = 4;
                        $filasRestantes = max(0, $filasMinimas - count($materiales));
                    ?>

                    <?php for ($i = count($materiales); $i < $filasRestantes; $i++): ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td></td>
                        </tr>
                    <?php endfor; ?>
                </table>
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

                    <?php for($i = 0; $i < $mitad; $i++): ?>
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
</body>

</html>