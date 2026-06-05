<?php
require_once('../config/db.php');

if (!isset($_GET['material_id'])) {
    http_response_code(400);
    exit('Material no valido');
}

$material_id = (int) $_GET['material_id'];

$stmt = $pdo->prepare("SELECT nombre, medida FROM materiales WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    http_response_code(404);
    exit('Material no encontrado');
}

$sql = "
SELECT
    a.id,
    a.nombre AS arco,
    COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
    COALESCE(inst.piezas_instaladas, 0) AS piezas_instaladas,
    COALESCE(inst.series_instaladas, 0) AS series_instaladas,
    COALESCE(cambios.piezas_cambiadas, 0) AS piezas_cambiadas,
    COALESCE(cambios.veces_cambiado, 0) AS veces_cambiado,
    cambios.ultima_fecha,
    cambios.observaciones
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
        r.arco_id,
        SUM(rm.cantidad) AS piezas_cambiadas,
        COUNT(rm.id) AS veces_cambiado,
        MAX(r.fecha_mantenimiento) AS ultima_fecha,
        STRING_AGG(DISTINCT NULLIF(TRIM(r.observaciones), ''), ' | ') AS observaciones
    FROM revision_material rm
    JOIN revisiones r ON r.id = rm.revision_id
    WHERE rm.material_id = ?
      AND COALESCE(rm.accion, 'cambio') <> 'retiro'
    GROUP BY r.arco_id
) cambios ON cambios.arco_id = a.id
WHERE COALESCE(cambios.veces_cambiado, 0) > 0
ORDER BY piezas_cambiadas DESC, piezas_instaladas DESC, a.nombre ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$material_id, $material_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$registros) {
    echo "<div class='report-empty-detail'>No hay registros para este componente.</div>";
    exit;
}

$totalInstalado = array_sum(array_map(fn($r) => (float)$r['piezas_instaladas'], $registros));
$totalCambiado = array_sum(array_map(fn($r) => (float)$r['piezas_cambiadas'], $registros));
$arcosInstalado = count(array_filter($registros, fn($r) => (float)$r['piezas_instaladas'] > 0));
$arcosAfectados = count(array_filter($registros, fn($r) => (float)$r['piezas_cambiadas'] > 0));
$arcoMasAfectado = $registros[0]['arco'] ?? 'N/A';
$unidad = htmlspecialchars($material['medida'] ?? '');
?>

<div class="report-detail-summary">
  <div>
    <span>Instalado</span>
    <strong><?= number_format($totalInstalado, 0) ?> <?= htmlspecialchars($material['medida'] ?? '') ?></strong>
  </div>
  <div>
    <span>Arcos con material</span>
    <strong><?= $arcosInstalado ?></strong>
  </div>
  <div>
    <span>Cambiado</span>
    <strong><?= number_format($totalCambiado, 0) ?> <?= $unidad ?></strong>
  </div>
  <div>
    <span>Arcos afectados</span>
    <strong><?= $arcosAfectados ?></strong>
  </div>
  <div>
    <span>Mas afectado</span>
    <strong><?= htmlspecialchars($arcoMasAfectado) ?></strong>
  </div>
</div>

<div class="report-component-detail-grid mt-3">
  <?php foreach ($registros as $r): ?>
    <?php
      $instalado = (float)$r['piezas_instaladas'];
      $cambiado = (float)$r['piezas_cambiadas'];
      $veces = (int)$r['veces_cambiado'];
      $porcentaje = $instalado > 0 ? min(100, round(($cambiado / $instalado) * 100)) : ($cambiado > 0 ? 100 : 0);
      $estadoClase = $cambiado > 0 ? 'is-warning' : 'is-ok';
      $estadoTexto = $cambiado > 0 ? 'Con cambios' : 'Sin cambios';
    ?>
    <article class="report-component-detail-card <?= $estadoClase ?>">
      <div class="report-component-detail-head">
        <div>
          <h6><?= htmlspecialchars($r['arco']) ?></h6>
          <small><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($r['ubicacion']) ?></small>
        </div>
        <span class="badge <?= $cambiado > 0 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $estadoTexto ?></span>
      </div>

      <div class="report-component-metrics">
        <div>
          <span>Instalado</span>
          <strong><?= number_format($instalado, 0) ?> <?= $unidad ?></strong>
        </div>
        <div>
          <span>Series</span>
          <strong><?= (int)$r['series_instaladas'] ?></strong>
        </div>
        <div>
          <span>Cambiado</span>
          <strong><?= number_format($cambiado, 0) ?> <?= $unidad ?></strong>
        </div>
        <div>
          <span>Veces</span>
          <strong><?= $veces ?></strong>
        </div>
      </div>

      <div class="report-component-progress">
        <div>
          <span>Relacion cambio/instalado</span>
          <strong><?= $porcentaje ?>%</strong>
        </div>
        <div class="report-mini-progress">
          <span style="width: <?= $porcentaje ?>%"></span>
        </div>
      </div>

      <div class="report-component-date">
        <i class="bi bi-calendar-event"></i>
        Ultimo cambio: <?= $r['ultima_fecha'] ? date("d-m-Y", strtotime($r['ultima_fecha'])) : 'N/A' ?>
      </div>

      <div class="report-component-observations">
        <?= nl2br(htmlspecialchars($r['observaciones'] ?: 'Sin observaciones')) ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>
