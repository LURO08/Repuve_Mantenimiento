<?php include('../views/header.php'); ?>

<!-- ======================= CONTENIDO PRINCIPAL ======================= -->
<div class="card shadow-sm border-0 p-4">
  <div class="card-body">

    <h4 class="fw-bold text-success">
      <i class="bi bi-house-door me-2"></i>Bienvenido al Panel REPUVE
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
            <h6 class="fw-semibold">Reportes</h6>
          </div>
        </a>
      </div>


      <!-- Materiales y Ubicaciones -->
      <div class="col-md-3 col-sm-6">
        <a href="materiales_ubicaciones.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-geo-alt text-success fs-2 mb-2"></i>
            <h6 class="fw-semibold">Materiales & Ciudades</h6>
          </div>
        </a>
      </div>

      <!-- Arcos -->
      <div class="col-md-3 col-sm-6">
        <a href="arcos.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-diagram-3 text-primary fs-2 mb-2"></i>
            <h6 class="fw-semibold">Arcos</h6>
          </div>
        </a>
      </div>

      <!-- Revisiones -->
      <div class="col-md-3 col-sm-6">
        <a href="revisiones.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-tools text-warning fs-2 mb-2"></i>
            <h6 class="fw-semibold">Revisiones</h6>
          </div>
        </a>
      </div>
     <?php if ($_SESSION['role'] === 'admin'): ?>
      <!-- Usuarios (NUEVO) -->
      <div class="col-md-3 col-sm-6">
        <a href="usuarios.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-people text-info fs-2 mb-2"></i>
            <h6 class="fw-semibold">Usuarios</h6>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- Cerrar sesión -->
      <div class="col-md-3 col-sm-6">
        <a href="/views/logout.php" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <i class="bi bi-box-arrow-right text-danger fs-2 mb-2"></i>
            <h6 class="fw-semibold">Cerrar Sesión</h6>
          </div>
        </a>
      </div>

    </div>
  </div>
</div>
