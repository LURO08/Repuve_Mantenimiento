<?php
require "controllers/login_controller.php";
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Repuve - Login</title>
  <link href="./assets/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f1f3f5;
      height: 100vh;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center; /* 🔥 Centrado vertical real */
    }

    .login-container {
      width: 100%;
      max-width: 420px;
    }

    .card {
      border-radius: 1.2rem;
      padding: 20px;
    }

    /* Vista móvil */
    @media (max-width: 768px) {
      .card {
        transform: scale(1.15);
        transform-origin: center;
      }
    }

    .password-wrapper {
      position: relative;
    }

    .toggle-pass {
      position: absolute;
      right: 12px;
      top: 70%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 1.2rem;
      color: #555;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="card shadow-lg">
      <h3 class="text-center mb-4">🔐 Acceso REPUVE</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">

        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input name="user" class="form-control" required>
        </div>

        <div class="mb-3 password-wrapper">
          <label class="form-label">Contraseña</label>
          <input id="passInput" type="password" name="pass" class="form-control" required>
          <span class="toggle-pass" onclick="togglePassword()">👁️</span>
        </div>

        <button class="btn btn-primary w-100">Entrar</button>

      </form>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById("passInput");
      input.type = input.type === "password" ? "text" : "password";
    }
  </script>

</body>
</html>
