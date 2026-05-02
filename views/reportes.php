<?php
include('../views/header.php');
require_once('../controllers/reportes_materiales_controller.php');
?>


<link rel="stylesheet" href="../css/reportes.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<div class="container py-4">

  <div class="text-center my-3">
    <h1 class="fw-bold">📦 Reporte de Componentes Dañados</h1>
    <hr class="mx-auto" style="width:60%; border-top:3px solid #28a745;">
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <table id="reportesTable" class="table table-striped table-hover align-middle">
        <thead class="table-dark text-center">
  <tr>
    <th>#</th>
    <th>Componente</th>
    <th>Frecuencia de Daño</th>
    <th>Nivel</th>
    <th>Detalle</th>
  </tr>
</thead>

<tbody>
<?php $i = 1; foreach ($materiales as $m): ?>
<tr>
  <td class="text-center fw-bold"><?= $i++ ?></td>

  <td class="fw-semibold text-center">
    <?= htmlspecialchars($m['componente']) ?>
  </td>

  <td class="text-center fw-bold fs-5">
    <?= $m['total_usos'] ?>
  </td>

  <td class="text-center">
    <?php if ($m['total_usos'] >= 10): ?>
      <span class="badge bg-danger">Alto</span>
    <?php elseif ($m['total_usos'] >= 5): ?>
      <span class="badge bg-warning text-dark">Medio</span>
    <?php else: ?>
      <span class="badge bg-success">Bajo</span>
    <?php endif; ?>
  </td>

  <td class="text-center">
    <button
      class="btn btn-outline-info btn-sm btnVerArcos"
      data-id="<?= $m['material_id'] ?>"
      data-nombre="<?= htmlspecialchars($m['componente']) ?>"
      data-bs-toggle="modal"
      data-bs-target="#modalArcos">
      📊 Ver detalle
    </button>
  </td>
</tr>
<?php endforeach; ?>
</tbody>


      </table>

      <div id="pagination-reportes" class="d-flex justify-content-center mt-3"></div>

    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalArcos" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          Arcos afectados: <span id="modalMaterialNombre"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

      <div class="mb-3 " >

        <div id="izquierda"3>
          <div id="contenidoArcos"></div>
        </div>

        <!-- <div id="derecha" class="col-md-6">
          <div class="row mb-4">
            <div class="col-md-6">
              <canvas id="graficoArcos"></canvas>
            </div>
            <div class="col-md-6">
              <canvas id="graficoMotivos"></canvas>
            </div>
          </div> -->

        </div>
      </div>
 

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../js/reporte.js"></script>

<?php include('../views/footer.php'); ?>
