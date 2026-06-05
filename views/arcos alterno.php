<?php
include('../views/header.php');
include('../config/db.php');
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

<link rel="stylesheet" href="../css/arcos.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<div class="text-center">
  <h1 class="fw-bold text-dark">
    <i class="bi bi-bounding-box-circles text-success"></i> Lista de Arcos
  </h1>
  <hr class="mt-2 mx-auto" style="width:60%;border-top:3px solid #28a745;">
</div><br>

<div class="card table-responsive shadow-sm rounded">

  <div class="interfaz card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarArco">
        <i class="bi bi-plus-circle"></i> Agregar
      </button>
    </div>

    <div class="d-flex justify-content-end align-items-center">
      <div class="input-group" style="max-width: 350px; width: 80%;">
        <span class="input-group-text bg-success text-white">
          <i class="bi bi-search"></i>
        </span>
        <input type="search" id="searchArcos" class="form-control shadow-sm" placeholder="Buscar arco..."
          onkeyup="filterTable('searchArcos', 'ArcosTable')">
      </div>
    </div>
  </div>

  <table id="ArcosTable" class="table table-striped align-middle mb-0">
    <thead class="table-dark text-center">
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Ubicación</th>
        <th>Fecha Instalación</th>
        <th>Ultimo Mantenimiento</th>
        <th>Proximo Mantenimiento</th>
        <th>Componentes</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <?php
      $stmt = $pdo->query("
          SELECT 
              a.*,
              u.nombre AS ubic,
              COUNT(r.id) AS fallas
          FROM arcos a
          LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
          LEFT JOIN revisiones r ON r.arco_id = a.id
          GROUP BY a.id
          ORDER BY a.fecha_instalacion DESC
      ");

      $arcos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <?php if (count($arcos) === 0): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-3">
            <i class="bi bi-info-circle"></i> No hay arcos registrados.
          </td>
        </tr>

      <?php else: ?>
        <?php foreach ($arcos as $r): ?>
          <?php
          // Cargar materiales de este arco
          $mats = $pdo->prepare("
                SELECT am.*, m.nombre AS material, m.medida AS medida, m.foto AS foto
                FROM arco_material am
                JOIN materiales m ON am.material_id = m.id
                WHERE am.arco_id = ?
            ");
          $mats->execute([$r['id']]);
          $lista = $mats->fetchAll();
          ?>
          <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
            <td class="text-primary fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalMapaArcos"
              data-lat="<?= $r['lat'] ?>" data-lng="<?= $r['lng'] ?>" data-fallas="<?= $r['fallas'] ?? 0 ?>"
              data-nombre="<?= htmlspecialchars($r['nombre']) ?>" data-ubic="<?= htmlspecialchars($r['ubic'] ?? '') ?>">
              <i class="bi bi-geo-alt-fill me-1"></i>
              <?= htmlspecialchars($r['nombre']) ?>
            </td>


            <td><?= htmlspecialchars($r['ubic'] ?? 'Sin ubicación') ?></td>
            <td><?= date("d-m-Y", strtotime($r['fecha_instalacion'])) ?></td>

            <td>
              <?php
              $stmtMantenimiento = $pdo->prepare("
                SELECT fecha_mantenimiento
                FROM revisiones
                WHERE arco_id = ?
                ORDER BY fecha_mantenimiento DESC
                LIMIT 1
              ");
              $stmtMantenimiento->execute([$r['id']]);
              $ultimoMantenimiento = $stmtMantenimiento->fetchColumn();
              if ($ultimoMantenimiento) {
                echo date("d-m-Y", strtotime($ultimoMantenimiento));
              } else {
                echo "<span class='text-muted'>N/A</span>";
              }
              ?>
            </td>
            <td>
              <?php
              if ($ultimoMantenimiento) {
                $proximo = date("d-m-Y", strtotime($ultimoMantenimiento . " +12 months"));
                echo $proximo;
              } else {
                echo "<span class='text-muted'>N/A</span>";
              }
              ?>
            <td>
              <button class="btn btn-sm btn-info verMaterialesBtn" data-id="<?= $r['id'] ?>"
                data-materiales='<?= htmlspecialchars(json_encode($lista), ENT_QUOTES, "UTF-8") ?>' data-bs-toggle="modal"
                data-bs-target="#modalMateriales">
                <i class="bi bi-box-seam"></i> Componentes
              </button>
            </td>

            <td class="text-center">
              <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-warning d-flex align-items-center justify-content-center editarArcoBtn"
                  data-id="<?= $r['id'] ?>" data-bs-toggle="modal" data-bs-target="#modalEditarArco" title="Editar arco">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <a href="../controllers/arcos_controller.php?action=delete&id=<?= $r['id'] ?>"
                  class="btn btn-danger d-flex align-items-center justify-content-center"
                  onclick="return confirm('¿Seguro que deseas eliminar este arco?')" title="Eliminar arco">
                  <i class="bi bi-trash-fill"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div id="pagination-Arcos" class="mt-2 d-flex justify-content-center"></div>
</div>

<!-- MODAL AGREGAR ARCO -->
<div class="modal fade" id="modalAgregarArco" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Arco</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="../controllers/arcos_controller.php">
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <input type="hidden" name="action" value="add">

          <div class="row mb-3 ">
            <div class="col-md-6">
              <label class="form-label">Nombre del Arco</label>
              <input name="nombre" class="form-control" required>
            </div>
            <div class="col-md-6 ">
              <label class="form-label">Ubicación</label>
              <select name="ubicacion_id" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($pdo->query('SELECT * FROM ubicaciones ORDER BY nombre') as $u): ?>
                  <option value="<?= $u['id'] ?>" data-lat="<?= htmlspecialchars($u['lat'] ?? '') ?>"
                    data-lng="<?= htmlspecialchars($u['lng'] ?? '') ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-md-4">
              <label class="form-label">Fecha de Instalación</label>
              <input type="date" name="fecha_instalacion" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Latitud</label>
              <input type="text" name="lat" id="latInput" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Longitud</label>
              <input type="text" name="lng" id="lngInput" class="form-control" required>
            </div>

            <div class="col-md-2 d-grid">
              <label class="form-label">&nbsp;</label>
              <button type="button" class="btn btn-outline-success abrirMapa" id="btnAbrirMapa" data-lat="latInput"
                data-lng="lngInput">
                <i class="bi bi-map"></i>
              </button>
            </div>
          </div>

          <div>
            <h5 class="text-success mb-3 text-center"><i class="bi bi-tools"></i> Materiales</h5>
            <hr>
            <button type="button"
              class="btn btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#modalRegistrarMaterial">
              ➕ Agregar material
            </button>

            
            <div> 
                <div class="row fw-bold text-muted bg-light py-2 px-2">
                  <div class="col-4">Material</div>
                  <div class="col-3">Serie</div>
                  <div class="col-3">Cantidad</div>
                  <div class="col-2 text-center">Acción</div>
                </div>

                <div id="materialesContainer" class="border rounded"></div>
            </div>
             

              <div id="resumenMateriales" class="mt-2 text-success small"></div>
            <!-- contenedor oculto real -->
           
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRegistrarMaterial" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-warning">
        <h5 class="modal-title">Agregar materiales</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="materialesContainerModal">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label>Nombre</label>
              <select name="material_id[]" class="form-select material-select" required>
                  <option value="">Seleccione material...</option>
                    <?php foreach ($pdo->query('SELECT * FROM materiales ORDER BY nombre') as $m): ?>
                        <option value="<?= $m['id'] ?>" data-medida="<?= $m['medida'] ?>">
                        <?= htmlspecialchars($m['nombre']) ?>
                  </option>
                      <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input tiene-serie" type="checkbox">
                <label class="form-check-label">
                  Tiene número de serie
                </label>
              </div>

              <div class="serie-wrapper d-none mt-2">
                <input name="material_serie[]" class="form-control" placeholder="Número de serie">
              </div>
            </div>

            <div class="col-12 col-md-3 cantidad-wrapper d-none">
              <label class="form-label">Cantidad</label>

              <div class="input-group">
                <input type="number"
                      step="any"
                      name="material_cantidad[]"
                      class="form-control text-center"
                      placeholder="0"
                      required>

                <span class="input-group-text medida-input">
                  —
                </span>
              </div>
            </div>

          </div>
        </div>
      </div>


      <div class="modal-footer">
        <button class="btn btn-secondary cerrarMaterial" id="cerrarMaterial" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" id="guardarMateriales">
          Guardar materiales
        </button>
      </div>

    </div>
  </div>
</div>

<script>
  const modalMaterial = new bootstrap.Modal(
    document.getElementById('modalRegistrarMaterial'),
    {
      backdrop: 'static',
      keyboard: false,
      focus: false   // 🔥 CLAVE
    }
  );

    // Abrir modal mapa (Agregar)
  document.querySelectorAll('#btnAbrirRegistroMaterial').forEach(btn => {
    btn.addEventListener('click', () => {
      const parentCustom = btn.closest('.custom-modal');
      if (parentCustom) {
        parentCustom.style.display = 'none';
        // Guardamos referencia para restaurarla cuando se cierre el selector
        modalMaterial._parentCustomModal = parentCustom;
      }
      modalMaterial.show();
    });
  });

  document.getElementById('guardarMateriales').addEventListener('click', () => {
    
    modalMaterial.hide();
  });


  
  // Cerrar modal
  document.querySelectorAll('.cerrarMaterial').forEach(btn => {
    btn.addEventListener('click', () => modalMaterial.hide());
  });



</script>

<!-- ===================== MODAL VER MATERIALES ===================== -->
<div class="modal" id="modalMateriales" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-box-seam"></i> Componentes del arco Registrado</h5>
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


<!-- ===================== MODAL EDITAR ARCO ===================== -->
<div class="modal fade" id="modalEditarArco" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Arco</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarArco" method="post" action="../controllers/arcos_controller.php">
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editar_id">

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Nombre del Arco</label>
              <input name="nombre" id="editar_nombre" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ubicación</label>
              <select name="ubicacion_id" id="editar_ubicacion" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($pdo->query('SELECT * FROM ubicaciones ORDER BY nombre') as $u): ?>
                  <option value="<?= $u['id'] ?>" data-lat="<?= htmlspecialchars($u['lat'] ?? '') ?>"
                    data-lng="<?= htmlspecialchars($u['lng'] ?? '') ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Fecha de Instalación</label>
              <input type="date" name="fecha_instalacion" id="editar_fecha" class="form-control" required>
            </div>


            <div class="col-md-3">
              <label class="form-label">Latitud</label>
              <input type="text" name="lat" id="editar_lat" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Longitud</label>
              <input type="text" name="lng" id="editar_lng" class="form-control">
            </div>

            <div class="col-md-2 d-grid">
              <label class="form-label">&nbsp;</label>
              <button type="button" class="btn btn-outline-warning abrirMapa" id="btnAbrirMapaEditar"
                data-lat="editar_lat" data-lng="editar_lng">
                <i class="bi bi-map"></i>
              </button>
            </div>
          </div>

          <h5 class="text-warning mb-3 text-center"><i class="bi bi-tools"></i> Materiales</h5>

          <div id="editarMaterialesContainer" class="text-center text-muted py-3"
            style="overflow-y: auto; max-height: 30vh; padding: 20px;">
            <div class="spinner-border text-warning responsive-mobile" role="status"></div>
            <p class="mt-2 mb-0">Cargando materiales...</p>
          </div>
          <button type="button" class="btn btn-outline-warning btn-sm" id="editarAddMaterial">
            <i class="bi bi-plus-lg"></i> Agregar otro material
          </button>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Actualizar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>



<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


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
        <!-- MAPA -->
        <div id="map" style="height: 400px; width: 100%;"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- ================= MODAL MAPA ARCOS EDITAR ================= -->
<div class="modal fade" id="modalMapaArcosEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">

      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">
          <i class="bi bi-map"></i> Ubicación de Arcos
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <!-- MAPA -->
        <div id="map" style="height: 400px; width: 100%;"></div>
      </div>

      <div class="modal-footer">

        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

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
        <div id="mapStatus" class="small text-muted mt-2">Estado: esperando ubicación...</div>
        <div id="mapHelp" class="small text-muted mt-1 d-none">Para permitir la ubicación, haz clic en el icono de
          candado en la barra de direcciones y habilita Permisos → Ubicación, o prueba en una ventana de
          incógnito/HTTPS.</div>

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


<script>
  function obtenerUbicacionActual(callback) {
    if (!navigator.geolocation) {
      console.warn("Geolocalización no soportada");
      callback(17.550826, -99.501462);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      pos => {
        callback(pos.coords.latitude, pos.coords.longitude);
      },
      err => {
        console.warn("Permiso denegado o error:", err.message);
        callback(17.550826, -99.501462); // fallback
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }

  // modal seleccionar ubicacion en mapa
  let mapSelector, markerSelector;
  let selectedLat = null;
  let selectedLng = null;
  let selectorInitialized = false;

  // Destinos dinámicos para lat/lng (soporta agregar o editar)
  let inputLatDestino = null;
  let inputLngDestino = null;

  const modalMapa = new bootstrap.Modal(
    document.getElementById('modalSeleccionarMapa'),
    {
      backdrop: 'static',
      keyboard: false,
      focus: false   // 🔥 CLAVE
    }
  );

  // Abrir modal mapa (Agregar)
  document.querySelectorAll('#btnAbrirMapa').forEach(btn => {
    btn.addEventListener('click', () => {
      console.log('abrirMapa click, data-lat:', btn.dataset.lat, 'data-lng:', btn.dataset.lng);
      inputLatDestino = btn.dataset.lat;
      inputLngDestino = btn.dataset.lng;

      // Si los inputs ya tienen valores (editar rápido), pre-seleccionarlos
      const latVal = document.getElementById('latInput')?.value;
      const lngVal = document.getElementById('lngInput')?.value;
      if (latVal && lngVal) {
        selectedLat = latVal;
        selectedLng = lngVal;
      } else {
        selectedLat = null;
        selectedLng = null;
      }

      const parentCustom = btn.closest('.custom-modal');
      if (parentCustom) {
        parentCustom.style.display = 'none';
        // Guardamos referencia para restaurarla cuando se cierre el selector
        modalMapa._parentCustomModal = parentCustom;
      }
      // Definir modo: editar o agregar (para personalizar el texto y el comportamiento)
      const mode = parentCustom && parentCustom.id === 'modalEditarUbicacion' ? 'editar' : 'agregar';
      modalMapa._mode = mode;

      // Cambiar estilo del header y texto según modo
      const headerEl = document.querySelector('#modalSeleccionarMapa .modal-header');
      const titleEl = document.querySelector('#modalSeleccionarMapa .modal-title');
      const acceptBtn = document.getElementById('btnAceptarUbicacion');
      const helpEl = document.getElementById('mapHelp');
      if (headerEl && titleEl && acceptBtn && helpEl) {
        if (mode === 'editar') {
          headerEl.classList.remove('bg-success', 'text-white');
          headerEl.classList.add('bg-warning', 'text-dark');
          titleEl.textContent = 'Seleccionar ubicación (Editar)';
          helpEl.textContent = 'Editar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
          acceptBtn.classList.remove('btn-success');
          acceptBtn.classList.add('btn-warning');
        } else {
          headerEl.classList.remove('bg-warning', 'text-dark');
          headerEl.classList.add('bg-success', 'text-white');
          titleEl.textContent = 'Seleccionar ubicación (Agregar)';
          helpEl.textContent = 'Agregar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
          acceptBtn.classList.remove('btn-warning');
          acceptBtn.classList.add('btn-success');
        }
      }

      modalMapa.show();
    });
  });

  // Abrir modal mapa (Editar)
  document.getElementById('btnAbrirMapaEditar')?.addEventListener('click', () => {
    inputLatDestino = 'editar_lat';
    inputLngDestino = 'editar_lng';

    const latVal = document.getElementById('editar_lat')?.value;
    const lngVal = document.getElementById('editar_lng')?.value;
    if (latVal && lngVal) {
      selectedLat = latVal;
      selectedLng = lngVal;
    } else {
      selectedLat = null;
      selectedLng = null;
    }

    modalMapa._mode = 'editar';

    const headerElE = document.querySelector('#modalSeleccionarMapa .modal-header');
    const titleElE = document.querySelector('#modalSeleccionarMapa .modal-title');
    const acceptBtnE = document.getElementById('btnAceptarUbicacion');
    const helpElE = document.getElementById('mapHelp');
    if (headerElE && titleElE && acceptBtnE && helpElE) {
      headerElE.classList.remove('bg-success', 'text-white');
      headerElE.classList.add('bg-warning', 'text-dark');
      titleElE.textContent = 'Seleccionar ubicación (Editar)';
      helpElE.textContent = 'Editar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
      acceptBtnE.classList.remove('btn-success');
      acceptBtnE.classList.add('btn-warning');
    }

    modalMapa.show();
  });

  document.getElementById('modalSeleccionarMapa')
    .addEventListener('shown.bs.modal', () => {

      // Resetear marcadores al abrir el selector para evitar problemas al colocar nuevos marcadores
      if (markerSelector && mapSelector) {
        try { mapSelector.removeLayer(markerSelector); } catch (e) { console.warn('Error al remover marcador al abrir selector:', e); }
      }
      markerSelector = null;

      if (!selectorInitialized) {

        obtenerUbicacionActual((lat, lng, error) => {

          // Si el selector ya tiene un valor preseleccionado (editar), centrar ahí
          const preLat = selectedLat || lat;
          const preLng = selectedLng || lng;

          mapSelector = L.map('mapSelector').setView([preLat, preLng], 13);

          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
          }).addTo(mapSelector);

          // Mostrar mensaje si hubo error al obtener la ubicación
          const statusEl = document.getElementById('mapStatus');
          if (error) {
            statusEl.textContent = 'Estado: ' + error;
            statusEl.classList.remove('text-muted');
            statusEl.classList.add('text-danger');
          } else {
            statusEl.textContent = 'Estado: ubicación obtenida.';
            statusEl.classList.remove('text-danger');
            statusEl.classList.add('text-success');
          }

          // Si ya había coordenadas seleccionadas (p.ej. por editar), colocar marcador
          if (selectedLat && selectedLng) {
            try { colocarMarcador(parseFloat(selectedLat), parseFloat(selectedLng)); } catch (e) { console.warn('pre-seed marker failed', e); }
          }

          // Click en mapa
          mapSelector.on('click', e => {
            colocarMarcador(e.latlng.lat, e.latlng.lng);
          });

        });

        selectorInitialized = true;
      } else {
        // Mapa ya inicializado: si hay valores preseleccionados, colocar marcador
        if (selectedLat && selectedLng) {
          try { colocarMarcador(parseFloat(selectedLat), parseFloat(selectedLng)); } catch (e) { console.warn('pre-seed marker failed', e); }
        }
      }

      setTimeout(() => mapSelector.invalidateSize(), 200);

    });

  function colocarMarcador(lat, lng) {
    selectedLat = Number(lat).toFixed(6);
    selectedLng = Number(lng).toFixed(6);

    const popupContent = `
      📍 <strong>Latitud:</strong> ${selectedLat}<br>
      📍 <strong>Longitud:</strong> ${selectedLng}
    `;

    if (!markerSelector) {
      markerSelector = L.marker([selectedLat, selectedLng], {
        draggable: true,
        autoPan: true
      }).addTo(mapSelector);

      markerSelector.on('dragend', () => {
        const pos = markerSelector.getLatLng();
        colocarMarcador(pos.lat, pos.lng);
      });
    } else {
      markerSelector.setLatLng([selectedLat, selectedLng]);
    }

    markerSelector.bindPopup(popupContent).openPopup();

    // Actualizar campos preview y destino (si existen)
    const lp = document.getElementById('latPreview');
    const lg = document.getElementById('lngPreview');
    if (lp) lp.value = selectedLat;
    if (lg) lg.value = selectedLng;

    if (inputLatDestino && document.getElementById(inputLatDestino)) document.getElementById(inputLatDestino).value = selectedLat;
    if (inputLngDestino && document.getElementById(inputLngDestino)) document.getElementById(inputLngDestino).value = selectedLng;
  }

  // ✅ Aceptar ubicación
  document.getElementById('btnAceptarUbicacion').addEventListener('click', () => {
    if (!selectedLat || !selectedLng) {
      alert('Selecciona una ubicación en el mapa');
      return;
    }

    const targetLatId = inputLatDestino || 'latInput';
    const targetLngId = inputLngDestino || 'lngInput';

    const latEl = document.getElementById(targetLatId);
    const lngEl = document.getElementById(targetLngId);
    if (latEl) latEl.value = selectedLat;
    if (lngEl) lngEl.value = selectedLng;

    // limpiar destino
    inputLatDestino = null;
    inputLngDestino = null;

    modalMapa.hide();
  });


  function solicitarPermisoDirecto() {
    const fallback = { lat: 17.550826, lng: -99.501462 };
    return new Promise(resolve => {
      if (!navigator.geolocation) {
        resolve({ lat: fallback.lat, lng: fallback.lng, error: 'Geolocalización no soportada' });
        return;
      }
      navigator.geolocation.getCurrentPosition(
        pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude, error: null }),
        err => {
          let msg = err.message || 'Error de geolocalización';
          try {
            switch (err.code) {
              case err.PERMISSION_DENIED:
                msg = 'Permiso denegado por el usuario.';
                break;
              case err.POSITION_UNAVAILABLE:
                msg = 'Posición no disponible.';
                break;
              case err.TIMEOUT:
                msg = 'Tiempo de espera agotado (timeout).';
                break;
            }
          } catch (e) { }
          resolve({ lat: fallback.lat, lng: fallback.lng, error: msg });
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  document.getElementById('btnUsarMiUbicacion').addEventListener('click', () => {
    const statusEl = document.getElementById('mapStatus');
    const helpEl = document.getElementById('mapHelp');
    statusEl.textContent = 'Estado: solicitando ubicación...';
    statusEl.classList.remove('text-danger', 'text-success');
    statusEl.classList.add('text-muted');
    helpEl.classList.add('d-none');

    const permissionQuery = (navigator.permissions && navigator.permissions.query)
      ? navigator.permissions.query({ name: 'geolocation' }).catch(() => ({ state: 'prompt' }))
      : Promise.resolve({ state: 'prompt' });

    permissionQuery.then(status => {
      console.log('permission.state =', status && status.state);
      return solicitarPermisoDirecto();
    }).then(({ lat, lng, error }) => {
      if (error) {
        statusEl.textContent = 'Estado: ' + error;
        statusEl.classList.remove('text-muted');
        statusEl.classList.add('text-danger');
        if (error.toLowerCase().includes('permiso denegado')) {
          helpEl.classList.remove('d-none');
          helpEl.textContent = 'Permiso denegado. Habilita “Ubicación” para este sitio desde la configuración del navegador (haz clic en el icono de candado en la barra de direcciones).';
        }
      } else {
        // Ubicación obtenida: inicializar o centrar mapa y colocar marcador
        statusEl.textContent = 'Estado: ubicación obtenida.';
        statusEl.classList.remove('text-danger');
        statusEl.classList.add('text-success');

        if (!selectorInitialized) {
          try {
            mapSelector = L.map('mapSelector').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap'
            }).addTo(mapSelector);
            mapSelector.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));
            selectorInitialized = true;
          } catch (err) {
            console.warn('Error al inicializar mapa desde Usar mi ubicación:', err);
          }
        } else {
          try { mapSelector.setView([lat, lng], 13); } catch (e) { console.warn('Error al centrar mapa:', e); }
        }

        try { colocarMarcador(lat, lng); } catch (e) { console.warn('Error al colocar marcador:', e); }
      }
    }).catch(err => console.warn('Error al solicitar ubicación:', err));

    // Sincronizar selects de ubicaciones con los inputs de lat/lng
    (function setupUbicacionSync() {
      // Al cambiar la ubicación en el modal Agregar, copiar coordenadas si existen
      const addSel = document.querySelector('#modalAgregarArco select[name="ubicacion_id"]');
      if (addSel) {
        addSel.addEventListener('change', function () {
          const opt = this.selectedOptions[0];
          if (!opt) return;
          const lat = opt.dataset.lat;
          const lng = opt.dataset.lng;
          if (lat !== undefined) document.getElementById('latInput').value = lat || '';
          if (lng !== undefined) document.getElementById('lngInput').value = lng || '';
        });

        // Al abrir el modal, pre-seleccionar coords si ya hay opción seleccionada
        document.getElementById('modalAgregarArco')?.addEventListener('shown.bs.modal', () => {
          const opt = addSel.selectedOptions[0];
          if (!opt) return;
          if (opt.dataset.lat) document.getElementById('latInput').value = opt.dataset.lat;
          if (opt.dataset.lng) document.getElementById('lngInput').value = opt.dataset.lng;
        });
      }

      // Para el modal Editar
      const editSel = document.querySelector('#formEditarArco select[name="ubicacion_id"]');
      if (editSel) {
        editSel.addEventListener('change', function () {
          const opt = this.selectedOptions[0];
          if (!opt) return;
          const lat = opt.dataset.lat;
          const lng = opt.dataset.lng;
          if (lat !== undefined) document.getElementById('editar_lat').value = lat || '';
          if (lng !== undefined) document.getElementById('editar_lng').value = lng || '';
        });

        document.getElementById('modalEditarArco')?.addEventListener('shown.bs.modal', () => {
          const opt = editSel.selectedOptions[0];
          if (!opt) return;
          if (opt.dataset.lat) document.getElementById('editar_lat').value = opt.dataset.lat;
          if (opt.dataset.lng) document.getElementById('editar_lng').value = opt.dataset.lng;
        });
      }
    })();
  });

  // Cerrar modal
  document.querySelectorAll('.cerrarMapa').forEach(btn => {
    btn.addEventListener('click', () => modalMapa.hide());
  });



</script>




<script src="../js/arcos2.js"></script>

<?php include('../views/footer.php'); ?>