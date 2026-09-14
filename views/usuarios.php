<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/tecnicos_schema.php';

if (!isset($_SESSION["user"])) {
  header("Location: ../index.php");
  exit;
}

// SOLO ADMIN PUEDE ENTRAR A ESTA PÁGINA

if ($_SESSION["role"] !== "admin") {
  header("Location: dashboard.php");
  exit;
}

asegurarRelacionTecnicos($pdo);

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$tecnicos = $pdo->query("SELECT * FROM tecnicos WHERE COALESCE(eliminado, 0) = 0 ORDER BY activo DESC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$usuariosJsVersion = file_exists(__DIR__ . '/../js/usuarios.js') ? filemtime(__DIR__ . '/../js/usuarios.js') : time();
$tecnicosJsVersion = file_exists(__DIR__ . '/../js/tecnicos.js') ? filemtime(__DIR__ . '/../js/tecnicos.js') : time();
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

<div class="container-fluid mt-4 px-3 px-xl-4">

  <div class="text-center my-4">
    <h1 class="fw-bold text-dark">
      <i class="bi bi-people-fill text-primary"></i>
      Gestión de <span class="text-secondary">Usuarios y Tecnicos</span>
    </h1>
    <hr class="mt-2 mx-auto" style="width:60%;border-top:3px solid #28a745;">
  </div>
  <div class="row g-3 align-items-start">
    <div class="col-12 col-xl-6">
  <div class="card shadow-sm h-100">
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

      <div class="table-responsive">
      <table id="usuariostable" class="table table-sm table-striped text-center align-middle mb-0">
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
      </div>

      <div id="pagination-usuarios" class="d-flex justify-content-center mt-3"></div>
    </div>
  </div>
    </div>

    <div class="col-12 col-xl-6">
  <div class="card shadow-sm h-100" id="seccionTecnicos">
    <div class="card-body">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-success btn-sm p-2" data-bs-toggle="modal" data-bs-target="#modalTecnico">
          <i class="bi bi-plus-circle"></i> Agregar Tecnico
        </button>

        <div class="input-group" style="width: 260px;">
          <span class="input-group-text bg-success text-white">
            <i class="bi bi-search"></i>
          </span>
          <input type="search" id="buscarTecnico" class="form-control" placeholder="Buscar tecnico...">
        </div>
      </div>

      <div class="table-responsive">
      <table class="table table-sm table-striped text-center align-middle mb-0" id="tecnicosTable">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Puesto</th>
            <th>Telefono</th>
            <th>Firma</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$tecnicos): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-info-circle"></i> No hay tecnicos registrados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tecnicos as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['id']) ?></td>
                <td class="text-start fw-semibold"><?= htmlspecialchars($t['nombre']) ?></td>
                <td><?= htmlspecialchars($t['puesto'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($t['telefono'] ?? 'N/A') ?></td>
                <td>
                  <?php if (!empty($t['firma']) && file_exists(__DIR__ . '/../' . $t['firma'])): ?>
                    <img src="../<?= htmlspecialchars($t['firma']) ?>" alt="Firma" style="max-height: 28px; max-width: 75px; object-fit: contain; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; padding: 2px;" title="Firma registrada">
                  <?php else: ?>
                    <span class="badge bg-light text-secondary border">Sin firma</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= (int)$t['activo'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                    <?= (int)$t['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-warning editarTecnicoBtn"
                      data-id="<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>"
                      data-nombre="<?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                      data-puesto="<?= htmlspecialchars($t['puesto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-telefono="<?= htmlspecialchars($t['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-activo="<?= (int)$t['activo'] ?>"
                      data-firma="<?= htmlspecialchars($t['firma'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-bs-toggle="modal" data-bs-target="#modalTecnico">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                    <a class="btn btn-outline-secondary"
                      href="../controllers/tecnicos_controller.php?action=toggle&redirect=usuarios&id=<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>"
                      title="Activar/Inactivar">
                      <i class="bi bi-power"></i>
                    </a>
                    <a class="btn btn-danger"
                      href="../controllers/tecnicos_controller.php?action=delete&redirect=usuarios&id=<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>"
                      onclick="return confirm('Seguro que deseas eliminar este tecnico?')">
                      <i class="bi bi-trash-fill"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <div id="pagination-Tecnicos" class="d-flex justify-content-center align-items-center flex-wrap gap-2 mt-3"></div>
    </div>
  </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTecnico" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalTecnicoTitulo">
          <i class="bi bi-person-badge"></i> Agregar tecnico
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="../controllers/tecnicos_controller.php" method="post" id="formTecnico" enctype="multipart/form-data">
        <input type="hidden" name="redirect" value="usuarios">
        <input type="hidden" name="action" id="tecnicoAction" value="add">
        <input type="hidden" name="id" id="tecnicoId">
        <input type="hidden" name="eliminar_firma" id="eliminarFirmaInput" value="0">
        <input type="hidden" name="firma_canvas" id="firmaCanvasInput">

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nombre</label>
            <input type="text" name="nombre" id="tecnicoNombre" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Puesto</label>
            <input type="text" name="puesto" id="tecnicoPuesto" class="form-control" placeholder="Ej. Tecnico de campo">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Telefono</label>
            <input type="text" name="telefono" id="tecnicoTelefono" class="form-control">
          </div>

          <!-- SECCIÓN DE FIRMA -->
          <div class="mb-3">
            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
              <span><i class="bi bi-pen"></i> Firma del Técnico</span>
              <span class="badge bg-secondary text-white" style="font-size: 0.7rem;">Opcional</span>
            </label>

            <!-- Preview firma existente -->
            <div id="firmaPreviewContainer" class="d-none mb-2 p-2 border rounded text-center bg-light">
              <div class="small text-muted mb-1">Firma actual registrada:</div>
              <img id="firmaPreviewImg" src="" alt="Firma" style="max-height: 55px; max-width: 180px; object-fit: contain; background: #fff; padding: 4px; border: 1px solid #ced4da; border-radius: 4px;">
              <div class="mt-2">
                <button type="button" class="btn btn-outline-danger btn-sm py-1" id="btnEliminarFirma">
                  <i class="bi bi-trash"></i> Eliminar firma actual
                </button>
              </div>
            </div>

            <!-- Opciones para agregar o actualizar firma -->
            <ul class="nav nav-pills nav-fill mb-2" id="firmaTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm py-1" id="tab-canvas-btn" data-bs-toggle="tab" data-bs-target="#tab-canvas" type="button" role="tab">
                  <i class="bi bi-brush"></i> Dibujar firma
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm py-1" id="tab-archivo-btn" data-bs-toggle="tab" data-bs-target="#tab-archivo" type="button" role="tab">
                  <i class="bi bi-upload"></i> Subir imagen
                </button>
              </li>
            </ul>

            <div class="tab-content" id="firmaTabsContent">
              <!-- Tab Canvas -->
              <div class="tab-pane fade show active" id="tab-canvas" role="tabpanel">
                <div class="border rounded p-2 bg-white text-center position-relative">
                  <canvas id="canvasFirma" width="420" height="130" style="touch-action: none; cursor: crosshair; width: 100%; height: 130px; border: 1px dashed #28a745; border-radius: 4px; background: #fff;"></canvas>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted"><i class="bi bi-info-circle"></i> Dibuja con el mouse o pantalla táctil</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btnLimpiarCanvas">
                      <i class="bi bi-eraser"></i> Limpiar
                    </button>
                  </div>
                </div>
              </div>

              <!-- Tab Archivo -->
              <div class="tab-pane fade" id="tab-archivo" role="tabpanel">
                <input type="file" name="firma_archivo" id="firmaArchivo" class="form-control form-control-sm" accept="image/png, image/jpeg, image/jpg, image/webp">
                <small class="text-muted d-block mt-1">Formatos admitidos: PNG, JPG, WEBP.</small>
              </div>
            </div>
          </div>

          <div class="form-check form-switch" id="tecnicoActivoGroup">
            <input class="form-check-input" type="checkbox" name="activo" id="tecnicoActivo" checked>
            <label class="form-check-label" for="tecnicoActivo">Activo</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Guardar
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
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

<script src="../js/usuarios.js?v=<?= $usuariosJsVersion ?>"></script>
<script src="../js/tecnicos.js?v=<?= $tecnicosJsVersion ?>"></script>

<?php include('../views/footer.php'); ?>
