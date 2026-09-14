<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/formatos_mantenimiento_schema.php';
asegurarTablaFormatosMantenimiento($pdo);

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];
$tipo = $_GET['tipo'] ?? '';
$revision = null;
$materiales = [];

if (empty($GLOBALS['DOMPDF_RENDERING'])) {
    $downloadParam = isset($_GET['download']) ? '&download=1' : '';
    $tipoParam = ($tipo === 'infra') ? '&tipo=infra' : '';
    header("Location: ../../controllers/pdf_controller.php?action=mantenimiento&id={$id}{$tipoParam}{$downloadParam}");
    exit;
}

if ($tipo === 'infra') {
    $stmt = $pdo->prepare("
        SELECT ir.*, t.nombre AS tecnico_responsable, t.firma AS tecnico_firma, n.nombre AS arco, n.tipo AS tipo_infra, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento, ir.infraestructura_id
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
        SELECT r.*, t.nombre AS tecnico_responsable, t.firma AS tecnico_firma, a.nombre AS arco, a.id AS arco_id, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento
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
            SELECT ir.*, t.nombre AS tecnico_responsable, t.firma AS tecnico_firma, n.nombre AS arco, n.tipo AS tipo_infra, u.nombre AS ubicacion, fecha_mantenimiento AS fecha_mantenimiento, ir.infraestructura_id
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

$fechaTimestamp = !empty($revision['fecha_mantenimiento']) ? strtotime($revision['fecha_mantenimiento']) : time();
$fechaMantenimiento = date("d/m/Y", $fechaTimestamp);
$fechaHoraMantenimiento = date("d/m/Y H:i", $fechaTimestamp);

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
if (!empty($revision['tecnico_firma'])) {
    $firmaDisk = __DIR__ . '/../../' . $revision['tecnico_firma'];
    if (file_exists($firmaDisk)) {
        $extF = strtolower(pathinfo($firmaDisk, PATHINFO_EXTENSION));
        $mimeF = ($extF === 'png') ? 'image/png' : 'image/jpeg';
        $firmaTecnicoData = 'data:' . $mimeF . ';base64,' . base64_encode(file_get_contents($firmaDisk));
    }
}

date_default_timezone_set('America/Mexico_City');
$fechaFormato = "02 -Abril-2026";
$codigoFormato = 'INN-FOR-002-04';
$tituloFormato = 'DIAGNÓSTICO INICIAL';

$safeArc = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($revision['arco'] ?? 'arco'));
$nombreArchivoPdf = "Diagnostico_Inicial_{$safeArc}_{$fechaMantenimiento}.pdf";

// Cargar o construir lista completa de componentes de Diagnóstico Inicial
$formatoStmt = $pdo->prepare("
    SELECT * FROM formatos_mantenimiento
    WHERE tipo = 'checklist'
      AND " . ($tipo === 'infra' ? "infraestructura_revision_id = ?" : "revision_id = ?") . "
    ORDER BY id DESC LIMIT 1
");
$formatoStmt->execute([$id]);
$formatoGuardado = $formatoStmt->fetch(PDO::FETCH_ASSOC);

$componentes = [];
$observacionesFormato = $revision['observaciones'] ?? '';

if ($formatoGuardado && !empty($formatoGuardado['datos'])) {
    $datosFormato = json_decode($formatoGuardado['datos'], true) ?: [];
    $componentes = array_values($datosFormato['componentes'] ?? []);
    if (!empty($datosFormato['observaciones'])) {
        $observacionesFormato = $datosFormato['observaciones'];
    }
} else {
    // Armar componentes desde el inventario del arco/sitio + materiales cambiados
    if ($tipo === 'infra') {
        $instStmt = $pdo->prepare("
            SELECT im.*, m.nombre AS material, m.medida
            FROM infraestructura_material im
            JOIN materiales m ON im.material_id = m.id
            WHERE im.infraestructura_id = ?
            ORDER BY im.id ASC
        ");
        $instStmt->execute([$revision['infraestructura_id'] ?? 0]);
        $instalados = $instStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $instStmt = $pdo->prepare("
            SELECT am.*, m.nombre AS material, m.medida
            FROM arco_material am
            JOIN materiales m ON am.material_id = m.id
            WHERE am.arco_id = ?
            ORDER BY am.id ASC
        ");
        $instStmt->execute([$revision['arco_id'] ?? 0]);
        $instalados = $instStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $cambiadosMap = [];
    foreach ($materiales as $mat) {
        if (!empty($mat['arco_material_id'])) {
            $cambiadosMap['rel_' . $mat['arco_material_id']] = $mat;
        }
        if (!empty($mat['infraestructura_material_id'])) {
            $cambiadosMap['rel_' . $mat['infraestructura_material_id']] = $mat;
        }
        $cambiadosMap['mat_' . $mat['material_id']] = $mat;
    }

    foreach ($instalados as $inst) {
        $relKey = 'rel_' . $inst['id'];
        $matKey = 'mat_' . $inst['material_id'];
        $fueCambiado = isset($cambiadosMap[$relKey]) || isset($cambiadosMap[$matKey]);
        $cambio = $cambiadosMap[$relKey] ?? ($cambiadosMap[$matKey] ?? null);

        $componentes[] = [
            'nombre' => $inst['material'],
            'serie' => $inst['serie'] ?? '',
            'ip' => $inst['ip'] ?? '',
            'mac' => $inst['mac'] ?? '',
            'estado' => $fueCambiado ? 'Malo' : 'Bueno',
            'cambiado' => $fueCambiado,
            'observacion' => $fueCambiado && !empty($revision['observaciones']) ? $revision['observaciones'] : ''
        ];
    }

    // Materiales agregados
    foreach ($materiales as $mat) {
        if (($mat['accion'] ?? '') === 'agregado' && empty($mat['arco_material_id']) && empty($mat['infraestructura_material_id'])) {
            $componentes[] = [
                'nombre' => $mat['material'],
                'serie' => $mat['serie'] ?? '',
                'ip' => $mat['ip'] ?? '',
                'mac' => $mat['mac'] ?? '',
                'estado' => 'Bueno',
                'cambiado' => true,
                'observacion' => 'Nuevo componente agregado'
            ];
        }
    }

    // Estructura metálica estándar si no existe
    $hasEstructura = false;
    foreach ($componentes as $c) {
        if (stripos($c['nombre'], 'estructura') !== false) {
            $hasEstructura = true;
            break;
        }
    }
    if (!$hasEstructura) {
        $componentes[] = [
            'nombre' => 'Estructura metálica',
            'serie' => '',
            'ip' => '',
            'mac' => '',
            'estado' => 'Bueno',
            'cambiado' => false,
            'observacion' => ''
        ];
    }
}

$totalComp = count($componentes);
$half = (int)ceil($totalComp / 2);
$leftCol = array_slice($componentes, 0, $half);
$rightCol = array_slice($componentes, $half);

if (!function_exists('revPdfMark')) {
    function revPdfMark(bool $checked): string {
        return $checked ? 'X' : '&nbsp;';
    }
}

$tipoMant = $revision['tipo_mantenimiento'] ?? 'Correctivo';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($tituloFormato) ?> - <?= htmlspecialchars($revision['arco']) ?></title>
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
      margin-bottom: 13px;
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
      padding: 7px;
      text-align: center;
    }
    .header-logo img {
      width: 155px;
      max-height: 54px;
      object-fit: contain;
    }
    .header-title {
      width: 48%;
      padding: 10px;
      color: #003865;
      font-size: 17px;
      font-weight: bold;
      text-align: center;
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
      padding: 4px 5px;
      border: 0;
      font-size: 7.5px;
      text-align: left;
    }
    .header-info th {
      width: 42%;
      background: #f28c13;
      color: #111;
    }
    .section { margin-top: 10px; }
    .section-title {
      padding: 5px 7px;
      background: #003865;
      color: #fff;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: .2px;
    }
    table.data,
    table.grid {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    table.data td,
    table.grid th,
    table.grid td {
      padding: 5px 6px;
      border: 0;
      vertical-align: top;
    }
    table.data td {
      background: #fff;
    }
    table.data tr + tr td {
      padding-top: 7px;
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
      padding: 3px 2px;
      background: #f1f5f9;
      color: #003865;
      font-size: 7px;
      font-weight: bold;
      border-bottom: 1.5px solid #003865;
      text-align: center;
    }
    .checklist-table-compact td {
      padding: 2.5px 3px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: middle;
    }
    .checklist-table-compact tbody tr:nth-child(even) td {
      background: #f8fafc;
    }
    .checklist-table-compact .mark {
      width: 11px;
      height: 11px;
      font-size: 8px;
      line-height: 9px;
      margin: 0;
    }
    .center { text-align: center; }
    .mark {
      display: inline-block;
      width: 13px;
      height: 13px;
      margin-right: 3px;
      border: 1px solid #1f2933;
      font-size: 9px;
      font-weight: bold;
      line-height: 11px;
      text-align: center;
    }
    .muted { color: #697782; }
    .observation-box {
      min-height: 48px;
      padding: 7px;
      border: 0;
      background: #fff;
      white-space: pre-wrap;
    }
    .signature {
      margin-top: 12px;
      text-align: center;
    }
    .signature-space {
      height: 34px;
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
      <td class="header-title" border="0"><?= htmlspecialchars($tituloFormato) ?></td>
      <td class="header-info" border="0">
        <table>
          <tr><th>Código</th><td><?= htmlspecialchars($codigoFormato) ?></td></tr>
          <tr><th>Fecha</th><td><?= htmlspecialchars($fechaFormato) ?></td></tr>
          <tr><th>Página</th><td>1 de 1</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="section">
    <div class="section-title">I. DATOS DEL SERVICIO</div>
    <table class="data">
      <tr>
        <td style="width:34%"><strong><?= $tipo === 'infra' ? 'Sitio / Infraestructura' : 'Arco' ?></strong><?= htmlspecialchars($revision['arco']) ?></td>
        <td style="width:28%"><strong>Ubicación</strong><?= htmlspecialchars($revision['ubicacion'] ?? 'N/A') ?></td>
        <td style="width:38%"><strong>Técnico responsable</strong><?= htmlspecialchars($revision['tecnico_responsable'] ?? 'N/A') ?></td>
      </tr>
      <tr>
        <td>
          <strong>Fecha y hora</strong>
          <?= $fechaHoraMantenimiento ?>
        </td>
        <td>
          <strong style="margin: 5px 0px;">Tipo de mantenimiento</strong>
          <span class="mark"><?= revPdfMark($tipoMant === 'Preventivo') ?></span> Preventivo
          &nbsp;
          <span class="mark"><?= revPdfMark($tipoMant === 'Correctivo') ?></span> Correctivo
        </td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">II. DIAGNÓSTICO DE COMPONENTES</div>
    <table class="checklist-dual-grid">
      <tr>
        <!-- Columna Izquierda (1 de 2) -->
        <td style="width: 49%; vertical-align: top; padding: 0;">
          <table class="checklist-table-compact">
            <thead>
              <tr>
                <th style="width: 52%; text-align: left; padding-left: 4px;">Componente</th>
                <th style="width: 16%;">Bueno</th>
                <th style="width: 16%;">Malo</th>
                <th style="width: 16%;">Camb.</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($leftCol)): ?>
                <tr><td colspan="4" class="center muted" style="padding: 6px;">Sin componentes</td></tr>
              <?php else: ?>
                <?php foreach ($leftCol as $component): ?>
                  <tr>
                    <td style="padding: 3px 4px; font-size: 7.5px; vertical-align: top;">
                      <strong><?= htmlspecialchars($component['nombre'] ?? '') ?></strong>
                      <?php
                      $details = [];
                      if (trim((string)($component['serie'] ?? '')) !== '') {
                          $details[] = 'S: ' . htmlspecialchars($component['serie']);
                      }
                      if (trim((string)($component['ip'] ?? '')) !== '') {
                          $details[] = 'IP: ' . htmlspecialchars($component['ip']);
                      }
                      if (trim((string)($component['mac'] ?? '')) !== '') {
                          $details[] = 'MAC: ' . htmlspecialchars($component['mac']);
                      }
                      if (!empty($details)): ?>
                        <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(($component['estado'] ?? '') === 'Bueno') ?></span></td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(($component['estado'] ?? '') === 'Malo') ?></span></td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(!empty($component['cambiado'])) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
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
                <th style="width: 52%; text-align: left; padding-left: 4px;">Componente</th>
                <th style="width: 16%;">Bueno</th>
                <th style="width: 16%;">Malo</th>
                <th style="width: 16%;">Camb.</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rightCol)): ?>
                <tr><td colspan="4" class="center muted" style="padding: 6px;">&nbsp;</td></tr>
              <?php else: ?>
                <?php foreach ($rightCol as $component): ?>
                  <tr>
                    <td style="padding: 3px 4px; font-size: 7.5px; vertical-align: top;">
                      <strong><?= htmlspecialchars($component['nombre'] ?? '') ?></strong>
                      <?php
                      $details = [];
                      if (trim((string)($component['serie'] ?? '')) !== '') {
                          $details[] = 'S: ' . htmlspecialchars($component['serie']);
                      }
                      if (trim((string)($component['ip'] ?? '')) !== '') {
                          $details[] = 'IP: ' . htmlspecialchars($component['ip']);
                      }
                      if (trim((string)($component['mac'] ?? '')) !== '') {
                          $details[] = 'MAC: ' . htmlspecialchars($component['mac']);
                      }
                      if (!empty($details)): ?>
                        <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(($component['estado'] ?? '') === 'Bueno') ?></span></td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(($component['estado'] ?? '') === 'Malo') ?></span></td>
                    <td class="center" style="vertical-align: middle;"><span class="mark"><?= revPdfMark(!empty($component['cambiado'])) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">III. OBSERVACIONES</div>
    <div class="observation-box">
      <?= !empty($observacionesFormato)
          ? nl2br(htmlspecialchars($observacionesFormato))
          : 'Sin observaciones adicionales registradas.' ?>
    </div>
  </div>

  <div class="signature">
    <div class="signature-space">
      <?php if (!empty($firmaTecnicoData)): ?>
        <img src="<?= $firmaTecnicoData ?>" alt="Firma" style="max-height: 28px; max-width: 120px; display: block; margin: 0 auto;">
      <?php endif; ?>
    </div>
    <strong><?= htmlspecialchars($revision['tecnico_responsable'] ?? 'N/A') ?></strong>
    <span>NOMBRE Y FIRMA DEL TÉCNICO RESPONSABLE</span>
  </div>

  <?php if (!empty($piePaginaData)): ?>
    <div class="footer">
      <img src="<?= $piePaginaData ?>" alt="Pie de Página">
    </div>
  <?php endif; ?>
</body>
</html>
