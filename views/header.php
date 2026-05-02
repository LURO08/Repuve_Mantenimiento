<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user'])) header(header: 'Location: ../index.php');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel REPUVE</title>

  <!-- Bootstrap -->
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/bootstrap-icons.css" rel="stylesheet">

  <!-- Estilos generales -->
  <style>
    body {
      background-color: #f8f9fa;
      font-family: "Segoe UI", sans-serif;
    }
    .navbar-brand {
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .navbar-brand i {
      font-size: 1.3rem;
    }
    .navbar {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .user-badge {
      background: rgba(255,255,255,0.1);
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .logout-btn {
      background-color: #dc3545;
      color: white;
      transition: 0.3s;
    }
    .logout-btn:hover {
      background-color: #bb2d3b;
      color: white;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
      <a class="navbar-brand" href="/views/dashboard.php">
        <i class="bi bi-broadcast-pin"></i> REPUVE
      </a>


      <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="dashboard.php"><i class="bi bi-house-door"></i> Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="materiales_ubicaciones.php"><i class="bi bi-geo-alt"></i> Materiales & Ciudades</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="arcos.php"><i class="bi bi-diagram-3"></i> Arcos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="revisiones.php"><i class="bi bi-tools"></i> Mantenimientos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="reportes.php"><i class="bi bi-speedometer2"></i> Reportes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="formatos.php"><i class="bi bi-speedometer2"></i> Formatos</a>
        </li>
        <li class="nav-item">
          <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="usuarios.php" class="nav-link"><i class="bi bi-person-circle"></i>  Usuario</a>
          <?php endif; ?>
        </li>
      </ul>

    </div>

      <div class="d-flex align-items-center">
        <div class="user-badge me-3 text-white">
          <i class="bi bi-person-circle"></i>
          <?= htmlspecialchars($_SESSION['user']) ?>
        </div>
        <a class="btn btn-sm logout-btn" href="/views/logout.php">
          <i class="bi bi-box-arrow-right"></i> Salir
        </a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
