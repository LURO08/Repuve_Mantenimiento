<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['material_id'])) {
    http_response_code(400);
    exit('Material no valido');
}

$material_id = (int) $_GET['material_id'];

$stmt = $pdo->prepare("SELECT nombre, medida, foto FROM materiales WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    http_response_code(404);
    exit('Material no encontrado');
}

$sql = "
WITH revision_timeline AS (
    SELECT 
        rm.id AS rm_id,
        rm.revision_id,
        rm.arco_material_id,
        rm.material_id AS material_colocado_id,
        rm.cantidad,
        rm.serie AS nueva_serie,
        rm.accion,
        r.arco_id,
        r.fecha_mantenimiento,
        r.tipo_mantenimiento,
        r.observaciones,
        r.tecnico_id,
        am.material_id AS base_material_id,
        COALESCE(
            LAG(rm.material_id) OVER (
                PARTITION BY rm.arco_material_id 
                ORDER BY r.fecha_mantenimiento ASC, rm.id ASC
            ),
            am.material_id,
            rm.material_id
        ) AS material_retirado_id
    FROM revision_material rm
    JOIN revisiones r ON r.id = rm.revision_id
    LEFT JOIN arco_material am ON am.id = rm.arco_material_id
    JOIN arcos a ON a.id = r.arco_id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
      AND COALESCE(rm.accion, 'cambio') <> 'retiro'
)
SELECT
    a.id,
    a.nombre AS arco,
    COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
    COALESCE(inst.piezas_instaladas, 0) AS piezas_instaladas,
    COALESCE(inst.series_instaladas, 0) AS series_instaladas,
    COALESCE(corr.piezas_falla, 0) AS piezas_falla,
    COALESCE(corr.veces_falla, 0) AS veces_falla,
    COALESCE(prev.piezas_prev, 0) AS piezas_prev,
    COALESCE(prev.veces_prev, 0) AS veces_prev,
    COALESCE(nuevos.piezas_nuevo, 0) AS piezas_nuevo,
    COALESCE(nuevos.veces_nuevo, 0) AS veces_nuevo,
    tot.ultima_fecha
FROM arcos a
LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
LEFT JOIN (
    SELECT
        arco_id,
        SUM(cantidad) AS piezas_instaladas,
        SUM(CASE WHEN serie IS NOT NULL AND TRIM(serie) <> '' THEN 1 ELSE 0 END) AS series_instaladas
    FROM arco_material
    WHERE material_id = ?
    GROUP BY arco_id
) inst ON inst.arco_id = a.id
LEFT JOIN (
    SELECT
        arco_id,
        SUM(cantidad) AS piezas_falla,
        COUNT(rm_id) AS veces_falla
    FROM revision_timeline
    WHERE material_retirado_id = ?
      AND tipo_mantenimiento = 'Correctivo'
    GROUP BY arco_id
) corr ON corr.arco_id = a.id
LEFT JOIN (
    SELECT
        arco_id,
        SUM(cantidad) AS piezas_prev,
        COUNT(rm_id) AS veces_prev
    FROM revision_timeline
    WHERE material_retirado_id = ?
      AND tipo_mantenimiento = 'Preventivo'
    GROUP BY arco_id
) prev ON prev.arco_id = a.id
LEFT JOIN (
    SELECT
        arco_id,
        SUM(cantidad) AS piezas_nuevo,
        COUNT(rm_id) AS veces_nuevo
    FROM revision_timeline
    WHERE material_colocado_id = ?
    GROUP BY arco_id
) nuevos ON nuevos.arco_id = a.id
LEFT JOIN (
    SELECT
        arco_id,
        MAX(fecha_mantenimiento) AS ultima_fecha,
        COUNT(rm_id) AS total_intervenciones
    FROM revision_timeline
    WHERE material_retirado_id = ? OR material_colocado_id = ?
    GROUP BY arco_id
) tot ON tot.arco_id = a.id
WHERE (COALESCE(inst.piezas_instaladas, 0) > 0 OR COALESCE(tot.total_intervenciones, 0) > 0)
  AND COALESCE(a.estado, 'Activo') <> 'Baja'
ORDER BY
    piezas_falla DESC,
    veces_falla DESC,
    piezas_prev DESC,
    piezas_nuevo DESC,
    piezas_instaladas DESC,
    a.nombre ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$material_id, $material_id, $material_id, $material_id, $material_id, $material_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$registros) {
    echo "<div class='report-empty-detail py-4 text-center text-muted'><i class='bi bi-info-circle me-1'></i> No hay registros ni arcos asociados para este componente.</div>";
    exit;
}

// Historial detallado por arco
$histSql = "
WITH revision_timeline AS (
    SELECT 
        rm.id AS rm_id,
        rm.revision_id,
        rm.arco_material_id,
        rm.material_id AS material_colocado_id,
        rm.cantidad,
        rm.serie AS nueva_serie,
        rm.accion,
        r.arco_id,
        r.fecha_mantenimiento,
        r.tipo_mantenimiento,
        r.observaciones,
        r.tecnico_id,
        am.material_id AS base_material_id,
        COALESCE(
            LAG(rm.material_id) OVER (
                PARTITION BY rm.arco_material_id 
                ORDER BY r.fecha_mantenimiento ASC, rm.id ASC
            ),
            am.material_id,
            rm.material_id
        ) AS material_retirado_id
    FROM revision_material rm
    JOIN revisiones r ON r.id = rm.revision_id
    LEFT JOIN arco_material am ON am.id = rm.arco_material_id
    JOIN arcos a ON a.id = r.arco_id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
      AND COALESCE(rm.accion, 'cambio') <> 'retiro'
)
SELECT 
    t.arco_id,
    t.revision_id,
    t.fecha_mantenimiento,
    t.tipo_mantenimiento,
    tec.nombre AS tecnico_nombre,
    t.rm_id,
    t.cantidad,
    t.nueva_serie,
    t.material_colocado_id,
    m_colocado.nombre AS material_colocado_nombre,
    t.material_retirado_id,
    m_retirado.nombre AS material_retirado_nombre,
    t.observaciones AS rev_observaciones,
    CASE
        WHEN t.material_retirado_id = ? AND t.material_colocado_id <> ? THEN 'REEMPLAZADO'
        WHEN t.material_colocado_id = ? AND (t.material_retirado_id IS NULL OR t.material_retirado_id <> ?) THEN 'COLOCADO_NUEVO'
        WHEN t.material_retirado_id = ? AND t.tipo_mantenimiento = 'Correctivo' THEN 'FALLA_CAMBIO'
        ELSE 'INTERVENCION'
    END AS rol_intervencion
FROM revision_timeline t
LEFT JOIN tecnicos tec ON tec.id = t.tecnico_id
LEFT JOIN materiales m_colocado ON m_colocado.id = t.material_colocado_id
LEFT JOIN materiales m_retirado ON m_retirado.id = t.material_retirado_id
WHERE t.material_retirado_id = ? OR t.material_colocado_id = ?
ORDER BY t.fecha_mantenimiento DESC, t.rm_id DESC
";

$histStmt = $pdo->prepare($histSql);
$histStmt->execute([$material_id, $material_id, $material_id, $material_id, $material_id, $material_id, $material_id]);
$historialRaw = $histStmt->fetchAll(PDO::FETCH_ASSOC);

$historialPorArco = [];
foreach ($historialRaw as $h) {
    $historialPorArco[$h['arco_id']][] = $h;
}

$totalInstalado = array_sum(array_map(fn($r) => (float)$r['piezas_instaladas'], $registros));
$totalFalla = array_sum(array_map(fn($r) => (float)$r['piezas_falla'], $registros));
$totalPrev = array_sum(array_map(fn($r) => (float)$r['piezas_prev'], $registros));
$totalNuevo = array_sum(array_map(fn($r) => (float)$r['piezas_nuevo'], $registros));
$arcosInstalado = count(array_filter($registros, fn($r) => (float)$r['piezas_instaladas'] > 0));
$arcosConFallas = count(array_filter($registros, fn($r) => (float)$r['piezas_falla'] > 0));
$arcosConPrev = count(array_filter($registros, fn($r) => (float)$r['piezas_prev'] > 0));
$arcosConNuevo = count(array_filter($registros, fn($r) => (float)$r['piezas_nuevo'] > 0));
$unidad = htmlspecialchars($material['medida'] ?: 'pz');
?>

<div class="report-detail-summary-v2">
  <div class="summary-box">
    <span>Total Instalado</span>
    <strong><?= number_format($totalInstalado, 0) ?> <small><?= $unidad ?></small></strong>
    <small><?= $arcosInstalado ?> arco(s) en servicio</small>
  </div>
  <div class="summary-box summary-danger">
    <span>Fallas / Daños (Correctivos)</span>
    <strong><?= number_format($totalFalla, 0) ?> <small><?= $unidad ?></small></strong>
    <small><?= $arcosConFallas ?> arco(s) con fallas</small>
  </div>
  <div class="summary-box summary-info">
    <span>Renovados (Preventivos)</span>
    <strong><?= number_format($totalPrev, 0) ?> <small><?= $unidad ?></small></strong>
    <small><?= $arcosConPrev ?> arco(s) renovados</small>
  </div>
  <?php if ($totalNuevo > 0): ?>
    <div class="summary-box">
      <span>Colocados Nuevos</span>
      <strong><?= number_format($totalNuevo, 0) ?> <small><?= $unidad ?></small></strong>
      <small><?= $arcosConNuevo ?> arco(s) receptores</small>
    </div>
  <?php endif; ?>
  <div class="summary-box">
    <span>Total Arcos Relacionados</span>
    <strong><?= count($registros) ?></strong>
    <small>Arcos con registro</small>
  </div>
</div>

<div class="report-component-detail-grid mt-3">
  <?php foreach ($registros as $r): ?>
    <?php
      $instalado = (float)$r['piezas_instaladas'];
      $piezasFalla = (float)$r['piezas_falla'];
      $vecesFalla = (int)$r['veces_falla'];
      $piezasPrev = (float)$r['piezas_prev'];
      $vecesPrev = (int)$r['veces_prev'];
      $piezasNuevo = (float)$r['piezas_nuevo'];
      $vecesNuevo = (int)$r['veces_nuevo'];

      if ($piezasFalla > 0) {
        $estadoClase = 'is-danger';
        $badgeClase = 'bg-danger';
        $badgeTexto = 'Falla en Correctivo (' . $vecesFalla . ')';
      } elseif ($piezasPrev > 0) {
        $estadoClase = 'is-info';
        $badgeClase = 'bg-info text-dark';
        $badgeTexto = 'Renovación / Preventivo (' . $vecesPrev . ')';
      } elseif ($piezasNuevo > 0 && $instalado == 0) {
        $estadoClase = 'is-info';
        $badgeClase = 'bg-primary';
        $badgeTexto = 'Nuevo Colocado (' . $vecesNuevo . ')';
      } else {
        $estadoClase = 'is-ok';
        $badgeClase = 'bg-success';
        $badgeTexto = 'Operativo Estable';
      }

      $arcosHistorial = $historialPorArco[$r['id']] ?? [];
    ?>
    <article class="report-component-detail-card <?= $estadoClase ?>">
      <div class="report-component-detail-head">
        <div>
          <h6><?= htmlspecialchars($r['arco']) ?></h6>
          <small><i class="bi bi-geo-alt-fill text-primary"></i> <?= htmlspecialchars($r['ubicacion']) ?></small>
        </div>
        <span class="badge <?= $badgeClase ?>"><?= $badgeTexto ?></span>
      </div>

      <div class="report-component-metrics-v2">
        <div class="metric-box">
          <span>Instalado</span>
          <strong><?= number_format($instalado, 0) ?> <?= $unidad ?></strong>
          <small><?= (int)$r['series_instaladas'] ?> serie(s)</small>
        </div>
        <div class="metric-box <?= $piezasFalla > 0 ? 'metric-danger' : '' ?>">
          <span>Fallas Correctivos</span>
          <strong><?= number_format($piezasFalla, 0) ?> <?= $unidad ?></strong>
          <small><?= $vecesFalla ?> cambio(s)</small>
        </div>
        <div class="metric-box <?= $piezasPrev > 0 ? 'metric-info' : '' ?>">
          <span>Nuevos Preventivos</span>
          <strong><?= number_format($piezasPrev, 0) ?> <?= $unidad ?></strong>
          <small><?= $vecesPrev ?> cambio(s)</small>
        </div>
      </div>

      <?php if (!empty($r['ultima_fecha'])): ?>
        <div class="report-component-date">
          <i class="bi bi-calendar-event me-1 text-primary"></i>
          Último movimiento registrado: <strong><?= date("d-m-Y", strtotime($r['ultima_fecha'])) ?></strong>
        </div>
      <?php endif; ?>

      <?php if (!empty($arcosHistorial)): ?>
        <div class="report-component-history">
          <div class="history-title"><i class="bi bi-journal-text me-1"></i> Historial de Intervenciones:</div>
          <?php foreach ($arcosHistorial as $h): ?>
            <?php
              $esCorrectivo = ($h['tipo_mantenimiento'] ?? '') === 'Correctivo';
              $rol = $h['rol_intervencion'];
              $badgeRol = 'bg-secondary';
              $textoRol = 'Intervención';
              if ($rol === 'REEMPLAZADO') {
                  $badgeRol = 'bg-danger';
                  $textoRol = 'Retirado / Reemplazado';
              } elseif ($rol === 'COLOCADO_NUEVO') {
                  $badgeRol = 'bg-info text-dark';
                  $textoRol = 'Instalado como nuevo';
              } elseif ($rol === 'FALLA_CAMBIO') {
                  $badgeRol = 'bg-danger';
                  $textoRol = 'Falla / Dañado';
              }
            ?>
            <div class="history-item <?= $esCorrectivo ? 'is-correctivo' : 'is-preventivo' ?>">
              <div class="history-item-header">
                <span class="badge <?= $esCorrectivo ? 'bg-danger' : 'bg-success' ?>"><?= htmlspecialchars($h['tipo_mantenimiento'] ?? 'Mantenimiento') ?></span>
                <span class="badge <?= $badgeRol ?>"><?= $textoRol ?></span>
                <span class="history-date"><i class="bi bi-calendar3 me-1"></i><?= date('d-m-Y', strtotime($h['fecha_mantenimiento'])) ?></span>
                <span class="history-cant"><i class="bi bi-box-seam me-1"></i><?= htmlspecialchars((float)$h['cantidad']) ?> <?= $unidad ?></span>
                <?php if (!empty($h['nueva_serie'])): ?>
                  <span class="history-serie"><i class="bi bi-upc-scan me-1"></i>Serie: <?= htmlspecialchars($h['nueva_serie']) ?></span>
                <?php endif; ?>
              </div>

              <?php if (!empty($h['material_colocado_nombre']) && $h['material_colocado_nombre'] !== $material['nombre']): ?>
                <div class="history-replacement-note text-muted small mt-1">
                  <i class="bi bi-arrow-right-circle text-warning me-1"></i>
                  Sustituido por: <strong><?= htmlspecialchars($h['material_colocado_nombre']) ?></strong>
                </div>
              <?php elseif (!empty($h['material_retirado_nombre']) && $h['material_retirado_nombre'] !== $material['nombre']): ?>
                <div class="history-replacement-note text-muted small mt-1">
                  <i class="bi bi-arrow-left-circle text-info me-1"></i>
                  Reemplazó a: <strong><?= htmlspecialchars($h['material_retirado_nombre']) ?></strong>
                </div>
              <?php endif; ?>

              <div class="history-obs mt-1">
                <?= nl2br(htmlspecialchars($h['rev_observaciones'] ?? 'Sin observaciones')) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
