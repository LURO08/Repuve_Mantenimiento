<?php
include('../views/header.php');
require_once('../controllers/reportes_materiales_controller.php');

function fechaReporte($fecha)
{
  return $fecha ? date("d-m-Y", strtotime($fecha)) : 'N/A';
}

function estadoArcoReporte($r)
{
  if ((float)$r['total_piezas'] <= 0) {
    return ['Sin material', 'bg-danger'];
  }
  if (!empty($r['proximo_mantenimiento']) && strtotime($r['proximo_mantenimiento']) < strtotime(date('Y-m-d'))) {
    return ['Requiere mantenimiento', 'bg-danger'];
  }
  if (empty($r['ultima_mantenimiento'])) {
    return ['Sin mantenimiento', 'bg-warning text-dark'];
  }
  if ((int)$r['correctivos'] >= 2) {
    return ['Revisar', 'bg-warning text-dark'];
  }
  if ((int)$r['preventivos'] > 0) {
    return ['Con preventivo', 'bg-success'];
  }
  return ['Estable', 'bg-success'];
}

function nivelMaterialReporte($m)
{
  if ((int)$m['total_usos'] >= 5 || (int)$m['arcos_afectados'] >= 3) {
    return ['Critico', 'bg-danger'];
  }
  if ((int)$m['total_usos'] >= 2) {
    return ['Atencion', 'bg-warning text-dark'];
  }
  if ((float)$m['total_instalado'] <= 0) {
    return ['Sin instalar', 'bg-secondary'];
  }
  return ['Normal', 'bg-success'];
}
?>

<link rel="stylesheet" href="../css/reportes.css?v=<?= filemtime(__DIR__ . '/../css/reportes.css') ?>">
<script>
  window.reporteMantenimientos = {
    preventivos: <?= json_encode($mantenimientosPreventivos, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>,
    correctivos: <?= json_encode($mantenimientosCorrectivos, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>,
    todos: <?= json_encode($mantenimientosReporte, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>
  };
  window.reporteCriticosMantenimiento = <?= json_encode($arcosCriticosMantenimiento, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>;
  window.reporteTotalArcos = <?= (int)$kpis['total_arcos'] ?>;
</script>

<div class="container-fluid px-3 px-lg-4 py-3 reportes-page">
  <div class="text-center reportes-heading">
    <h1 class="fw-bold text-dark">Reportes operativos</h1>
    <hr class="mx-auto">
  </div>

  <div class="report-kpi-grid mb-3">
    <button type="button" class="report-kpi-card kpi-danger report-kpi-button btnReporteCriticos" data-bs-toggle="modal" data-bs-target="#modalCriticosReporte">
      <span>Criticos</span>
      <strong><?= count($arcosCriticosMantenimiento) ?></strong>
      <small>Arcos que ya requieren mantenimiento</small>
    </button>
    <div class="report-kpi-card">
      <span>Arcos registrados</span>
      <strong><?= (int)$kpis['total_arcos'] ?></strong>
      <small>Inventario activo de arcos</small>
    </div>
    <button type="button" class="report-kpi-card kpi-success report-kpi-button btnReporteMantenimientos" data-tipo="preventivos" data-periodo="actual" data-bs-toggle="modal" data-bs-target="#modalMantenimientosReporte">
      <span>Preventivos bimestre</span>
      <strong><?= (int)$kpis['preventivos_bimestre'] ?> <small class="kpi-percent"><?= number_format((float)$kpis['porcentaje_preventivos_bimestre'], 1) ?>%</small></strong>
      <small><?= (int)$kpis['arcos_preventivos_bimestre'] ?> arco(s). Formula: mantenimientos preventivos / arcos registrados</small>
    </button>
    <button type="button" class="report-kpi-card kpi-warning report-kpi-button btnReporteMantenimientos" data-tipo="correctivos" data-periodo="actual" data-bs-toggle="modal" data-bs-target="#modalMantenimientosReporte">
      <span>Correctivos bimestre</span>
      <strong><?= (int)$kpis['correctivos_bimestre'] ?> <small class="kpi-percent"><?= number_format((float)$kpis['porcentaje_correctivos_bimestre'], 1) ?>%</small></strong>
      <small><?= (int)$kpis['arcos_correctivos_bimestre'] ?> arco(s). Formula: mantenimientos correctivos / arcos registrados</small>
    </button>
  </div>

  <div class="card shadow-sm report-card report-chart-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0 fw-bold">Mantenimientos por bimestre</h5>
        <small class="text-muted">Correctivos y preventivos del año actual en una sola grafica.</small>
      </div>
      <div class="report-chart-legend">
        <span><i class="legend-dot legend-correctivo"></i> Correctivos</span>
        <span><i class="legend-dot legend-preventivo"></i> Preventivos</span>
      </div>
    </div>
    <div class="card-body">
      <div id="graficaMantenimientosReporte" class="report-maintenance-chart"></div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="card shadow-sm report-card h-100">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-0 fw-bold">Material por arco</h5>
            <small class="text-muted">Conteo de piezas, componentes, series y mantenimiento por arco.</small>
          </div>
          <div class="input-group report-search">
            <span class="input-group-text bg-success text-white"><i class="bi bi-search"></i></span>
            <input type="search" id="searchArcosMaterial" class="form-control" placeholder="Buscar arco..."
              onkeyup="filterTable('searchArcosMaterial', 'arcosMaterialTable')">
          </div>
        </div>
        <div class="table-responsive report-table-scroll">
          <table id="arcosMaterialTable" class="table table-striped align-middle mb-0 report-material-table">
            <colgroup>
              <col class="report-col-arco">
              <col class="report-col-inventario">
              <col class="report-col-cambios">
              <col class="report-col-mantenimiento">
              <col class="report-col-estado">
            </colgroup>
            <thead class="table-dark text-center">
              <tr>
                <th>Arco</th>
                <th>Inventario</th>
                <th>Cambios</th>
                <th>Mantenimiento</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php foreach ($reporteArcosMaterial as $r): ?>
                <?php [$estadoTexto, $estadoClase] = estadoArcoReporte($r); ?>
                <tr>
                  <td class="text-start">
                    <div class="fw-semibold"><?= htmlspecialchars($r['arco']) ?></div>
                    <small class="text-muted"><i class="bi bi-geo-alt-fill text-primary me-1"></i><?= htmlspecialchars($r['ubicacion'] ?? 'Sin ubicacion') ?></small>
                  </td>
                  <td>
                    <div class="report-table-stack">
                      <strong><?= number_format((float)$r['total_piezas'], 0) ?> pz</strong>
                      <small><?= (int)$r['componentes_distintos'] ?> comp. / <?= (int)$r['series_registradas'] ?> series</small>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?= (int)$r['total_cambios'] > 0 ? 'bg-info text-dark' : 'bg-secondary' ?>">
                      <?= (int)$r['total_cambios'] ?>
                    </span>
                  </td>
                  <td>
                    <div class="report-table-stack text-start">
                      <small><strong>Ult.:</strong> <?= fechaReporte($r['ultima_mantenimiento']) ?></small>
                      <small><strong>Prox.:</strong> <?= fechaReporte($r['proximo_mantenimiento']) ?></small>
                      <small><strong>Prev.:</strong> <?= fechaReporte($r['ultimo_preventivo']) ?></small>
                    </div>
                  </td>
                  <td><span class="badge <?= $estadoClase ?>"><?= $estadoTexto ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div id="pagination-arcosMaterial" class="d-flex justify-content-center mt-2"></div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm report-card h-100">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">Arcos por ubicacion</h5>
          <small class="text-muted">Ubicaciones con arcos registrados.</small>
        </div>
        <div class="card-body">
          <div class="report-location-summary">
            <div>
              <span>Ubicaciones</span>
              <strong><?= number_format((int)($totalUbicacionesConArcos ?? count($topUbicaciones)), 0) ?></strong>
            </div>
            <div>
              <span>Total arcos</span>
              <strong><?= number_format((int)($totalArcosTopUbicaciones ?? 0), 0) ?></strong>
            </div>
          </div>
          <div id="ubicacionesTable" class="report-location-grid">
            <?php foreach ($topUbicaciones as $idx => $u): ?>
              <article class="report-location-card report-page-item">
                <div class="report-location-card-top">
                  <span class="report-location-rank"><?= $idx + 1 ?></span>
                  <i class="bi bi-geo-alt-fill"></i>
                </div>
                <strong><?= htmlspecialchars($u['ubicacion']) ?></strong>
                <div class="report-location-count">
                  <span><?= number_format((int)$u['arcos'], 0) ?></span>
                  <small>arco(s)</small>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <div id="pagination-ubicaciones" class="d-flex justify-content-center mt-2"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm report-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0 fw-bold">Componentes criticos</h5>
        <small class="text-muted">Material instalado contra material cambiado en mantenimientos.</small>
      </div>
      <div class="input-group report-search">
        <span class="input-group-text bg-success text-white"><i class="bi bi-search"></i></span>
        <input type="search" id="searchReportes" class="form-control" placeholder="Buscar componente..."
          onkeyup="filterTable('searchReportes', 'reportesTable')">
      </div>
    </div>
    <div id="reportesTable" class="report-critical-card-grid report-card-scroll">
      <?php foreach ($materiales as $m): ?>
        <?php [$nivelTexto, $nivelClase] = nivelMaterialReporte($m); ?>
        <?php
          $instalado = (float)$m['total_instalado'];
          $cambiado = (float)$m['piezas_cambiadas'];
          $porcentajeCambio = $instalado > 0 ? min(100, round(($cambiado / $instalado) * 100)) : ($cambiado > 0 ? 100 : 0);
          $foto = trim((string)($m['foto'] ?? ''));
          $tieneFoto = $foto !== '' && strtolower($foto) !== 'null';
          $nivelVisual = $nivelClase === 'bg-danger' ? 'is-danger' : ($nivelClase === 'bg-warning text-dark' ? 'is-warning' : 'is-ok');
        ?>
        <article class="report-critical-card report-page-item <?= $nivelVisual ?>">
          <div class="report-critical-photo">
            <?php if ($tieneFoto): ?>
              <img
                src="../uploads/materiales/<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($m['componente'], ENT_QUOTES, 'UTF-8') ?>"
                loading="lazy"
                onerror="this.closest('.report-critical-photo').classList.add('is-empty'); this.remove();">
            <?php else: ?>
              <i class="bi bi-image"></i>
              <span>Sin foto</span>
            <?php endif; ?>
          </div>

          <div class="report-critical-card-body">
            <div class="report-critical-card-head">
              <div>
                <h6><?= htmlspecialchars($m['componente']) ?></h6>
                <small><?= htmlspecialchars($m['medida'] ?: 'pieza') ?></small>
              </div>
              <span class="badge <?= $nivelClase ?>"><?= $nivelTexto ?></span>
            </div>

            <div class="report-critical-card-stats">
              <div>
                <span>Instalado</span>
                <strong><?= number_format($instalado, 0) ?> <?= htmlspecialchars($m['medida'] ?? '') ?></strong>
                <small><?= (int)$m['arcos_instalado'] ?> arco(s)</small>
              </div>
              <div>
                <span>Cambiado</span>
                <strong><?= number_format($cambiado, 0) ?> <?= htmlspecialchars($m['medida'] ?? '') ?></strong>
                <small><?= (int)$m['total_usos'] ?> vez/veces</small>
              </div>
            </div>

            <div class="report-critical-impact">
              <div>
                <span><?= (int)$m['arcos_afectados'] ?> arco(s) afectados · Ultimo: <?= fechaReporte($m['ultima']) ?></span>
                <strong><?= $porcentajeCambio ?>%</strong>
              </div>
              <div class="report-mini-progress" title="<?= $porcentajeCambio ?>% contra instalado">
                <span style="width: <?= $porcentajeCambio ?>%"></span>
              </div>
            </div>

            <button
              type="button"
              class="btn btn-outline-info btn-sm btnVerArcos report-detail-btn"
              data-id="<?= (int)$m['material_id'] ?>"
              data-nombre="<?= htmlspecialchars($m['componente'], ENT_QUOTES, 'UTF-8') ?>"
              data-bs-toggle="modal"
              data-bs-target="#modalArcos">
              <i class="bi bi-eye me-1"></i> Detalle
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div id="pagination-reportes" class="d-flex justify-content-center mt-2"></div>
  </div>
</div>

<div class="modal fade" id="modalCriticosReporte" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Arcos que requieren mantenimiento
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="contenidoCriticosReporte"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalMantenimientosReporte" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-clipboard-check me-2"></i>
          <span id="modalMantenimientosTitulo">Mantenimientos</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="contenidoMantenimientosReporte"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalArcos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-cpu me-2"></i>
          <span id="modalMaterialNombre"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="contenidoArcos"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/reporte.js?v=<?= filemtime(__DIR__ . '/../js/reporte.js') ?>"></script>

<?php include('../views/footer.php'); ?>

