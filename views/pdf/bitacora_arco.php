<?php
require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];
$tipo = $_GET['tipo'] ?? '';

if (empty($GLOBALS['DOMPDF_RENDERING'])) {
    $downloadParam = isset($_GET['download']) ? '&download=1' : '';
    $tipoParam = ($tipo === 'infra') ? '&tipo=infra' : '';
    header("Location: ../../controllers/pdf_controller.php?action=bitacora_pdf&id={$id}{$tipoParam}{$downloadParam}");
    exit;
}

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
            t.firma AS tecnico_firma,
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
        ORDER BY im.id ASC
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
                t.firma AS tecnico_firma,
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
            ORDER BY im.id ASC
        ");
        $matStmt->execute([$id]);
        $materiales = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $bitStmt = $pdo->prepare("
            SELECT 
                b.id,
                t.nombre AS encargado,
                t.firma AS tecnico_firma,
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
            ORDER BY am.id ASC
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
} else {
    $checkStmt = $pdo->query("SELECT id, nombre, 0 AS realizado FROM checklist_conceptos ORDER BY id ASC");
    $checks = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
}

$logoDiskPath = __DIR__ . '/../../assets/LOGO INNOVATEC PDF.jpg';
if (!file_exists($logoDiskPath)) {
    $logoDiskPath = __DIR__ . '/../../assets/LOGO INNOVATEC.png';
}
$logoData = file_exists($logoDiskPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoDiskPath)) : '';

$piePaginaDiskPath = __DIR__ . '/../../assets/img/PiePagina.jpg';
if (!file_exists($piePaginaDiskPath)) {
    $piePaginaDiskPath = __DIR__ . '/../../assets/img/PiePagina.png';
}
$piePaginaData = file_exists($piePaginaDiskPath)
    ? 'data:image/' . (pathinfo($piePaginaDiskPath, PATHINFO_EXTENSION) === 'png' ? 'png' : 'jpeg') . ';base64,' . base64_encode(file_get_contents($piePaginaDiskPath))
    : '';

$firmaTecnicoData = '';
if (!empty($bitacora['tecnico_firma'])) {
    $firmaDisk = __DIR__ . '/../../' . $bitacora['tecnico_firma'];
    if (file_exists($firmaDisk)) {
        $extF = strtolower(pathinfo($firmaDisk, PATHINFO_EXTENSION));
        $mimeF = ($extF === 'png') ? 'image/png' : 'image/jpeg';
        $firmaTecnicoData = 'data:' . $mimeF . ';base64,' . base64_encode(file_get_contents($firmaDisk));
    }
}

date_default_timezone_set('America/Mexico_City');
$fechaFormato = '02 -Abril-2026';
$codigoFormato = 'INN-FOR-002-03';
$tituloFormato = ($tipo === 'infra') ? 'BITÁCORA INSTALACIÓN DE<br>INFRAESTRUCTURA' : 'BITÁCORA INSTALACIÓN DE<br>ARCO LECTOR';

// Materiales en 2 columnas
$totalMat = count($materiales);
$mitadMat = (int)ceil($totalMat / 2);
$matCol1 = array_slice($materiales, 0, $mitadMat);
$matCol2 = array_slice($materiales, $mitadMat);

// Checklist en 2 columnas
$totalChecks = count($checks);
$mitadChecks = (int)ceil($totalChecks / 2);
$checkCol1 = array_slice($checks, 0, $mitadChecks);
$checkCol2 = array_slice($checks, $mitadChecks);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bitácora de Instalación - <?= htmlspecialchars($arco['nombre']) ?></title>
  <style>
    @page { margin: 15px 24px 42px; }
    * { box-sizing: border-box; }

    body {
      margin: 0;
      color: #1d252c;
      font-family: "DejaVu Sans", Arial, sans-serif;
      font-size: 7.8px;
      line-height: 1.25;
    }
    .header {
      width: 100%;
      margin-bottom: 6px;
      border-collapse: collapse;
      table-layout: fixed;
      border-bottom: 6px solid #003865;
    }
    .header td {
      border: 0;
      vertical-align: middle;
    }
    .header-logo {
      width: 26%;
      padding: 3px 0;
      text-align: left;
    }
    .header-logo img {
      width: 140px;
      max-height: 46px;
      object-fit: contain;
    }
    .header-title {
      width: 50%;
      padding: 3px;
      color: #003865;
      font-size: 13.5px;
      font-weight: bold;
      text-align: center;
      line-height: 1.15;
    }
    .header-info {
      width: 24%;
      padding: 0;
    }
    .header-info table {
      width: 100%;
      border-collapse: collapse;
    }
    .header-info th,
    .header-info td {
      padding: 2.5px 4px;
      border: 0;
      font-size: 7px;
      text-align: left;
    }
    .header-info th {
      width: 42%;
      background: #f28c13;
      color: #111;
      font-weight: bold;
    }
    .header-info td {
      background: #f8fafc;
      color: #111;
      font-weight: bold;
    }
    .section { margin-top: 5px; }
    .section-title {
      padding: 3px 6px;
      background: #003865;
      color: #fff;
      font-size: 8px;
      font-weight: bold;
      letter-spacing: .2px;
    }
    table.data {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-top: 2px;
    }
    table.data td {
      padding: 3px 5px;
      border: 1px solid #e2e8f0;
      background: #fff;
      vertical-align: middle;
      font-size: 7.5px;
    }
    table.data strong {
      color: #003865;
      font-size: 7px;
      text-transform: uppercase;
    }
    .checklist-dual-grid {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-top: 2px;
    }
    .checklist-table-compact {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .checklist-table-compact th {
      padding: 2.5px 4px;
      background: #f1f5f9;
      color: #003865;
      font-size: 6.8px;
      font-weight: bold;
      border-bottom: 1.5px solid #003865;
      border-top: 1px solid #cbd5e1;
      border-left: 1px solid #cbd5e1;
      border-right: 1px solid #cbd5e1;
      text-align: center;
    }
    .checklist-table-compact td {
      padding: 2.5px 4px;
      border: 1px solid #e2e8f0;
      vertical-align: middle;
      font-size: 7.2px;
    }
    .checklist-table-compact tbody tr:nth-child(even) td {
      background: #f8fafc;
    }
    .center { text-align: center; }
    .muted { color: #64748b; }
    .mark-box {
      font-size: 8px;
      font-weight: bold;
      color: #003865;
    }
    .observation-box {
      min-height: 24px;
      max-height: 38px;
      padding: 3px 5px;
      border: 1px solid #cbd5e1;
      background: #fff;
      white-space: pre-wrap;
      font-size: 7.5px;
      line-height: 1.2;
      margin-top: 2px;
    }
    .signatures-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-top: 10px;
    }
    .signature-block {
      text-align: center;
      vertical-align: top;
      padding: 0 8px;
    }
    .signature-line {
      height: 18px;
      border-bottom: 1px solid #1f2933;
      width: 75%;
      margin: 0 auto;
    }
    .signature-block strong {
      display: block;
      margin-top: 3px;
      font-size: 7.5px;
      text-transform: uppercase;
      color: #111;
    }
    .signature-block span {
      display: block;
      color: #475569;
      font-size: 6.5px;
      font-style: italic;
    }
    .footer {
      position: fixed;
      bottom: -32px;
      left: 0;
      right: 0;
      text-align: center;
    }
    .footer img {
      width: 82%;
      max-height: 36px;
      display: block;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <!-- ENCABEZADO OFICIAL -->
  <table class="header" border="0">
    <tr>
      <td class="header-logo">
        <?php if ($logoData): ?><img src="<?= $logoData ?>" alt="Innovación y Tecnología"><?php endif; ?>
      </td>
      <td class="header-title"><?= $tituloFormato ?></td>
      <td class="header-info">
        <table>
          <tr><th>Código</th><td><?= htmlspecialchars($codigoFormato) ?></td></tr>
          <tr><th>Fecha</th><td><?= htmlspecialchars($fechaFormato) ?></td></tr>
          <tr><th>Página</th><td>1 de 1</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- I. DATOS DEL SERVICIO -->
  <div class="section">
    <div class="section-title">I. DATOS DEL SERVICIO</div>
    <table class="data">
      <tr>
        <td style="width: 65%;">
          <strong>Nombre del <?= ($tipo === 'infra') ? 'Sitio' : 'Arco' ?>:</strong>
          <?= htmlspecialchars($arco['nombre']) ?><?= !empty($arco['ubicacion']) ? ' - ' . htmlspecialchars($arco['ubicacion']) : '' ?>
        </td>
        <td style="width: 35%;">
          <strong>Fecha Instalación:</strong>
          <?= !empty($arco['fecha_instalacion']) ? date("d/m/Y", strtotime($arco['fecha_instalacion'])) : 'N/A' ?>
        </td>
      </tr>
      <tr>
        <td>
          <strong>Técnico Responsable:</strong>
          <?= htmlspecialchars($bitacora['encargado'] ?? 'Sin asignar') ?>
        </td>
        <td>
          <strong>Hora:</strong>
          <?= !empty($bitacora['fecha_registro']) ? date("h:i A", strtotime($bitacora['fecha_registro'])) : date("h:i A") ?>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <strong>Coordenadas de instalación:</strong>
          <span style="margin-left: 8px;"><strong>Lat:</strong> <?= !empty($arco['lat']) ? htmlspecialchars($arco['lat']) : 'N/A' ?></span>
          <span style="margin-left: 15px;"><strong>Lng:</strong> <?= !empty($arco['lng']) ? htmlspecialchars($arco['lng']) : 'N/A' ?></span>
        </td>
      </tr>
    </table>
  </div>

  <!-- II. COMPONENTES INSTALADOS -->
  <div class="section">
    <div class="section-title">II. COMPONENTES INSTALADOS EN EL <?= ($tipo === 'infra') ? 'SITIO' : 'ARCO' ?></div>
    <?php if (empty($materiales)): ?>
      <table class="data">
        <tr>
          <td class="center muted" style="padding: 6px;">No hay materiales o componentes registrados.</td>
        </tr>
      </table>
    <?php else: ?>
      <table class="checklist-dual-grid">
        <tr>
          <!-- Columna Izquierda (1 de 2) -->
          <td style="width: 49%; vertical-align: top; padding: 0;">
            <table class="checklist-table-compact">
              <thead>
                <tr>
                  <th style="width: 78%; text-align: left; padding-left: 5px;">COMPONENTE / ESPECIFICACIÓN</th>
                  <th style="width: 22%; text-align: center;">CANT.</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($matCol1 as $m): ?>
                  <?php
                  $details = [];
                  if (trim((string)($m['serie'] ?? '')) !== '') {
                      $details[] = 'S: ' . htmlspecialchars($m['serie']);
                  }
                  if (trim((string)($m['ip'] ?? '')) !== '') {
                      $details[] = 'IP: ' . htmlspecialchars($m['ip']);
                  }
                  if (trim((string)($m['mac'] ?? '')) !== '') {
                      $details[] = 'MAC: ' . htmlspecialchars($m['mac']);
                  }
                  ?>
                  <tr>
                    <td style="padding: 2px 4px; vertical-align: middle;">
                      <strong><?= htmlspecialchars($m['material']) ?></strong>
                      <?php if (!empty($details)): ?>
                        <span class="muted" style="display:block; font-size:6px; line-height: 1.1; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="center" style="font-weight: bold; font-size: 7.5px; vertical-align: middle;">
                      <?= htmlspecialchars($m['cantidad']) ?> <?= htmlspecialchars($m['medida'] === 'm' ? 'm' : ($m['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </td>

          <!-- Separador Central -->
          <td style="width: 2%;"></td>

          <!-- Columna Derecha (2 de 2) -->
          <td style="width: 49%; vertical-align: top; padding: 0;">
            <table class="checklist-table-compact">
              <thead>
                <tr>
                  <th style="width: 78%; text-align: left; padding-left: 5px;">COMPONENTE / ESPECIFICACIÓN</th>
                  <th style="width: 22%; text-align: center;">CANT.</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($matCol2)): ?>
                  <tr><td colspan="2" class="center muted" style="padding: 4px;">&nbsp;</td></tr>
                <?php else: ?>
                  <?php foreach ($matCol2 as $m): ?>
                    <?php
                    $details = [];
                    if (trim((string)($m['serie'] ?? '')) !== '') {
                        $details[] = 'S: ' . htmlspecialchars($m['serie']);
                    }
                    if (trim((string)($m['ip'] ?? '')) !== '') {
                        $details[] = 'IP: ' . htmlspecialchars($m['ip']);
                    }
                    if (trim((string)($m['mac'] ?? '')) !== '') {
                        $details[] = 'MAC: ' . htmlspecialchars($m['mac']);
                    }
                    ?>
                    <tr>
                      <td style="padding: 2px 4px; vertical-align: middle;">
                        <strong><?= htmlspecialchars($m['material']) ?></strong>
                        <?php if (!empty($details)): ?>
                          <span class="muted" style="display:block; font-size:6px; line-height: 1.1; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="center" style="font-weight: bold; font-size: 7.5px; vertical-align: middle;">
                        <?= htmlspecialchars($m['cantidad']) ?> <?= htmlspecialchars($m['medida'] === 'm' ? 'm' : ($m['cantidad'] == 1 ? 'pz' : 'pzs')) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </td>
        </tr>
      </table>
    <?php endif; ?>
  </div>

  <!-- III. CHECKLIST DE INSTALACIÓN Y PRUEBAS -->
  <div class="section">
    <div class="section-title">III. CHECKLIST DE INSTALACIÓN Y PRUEBAS</div>
    <table class="checklist-dual-grid">
      <tr>
        <!-- Columna Izquierda (1 de 2) -->
        <td style="width: 49%; vertical-align: top; padding: 0;">
          <table class="checklist-table-compact">
            <thead>
              <tr>
                <th style="width: 82%; text-align: left; padding-left: 5px;">CONCEPTO</th>
                <th style="width: 18%; text-align: center;">✓</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($checkCol1 as $chk): ?>
                <tr>
                  <td style="padding: 2px 4px; vertical-align: middle;">
                    <?= htmlspecialchars($chk['nombre']) ?>
                  </td>
                  <td class="center mark-box" style="vertical-align: middle;">
                    <?= !empty($chk['realizado']) ? '☑' : '☐' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </td>

        <!-- Separador Central -->
        <td style="width: 2%;"></td>

        <!-- Columna Derecha (2 de 2) -->
        <td style="width: 49%; vertical-align: top; padding: 0;">
          <table class="checklist-table-compact">
            <thead>
              <tr>
                <th style="width: 82%; text-align: left; padding-left: 5px;">CONCEPTO</th>
                <th style="width: 18%; text-align: center;">✓</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($checkCol2)): ?>
                <tr><td colspan="2" class="center muted" style="padding: 4px;">&nbsp;</td></tr>
              <?php else: ?>
                <?php foreach ($checkCol2 as $chk): ?>
                  <tr>
                    <td style="padding: 2px 4px; vertical-align: middle;">
                      <?= htmlspecialchars($chk['nombre']) ?>
                    </td>
                    <td class="center mark-box" style="vertical-align: middle;">
                      <?= !empty($chk['realizado']) ? '☑' : '☐' ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </td>
      </tr>
    </table>
  </div>

  <!-- IV. OBSERVACIONES -->
  <div class="section">
    <div class="section-title">IV. OBSERVACIONES</div>
    <div class="observation-box">
      <?= !empty($bitacora['observaciones'])
          ? nl2br(htmlspecialchars($bitacora['observaciones']))
          : 'Sin observaciones registradas.' ?>
    </div>
  </div>

  <!-- V. FIRMAS -->
  <table class="signatures-table">
    <tr>
      <td class="signature-block" style="width: 48%;">
        <div class="signature-line">
          <?php if (!empty($firmaTecnicoData)): ?>
            <img src="<?= $firmaTecnicoData ?>" alt="Firma" style="max-height: 22px; max-width: 110px; display: block; margin: 0 auto;">
          <?php endif; ?>
        </div>
        <strong><?= htmlspecialchars($bitacora['encargado'] ?? 'TÉCNICO RESPONSABLE') ?></strong>
        <span>FIRMA Y NOMBRE DEL TÉCNICO RESPONSABLE</span>
      </td>
      <td style="width: 4%;"></td>
      <td class="signature-block" style="width: 48%;">
        <div class="signature-line"></div>
        <strong>COORDINADOR OPERATIVO</strong>
        <span>FIRMA Y NOMBRE DEL COORDINADOR OPERATIVO</span>
      </td>
    </tr>
  </table>

  <!-- PIE DE PÁGINA INSTITUCIONAL -->
  <?php if (!empty($piePaginaData)): ?>
    <div class="footer">
      <img src="<?= $piePaginaData ?>" alt="Pie de Página">
    </div>
  <?php endif; ?>
</body>
</html>