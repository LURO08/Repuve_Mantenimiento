<?php
include('../views/header.php');
include('../config/db.php');

$revisionesCssVersion = file_exists(__DIR__ . '/../css/revisiones.css') ? filemtime(__DIR__ . '/../css/revisiones.css') : time();
$revisionesJsVersion = file_exists(__DIR__ . '/../js/revisiones2.js') ? filemtime(__DIR__ . '/../js/revisiones2.js') : time();
?>

<link rel="stylesheet" href="../css/revisiones.css?v=<?= $revisionesCssVersion ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
  <?php if (isset($_GET["msg"])): ?>
    <div id="notifSuccess" class="notification alert alert-success alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 1050;
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
    <div id="notifSuccess" class="notification alert alert-success alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; width: 300px; z-index: 1050;
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
</div>

<div class="container">

  <div class="text-center revisiones-page-heading">
    <h1 class="fw-bold text-dark">
      📋Mantenimiento
    </h1>
    <hr class="mt-2 mx-auto" style="width:60%; border-top:3px solid #28a745; ">
  </div>

  <div class="revisiones-table-switch d-flex justify-content-center mb-3">
    <div class="btn-group shadow-sm" role="group" aria-label="Cambiar tabla de mantenimiento">
      <button type="button" class="btn btn-success active revision-tabla-toggle-btn" data-table-view-target="revisionViewArcos">
        <i class="bi bi-bounding-box-circles"></i> Arcos
      </button>
      <button type="button" class="btn btn-outline-primary revision-tabla-toggle-btn" data-table-view-target="revisionViewInfra">
        <i class="bi bi-broadcast-pin"></i> Puentes / Sitios
      </button>
    </div>
  </div>

  <div class="card shadow-sm rounded revisiones-table-view" id="revisionViewArcos">

    <div class=" interfaz justify-content-between align-items-center  gap-1 p-1" style="height: 80px; display:flex;">

      <!-- BOTÓN IZQUIERDA -->
      <button class="btn btn-success shadow-sm" style="padding: 8px 10px;" data-bs-toggle="modal"
        data-bs-target="#modalRevision">
        <i class="bi bi-plus-circle me-1"></i> Registrar Mantenimiento
      </button>

      <button class="btn btn-primary shadow-sm d-none" style="padding: 8px 10px;" data-bs-toggle="modal"
        data-bs-target="#modalRevisionInfra">
        <i class="bi bi-broadcast-pin me-1"></i> Puente/Sitio
      </button>

      <!-- BUSCADOR DERECHA -->
      <div class="input-group" style="width: 30%; padding: 8px 10px; ">
        <span class="input-group-text bg-success text-white">
          <i class="bi bi-search"></i>
        </span>
        <input type="search" id="searchRevisiones" class="form-control shadow-sm" placeholder="Buscar Mantenimiento..."
          onkeyup="filterTable('searchRevisiones', 'revisionesTable')">
      </div>
    </div>

    <div class="revision-tabla-scroll">
    <table id="revisionesTable" class="table table-striped align-middle mb-0">
      <thead class="table-dark text-center">
        <tr>
          <th>ID</th>
          <th>Arco</th>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Componentes Cambiados</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php
        $sql = "SELECT r.*, a.nombre AS arco, u.nombre AS ubic, a.lat AS lat, a.lng AS lng, r.tecnico_responsable AS tecnicoresponsable,
                (SELECT COUNT(*) FROM revision_evidencias re WHERE re.revision_id = r.id) AS evidencias_count
                FROM revisiones r
                JOIN arcos a ON r.arco_id=a.id
                JOIN ubicaciones u ON a.ubicacion_id=u.id
                ORDER BY r.fecha_mantenimiento DESC";

        $revisiones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (count($revisiones) === 0): ?>

          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              <i class="bi bi-info-circle"></i> No hay revisiones registradas.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($revisiones as $r):
            $mats = $pdo->prepare("SELECT rm.*, rm.id AS relacion_id, m.medida as medida, m.foto as foto, m.nombre AS material, r.fecha_mantenimiento AS fecha_mantenimiento
                                FROM revision_material rm 
                                JOIN materiales m ON rm.material_id=m.id 
                                JOIN revisiones r ON rm.revision_id = r.id
                                WHERE rm.revision_id=?
                                ORDER BY rm.id ASC");
            $mats->execute([$r['id']]);
            $lista = $mats->fetchAll();
            $detalleMantenimiento = [
              'id' => $r['id'],
              'origen' => 'Arco',
              'objetivo' => $r['arco'],
              'ubicacion' => $r['ubic'],
              'fecha' => $r['fecha_mantenimiento'],
              'tipo' => $r['tipo_mantenimiento'] ?? 'Correctivo',
              'tecnico' => $r['tecnicoresponsable'] ?? '',
              'observaciones' => $r['observaciones'] ?? '',
              'evidencias' => (int)($r['evidencias_count'] ?? 0),
              'evidencias_ajax' => true,
              'pdf' => "../views/pdf/revision_pdf.php?id={$r['id']}",
              'materiales' => $lista
            ];
            ?>
            <tr data-lat="<?= htmlspecialchars($r['lat'] ?? '') ?>" data-lng="<?= htmlspecialchars($r['lng'] ?? '') ?>">
              <td class="text-center fw-semibold"><?= $r['id'] ?></td>
              <td>
                <?= htmlspecialchars($r['arco']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($r['ubic']) ?></small>
              </td>
              <td><?= date("d-m-Y", strtotime($r['fecha_mantenimiento'])) ?></td>
              <td>
                <?php $tipoMant = $r['tipo_mantenimiento'] ?? 'Correctivo'; ?>
                <span class="badge <?= $tipoMant === 'Correctivo' ? 'bg-warning text-dark' : 'bg-success' ?>">
                  <?= htmlspecialchars($tipoMant) ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-info verMaterialesBtn" data-id="<?= $r['id'] ?>"
                  data-materiales='<?= htmlspecialchars(json_encode($lista), ENT_QUOTES, "UTF-8") ?>' data-bs-toggle="modal"
                  data-bs-target="#modalMateriales">
                  <i class="bi bi-box-seam"></i> Componentes
                </button>
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-outline-secondary btn-sm verDetalleMantenimientoBtn"
                  data-detalle='<?= htmlspecialchars(json_encode($detalleMantenimiento, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, "UTF-8") ?>'
                  data-bs-toggle="modal" data-bs-target="#modalDetalleMantenimiento"
                  title="Ver detalle">
                  <i class="bi bi-eye"></i>
                </button>

                <form action="../controllers/revisiones_controller.php" method="POST" class="d-inline eliminar-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>

               <a href="../views/pdf/revision_pdf.php?id=<?= $r['id'] ?>" 
                  target="_blank"
                  class="btn btn-danger btn-sm"
                  title="Ver PDF">
                  <i class="bi bi-file-earmark-pdf"></i>
                </a>



              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div id="pagination-revisiones" class="d-flex justify-content-center mt-3"></div>
  </div>

  <div class="card shadow-sm rounded revisiones-table-view d-none" id="revisionViewInfra">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-broadcast-pin"></i> Mantenimientos de Puentes / Sitios</h5>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-success shadow-sm" style="padding: 8px 10px;" data-bs-toggle="modal"
          data-bs-target="#modalRevision">
          <i class="bi bi-plus-circle me-1"></i> Registrar Mantenimiento
        </button>
        <div class="input-group revision-search-input">
          <span class="input-group-text bg-primary text-white">
            <i class="bi bi-search"></i>
          </span>
          <input type="search" id="searchInfraRevisiones" class="form-control shadow-sm" placeholder="Buscar Puente/Sitio..."
            onkeyup="filterTable('searchInfraRevisiones', 'infraRevisionesTable')">
        </div>
      </div>
    </div>
    <div class="revision-tabla-scroll">
    <table id="infraRevisionesTable" class="table table-striped align-middle mb-0">
      <thead class="table-dark text-center">
        <tr>
          <th>ID</th>
          <th>Puente/Sitio</th>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Componentes</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php
        $infraSql = "
          SELECT ir.*, n.nombre AS infraestructura, n.tipo AS tipo_infraestructura, u.nombre AS ubicacion,
                 (SELECT COUNT(*) FROM infraestructura_revision_evidencias ire WHERE ire.revision_id = ir.id) AS evidencias_count
          FROM infraestructura_revisiones ir
          JOIN infraestructura_nodos n ON n.id = ir.infraestructura_id
          LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
          ORDER BY ir.fecha_mantenimiento DESC
        ";
        $infraRevisiones = $pdo->query($infraSql)->fetchAll(PDO::FETCH_ASSOC);
        if (count($infraRevisiones) === 0): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              <i class="bi bi-info-circle"></i> No hay mantenimientos de puentes o sitios registrados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($infraRevisiones as $ir):
            $infraMats = $pdo->prepare("
              SELECT irm.*, irm.id AS relacion_id, m.medida, m.foto, m.nombre AS material, ir.fecha_mantenimiento
              FROM infraestructura_revision_material irm
              JOIN materiales m ON irm.material_id = m.id
              JOIN infraestructura_revisiones ir ON irm.revision_id = ir.id
              WHERE irm.revision_id = ?
              ORDER BY irm.id ASC
            ");
            $infraMats->execute([$ir['id']]);
            $infraLista = $infraMats->fetchAll(PDO::FETCH_ASSOC);
            $detalleInfraMantenimiento = [
              'id' => $ir['id'],
              'origen' => $ir['tipo_infraestructura'] ?? 'Puente/Sitio',
              'objetivo' => $ir['infraestructura'],
              'ubicacion' => $ir['ubicacion'] ?? '',
              'fecha' => $ir['fecha_mantenimiento'],
              'tipo' => $ir['tipo_mantenimiento'] ?? 'Correctivo',
              'tecnico' => $ir['tecnico_responsable'] ?? '',
              'observaciones' => $ir['observaciones'] ?? '',
              'evidencias' => (int)($ir['evidencias_count'] ?? 0),
              'evidencias_ajax' => true,
              'evidencias_tipo' => 'infra',
              'pdf' => '',
              'materiales' => $infraLista
            ];
          ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($ir['id']) ?></td>
              <td>
                <?= htmlspecialchars($ir['infraestructura']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($ir['tipo_infraestructura']) ?></small>
                <?php if (!empty($ir['ubicacion'])): ?>
                  <br><small class="text-muted"><?= htmlspecialchars($ir['ubicacion']) ?></small>
                <?php endif; ?>
              </td>
              <td><?= date("d-m-Y", strtotime($ir['fecha_mantenimiento'])) ?></td>
              <td>
                <span class="badge <?= $ir['tipo_mantenimiento'] === 'Correctivo' ? 'bg-warning text-dark' : 'bg-success' ?>">
                  <?= htmlspecialchars($ir['tipo_mantenimiento']) ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-info verInfraMaterialesBtn"
                  data-materiales='<?= htmlspecialchars(json_encode($infraLista, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, "UTF-8") ?>'
                  data-bs-toggle="modal" data-bs-target="#modalMateriales">
                  <i class="bi bi-box-seam"></i> Componentes
                </button>
              </td>
              <td>
                <button type="button" class="btn btn-outline-secondary btn-sm verDetalleMantenimientoBtn"
                  data-detalle='<?= htmlspecialchars(json_encode($detalleInfraMantenimiento, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, "UTF-8") ?>'
                  data-bs-toggle="modal" data-bs-target="#modalDetalleMantenimiento"
                  title="Ver detalle">
                  <i class="bi bi-eye"></i>
                </button>
                <form action="../controllers/revisiones_controller.php" method="POST" class="d-inline eliminar-form">
                  <input type="hidden" name="action" value="delete_infra">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($ir['id']) ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar mantenimiento">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div id="pagination-infraRevisiones" class="d-flex justify-content-center mt-3"></div>
  </div>

  <div class="modal fade" id="modalRevision" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">

        <form action="../controllers/revisiones_controller.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">

          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="bi bi-tools me-2"></i> Registrar Mantenimiento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <!-- Body: dos columnas (form / materiales) -->
          <div class="modal-body p-0">
            <div class=" d-flex flex-row">
              <!-- Left: formulario -->
              <div class="col col-xl-6 border-top bg-light p-3 lado-izquierdo flex-fill " >
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="checkMantenimientoInfra" name="es_infraestructura_revision" value="1">
                  <label class="form-check-label fw-semibold" for="checkMantenimientoInfra">
                    Mantenimiento de Puente/Sitio
                  </label>
                </div>

                <div class="row">
                  <!-- UBICACIÓN -->
                  <div class="col mb-3">
                    <label class="form-label fw-semibold">Ubicación</label>
                    <select id="ubicacionSelect" class="form-select" required>
                      <option value="">Seleccione una ubicación...</option>
                      <?php
                        $ubic = $pdo->query("SELECT * FROM ubicaciones ORDER BY nombre")->fetchAll();
                        foreach ($ubic as $u)
                        echo "<option value='{$u['id']}'>" . htmlspecialchars($u['nombre']) . "</option>";
                      ?>
                    </select>
                  </div>
                  <!-- ARCO -->
                  <div class="col mb-3">
                          <label class="form-label fw-semibold" id="objetivoMantenimientoLabel">Arco</label>
                          <select name="arco_id" id="arcoSelect" class="form-select" required>
                            <option value="">Seleccione una ubicación primero...</option>
                          </select>
                        </div>

                        <!-- FECHA -->
                        <div class="col mb-2">
                              <label class="form-label fw-semibold">Fecha mantenimiento</label>
                              <input type="datetime-local" name="fecha_mantenimiento" class="form-control" required>
                        </div>
                      </div>

                      <div class="row align-items-end mantenimiento-tipo-tecnico-row">
                      <div class="col-md-5 mb-3 mantenimiento-field">
                        <label class="form-label fw-semibold">Tipo de mantenimiento</label>
                        <select name="tipo_mantenimiento" class="form-select" required>
                          <option value="Preventivo">Preventivo</option>
                          <option value="Correctivo" selected>Correctivo</option>
                        </select>
                      </div>

                      <div class="col-md-7 mb-3 mantenimiento-field">
                        <label for="tecnicoresponsable">Técnico responsable</label>
                        <input type="text" name="tecnicoresponsable" id="tecnicoresponsable" class="form-control" required>
                      </div>
                      </div>

                      <!-- OBSERVACIONES -->
                      <div class=" col mb-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" cols="1"></textarea>
                      </div>

                      <div class="row mb-3">
                      <!-- EVIDENCIAS (IMÁGENES / PDF) -->
                      <div class="col mb-3">
                        <label class="form-label fw-semibold">Evidencias (imágenes o PDF)</label>
                        <input type="file" name="evidencias[]" id="evidenciasInput"
                          accept="image/*,application/pdf"
                          multiple class="form-control">
                        <small class="form-text text-muted">Puedes subir varias evidencias (opcional).</small>
                      </div>

                      <div id="previewEvidencias" class="preview-evidencias"></div>

                </div>
              </div>

              <!-- Right: materiales (scroll independiente) -->
              <div class="col col-xl-6 border-top bg-light p-3 lado-derecho flex-fill">
                <h6 class="fw-semibold text-center" id="tituloMaterialesMantenimiento">Material(es) cambiados</h6>
                <div id="materialesContainer" class=" mt-3">
                  Seleccione un arco para mostrar sus materiales...
                </div>
              </div>

              <div id="materialesHidden"></div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>
            <button type="submit" class="btn btn-success">
              Guardar Registro
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalRevisionInfra" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <form action="../controllers/revisiones_controller.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_infra">

          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="bi bi-broadcast-pin me-2"></i> Registrar Mantenimiento de Puente/Sitio</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body p-0">
            <div class="d-flex flex-row">
              <div class="col col-xl-6 border-top bg-light p-3 flex-fill">
                <div class="row">
                  <div class="col mb-3">
                    <label class="form-label fw-semibold">Puente/Sitio</label>
                    <select name="infraestructura_id" class="form-select" required>
                      <option value="">Seleccione...</option>
                      <?php foreach ($pdo->query("SELECT n.id, n.tipo, n.nombre, u.nombre AS ubicacion FROM infraestructura_nodos n LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id ORDER BY n.tipo, n.nombre") as $infra): ?>
                        <option value="<?= htmlspecialchars($infra['id']) ?>">
                          <?= htmlspecialchars($infra['tipo'] . ' - ' . $infra['nombre'] . (!empty($infra['ubicacion']) ? ' (' . $infra['ubicacion'] . ')' : '')) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col mb-3">
                    <label class="form-label fw-semibold">Fecha mantenimiento</label>
                    <input type="datetime-local" name="fecha_mantenimiento" class="form-control" required>
                  </div>
                </div>

                <div class="col mb-3">
                  <label class="form-label fw-semibold">Tipo de mantenimiento</label>
                  <select name="tipo_mantenimiento" class="form-select" required>
                    <option value="Preventivo">Preventivo</option>
                    <option value="Correctivo" selected>Correctivo</option>
                  </select>
                </div>

                <div class="col mb-3">
                  <label class="form-label fw-semibold">TÃ©cnico responsable</label>
                  <input type="text" name="tecnicoresponsable" class="form-control" required>
                </div>

                <div class="col mb-3">
                  <label class="form-label fw-semibold">Observaciones</label>
                  <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>

                <div class="col mb-3">
                  <label class="form-label fw-semibold">Evidencias (imágenes o PDF)</label>
                  <input type="file" name="evidencias[]" accept="image/*,application/pdf" multiple class="form-control">
                  <small class="form-text text-muted">Puedes subir varias evidencias (opcional).</small>
                </div>
              </div>

              <div class="col col-xl-6 border-top bg-light p-3 flex-fill">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-semibold mb-0">Material(es) cambiados</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInfraRevisionMaterial">
                    <i class="bi bi-plus-lg"></i> Material
                  </button>
                </div>

                <div id="infraRevisionMaterialRows" class="infra-revision-material-rows">
                  <div class="infra-revision-material-row">
                    <select name="infra_material_id[]" class="form-select infra-revision-material-select" required>
                      <option value="">Seleccione material...</option>
                      <?php foreach ($pdo->query('SELECT id, nombre, medida FROM materiales ORDER BY nombre') as $m): ?>
                        <option value="<?= htmlspecialchars($m['id']) ?>" data-medida="<?= htmlspecialchars($m['medida']) ?>">
                          <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <input type="number" name="infra_cantidad[]" class="form-control infra-revision-cantidad" min="0.1" step="0.1" value="1">
                    <input type="text" name="infra_serie[]" class="form-control" placeholder="Serie">
                    <button type="button" class="btn btn-outline-danger infra-revision-remove-material">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Registro</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<div class="modal" id="modalSerie" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-success text-white">
          <h6 class="modal-title">
            <i class="bi bi-pencil"></i> Editar material cambiado
          </h6>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body bg-light">
          <input type="hidden" id="modalSelectMaterial">
          <input type="hidden" id="modalMaterialId">

          <div class="modal-material-layout">
            <div class="modal-material-list-panel">
              <label class="form-label fw-semibold">Material</label>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text bg-success text-white"><i class="bi bi-search"></i></span>
                <input type="search" id="modalBuscarMaterial" class="form-control" placeholder="Buscar material...">
              </div>
              <div id="modalMaterialGrid" class="modal-material-grid">
                <div class="text-center text-muted py-3">Cargando materiales...</div>
              </div>
            </div>

            <aside class="modal-material-config-panel">
              <div class="modal-material-config-head">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center">
                  <i class="bi bi-sliders text-success"></i>
                </div>
                <h6 class="fw-bold text-success mb-1">Configuracion</h6>
                <small class="text-muted">Complete los datos del material</small>
              </div>

              <div id="modalMaterialSeleccionado" class="modal-material-selected mb-3">
                Selecciona el material que quedara instalado.
              </div>

              <div class="modal-material-fields">
                <div id="DatosSeries" class="d-none">
                  <div class="form-check form-switch modal-serie-switch">
                    <input class="form-check-input" type="checkbox" id="modalCheckSerie">
                    <label class="form-check-label fw-semibold" for="modalCheckSerie">
                      Este material tiene numero de serie
                    </label>
                  </div>
                  <div id="modalSerieField" class="modal-serie-input d-none">
                    <label class="form-label small">Numero de serie</label>
                    <input type="text" id="modalSerieInput" class="form-control form-control-sm" placeholder="Ingrese el numero de serie">
                  </div>
                </div>

                <div class="d-none" id="DatosCantidad">
                  <label class="form-label small">Cantidad</label>
                  <div class="input-group input-group-sm">
                    <input type="number" id="modalCantidadInput" class="form-control" min="1">
                    <span class="input-group-text text-muted" id="medida-label">
                      pz
                    </span>
                  </div>
                </div>
              </div>
            </aside>
          </div>
          
        </div>

        <div class="modal-footer">
          <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <a class="btn btn-sm btn-primary" id="btnGuardarSerie">Guardar</a>
        </div>

      </div>
    </div>
</div>

<!-- ===================== MODAL VER MATERIALES ===================== -->
<div class="modal modalverMateriales" id="modalMateriales" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-box-seam"></i> Componentes cambiados por mantenimiento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="contenedorMateriales" style="overflow-y: auto; max-height: 60vh;">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL DETALLE MANTENIMIENTO ===================== -->
<div class="modal fade" id="modalDetalleMantenimiento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="bi bi-eye"></i> Detalle del mantenimiento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalleMantenimientoContenido"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para evidencias -->
<div class="modal fade" id="modalEvidencias" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title tituloEvidencias"><i class="bi bi-camera"></i> Evidencias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" 
        id="evidenciasContainer"
        style="
          max-height: 70vh;
          overflow-y: auto;
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
          gap: 12px;
        ">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal visor de imagen -->
<div class="modal fade" id="modalImagen" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered">

    <div class="modal-content bg-dark border-0 position-relative">

      <div class="modal-body text-center position-relative" style="overflow-y: auto; max-height: 80vh;">
        <button type="button" class="btn-close btn-close-white ms-auto position-absolute top-0 end-0 m-2" data-bs-dismiss="modal"></button>

        <!-- Flecha izquierda -->
        <button id="btnPrevImg"
                class="btn btn-dark position-absolute top-50 start-0 translate-middle-y ms-3 z-3">
          <i class="bi bi-chevron-left fs-3"></i>
        </button>

        <!-- Imagen -->
        <img id="imagenAmpliada"
             src=""
             class="img-fluid"
             style="max-height: 70vh; object-fit: contain;">

        <!-- Flecha derecha -->
        <button id="btnNextImg"
                class="btn btn-dark position-absolute top-50 end-0 translate-middle-y me-3 z-3">
          <i class="bi bi-chevron-right fs-3"></i>
        </button>

      </div>
    </div>
  </div>
</div>

<!-- Modal visor PDF -->
<div class="modal fade" id="modalPdf" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark border-0">

      <div class="modal-body position-relative p-2">
        <button type="button"
                class="btn-close btn-close-white position-absolute top-0 end-0 m-2 z-3"
                data-bs-dismiss="modal">
        </button>

        <iframe id="pdfAmpliado"
                src=""
                style="width:100%; height:85vh; border:none; border-radius:10px;">
        </iframe>
      </div>

    </div>
  </div>
</div>


<script src="../js/revisiones2.js?v=<?= $revisionesJsVersion ?>"></script>

<?php include('../views/footer.php'); ?>
