<?php include('../views/header.php'); ?>

<!-- ======================= CONTENIDO PRINCIPAL ======================= -->
<div class="card shadow-sm border-0 p-4">
  <div class="card-body">

    <h4 class="fw-bold text-success">
      <i class="bi bi-house-door me-2"></i>FORMATOS REPUVE
    </h4>

    <p class="text-muted mb-4">
      Desde aquí puedes administrar todos los módulos del sistema:
      materiales, ubicaciones, arcos y registrar revisiones o mantenimientos con sus respectivas fotos.
    </p>

    <div class="row g-4">

      <!-- Dashboard -->
      <div class="col-md-3 col-sm-6">
        <a href="reportes.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-speedometer2 text-danger fs-2 mb-2"></i>
            <h6 class="fw-semibold">Bitacora</h6>
          </div>
        </a>
      </div>

      <td>
    <a href="../views/pdf/bitacora_arco.php?id=2>"
       target="_blank"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-file-earmark-text"></i> Bitácora
    </a>
</td>



      <!-- Materiales y Ubicaciones -->
      <div class="col-md-3 col-sm-6">
        <a href="materiales_ubicaciones.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-geo-alt text-success fs-2 mb-2"></i>
            <h6 class="fw-semibold">Formatos de prueba de calidad</h6>
          </div>
        </a>
      </div>

      <!-- Arcos -->
      <div class="col-md-3 col-sm-6">
        <a href="arcos.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-diagram-3 text-primary fs-2 mb-2"></i>
            <h6 class="fw-semibold">Check list de Diagnostico Incial</h6>
          </div>
        </a>
      </div>

    </div>
  </div>
</div>
