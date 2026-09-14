<?php
require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("ID de baja no recibido");
}

$id = (int)$_GET['id'];

if (empty($GLOBALS['DOMPDF_RENDERING'])) {
    $downloadParam = isset($_GET['download']) ? '&download=1' : '';
    header("Location: ../../controllers/pdf_controller.php?action=baja_pdf&id={$id}{$downloadParam}");
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        b.*,
        a.nombre AS arco,
        a.fecha_instalacion,
        a.lat,
        a.lng,
        a.estado,
        u.nombre AS ubicacion,
        t.nombre AS tecnico_responsable,
        t.firma AS tecnico_firma
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
if (!empty($baja['tecnico_firma'])) {
    $firmaDisk = __DIR__ . '/../../' . $baja['tecnico_firma'];
    if (file_exists($firmaDisk)) {
        $extF = strtolower(pathinfo($firmaDisk, PATHINFO_EXTENSION));
        $mimeF = ($extF === 'png') ? 'image/png' : 'image/jpeg';
        $firmaTecnicoData = 'data:' . $mimeF . ';base64,' . base64_encode(file_get_contents($firmaDisk));
    }
}

date_default_timezone_set('America/Mexico_City');
$fechaFormato = "08 - Sep - 2026";
$codigoFormato = 'INN-FOR-002-06';
$tituloFormato = 'BITÁCORA DE BAJA<br>DE ARCO LECTOR';

$safeArc = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($baja['arco'] ?? 'arco'));
$nombreArchivoPdf = "Baja_Arco_{$safeArc}_{$id}.pdf";

$totalMat = count($materiales);
$mitadMat = (int)ceil($totalMat / 2);
$matCol1 = array_slice($materiales, 0, $mitadMat);
$matCol2 = array_slice($materiales, $mitadMat);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bitácora de Baja - <?= htmlspecialchars($baja['arco']) ?></title>
  <style>
    @page { margin: 20px 30px 65px; }
    * { box-sizing: border-box; }

    body {
      margin: 0;
      color: #1d252c;
      font-family: "DejaVu Sans", Arial, sans-serif;
      font-size: 9px;
      line-height: 1.35;
    }
    .header {
      width: 100%;
      margin-bottom: 11px;
      border-collapse: collapse;
      table-layout: fixed;
      border-bottom: 8px solid #003865;
    }
    .header td {
      border: 0;
      vertical-align: middle;
    }
    .header-logo {
      width: 28%;
      padding: 6px;
      text-align: center;
    }
    .header-logo img {
      width: 155px;
      max-height: 54px;
      object-fit: contain;
    }
    .header-title {
      width: 48%;
      padding: 8px;
      color: #003865;
      font-size: 15px;
      font-weight: bold;
      text-align: center;
      line-height: 1.2;
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
      padding: 3.5px 5px;
      border: 0;
      font-size: 7.5px;
      text-align: left;
    }
    .header-info th {
      width: 42%;
      background: #f28c13;
      color: #111;
    }
    .section { margin-top: 8px; }
    .section-title {
      padding: 4px 7px;
      background: #003865;
      color: #fff;
      font-size: 8.5px;
      font-weight: bold;
      letter-spacing: .2px;
    }
    table.data {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    table.data td {
      padding: 4px 6px;
      border: 0;
      background: #fff;
      vertical-align: top;
    }
    table.data strong {
      display: block;
      margin-bottom: 2px;
      color: #4c5964;
      font-size: 7px;
      text-transform: uppercase;
    }
    .checklist-dual-grid {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-top: 3px;
    }
    .checklist-table-compact {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .checklist-table-compact th {
      padding: 3px 4px;
      background: #f1f5f9;
      color: #003865;
      font-size: 7px;
      font-weight: bold;
      border-bottom: 1.5px solid #003865;
      text-align: center;
    }
    .checklist-table-compact td {
      padding: 3px 4px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: middle;
    }
    .checklist-table-compact tbody tr:nth-child(even) td {
      background: #f8fafc;
    }
    .center { text-align: center; }
    .muted { color: #697782; }
    .badge-baja {
      display: inline-block;
      padding: 2px 6px;
      background: #dc3545;
      color: #fff;
      font-weight: bold;
      border-radius: 3px;
      font-size: 7.5px;
    }
    .observation-box {
      min-height: 40px;
      padding: 6px 7px;
      border: 0;
      background: #fff;
      white-space: pre-wrap;
      font-size: 8.5px;
      line-height: 1.3;
    }
    .signature {
      margin-top: 70px;
      text-align: center;
    }
    .signature-space {
      height: 32px;
      border-bottom: 1px solid #1f2933;
      width: 45%;
      margin: 0 auto;
    }
    .signature strong {
      display: block;
      margin-top: 4px;
      text-transform: uppercase;
    }
    .signature span {
      color: #66727d;
      font-size: 7px;
    }
    .footer {
      position: fixed;
      bottom: -45px;
      left: 0;
      right: 0;
      text-align: center;
    }
    .footer img {
      width: 82%;
      max-height: 42px;
      display: block;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <table class="header" border="0">
    <tr style="border: 0;">
      <td class="header-logo">
        <?php if ($logoData): ?><img src="<?= $logoData ?>" alt="Innovación y Tecnología"><?php endif; ?>
      </td>
      <td class="header-title" border="0"><?= $tituloFormato ?></td>
      <td class="header-info" border="0">
        <table>
          <tr><th>Código</th><td><?= htmlspecialchars($codigoFormato) ?></td></tr>
          <tr><th>Fecha</th><td><?= htmlspecialchars($fechaFormato) ?></td></tr>
          <tr><th>Página</th><td>1 de 1</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- I. DATOS DEL ARCO -->
  <div class="section">
    <div class="section-title">I. DATOS DEL ARCO</div>
    <table class="data">
      <tr>
        <td colspan="2">
          <strong>Nombre del Arco</strong>
          <?= htmlspecialchars($baja['arco']) ?>
        </td>
        <td colspan="2">
          <strong>Ubicación</strong>
          <?= htmlspecialchars($baja['ubicacion'] ?? 'N/A') ?>
        </td>
      </tr>
      <tr>
        <td style="width: 25%;">
          <strong>Fecha Instalación</strong>
          <?= !empty($baja['fecha_instalacion']) ? date("d/m/Y H:i", strtotime($baja['fecha_instalacion'])) : 'N/A' ?>
        </td>
        <td style="width: 25%;">
          <strong>Latitud</strong>
          <?= htmlspecialchars($baja['lat'] ?? 'N/A') ?>
        </td>
        <td style="width: 25%;">
          <strong>Longitud</strong>
          <?= htmlspecialchars($baja['lng'] ?? 'N/A') ?>
        </td>
      </tr>
    </table>
  </div>

  <!-- II. DATOS DE BAJA -->
  <div class="section">
    <div class="section-title">II. DATOS DE BAJA</div>
    <table class="data">
      <tr>
        <td style="width: 25%;">
          <strong>Fecha de Baja</strong>
          <?= date("d/m/Y", strtotime($baja['fecha_baja'])) ?>
        </td>
        <td style="width: 25%;">
          <strong>Hora de Baja</strong>
          <?= date("H:i A", strtotime($baja['fecha_baja'])) ?>
        </td>
        <td style="width: 25%;">
          <strong>Estado</strong>
          <span class="badge-baja"><?= htmlspecialchars($baja['estado'] ?? 'Baja') ?></span>
        </td>
      </tr>
      <tr>
        <td colspan="1">
          <strong>Motivo de Baja</strong>
          <?= htmlspecialchars($baja['motivo']) ?>
        </td>
        <td colspan="2" style="width: 50%;">
          <strong>Técnico Responsable</strong>
          <?= htmlspecialchars($baja['tecnico_responsable'] ?? 'N/A') ?>
        </td>
      </tr>
    </table>
  </div>

  <!-- III. COMPONENTES REGISTRADOS -->
  <div class="section">
    <div class="section-title">III. COMPONENTES REGISTRADOS</div>
    <?php if (empty($materiales)): ?>
      <table class="data">
        <tr>
          <td class="center muted" style="padding: 10px;">No se registraron componentes asociados a este arco.</td>
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
                  <th style="width: 78%; text-align: left; padding-left: 6px;">COMPONENTE / ESPECIFICACIÓN</th>
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
                    <td style="padding: 3px 4px; font-size: 7.5px; vertical-align: middle;">
                      <strong><?= htmlspecialchars($m['material']) ?></strong>
                      <?php if (!empty($details)): ?>
                        <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="center" style="font-weight: bold; font-size: 8px; vertical-align: middle;">
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
                  <th style="width: 78%; text-align: left; padding-left: 6px;">COMPONENTE / ESPECIFICACIÓN</th>
                  <th style="width: 22%; text-align: center;">CANT.</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($matCol2)): ?>
                  <tr><td colspan="2" class="center muted" style="padding: 6px;">&nbsp;</td></tr>
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
                      <td style="padding: 3px 4px; font-size: 7.5px; vertical-align: middle;">
                        <strong><?= htmlspecialchars($m['material']) ?></strong>
                        <?php if (!empty($details)): ?>
                          <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="center" style="font-weight: bold; font-size: 8px; vertical-align: middle;">
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

  <!-- IV. OBSERVACIONES -->
  <div class="section"">
    <div class="section-title">IV. OBSERVACIONES</div>
    <div class="observation-box">
      <?= !empty($baja['observaciones'])
          ? nl2br(htmlspecialchars($baja['observaciones']))
          : 'Sin observaciones adicionales registradas.' ?>
    </div>
  </div>


  <!-- FIRMA -->
  <div class="signature">
    <div class="signature-space">
      <?php if (!empty($firmaTecnicoData)): ?>
        <img src="<?= $firmaTecnicoData ?>" alt="Firma" style="max-height: 28px; max-width: 120px; display: block; margin: 0 auto;">
      <?php endif; ?>
    </div>
    <strong><?= htmlspecialchars($baja['tecnico_responsable'] ?? 'N/A') ?></strong>
    <span>NOMBRE Y FIRMA DEL TÉCNICO RESPONSABLE</span>
  </div>

  <?php if (!empty($piePaginaData)): ?>
    <div class="footer">
      <img src="<?= $piePaginaData ?>" alt="Pie de Página">
    </div>
  <?php endif; ?>
</body>
</html>
