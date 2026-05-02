<?php
include('../views/header.php');
include('../config/db.php');

if (!isset($_SESSION["user"])) {
  header("Location: ../index.php");
  exit;
}

// SOLO ADMIN PUEDE ENTRAR A ESTA PÁGINA

if ($_SESSION["role"] !== "admin") {
  header("Location: dashboard.php");
  exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../css/usuarios.css">

<?php if (isset($_GET["msg"])): ?>
    <div id="notifSuccess" class="notification alert alert-success alert-dismissible fade show"
        style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 1050;
        background-color: #4CAF50; color: white; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); padding: 20px; padding-top: 30px; text-align: center;">
        
        <strong>Éxito!</strong> <?= htmlspecialchars($_GET["msg"]) ?>
    </div>

    <script>
        // Quitar el parámetro msg de la URL SIN recargar
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.toString());
        }

        // Ocultar notificación automáticamente
        setTimeout(() => {
            const n = document.getElementById('notifSuccess');
            if (n) {
                n.style.transition = "opacity 0.5s ease";
                n.style.opacity = "0";
                setTimeout(() => n.remove(), 500);
            }
        }, 3000);
    </script>
<?php endif; ?>

<?php if (isset($_GET["error"])): ?>
    <div id="notifSuccess" class="notification alert alert-success alert-dismissible fade show"
        style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 1050;
        background-color: #fe0000ff; color: white; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); padding: 20px; padding-top: 30px; text-align: center;">
        
        <strong>Error!</strong> <?= htmlspecialchars($_GET["error"]) ?>
    </div>

    <script>
        // Quitar el parámetro msg de la URL SIN recargar
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url.toString());
        }

        // Ocultar notificación automáticamente
        setTimeout(() => {
            const n = document.getElementById('notifSuccess');
            if (n) {
                n.style.transition = "opacity 0.5s ease";
                n.style.opacity = "0";
                setTimeout(() => n.remove(), 500);
            }
        }, 3000);
    </script>
<?php endif; ?>

<div class="container mt-4">

  <div class="text-center my-4">
    <h1 class="fw-bold text-dark">
      <i class="bi bi-people-fill text-primary"></i>
      Gestión de <span class="text-secondary">Usuarios</span>
    </h1>
    <hr class="mt-2 mx-auto" style="width:60%;border-top:3px solid #28a745;">
  </div>
  <div class="card shadow-sm">
    <div class="card-body">

      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      
        <!-- Botón Agregar -->
        <button class="btn btn-success btn-sm p-2" data-bs-toggle="modal" data-bs-target="#modalAgregar">
            ➕ Agregar Usuario
        </button>

        <!-- Buscador -->
        <div class="input-group" style="width: 260px;">
            <span class="input-group-text bg-success text-white">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="searchUser" class="form-control" placeholder="Buscar usuario..." onkeyup="filterTable('searchUser', 'usuariostable')">
        </div>
      </div>

      <table id="usuariostable" class="table table-sm table-striped text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody>
      
        <?php
          if (count($users) === 0): ?>
          <tr>
            <td colspan="4" class="text-center text-muted py-4">
              <i class="bi bi-info-circle"></i> No hay Usuarios registradas.
            </td>
          </tr>
        <?php endif; ?>

          <?php foreach ($users as $u): ?>



            <tr data-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" data-role="<?= $u['role'] ?>">
              <td><?= $u['id'] ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= $u['role'] ?></td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                  <button class="btn btn-warning btn-sm btnEditar" data-bs-toggle="modal" data-bs-target="#modalEditar">✏️</button>
                  <form action="../controllers/usuarios_controller.php?action=delete" method="POST"
                    onsubmit="return confirm('¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.')"
                    class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">

                    <button class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" title="Eliminar usuario">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </form>

                </div>

              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div id="pagination-usuarios" class="d-flex justify-content-center mt-3"></div>
    </div>
  </div>
</div>

<!-- 🔵 MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="../controllers/usuarios_controller.php?action=add">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Agregar Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label>Usuario</label>
            <input name="username" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="pass1" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Rol</label>
            <select name="role" class="form-control">
              <option value="user">Usuario</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- 🟡 MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="../controllers/usuarios_controller.php?action=edit">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Editar Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">

          <div class="mb-3">
            <label>Usuario</label>
            <input id="edit_user" name="username" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Rol</label>
            <select id="edit_role" name="role" class="form-control">
              <option value="user">Usuario</option>
              <option value="admin">Administrador</option>
            </select>
          </div>

          <div class="mb-3">
            <label>Nueva contraseña (opcional)</label>
            <input id="edit_pass" type="password" name="pass" class="form-control"
              placeholder="Déjalo vacío si no deseas cambiarla">
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-warning">Actualizar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="../js/usuarios.js"></script>

<?php include('../views/footer.php'); ?>