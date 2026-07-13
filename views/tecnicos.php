<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/tecnicos_schema.php';

asegurarRelacionTecnicos($pdo);

$tecnicosJsVersion = file_exists(__DIR__ . '/../js/tecnicos.js') ? filemtime(__DIR__ . '/../js/tecnicos.js') : time();
$tecnicos = $pdo->query("SELECT * FROM tecnicos WHERE COALESCE(eliminado, 0) = 0 ORDER BY activo DESC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET["msg"]) || isset($_GET["error"])): ?>
  <div id="notifTecnicos"
    class="notification alert <?= isset($_GET["error"]) ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show"
    style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 1050; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.25); padding: 18px; text-align: center;">
    <strong><?= isset($_GET["error"]) ? 'Error' : 'Exito' ?>:</strong>
    <?= htmlspecialchars($_GET["error"] ?? $_GET["msg"]) ?>
  </div>
<?php endif; ?>

<div class="text-center mb-3">
  <h1 class="fw-bold text-dark">
    <i class="bi bi-person-badge text-success"></i> Tecnicos
  </h1>
  <hr class="mt-2 mx-auto" style="width:60%;border-top:3px solid #28a745;">
</div>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTecnico">
      <i class="bi bi-plus-circle"></i> Agregar tecnico
    </button>

    <div class="input-group" style="max-width: 360px;">
      <span class="input-group-text bg-success text-white"><i class="bi bi-search"></i></span>
      <input type="search" id="buscarTecnico" class="form-control" placeholder="Buscar tecnico...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0" id="tecnicosTable">
      <thead class="table-dark text-center">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Puesto</th>
          <th>Telefono</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php if (!$tecnicos): ?>
          <tr>
            <td colspan="6" class="text-muted py-4">
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
                    data-bs-toggle="modal" data-bs-target="#modalTecnico">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <a class="btn btn-outline-secondary"
                    href="../controllers/tecnicos_controller.php?action=toggle&id=<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>"
                    title="Activar/Inactivar">
                    <i class="bi bi-power"></i>
                  </a>
                  <a class="btn btn-danger"
                    href="../controllers/tecnicos_controller.php?action=delete&id=<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>"
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
  <div id="pagination-Tecnicos" class="d-flex justify-content-center align-items-center flex-wrap gap-2 p-3"></div>
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

      <form action="../controllers/tecnicos_controller.php" method="post" id="formTecnico">
        <input type="hidden" name="action" id="tecnicoAction" value="add">
        <input type="hidden" name="id" id="tecnicoId">

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

<script src="../js/tecnicos.js?v=<?= $tecnicosJsVersion ?>"></script>

<?php include('../views/footer.php'); ?>
