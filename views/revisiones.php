<?php
include('../views/header.php');
include('../config/db.php');
?>

<link rel="stylesheet" href="../css/revisiones.css">

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

  <div class="text-center my-2">
    <h1 class="fw-bold text-dark">
      📋Mantenimiento
    </h1>
    <hr class="mt-2 mx-auto" style="width:60%; border-top:3px solid #28a745; ">
  </div>

  <div class="card shadow-sm rounded">

    <div class=" interfaz justify-content-between align-items-center  gap-1 p-1" style="height: 80px; display:flex;">

      <!-- BOTÓN IZQUIERDA -->
      <button class="btn btn-success shadow-sm" style="padding: 8px 10px;" data-bs-toggle="modal"
        data-bs-target="#modalRevision">
        <i class="bi bi-plus-circle me-1"></i> Registrar Mantenimiento
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

    <table id="revisionesTable" class="table table-striped align-middle mb-0">
      <thead class="table-dark text-center">
        <tr>
          <th>ID</th>
          <th>Arco</th>
          <th>Fecha</th>
          <th>Componentes Cambiados</th>
          <th>Observaciones</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php
        $sql = "SELECT r.*, a.nombre AS arco, u.nombre AS ubic, a.lat AS lat, a.lng AS lng,
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
            $mats = $pdo->prepare("SELECT rm.*, m.medida as medida, m.foto as foto,  m.nombre AS material
                                FROM revision_material rm 
                                JOIN materiales m ON rm.material_id=m.id 
                                WHERE rm.revision_id=?  ");
            $mats->execute([$r['id']]);
            $lista = $mats->fetchAll();
            ?>
            <tr data-lat="<?= htmlspecialchars($r['lat'] ?? '') ?>" data-lng="<?= htmlspecialchars($r['lng'] ?? '') ?>">
              <td class="text-center fw-semibold"><?= $r['id'] ?></td>
              <td>
                <?= htmlspecialchars($r['arco']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($r['ubic']) ?></small>
              </td>
              <td><?= date("d-m-Y", strtotime($r['fecha_mantenimiento'])) ?></td>
              <td>
                <button class="btn btn-sm btn-info verMaterialesBtn" data-id="<?= $r['id'] ?>"
                  data-materiales='<?= htmlspecialchars(json_encode($lista), ENT_QUOTES, "UTF-8") ?>' data-bs-toggle="modal"
                  data-bs-target="#modalMateriales">
                  <i class="bi bi-box-seam"></i> Componentes
                </button>
              </td>
              <td><?= nl2br(htmlspecialchars($r['observaciones'])) ?></td>
              <td class="text-center">
                <?php if (!empty($r['evidencias_count']) && $r['evidencias_count'] > 0): ?>
                  <button class="btn btn-sm btn-outline-primary verEvidenciasBtn" data-id="<?= $r['id'] ?>"
                    data-bs-toggle="modal" data-bs-target="#modalEvidencias">
                    <i class="bi bi-camera"></i> <?= $r['evidencias_count'] ?>
                  </button>
                <?php endif; ?>

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
    <div id="pagination-revisiones" class="d-flex justify-content-center mt-3"></div>
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
                          <label class="form-label fw-semibold">Arco</label>
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

                      <div class="col mb-3">
                        <label for="tecnicoresponsable">Técnico responsable</label>
                        <input type="text" name="tecnicoresponsable" id="tecnicoresponsable" class="form-control" required>
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
                <h6 class="fw-semibold text-center">Material(es) cambiados</h6>
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

<div class="modal" id="modalSerie" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-success text-white">
          <h6 class="modal-title">
            <i class="bi bi-pencil"></i> Cambiar serie
          </h6>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-2">
            <small class="text-muted">Material:</small>
            <div class="flex-grow-2 col" style="min-width: 200px;">
              <label class="form-label fw-semibold">Material</label>
                <select name="material_id[]" class="form-select material-select" id="modalSelectMaterial" required>
                </select>
            </div>
          </div>

          <input type="hidden" id="modalMaterialId">

          <div id="DatosSeries" class="d-none">
            <div class="mb-2">
              <label class="form-label small">Serie</label>
              <input type="text" id="modalSerieInput" class="form-control form-control-sm">
            </div>
          </div>

          <div class="mb-2 d-none" id="DatosCantidad">
            <div>
              <label class="form-label small">Cantidad</label>
              <div class="input-group input-group-sm">
                <input type="number" id="modalCantidadInput" class="form-control" min="1">
                <span class="input-group-text text-muted" id="medida-label">
                  pz
                </span>
              </div>
            </div>
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
        <h5 class="modal-title"><i class="bi bi-box-seam"></i> Componentes del arco Cambiado</h5>
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


<script src="../js/revisiones2.js"></script>

<?php include('../views/footer.php'); ?>