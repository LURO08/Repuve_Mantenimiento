<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user'])) {
  header('Location: ../index.php');
  exit;
}
$currentUser = $_SESSION['user'] ?? '';
$currentRole = $_SESSION['role'] ?? '';
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
      text-decoration: none;
      transition: background-color .2s ease;
    }
    .user-badge:hover {
      background: rgba(255,255,255,0.2);
      color: white;
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
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid px-3 px-md-4">
      <a class="navbar-brand" href="/views/dashboard.php">
        <i class="bi bi-broadcast-pin"></i> REPUVE
      </a>

      <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarMenu">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 py-2 py-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Inicio</a>
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
            <a class="nav-link" href="formatos.php"><i class="bi bi-file-earmark-text"></i> Formatos</a>
          </li>
          <?php if ($currentRole === 'admin'): ?>
          <li class="nav-item">
            <a href="usuarios.php" class="nav-link"><i class="bi bi-person-circle"></i> Usuarios / Técnicos</a>
          </li>
          <?php endif; ?>
        </ul>

        <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0 pt-2 pt-lg-0 border-top border-secondary border-opacity-50 border-lg-0">
          <a class="user-badge text-white" href="perfil.php" title="Abrir mi cuenta">
            <i class="bi bi-person-circle"></i>
            <span><?= htmlspecialchars($currentUser) ?></span>
          </a>
          <a class="btn btn-sm logout-btn" href="/views/logout.php">
            <i class="bi bi-box-arrow-right"></i> Salir
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
