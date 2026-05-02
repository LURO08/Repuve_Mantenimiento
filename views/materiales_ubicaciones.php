<?php

include('../views/header.php');
include('../config/db.php');

if (!isset($_SESSION["user"])) {
  header("Location: ../index.php");
  exit;
}
?>

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

<!-- LINK PARA MAPA -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<link rel="stylesheet" href="../css/material_ubicaciones.css">

<div class="container">
  <div class="text-center">
    <h1 class="fw-bold text-dark">
      <i class="bi bi-diagram-3 text-success"></i>
      Catalogo de Materiales <span class="text-secondary">& Ciudades</span>
    </h1>
    <hr class="mt-2 mx-auto" style="width:60%; border-top:3px solid #28a745; ">
  </div>

  <div class="row g-4">
    <!-- MATERIAL -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Materiales</h5>
          <button class="btn btn-success btn-sm" style="padding: 8px;" onclick="openModal('modalAgregarMaterial')">➕
            Agregar</button>
        </div>
        <div class="card-body table-responsive-mobile">
          <div class="d-flex justify-content-end align-items-center">
            <div class="input-group" style="width: 260px; margin-bottom: 10px;">
              <span class="input-group-text bg-success text-white">
                <i class="bi bi-search"></i>
              </span>
              <input type="search" id="searchMaterial" class="form-control shadow-sm"
                placeholder="Buscar Componentes..." onkeyup="filterTable('searchMaterial', 'materialesTable')">
            </div>

          </div>
          <table id="materialesTable" class="table table-striped table-hover align-middle"
            style="  overflow-x: scroll;  min-width: 45vh;">
            <thead class="table-dark text-center">
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Imagen</th>
                <th>Medida </th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody class="text-center">


              <?php $material = $pdo->query('SELECT * FROM materiales')->fetchAll(PDO::FETCH_ASSOC) ?>

              <?php if (count($material) === 0): ?>
                <tr>
                  <td style="width: 100vh;" class="text-center text-muted py-4">
                    <i class="bi bi-info-circle"></i> No hay materiales registrados.
                  </td>
                </tr>
              <?php endif; ?>

              <?php foreach ($material as $m): ?>

                <tr onclick="" data-id="<?= $m['id'] ?>" data-nombre="<?= htmlspecialchars($m['nombre']) ?>"
                  data-medida="<?= $m['medida'] ?>" data-foto="<?= htmlspecialchars($m['foto']) ?>">
                  <td><?= $m['id'] ?></td>
                  <td class="td-material-nombre text-truncate" style="max-width: 280px;"
                    title="<?= htmlspecialchars($m['nombre']) ?>">
                    <?= htmlspecialchars($m['nombre']) ?>
                  </td>


                  <td style=" text-align: center;">
                    <?php if (!empty($m['foto'])): ?>
                      <img src="../uploads/materiales/<?= htmlspecialchars($m['foto']) ?>" class="img-material">

                    <?php else: ?>
                      <span class="text-muted">Sin foto</span>
                    <?php endif; ?>
                  </td>
                  <?php $medida = htmlspecialchars($m['medida']);
                  if ($medida == 'pz') {
                    $medida = 'Piezas (pz)';
                  } else if ($medida == 'm') {
                    $medida = 'Metros (m)';
                  }
                  ?>
                  <td><?= $medida ?></td>


                  <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                      <button class="btn btn-warning btnEditarMaterial d-flex align-items-center justify-content-center"
                        onclick="openEditMaterial(this)" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                      </button>
                      <a class="btn btn-danger d-flex align-items-center justify-content-center"
                        href="../controllers/materiales_controller.php?action=delete&id=<?= $m['id'] ?>"
                        onclick="return confirm('¿Borrar material?')" title="Eliminar">
                        <i class="bi bi-trash-fill"></i>
                      </a>
                    </div>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div id="pagination-materiales" class=" d-flex justify-content-center"></div>
        </div>
      </div>
    </div>


    <!-- Ciudades -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Ciudades</h5>
          <button class="btn btn-success btn-sm" style="padding: 8px;" onclick="openModal('modalAgregarUbicacion')">➕
            Agregar</button>
        </div>
        <div class="card-body table-responsive-mobile">
          <div class="d-flex justify-content-end align-items-center">
            <div class="input-group" style="width: 280px;  margin-bottom: 10px;">
              <span class="input-group-text bg-success text-white">
                <i class="bi bi-search"></i>
              </span>
              <input type="search" id="searchUbic" class="form-control shadow-sm" placeholder="Buscar Ciudad..."
                onkeyup="filterTable('searchUbic', 'ubicacionesTable')">
            </div>
          </div>

          <table id="ubicacionesTable" class="table table-striped align-middle"
            style="width: 100%; table-layout: fixed;  overflow-x: scroll; min-width: 45vh;">
            <thead class="table-dark text-center">
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody class="text-center">

              <?php $ubicaciones = $pdo->query('SELECT * FROM ubicaciones')->fetchAll(PDO::FETCH_ASSOC); ?>
              <?php if (count($ubicaciones) === 0): ?>
                <tr>
                  <td style="width: 100vh;" class="text-center text-muted py-4">
                    <i class="bi bi-info-circle"></i> No hay Ciudades registradas.
                  </td>
                </tr>
              <?php endif; ?>

              <?php foreach ($ubicaciones as $u): ?>

                <tr data-id="<?= $u['id'] ?>" data-nombre="<?= htmlspecialchars($u['nombre']) ?>" data-lat="<?= $u['lat'] ?? '' ?>" data-lng="<?= $u['lng'] ?? '' ?>">
                  <td> <?= $u['id'] ?></td>

                  <td class="text-primary fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalMapaArcos"
                  data-lat="<?= $u['lat'] ?>" data-lng="<?= $u['lng'] ?>" data-fallas="<?= $u['fallas'] ?? 0 ?>"
                  data-nombre="<?= htmlspecialchars($u['nombre']) ?>" data-ubic="<?= htmlspecialchars($u['ubic'] ?? '') ?>">
                  <i class="bi bi-geo-alt-fill me-1"></i>
                  <?= htmlspecialchars($u['nombre']) ?>
                </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <button class="btn btn-warning d-flex align-items-center justify-content-center"
                        onclick="openEditUbicacion(this, <?= $u['lat'] ?? 0 ?>, <?= $u['lng'] ?? 0 ?>)" title="Editar ubicación">
                        <i class="bi bi-pencil-fill"></i>
                      </button>

                      <a class="btn btn-danger d-flex align-items-center justify-content-center"
                        href="../controllers/ubicaciones_controller.php?action=delete&id=<?= $u['id'] ?>"
                        onclick="return confirm('¿Borrar ubicación?')" title="Eliminar ubicación">
                        <i class="bi bi-trash-fill"></i>
                      </a>
                    </div>
                  </td>
                </tr>
          
              <?php endforeach; ?>
            </tbody>
          </table>
          <div id="pagination-ubicaciones" class=" d-flex justify-content-center"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL MAPA ARCOS ================= -->
<div class="modal fade" id="modalMapaArcos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-map"></i> Ubicación de Arcos
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <div class="row g-0">
          <!-- MAPA -->
          <div class="col-md-8">
            <div id="map" style="height: 400px; width: 100%;"></div>
          </div>

          <!-- LISTA DE ARCOS -->
          <div class="col-md-4 border-start">
            <div class="p-3">
              <h6 class="mb-2">Arcos registrados</h6>
              <div id="listaMsg" class="small text-muted mb-2"></div>
              <div id="listaArcos" style="max-height: 400px; overflow: auto;"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- ------------------------ MODAL AGREGAR MATERIAL ------------------------ -->
<div class="custom-modal" id="modalAgregarMaterial">
  <div class="custom-modal-content">
    <div class="modal-header-custom">
      <h5>Agregar Material</h5>
      <span class="close-modal" onclick="closeModal('modalAgregarMaterial')">&times;</span>
    </div>

    <div style="padding: 10px;">
      <form method="post" action="../controllers/materiales_controller.php" enctype="multipart/form-data">

        <div class="row-two">
          <div class="col">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
          </div>

          <div class="col">
            <label>Medida:</label>
            <select name="medida" required>
              <option value="pz">pz</option>
              <option value="m">m</option>
            </select>
          </div>
        </div>

        <label>Foto:</label>
        <input type="file" name="foto" class="form-control" accept="image/*"
          onchange="previewImage('previewAgregar', event)">
        <img id="previewAgregar" class="preview-img">

        <button class="btn btn-success btn-sm  w-100 mt-3">Guardar</button>
      </form>
    </div>
  </div>
</div>

<!-- ------------------------ MODAL EDITAR MATERIAL ------------------------ -->
<div class="custom-modal" id="modalEditarMaterial">
  <div class="custom-modal-content">
    <div class="modal-header-customEditar">
      <h5>Editar Material</h5>
      <span class="close-modal" onclick="closeModal('modalEditarMaterial')">&times;</span>
    </div>

    <div style="padding: 10px;">
      <form method="post" action="../controllers/materiales_controller.php?action=update" enctype="multipart/form-data">

        <input type="hidden" name="id" id="edit-material-id">

        <div class="row-two">
          <div class="col">
            <label>Nombre:</label>
            <input type="text" name="nombre" id="edit-material-nombre" class="form-control" required>
          </div>

          <div class="col">
            <label>Medida:</label>
            <select name="medida" id="edit-material-medida" class="form-control" required>
              <option value="pz">pz</option>
              <option value="m">m</option>
            </select>
          </div>
        </div>

        <label>Cambiar Foto:</label>
        <input type="file" name="foto" class="form-control" accept="image/*"
          onchange="previewImage('previewEditar', event)">
        <img id="previewEditar" class="preview-img">

        <button class="btn w-100 mt-3" id="btnActualizar">Actualizar</button>
      </form>
    </div>

  </div>
</div>

<!-- ------------------------ MODAL AGREGAR UBICACIÓN ------------------------ -->
<div class="custom-modal" id="modalAgregarUbicacion">
  <div class="custom-modal-content">

    <div class="modal-header-custom">
      <h5>Agregar Ubicación</h5>
      <span class="close-modal" onclick="closeModal('modalAgregarUbicacion')">&times;</span>
    </div>

    <div style="padding: 10px 10px;">
      <form method="post" action="../controllers/ubicaciones_controller.php">
        <label>Nombre:</label>
        <input name="nombre" class="form-control" required>

        <div class="row-two mt-2">
          <div class="col ">
            <label>Latitud:</label>
            <input type="number" step="any" name="latAgregar" id="latAgregar" class="form-control">
          </div>

          <div class="col">
            <label>Longitud:</label>
            <input type="number" step="any" name="lngAgregar" id="lngAgregar" class="form-control">
          </div>


          <div class="col-md-1 d-flex justify-content-end align-items-end">
            <label class="form-label">&nbsp;</label>
            <button
              type="button"
              class="btn btn-outline-success abrirMapa"
              >
              <i class="bi bi-map"></i>
            </button>

          </div>
        </div>


        <button class="btn btn-success w-100 mt-3">Guardar</button>



      </form>
    </div>

  </div>
</div>

<!-- ------------------------ MODAL EDITAR UBICACIÓN ------------------------ -->
<div class="custom-modal" id="modalEditarUbicacion">
  <div class="custom-modal-content">
    <div class="modal-header-customEditar">
      <h5>Editar Ubicación</h5>
      <span class="close-modal" onclick="closeModal('modalEditarUbicacion')">&times;</span>
    </div>

    <div style="padding: 10px;">
      <form method="post" action="../controllers/ubicaciones_controller.php?action=update">

        <input type="hidden" name="id" id="edit-ubicacion-id">

        <label>Nombre:</label>
        <input name="nombre" id="edit-ubicacion-nombre" class="form-control" required>

        <div class="row-two mt-2 movilEditarUbicacion">
              <div class="col ">
                <label>Latitud:</label>
                <input type="number" step="any" name="lateditar" id="edit-material-latitud" class="form-control">
              </div>

            <div class="col">
              <label>Longitud:</label>
              <input type="number" step="any" name="lngeditar" id="edit-material-longitud" class="form-control">
            </div>
          
           
          <div class="col-md-1 d-flex justify-content-center align-items-end">
            <label class="form-label">&nbsp;</label>
            <button
              type="button"
              class="btn btn-outline-warning abrirMapa"
              data-lat="edit-material-latitud"
              data-lng="edit-material-longitud">
              <i class="bi bi-map"></i>
            </button>
          </div>
        </div>

        <button class="btn btn-warning w-100 mt-3">Actualizar</button>
      </form>
    </div>

  </div>
</div>


<div class="modal fade" id="modalSeleccionarMapa" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-geo-alt"></i> Seleccionar ubicación del Arco
        </h5>
        <button type="button" class="btn-close cerrarMapa"></button>
      </div>

      <div class="modal-body">

        <div id="mapSelector" style="height:300px;"></div>
        <div id="mapStatus" class="small text-muted mt-2"></div>
        <div id="mapHelp" class="small text-muted mt-1 d-none">Para permitir la ubicación, haz clic en el icono de candado en la barra de direcciones y habilita Permisos → Ubicación, o prueba en una ventana de incógnito/HTTPS.</div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-primary me-auto" id="btnUsarMiUbicacion">
          <i class="bi bi-pin-map"></i> Usar mi ubicación
        </button>
        <button class="btn btn-success" id="btnAceptarUbicacion">
          <i class="bi bi-check-circle"></i> Aceptar ubicación
        </button>
        <button class="btn btn-secondary cerrarMapa">Cancelar</button>
      </div>

    </div>
  </div>
</div>




<script src="../js/materiales_ubicaciones.js"></script>

<?php include('../views/footer.php'); ?>