<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

include('../config/db.php');

$stmt = $pdo->prepare('SELECT id, username, role, created_at FROM users WHERE username = ?');
$stmt->execute([$_SESSION['user']]);
$usuarioPerfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuarioPerfil) {
    header('Location: logout.php');
    exit;
}

if (empty($_SESSION['perfil_csrf'])) {
    $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
}

$perfilCssVersion = file_exists(__DIR__ . '/../css/perfil.css') ? filemtime(__DIR__ . '/../css/perfil.css') : time();
$perfilJsVersion = file_exists(__DIR__ . '/../js/perfil.js') ? filemtime(__DIR__ . '/../js/perfil.js') : time();

include('../views/header.php');
?>

<link rel="stylesheet" href="../css/perfil.css?v=<?= $perfilCssVersion ?>">

<?php if (isset($_GET['msg']) || isset($_GET['error'])): ?>
  <div id="perfilNotificacion"
    class="alert <?= isset($_GET['error']) ? 'alert-danger' : 'alert-success' ?> perfil-notificacion">
    <strong><?= isset($_GET['error']) ? 'Error:' : 'Éxito:' ?></strong>
    <?= htmlspecialchars($_GET['error'] ?? $_GET['msg']) ?>
  </div>
<?php endif; ?>

<div class="perfil-page">
  <div class="perfil-heading">
    <div class="perfil-avatar">
      <i class="bi bi-person-circle"></i>
    </div>
    <div>
      <h1>Mi cuenta</h1>
      <p>Actualiza tu usuario y contraseña de acceso.</p>
    </div>
  </div>

  <div class="perfil-layout">
    <aside class="perfil-summary">
      <span class="perfil-summary-label">Usuario actual</span>
      <strong><?= htmlspecialchars($usuarioPerfil['username']) ?></strong>

      <span class="perfil-summary-label">Rol</span>
      <span class="badge <?= $usuarioPerfil['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?>">
        <?= htmlspecialchars($usuarioPerfil['role']) ?>
      </span>

      <?php if (!empty($usuarioPerfil['created_at'])): ?>
        <span class="perfil-summary-label">Cuenta creada</span>
        <span><?= date('d-m-Y', strtotime($usuarioPerfil['created_at'])) ?></span>
      <?php endif; ?>
    </aside>

    <form method="post" action="../controllers/perfil_controller.php" class="perfil-form">
      <input type="hidden" name="csrf_token"
        value="<?= htmlspecialchars($_SESSION['perfil_csrf'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="perfil-section">
        <h2>Datos de usuario</h2>
        <div>
          <label class="form-label fw-semibold" for="perfilUsername">Nombre de usuario</label>
          <input type="text" name="username" id="perfilUsername" class="form-control"
            value="<?= htmlspecialchars($usuarioPerfil['username'], ENT_QUOTES, 'UTF-8') ?>"
            minlength="3" maxlength="100" required>
        </div>
      </div>

      <div class="perfil-section">
        <h2>Seguridad</h2>

        <div>
          <label class="form-label fw-semibold" for="passwordActual">Contraseña actual</label>
          <div class="input-group">
            <input type="password" name="password_actual" id="passwordActual" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary togglePassword" data-target="passwordActual"
              title="Mostrar u ocultar contraseña">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="passwordNueva">Nueva contraseña</label>
            <div class="input-group">
              <input type="password" name="password_nueva" id="passwordNueva" class="form-control" minlength="8">
              <button type="button" class="btn btn-outline-secondary togglePassword" data-target="passwordNueva"
                title="Mostrar u ocultar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="passwordConfirmacion">Confirmar contraseña</label>
            <div class="input-group">
              <input type="password" name="password_confirmacion" id="passwordConfirmacion"
                class="form-control" minlength="8">
              <button type="button" class="btn btn-outline-secondary togglePassword"
                data-target="passwordConfirmacion" title="Mostrar u ocultar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <small class="text-muted">Déjala vacía si solamente quieres cambiar el nombre de usuario.</small>
      </div>

      <div class="perfil-actions">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save"></i> Guardar cambios
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script src="../js/perfil.js?v=<?= $perfilJsVersion ?>"></script>

<?php include('../views/footer.php'); ?>
