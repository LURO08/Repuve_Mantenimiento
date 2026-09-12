<?php
if (!function_exists('pdfText')) {
    function pdfText($value, string $fallback = 'N/A'): string
    {
        $text = trim((string)$value);
        return $text !== ''
            ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            : $fallback;
    }
}

if (!function_exists('pdfMark')) {
    function pdfMark(bool $checked): string
    {
        return $checked ? 'X' : '&nbsp;';
    }
}

if (!function_exists('pdfToolItem')) {
    function pdfToolItem($item): string
    {
        if (!is_array($item)) return pdfText($item);
        $name = pdfText($item['nombre'] ?? '');
        $quantity = (int)($item['cantidad'] ?? 0);
        $unit = pdfText($item['unidad'] ?? '', '');
        return $quantity > 0 ? $quantity . ' ' . $unit . ' - ' . $name : $name;
    }
}

$fechaMantenimiento = !empty($registro['fecha_mantenimiento']) ? strtotime($registro['fecha_mantenimiento']) : null;
$fechaDocumento = $fechaMantenimiento ?: (!empty($registro['created_at']) ? strtotime($registro['created_at']) : time());
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= pdfText($config['pdf_title']) ?></title>
  <style>
    @page { margin: 24px 32px 145px; }
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
    table.grid th {
      background: #fff;
      color: #003865;
      font-size: 7.5px;
      text-align: center;
    }
    table.grid tbody tr:nth-child(even) td {
      background: #fff;
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
    .yes { color: #003865; font-weight: bold; }
    .bad { color: #b02a37; font-weight: bold; }
    .muted { color: #697782; }
    .summary-grid {
      width: 100%;
      border-collapse: separate;
      border-spacing: 5px;
      table-layout: fixed;
    }
    .summary-grid td {
      padding: 7px;
      border: 0;
      background: #fff;
      vertical-align: top;
    }
    .summary-grid strong {
      display: block;
      margin-bottom: 5px;
      color: #4c5964;
      font-size: 7px;
      text-transform: uppercase;
    }
    .item-columns {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .item-columns td {
      width: 50%;
      padding: 5px 7px;
      border: 0;
    }
    .item-columns tr:nth-child(even) td {
      background: #fff;
    }
    .item-columns .check {
      color: #003865;
      font-weight: bold;
    }
    .observation-box {
      min-height: 48px;
      padding: 7px;
      border: 0;
      background: #fff;
      white-space: pre-wrap;
    }
    .signature {
      position: fixed;
      right: 145px;
      bottom: 73px;
      left: 145px;
      text-align: center;
    }
    .signature-space {
      height: 38px;
      border-bottom: 1px solid #1f2933;
      width: 50%;
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
    .tools-document .section {
      margin-top: 7px;
    }
    .tools-document .section-title {
      padding-top: 4px;
      padding-bottom: 4px;
    }
    .tools-document .item-columns td {
      padding: 2.5px 7px;
      font-size: 8px;
    }
    .tools-document .observation-box {
      padding: 4px 7px;
      font-size: 8px;
    }
    .tools-document .signature {
      bottom: 68px;
    }
    .page-break { page-break-before: always; }
  </style>
</head>
<body class="<?= $registro['tipo'] === 'tools' ? 'tools-document' : '' ?>">
  <table class="header" border="0">
    <tr style="border: 0;">
      <td class="header-logo">
        <?php if ($logoData): ?><img src="<?= $logoData ?>" alt="Innovación y Tecnología"><?php endif; ?>
      </td>
      <td class="header-title" border="0"><?= pdfText($config['pdf_title']) ?></td>
      <td class="header-info" border="0">
        <table>
          <tr><th>Código</th><td><?= pdfText($config['code']) ?></td></tr>
          <tr><th>Fecha</th><td><?= $fechaDocumento ? date('d/m/Y', $fechaDocumento) : date('d/m/Y') ?></td></tr>
          <tr><th>Página</th><td>1 de 1</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="section">
    <div class="section-title">I. DATOS DEL SERVICIO</div>
    <table class="data">
      <?php if ($registro['tipo'] === 'checklist'): ?>
      <tr>
        <td style="width:34%"><strong>Arco</strong><?= pdfText($registro['arco']) ?></td>
        <td style="width:28%"><strong>Ubicación</strong><?= pdfText($registro['ubicacion']) ?></td>
        <td style="width:38%"><strong>Técnico responsable</strong><?= pdfText($registro['tecnico_responsable']) ?></td>
      </tr>
      <?php else: ?>
      <tr>
        <td style="width:58%">
          <strong>Arco / Ubicación</strong>
          <?= pdfText($registro['arco']) ?> - <?= pdfText($registro['ubicacion']) ?>
        </td>
        <td style="width:42%"><strong>Técnico responsable</strong><?= pdfText($registro['tecnico_responsable']) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>
          <strong>Fecha y hora</strong>
          <?= $fechaMantenimiento ? date('d/m/Y H:i', $fechaMantenimiento) : 'N/A' ?>
        </td>
        <td>
          <strong style="margin: 5px 0px;">Tipo de mantenimiento</strong>
          <?php if ($registro['tipo'] === 'tools'): ?>
            <span class="mark"><?= pdfMark($registro['tipo_mantenimiento'] === 'Nueva Instalacion') ?></span> Nueva instalación
            &nbsp;
          <?php endif; ?>
          <span class="mark"><?= pdfMark($registro['tipo_mantenimiento'] === 'Preventivo') ?></span> Preventivo
          &nbsp;
          <span class="mark"><?= pdfMark($registro['tipo_mantenimiento'] === 'Correctivo') ?></span> Correctivo
        </td>
      </tr>
    </table>
  </div>

  <?php if ($registro['tipo'] === 'checklist'): ?>
    <?php
    $componentes = array_values($datos['componentes'] ?? []);
    $totalComp = count($componentes);
    $half = (int)ceil($totalComp / 2);
    $leftCol = array_slice($componentes, 0, $half);
    $rightCol = array_slice($componentes, $half);

    $allObservations = [];
    foreach ($componentes as $comp) {
        $obs = trim((string)($comp['observacion'] ?? ''));
        if ($obs !== '') {
            $allObservations[] = '<strong>' . pdfText($comp['nombre']) . ':</strong> ' . pdfText($obs);
        }
    }
    ?>
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
                        <strong><?= pdfText($component['nombre'] ?? '') ?></strong>
                        <?php
                        $details = [];
                        if (trim((string)($component['serie'] ?? '')) !== '') {
                            $details[] = 'S: ' . pdfText($component['serie']);
                        }
                        if (trim((string)($component['ip'] ?? '')) !== '') {
                            $details[] = 'IP: ' . pdfText($component['ip']);
                        }
                        if (trim((string)($component['mac'] ?? '')) !== '') {
                            $details[] = 'MAC: ' . pdfText($component['mac']);
                        }
                        if (!empty($details)): ?>
                          <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($component['observacion'] ?? '')) !== ''): ?>
                          <span style="display:block; font-size:6.5px; color:#854d0e; line-height: 1.15; margin-top: 1px;">
                            <em>Obs: <?= pdfText($component['observacion']) ?></em>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(($component['estado'] ?? '') === 'Bueno') ?></span></td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(($component['estado'] ?? '') === 'Malo') ?></span></td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(!empty($component['cambiado'])) ?></span></td>
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
                        <strong><?= pdfText($component['nombre'] ?? '') ?></strong>
                        <?php
                        $details = [];
                        if (trim((string)($component['serie'] ?? '')) !== '') {
                            $details[] = 'S: ' . pdfText($component['serie']);
                        }
                        if (trim((string)($component['ip'] ?? '')) !== '') {
                            $details[] = 'IP: ' . pdfText($component['ip']);
                        }
                        if (trim((string)($component['mac'] ?? '')) !== '') {
                            $details[] = 'MAC: ' . pdfText($component['mac']);
                        }
                        if (!empty($details)): ?>
                          <span class="muted" style="display:block; font-size:6.5px; line-height: 1.15; margin-top: 1px;"><?= implode(' | ', $details) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($component['observacion'] ?? '')) !== ''): ?>
                          <span style="display:block; font-size:6.5px; color:#854d0e; line-height: 1.15; margin-top: 1px;">
                            <em>Obs: <?= pdfText($component['observacion']) ?></em>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(($component['estado'] ?? '') === 'Bueno') ?></span></td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(($component['estado'] ?? '') === 'Malo') ?></span></td>
                      <td class="center" style="vertical-align: middle;"><span class="mark"><?= pdfMark(!empty($component['cambiado'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </td>
        </tr>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($registro['tipo'] === 'quality'): ?>
    <div class="section">
      <div class="section-title">II. PRUEBAS POR CARRIL</div>
      <table class="grid">
        <thead>
          <tr>
            <th style="width:20%">Carril</th>
            <th style="width:18%">Lectura exitosa</th>
            <th style="width:22%">Sistema de monitoreo</th>
            <th style="width:40%">Observaciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($datos['carriles'] ?? []) as $index => $lane): ?>
            <?php
            $hasLaneData = trim((string)($lane['nombre'] ?? '')) !== ''
              || trim((string)($lane['lectura'] ?? '')) !== ''
              || trim((string)($lane['monitoreo'] ?? '')) !== ''
              || trim((string)($lane['observacion'] ?? '')) !== '';
            if (!$hasLaneData) continue;
            ?>
            <tr>
              <td><?= pdfText($lane['nombre'] ?? '', 'Carril ' . ($index + 1)) ?></td>
              <td class="center">
                <span class="mark"><?= pdfMark(($lane['lectura'] ?? '') === 'Sí' || ($lane['lectura'] ?? '') === 'Si') ?></span> Sí &nbsp;
                <span class="mark"><?= pdfMark(($lane['lectura'] ?? '') === 'No') ?></span> No
              </td>
              <td class="center">
                <span class="mark"><?= pdfMark(($lane['monitoreo'] ?? '') === 'Sí' || ($lane['monitoreo'] ?? '') === 'Si') ?></span> Sí &nbsp;
                <span class="mark"><?= pdfMark(($lane['monitoreo'] ?? '') === 'No') ?></span> No
              </td>
              <td><?= trim((string)($lane['observacion'] ?? '')) !== '' ? pdfText($lane['observacion']) : '&nbsp;' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="section">
      <div class="section-title">III. RESULTADO GENERAL</div>
      <table class="summary-grid">
        <tr>
          <td>
            <strong>Fuente de energía operando</strong>
            <span class="mark"><?= pdfMark(!empty($datos['energia_luz'])) ?></span> Luz eléctrica (1+1)<br>
            <span class="mark"><?= pdfMark(!empty($datos['energia_solar'])) ?></span> Paneles solares / baterías
          </td>
          <td>
            <strong>Enlace funcionando</strong><br>
            <span class="mark"><?= pdfMark(($datos['enlace'] ?? '') === 'Si' || ($datos['enlace'] ?? '') === 'Sí') ?></span> Sí &nbsp;
            <span class="mark"><?= pdfMark(($datos['enlace'] ?? '') === 'No') ?></span> No
          </td>
          <td>
            <strong>Sistema de monitoreo</strong><br>
            <span class="mark"><?= pdfMark(($datos['sistema_monitoreo'] ?? '') === 'Si' || ($datos['sistema_monitoreo'] ?? '') === 'Sí') ?></span> Sí &nbsp;
            <span class="mark"><?= pdfMark(($datos['sistema_monitoreo'] ?? '') === 'No') ?></span> No
          </td>
          <td>
            <strong>Resultado de la prueba</strong><br>
            <span class="mark"><?= pdfMark(($datos['resultado'] ?? '') === 'Exitosa') ?></span> Exitosa &nbsp;
            <span class="mark"><?= pdfMark(($datos['resultado'] ?? '') === 'Fallida') ?></span> Fallida
          </td>
        </tr>
      </table>
      <?php if (!empty(trim((string)($datos['acciones_correctivas'] ?? '')))): ?>
        <div class="observation-box" style="margin-top: 5px; padding: 4px 6px; font-size: 7.5px; border-left: 3px solid #003865; background: #f8fafc; min-height: 20px;">
          <strong style="color: #003865; font-size: 7px; text-transform: uppercase; margin-bottom: 2px;">Acciones correctivas realizadas:</strong><br>
          <?= pdfText($datos['acciones_correctivas']) ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($registro['tipo'] === 'tools'): ?>
    <?php
    $toolGroups = [
      'II. HERRAMIENTAS' => $datos['herramientas'] ?? [],
      'III. CONSUMIBLES Y REFACCIONES' => $datos['consumibles'] ?? [],
      'IV. EQUIPO DE PROTECCIÓN PERSONAL' => $datos['epp'] ?? [],
    ];
    ?>
    <?php foreach ($toolGroups as $groupTitle => $items): ?>
      <div class="section">
        <div class="section-title"><?= $groupTitle ?></div>
        <table class="item-columns">
          <?php if (!$items): ?>
            <tr><td colspan="2" class="center muted">Sin elementos seleccionados</td></tr>
          <?php else: ?>
            <?php for ($index = 0; $index < count($items); $index += 2): ?>
              <tr>
                <td><span class="check">[X]</span> <?= pdfToolItem($items[$index]) ?></td>
                <td>
                  <?php if (isset($items[$index + 1])): ?>
                    <span class="check">[X]</span> <?= pdfToolItem($items[$index + 1]) ?>
                  <?php else: ?>&nbsp;<?php endif; ?>
                </td>
              </tr>
            <?php endfor; ?>
          <?php endif; ?>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="signature">
    <div class="signature-space"></div>
    <strong><?= pdfText($registro['tecnico_responsable']) ?></strong>
    <span>NOMBRE Y FIRMA DEL TÉCNICO RESPONSABLE</span>
  </div>

</body>
</html>
