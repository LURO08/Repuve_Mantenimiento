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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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
          onkeyup="buscarConOrden(this)">

      </div>
    </div>
  </div>

  <table id="ArcosTable" class="table table-striped align-middle mb-0">
    <thead class="table-dark text-center">
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Ubicación</th>
        <?php
        $order = $_GET['order'] ?? 'desc'; // por defecto descendente
        $newOrder = $order === 'asc' ? 'desc' : 'asc';
        ?>

        <th>
          <a href="?<?= http_build_query(array_merge($_GET, ['order' => $newOrder])) ?>"
            class="text-decoration-none text-white">

            Fecha instalación

            <?php if ($order === 'asc'): ?>
              <i class="bi bi-arrow-up"></i>
            <?php else: ?>
              <i class="bi bi-arrow-down"></i>
            <?php endif; ?>

          </a>
        </th>

        <?php
        $orderinstalacion = $_GET['orderinstalacion'] ?? 'desc'; // por defecto descendente
        $newOrderInstalacion = $orderinstalacion === 'asc' ? 'desc' : 'asc';
        ?>

        <th>
          <!-- <a href="?<?= http_build_query(array_merge($_GET, ['orderinstalacion' => $newOrderInstalacion])) ?>" class="text-decoration-none text-white">
            Ultima Mantenimiento
            <?php if ($orderinstalacion === 'asc'): ?>
              <i class="bi bi-caret-up-fill"></i>
            <?php else: ?>
              <i class="bi bi-caret-down-fill"></i>
            <?php endif; ?> -->
          Ultima Mantenimiento

        </th>
        <th>Proximo Mantenimiento</th>
        <th>Componentes</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <?php

      $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

      $stmt = $pdo->query("
    SELECT 
        a.*,
        u.nombre AS ubic,
        COUNT(r.id) AS fallas
    FROM arcos a
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN revisiones r ON r.arco_id = a.id
    GROUP BY a.id
    ORDER BY a.fecha_instalacion $order
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
                  SELECT am.*, m.nombre AS material, m.medida AS medida, m.foto AS foto, a.fecha_instalacion AS fecha_instalacion
                  FROM arco_material am
                  JOIN materiales m ON am.material_id = m.id
                  JOIN arcos a ON am.arco_id = a.id
                  WHERE am.arco_id = ?
              ");
          $mats->execute([$r['id']]);
          $materiales_actuales = $mats->fetchAll();

          $cambiados = $pdo->prepare("
              SELECT rm.*, m.nombre AS material, m.medida AS medida, m.foto AS foto, r.fecha_mantenimiento AS fecha_mantenimiento
              FROM revision_material rm
              JOIN materiales m ON rm.material_id = m.id
              JOIN revisiones r ON rm.revision_id = r.id
              WHERE r.arco_id = ?
          ");
          $cambiados->execute([$r['id']]); // 👈 importante
          $materiales_cambiados = $cambiados->fetchAll(PDO::FETCH_ASSOC);

          ?>

          <?php

          $orderfechainstalacion = ($_GET['orderinstalacion'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
          $stmtMantenimiento = $pdo->prepare("
                SELECT fecha_mantenimiento
                FROM revisiones
                WHERE arco_id = ?
                ORDER BY fecha_mantenimiento $orderfechainstalacion
                LIMIT 1
              ");


          $stmtMantenimiento->execute([$r['id']]);
          $ultimoMantenimiento = $stmtMantenimiento->fetchColumn();

          ?>
          <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
            <td class="text-primary fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalMapaArcos"
              data-lat="<?= $r['lat'] ?>" data-lng="<?= $r['lng'] ?>" data-fallas="<?= $r['fallas'] ?? 0 ?>"
              data-nombre="<?= htmlspecialchars($r['nombre']) ?>" data-ubic="<?= htmlspecialchars($r['ubic'] ?? '') ?>">


              <span class="arco-nombre">
                <i class="bi bi-geo-alt-fill me-1"></i>
                <?= htmlspecialchars($r['nombre']) ?>
              </span>
            </td>


            <td><?= htmlspecialchars($r['ubic'] ?? 'Sin ubicación') ?></td>
            <td><?= date("d-m-Y", strtotime($r['fecha_instalacion'])) ?></td>

            <td>
              <?php
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
                data-nuevos='<?= json_encode($materiales_cambiados) ?>'
                data-anteriores='<?= json_encode($materiales_actuales) ?>' data-bs-toggle="modal"
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

                <?php
                $bitExiste = $pdo->prepare("
                      SELECT id
                      FROM bitacoras_arco
                      WHERE arco_id = ?
                      LIMIT 1
                  ");
                $bitExiste->execute([$r['id']]);
                $yaExiste = $bitExiste->fetch(PDO::FETCH_ASSOC);
                ?>

                <?php if ($yaExiste): ?>
                  <a href="../views/pdf/bitacora_arco.php?id=<?= $r['id'] ?>" target="_blank"
                    class="btn btn-outline-primary btn-sm p-2">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                <?php else: ?>
                  <button class="btn btn-primary btn-sm generarBitacoraBtn p-2" data-id="<?= $r['id'] ?>"
                    data-bs-toggle="modal" data-bs-target="#modalBitacora">
                    <i class="bi bi-file-earmark-plus"></i>
                  </button>
                <?php endif; ?>
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
<div class="modal  modalAgregarArco" id="modalAgregarArco" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Arco</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="../controllers/arcos_controller.php" enctype="multipart/form-data">
        <div class="modal-body" style="  max-height: 80vh; overflow-y: auto;">
          <div class="row g-3">
            <div id="modalFormulario" class="col-12 col-lg-6 pl-3">
              <input type="hidden" name="action" value="add">

              <div class="row mb-1 ">
                <div class="col-md-11">
                  <label class="form-label">Nombre del Arco</label>
                  <input name="nombre" class="form-control" required>
                </div>
              </div>

               <div class="row mb-1">
                  <div class="col-md-6 p-2">
                    <label class="form-label">Ubicación</label>
                    <select name="ubicacion_id" class="form-select" required>
                      <option value="">Seleccione...</option>
                      <?php foreach ($pdo->query('SELECT * FROM ubicaciones ORDER BY nombre') as $u): ?>
                        <option value="<?= $u['id'] ?>" data-lat="<?= htmlspecialchars($u['lat'] ?? '') ?>"
                          data-lng="<?= htmlspecialchars($u['lng'] ?? '') ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-5 p-2">
                    <label class="form-label">Fecha de Instalación</label>
                    <div class="form-section">
                      <input type="datetime-local" name="fecha_instalacion" class="form-control" required>
                    </div>
                  </div>
              </div>

              <div class="row mb-1">
                
                <div class="col-md-4">
                  <label class="form-label">Latitud</label>
                  <div class="form-section">
                      <input type="text" name="lat" id="latInput" class="form-control" required>
                  </div>
                  
                </div>

                <div class="col-md-4">
                  <label class="form-label">Longitud</label>
                  <div class="form-section">
                      <input type="text" name="lng" id="lngInput" class="form-control" required>
                  </div>
                </div>

                <div class="col-md-3 d-grid">
                  <label class="form-label">&nbsp;</label>
                  <button type="button" class="btn btn-outline-success abrirMapa" id="btnAbrirMapa" data-lat="latInput"
                    data-lng="lngInput">
                    <i class="bi bi-map"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Materiales Agregar -->
            <div class="col-12 col-lg-6">
              <div class="col-12 d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

                <!-- Título -->
                <div class="d-flex align-items-center gap-2">
                  <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center"
                    style="width:36px; height:36px;">
                    <i class="bi bi-tools"></i>
                  </div>
                  <h5 class="mb-0 fw-semibold text-success">Materiales</h5>
                </div>

                <!-- Botón -->
                <button type="button" class="btn btn-success d-flex align-items-center gap-2 px-3 py-2 shadow-sm"
                  id="addMaterial">
                  <i class="bi bi-plus-lg"></i>
                  <span>Agregar material</span>
                </button>

              </div>
              <div id="materialesContainer" style="max-height: 50vh; overflow-y: auto;">
                <div id="materialesContainerModal"
                  class="material-row d-flex align-items-center gap-2 mb-2 bg-light p-2 rounded flex-wrap">
                  <div id="listaMaterialesAgregados"  class="materiales-grid-added">
                    <i class="bi bi-box-seam me-2"></i>
                    <span class="fw-semibold">Ningún material agregado</span>
                  </div>

              </div>
            </div>
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

<!-- MODAL AGREGAR MATERIAL -->
<!-- ===================== MODAL AGREGAR MATERIAL ===================== -->
<div id="modalAgregarMaterial" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">

      <!-- HEADER -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-box-seam me-2"></i>
          Agregar Material al Arco
        </h5>

        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
        </button>
      </div>

      <!-- BODY -->
      <div class="modal-body bg-light">
        <div class="row g-4">

          <!-- IZQUIERDA -->
          <div id="materialesColumna" class="col-12">

            <!-- ================= IZQUIERDA ================= -->

            <!-- BUSCADOR -->
            <div class="mb-4">
              <label class="form-label fw-bold text-secondary">
                <i class="bi bi-search me-1"></i>
                Buscar material
              </label>
              <input type="search" id="buscarMaterial" class="form-control form-control-lg shadow-sm"
                placeholder="Escriba el nombre del material...">
            </div>
            <!-- GRID DE MATERIALES -->
            <div class="row g-3" id="materialesGrid" style="max-height: 60vh; overflow-y: auto;">
              <?php foreach ($pdo->query('SELECT * FROM materiales ORDER BY nombre') as $m): ?>
                <div class="col-md-6 col-xl-3 material-item" data-id="<?= $m['id'] ?>"
                  data-nombre="<?= strtolower($m['nombre']) ?>" data-medida="<?= $m['medida'] ?>"
                  data-foto="<?= $m['foto'] ?>">
                  <div class="card material-card border-0 shadow-sm h-100 cursor-pointer">
                    <div class="card-body text-center p-4">
                      <!-- ICONO -->
                      <div class="mb-3">
                        <div
                          class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                          style="width:70px;height:70px;">
                          <?php if ($m['foto']): ?>
                            <img src="../uploads/materiales/<?= htmlspecialchars($m['foto']) ?>"
                              alt="<?= htmlspecialchars($m['nombre']) ?>" class="img-fluid"
                              style="max-width: 40px; max-height: 40px;">
                          <?php else: ?>
                            <i class="bi bi-box-seam text-primary" style="font-size: 24px;"></i>
                          <?php endif; ?>
                        </div>
                      </div>
                      <!-- NOMBRE -->
                      <h6 class="fw-bold mb-1">
                        <?= htmlspecialchars($m['nombre']) ?>
                      </h6>
                      <!-- MEDIDA -->
                      <small class="text-muted">
                        <?php if ($m['medida'] == 'pz'): ?>
                          Por pieza
                        <?php else: ?>
                          Medida: <?= htmlspecialchars($m['medida']) ?>
                        <?php endif; ?>
                      </small>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- ================= DERECHA ================= -->
          <div id="configuracionColumna" class="col-lg-4 d-none">
            <div id="camposDinamicos" class="card border-0 shadow-sm d-none" style="top:10px;">
              <div class="card-body">
                <!-- TITULO -->
                <div class="text-center mb-4">
                  <div
                    class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                    style="width:70px;height:70px;">
                    <i class="bi bi-sliders text-primary fs-3"></i>
                  </div>
                  <h5 class="fw-bold text-primary mb-1">
                    Configuración
                  </h5>
                  <small class="text-muted">
                    Complete los datos del material
                  </small>
                </div>
                <!-- MATERIAL SELECCIONADO -->
                <div class="alert alert-primary py-2 text-center fw-semibold" id="materialSeleccionado">
                  Ningún material seleccionado
                </div>
                <!-- CHECK SERIE -->
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="checkSerie">
                  <label class="form-check-label fw-semibold" for="checkSerie">
                    Este material tiene número de serie
                  </label>
                </div>
                <!-- INPUT SERIE -->
                <div id="serieContainer" class="d-none mb-4">
                  <label class="form-label fw-bold text-secondary">
                    <i class="bi bi-upc-scan me-1"></i>
                    Número de Serie
                  </label>
                  <input type="text" id="serieInput" class="form-control form-control-lg"
                    placeholder="Ingrese el número de serie">
                </div>
                <!-- CANTIDAD -->
                <div id="cantidadContainer" class="d-none mb-4">
                  <label class="form-label fw-bold text-secondary">
                    <i class="bi bi-rulers me-1"></i>
                    Cantidad utilizada
                  </label>
                  <div class="input-group input-group-lg">
                    <input type="number" id="cantidadInput" class="form-control" min="0.1" step="0.1"
                      placeholder="Ingrese cantidad">
                    <span class="input-group-text" id="unidadMedida">
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- FOOTER -->
      <div class="modal-footer bg-white">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="button" id="guardarMaterialModal" class="btn btn-primary px-5 shadow">
          <i class="bi bi-check-circle me-2"></i>
          Agregar Material
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== ESTILOS ===================== -->
<style>
  .material-card {
    cursor: pointer;
    transition: .2s ease;
    border: 2px solid transparent;
  }

  .material-card:hover {
    transform: translateY(-4px);
  }

  .selected-material {
    border: 2px solid #0d6efd !important;
    background: #eef5ff;
  }

  #materialesGrid::-webkit-scrollbar {
    width: 8px;
  }

  #materialesGrid::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
  }
</style>

<!-- ===================== SCRIPT ===================== -->
<!-- <script>

  let materialSeleccionado = null;

  document.addEventListener('DOMContentLoaded', () => {

    // ================= BUSCADOR =================
    document.getElementById('buscarMaterial')
      .addEventListener('keyup', function () {

        const valor = this.value.toLowerCase();

        document.querySelectorAll('.material-item')
          .forEach(item => {
            const nombre = item.dataset.nombre;
            item.style.display =
              nombre.includes(valor)
                ? ''
                : 'none';
          });

      });

    // ================= SELECCIONAR MATERIAL =================
    const materialItems = document.querySelectorAll('.material-item');
    materialItems.forEach(item => {

      item.addEventListener('click', function (e) {

        // NO DISPARAR SI HACE CLICK EN INPUTS
        if (
          e.target.closest('input') ||
          e.target.closest('button') ||
          e.target.closest('textarea')
        ) {
          return;
        }

        // limpiar selección
        const materialCards = document.querySelectorAll('.material-card');
        materialCards.forEach(card => {
          card.classList.remove(
            'selected-material',
            'border-primary',
            'border-3'
          );
        });
        // marcar seleccion
        const card = this.querySelector('.material-card');
        card.classList.add(
          'selected-material',
          'border-primary',
          'border-3'
        );

        // guardar material
        materialSeleccionado = {
          id: this.dataset.id,
          nombre: this.dataset.nombre,
          medida: this.dataset.medida
        };

        // MOSTRAR PANEL DERECHO
        const configuracionColumna = document.getElementById('configuracionColumna');
        configuracionColumna.classList.remove('d-none');

        // REDUCIR GRID
        const col = document.getElementById('materialesColumna');
        col.classList.remove('col-12');
        col.classList.add('col-lg-8');

        // mostrar área dinámica
        const camposDinamicos = document.getElementById('camposDinamicos');
        camposDinamicos.classList.remove('d-none');

        // nombre material
        const materialSeleccionadoElement = document.getElementById('materialSeleccionado');
        materialSeleccionadoElement.innerHTML = `
              <i class="bi bi-box-seam me-2"></i>
              ${materialSeleccionado.nombre}
          `;

        // reset serie
        const checkSerie = document.getElementById('checkSerie');
        checkSerie.checked = false;

        const serieContainer = document.getElementById('serieContainer');
        serieContainer.classList.add('d-none');

        const serieInput = document.getElementById('serieInput');
        serieInput.value = '';
        // cantidad
        if (materialSeleccionado.medida !== 'pz') {

          const cantidadContainer = document.getElementById('cantidadContainer');
          cantidadContainer.classList.remove('d-none');

          const unidadMedida = document.getElementById('unidadMedida');
          unidadMedida.textContent = materialSeleccionado.medida;
        } else {
          const cantidadContainer = document.getElementById('cantidadContainer');
          cantidadContainer.classList.add('d-none');
        }
      });

    });

    // ================= CHECK SERIE =================
    const checkSerie = document.getElementById('checkSerie');
    checkSerie.addEventListener('change', function () {
      const serieContainer = document.getElementById('serieContainer');
      if (this.checked) {
        serieContainer.classList.remove('d-none');
      } else {
        serieContainer.classList.add('d-none');
        const serieInput = document.getElementById('serieInput');
        serieInput.value = '';
      }
    });
    // TODO TU JS AQUÍ
  });
</script> -->


<!-- ===================== MODAL VER MATERIALES ===================== -->
<div class="modal modalverMateriales" id="modalMateriales" tabindex="-1" aria-hidden="true">
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
<div class="modal modalEditarArco" id="modalEditarArco" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Arco</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarArco" method="post" action="../controllers/arcos_controller.php"
        enctype="multipart/form-data">
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">

          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editar_id">

          <div class="row g-3">

            <!-- ================== COLUMNA IZQUIERDA (FORMULARIO) ================== -->
            <div class="col-12 col-lg-6">

              <!-- FILA 1 -->
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
                        data-lng="<?= htmlspecialchars($u['lng'] ?? '') ?>">
                        <?= htmlspecialchars($u['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- FILA 2 -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Fecha de Instalación</label>
                  <input type="datetime-local" name="fecha_instalacion" id="editar_fecha" class="form-control" required>
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

            </div>

            <!-- ================== COLUMNA DERECHA (MATERIALES) ================== -->
            <div class="col-12 col-lg-6 border-start">
              <div class="col-12 d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <!-- Título -->
                <div class="d-flex align-items-center gap-2">
                  <div class="bg-warning text-white rounded-circle d-flex justify-content-center align-items-center"
                    style="width:36px; height:36px;">
                    <i class="bi bi-tools"></i>
                  </div>
                  <h5 class="mb-0 fw-semibold text-warning">Materiales</h5>
                </div>
                <!-- Botón -->
                <button type="button"
                  class="btn btn-warning d-flex align-items-center gap-2 px-3 py-2 shadow-sm text-white"
                  id="editarAddMaterial">
                  <i class="bi bi-plus-lg"></i>
                  <span>Agregar material</span>
                </button>
              </div>

              <div id="editarMaterialesContainer" class=" text-muted py-2"
                style="overflow-y: auto; max-height: 40vh; padding: 10px;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-2 mb-0">Cargando materiales...</p>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Actualizar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalBitacora" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-4">

      <form action="../controllers/bitacora_controller.php" method="POST">
        <input type="hidden" name="arco_id" id="bitacoraArcoId">

        <!-- HEADER -->
        <div class="modal-header bg-primary text-white rounded-top-4">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-file-earmark-text me-2"></i>
            Generar Bitácora de Instalación
          </h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body px-4">

          <!-- DATOS GENERALES -->
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="fw-bold text-primary mb-3">
                Datos Generales
              </h6>

              <div class="row">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    Nombre del Encargado
                  </label>
                  <input type="text" name="encargado" class="form-control" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    Observaciones
                  </label>
                  <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- CHECKLIST -->
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="mb-3">
                <h6 class="fw-bold text-primary mb-1">
                  <i class="bi bi-check2-square me-2"></i>
                  Checklist de Instalación
                </h6>

                <small class="text-muted d-block">
                  Selecciona únicamente los conceptos que fueron realizados durante la instalación del arco.
                </small>
              </div>


              <div class="row">
                <?php
                $conceptos = $pdo->query("
                        SELECT * 
                        FROM checklist_conceptos
                        WHERE activo = 1
                        ORDER BY id ASC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                $mitad = ceil(count($conceptos) / 2);

                $columna1 = array_slice($conceptos, 0, $mitad);
                $columna2 = array_slice($conceptos, $mitad);
                ?>

                <!-- COLUMNA 1 -->
                <div class="col-md-6">
                  <?php foreach ($columna1 as $c): ?>
                    <label class="check-card mb-2">
                      <input type="checkbox" name="checklist[]" value="<?= $c['id'] ?>" class="check-input">

                      <div class="check-content">
                        <span class="check-text">
                          <?= htmlspecialchars($c['nombre']) ?>
                        </span>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>

                <!-- COLUMNA 2 -->
                <div class="col-md-6">
                  <?php foreach ($columna2 as $c): ?>
                    <label class="check-card mb-2">
                      <input type="checkbox" name="checklist[]" value="<?= $c['id'] ?>" class="check-input">

                      <div class="check-content">
                        <span class="check-text">
                          <?= htmlspecialchars($c['nombre']) ?>
                        </span>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer bg-light sticky-bottom">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>

          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-printer me-1"></i>
            Guardar y Generar PDF
          </button>
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

<!-- MODAL PARA VER MATERIAL ANTERIORES -->
<div class="modal fade" id="modalAnterior" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">Material anterior</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalAnteriorBody"></div>

    </div>
  </div>
</div>


<!-- CODIGO PARA MOSTRAR ARCO EN MAPA -->
<script>
  document.querySelectorAll('.generarBitacoraBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('bitacoraArcoId').value = this.dataset.id;
    });
  });


  let map;
  let mapInitialized = false;
  let selectedMarker = null;
  let lastSearchController = null;


  const modalMapaArcos = document.getElementById('modalMapaArcos');

  modalMapaArcos.addEventListener('show.bs.modal', function (event) {

    const trigger = event.relatedTarget;
    if (!trigger) return;

    const lat = parseFloat(trigger.getAttribute('data-lat'));
    const lng = parseFloat(trigger.getAttribute('data-lng'));
    const nombre = trigger.getAttribute('data-nombre');
    const ubic = trigger.getAttribute('data-ubic');
    const fallas = trigger.getAttribute('data-fallas');

    // Inicializar mapa una sola vez
    if (!mapInitialized) {
      map = L.map('map').setView([lat || 19.432608, lng || -99.133209], 14);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
      }).addTo(map);

      mapInitialized = true;
    }

    // Recalcular tamaño
    setTimeout(() => {
      map.invalidateSize();
    }, 200);

    const popupContent = `
            <strong>${nombre}</strong><br>
            📍 ${ubic || 'Sin ubicación'}
            <br>⚠️ Fallas: ${fallas}  <br>
        `;

    // Limpiar marcador anterior
    if (selectedMarker) {
      map.removeLayer(selectedMarker);
      selectedMarker = null;
    }

    // Colocar marcador SOLO del arco seleccionado
    if (!isNaN(lat) && !isNaN(lng)) {
      selectedMarker = L.marker([lat, lng]).addTo(map);

      selectedMarker.bindPopup(popupContent).openPopup();

      map.setView([lat, lng], 16);
    }
  });
</script>


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



  // // SCRIPT PARA MODAL AGREGAR MATERIAL DEL ARCO

  // document.addEventListener('DOMContentLoaded', function () {
  //   const contenedor = document.getElementById('contenedorMateriales');
  //   const btnAdd = document.getElementById('btnAddRow');

  //   // 1. Manejar Switch de Serie
  //   contenedor.addEventListener('change', function (e) {
  //     if (e.target.classList.contains('toggle-serie')) {
  //       const row = e.target.closest('.material-row');
  //       const inputContainer = row.querySelector('.serie-input-container');
  //       const infoText = row.querySelector('.no-serie-text');
  //       const inputField = row.querySelector('input[name="serie[]"]');

  //       if (e.target.checked) {
  //         inputContainer.classList.remove('d-none');
  //         infoText.classList.add('d-none');
  //         inputField.focus();
  //       } else {
  //         inputContainer.classList.add('d-none');
  //         infoText.classList.remove('d-none');
  //         inputField.value = '';
  //       }
  //     }
  //   });

  //   // 2. Eliminar Fila
  //   contenedor.addEventListener('click', function (e) {
  //     if (e.target.closest('.remove-material')) {
  //       const filas = contenedor.querySelectorAll('.material-row');
  //       if (filas.length > 1) {
  //         e.target.closest('.material-row').remove();
  //       } else {
  //         alert("Al menos debes dejar un material.");
  //       }
  //     }
  //   });

  //   // 3. Clonar Fila (Para agregar múltiples)
  //   btnAdd.addEventListener('click', function () {
  //     const firstRow = document.querySelector('.material-row');
  //     const newRow = firstRow.cloneNode(true);

  //     // Limpiar valores del clon
  //     newRow.querySelector('select').value = '';
  //     newRow.querySelector('input[type="text"]').value = '';
  //     newRow.querySelector('.toggle-serie').checked = false;
  //     newRow.querySelector('.serie-input-container').classList.add('d-none');
  //     newRow.querySelector('.no-serie-text').classList.remove('d-none');

  //     // ID único para el switch del clon
  //     const uniqueId = 'sw_' + Date.now();
  //     newRow.querySelector('.toggle-serie').id = uniqueId;
  //     newRow.querySelector('.form-check-label').setAttribute('for', uniqueId);

  //     contenedor.appendChild(newRow);
  //   });
  // });


</script>

<script>

  let materialSeleccionado = null;

  document.addEventListener('DOMContentLoaded', () => {

      // =========================================================
      // ELEMENTOS
      // =========================================================

      const btnAddMaterial =
          document.getElementById('addMaterial');

      const modalElement =
          document.getElementById('modalAgregarMaterial');

      const buscarMaterial =
          document.getElementById('buscarMaterial');

      const configuracionColumna =
          document.getElementById('configuracionColumna');

      const materialesColumna =
          document.getElementById('materialesColumna');

      const camposDinamicos =
          document.getElementById('camposDinamicos');

      const materialSeleccionadoText =
          document.getElementById('materialSeleccionado');

      const checkSerie =
          document.getElementById('checkSerie');

      const serieContainer =
          document.getElementById('serieContainer');

      const serieInput =
          document.getElementById('serieInput');

      const cantidadContainer =
          document.getElementById('cantidadContainer');

      const cantidadInput =
          document.getElementById('cantidadInput');

      const unidadMedida =
          document.getElementById('unidadMedida');

      // =========================================================
      // VALIDAR EXISTENCIA
      // =========================================================

      if (!btnAddMaterial || !modalElement) {
          console.warn('No existe el botón o modal');
          return;
      }

      // =========================================================
      // INSTANCIA ÚNICA DEL MODAL
      // =========================================================

      const modalAgregarMaterial =
          new bootstrap.Modal(modalElement, {
              backdrop: true,
              keyboard: true,
              focus: false
          });

      // =========================================================
      // ABRIR MODAL
      // =========================================================

      btnAddMaterial.addEventListener('click', () => {

          // LIMPIAR BUSCADOR
          buscarMaterial.value = '';

          // MOSTRAR TODOS LOS MATERIALES
          document.querySelectorAll('.material-item')
          .forEach(item => {
              item.style.display = '';
          });

          // QUITAR SELECCIÓN
          document.querySelectorAll('.material-card')
          .forEach(card => {
              card.classList.remove(
                  'selected-material',
                  'border-primary',
                  'border-3',
                  'shadow'
              );
          });

          // OCULTAR PANEL DERECHO
          configuracionColumna.classList.add('d-none');
          // EXPANDIR GRID
          materialesColumna.classList.remove('col-lg-8');
          materialesColumna.classList.add('col-12');
          // OCULTAR CARD DINÁMICA
          camposDinamicos.classList.add('d-none');
          // LIMPIAR INPUTS
          checkSerie.checked = false;
          serieContainer.classList.add('d-none');
          serieInput.value = '';
          cantidadInput.value = '';
          materialSeleccionado = null;

          // MOSTRAR MODAL
          modalAgregarMaterial.show();

      });

      // =========================================================
      // CUANDO EL MODAL YA ABRIÓ
      // =========================================================

      modalElement.addEventListener('shown.bs.modal', () => {

          setTimeout(() => {

              buscarMaterial.focus();

          }, 200);

      });

      // =========================================================
      // BUSCADOR
      // =========================================================

      buscarMaterial.addEventListener('input', function () {

          const valor = this.value.toLowerCase();

          document.querySelectorAll('.material-item')
          .forEach(item => {

              const nombre = item.dataset.nombre;

              item.style.display =
                  nombre.includes(valor)
                      ? ''
                      : 'none';

          });

      });

      // =========================================================
      // SELECCIONAR MATERIAL
      // =========================================================

      document.querySelectorAll('.material-item')
      .forEach(item => {

          item.addEventListener('click', function (e) {

              // EVITAR PROBLEMAS CON INPUTS
              if (
                  e.target.closest('input') ||
                  e.target.closest('textarea') ||
                  e.target.closest('button')
              ) {
                  return;
              }

              // LIMPIAR SELECCIÓN
              document.querySelectorAll('.material-card')
              .forEach(card => {
                  card.classList.remove(
                      'selected-material',
                      'border-primary',
                      'border-3',
                      'shadow'
                  );
              });

              // ACTIVAR CARD
              const card =
                  this.querySelector('.material-card');

              card.classList.add(
                  'selected-material',
                  'border-primary',
                  'border-3',
                  'shadow'
              );

              // GUARDAR MATERIAL
              materialSeleccionado = {
                  id: this.dataset.id,
                  nombre: this.dataset.nombre,
                  medida: this.dataset.medida,
                  foto: this.dataset.foto
              };

              // MOSTRAR PANEL DERECHO
              configuracionColumna.classList.remove('d-none');
              // REDUCIR GRID
              materialesColumna.classList.remove('col-12');
              materialesColumna.classList.add('col-lg-8');
              // MOSTRAR CAMPOS
              camposDinamicos.classList.remove('d-none');
              // NOMBRE MATERIAL
              materialSeleccionadoText.innerHTML = `
                  <i class="bi bi-box-seam me-2"></i>
                  ${materialSeleccionado.nombre}
              `;

              // RESETEAR SERIE
              checkSerie.checked = false;
              serieContainer.classList.add('d-none');
              serieInput.value = '';

              // MOSTRAR CANTIDAD SI NO ES PZ
              if (materialSeleccionado.medida !== 'pz') {
                  cantidadContainer.classList.remove('d-none');
                  unidadMedida.textContent =
                      materialSeleccionado.medida;
              } else {

                  cantidadContainer.classList.add('d-none');

                  cantidadInput.value = '';
              }
          });
      });

      // =========================================================
      // CHECK SERIE
      // =========================================================

      checkSerie.addEventListener('change', function () {
          if (this.checked) {
              serieContainer.classList.remove('d-none');
              setTimeout(() => {
                  serieInput.focus();
              }, 100);

          } else {
              serieContainer.classList.add('d-none');

              serieInput.value = '';
          }
      });

      // =========================================================
      // EVITAR QUE BOOTSTRAP BLOQUEE INPUTS
      // =========================================================

      document.addEventListener('focusin', function(e) {

          if (
              e.target.closest('#modalAgregarMaterial')
          ) {
              e.stopPropagation();
          }

      });

  });

  let materialesAgregados = [];

  // ===============================================
  // GUARDAR MATERIAL
  // ===============================================

  document.getElementById('guardarMaterialModal')
  .addEventListener('click', function(){

      // VALIDAR MATERIAL
      if(!materialSeleccionado){

          alert('Seleccione un material');

          return;
      }

      // OBTENER DATOS
      const tieneSerie =
          document.getElementById('checkSerie').checked;

      const serie =
          document.getElementById('serieInput').value.trim();

      const cantidad =
          document.getElementById('cantidadInput').value;

      // VALIDAR SERIE
      if(tieneSerie && serie === ''){

          alert('Ingrese la serie');

          return;
      }

      // VALIDAR CANTIDAD
      if (materialSeleccionado.medida !== 'pz' &&
          (
              cantidad === '' || parseFloat(cantidad) <= 0
          )
      ){

          alert('Ingrese una cantidad válida');

          return;
      }

      // CREAR OBJETO
      const material = {

          id: materialSeleccionado.id,
          nombre: materialSeleccionado.nombre,
          medida: materialSeleccionado.medida,
          serie: tieneSerie ? serie : '',
          cantidad:
              materialSeleccionado.medida !== 'pz'
              ? cantidad
              : 1,
          foto: materialSeleccionado.foto

      };

      // GUARDAR EN ARRAY
      materialesAgregados.push(material);

      // RENDER
      renderMateriales();

      // CERRAR MODAL
      const modalAddMaterial =
          bootstrap.Modal.getInstance(
              document.getElementById('modalAgregarMaterial')
          );

      modalAddMaterial.hide();

  });
  // ===============================================
  // RENDER MATERIALES
  // ===============================================

  function renderMateriales(){
    const contenedor =
        document.getElementById('listaMaterialesAgregados');

    contenedor.innerHTML = '';

    // ================= VACÍO =================
    if(materialesAgregados.length === 0){

        contenedor.innerHTML = `

            <div class="w-100">

                <div class="text-center py-5 text-muted bg-white rounded-4 border">

                    <i class="bi bi-box-seam"
                       style="font-size:65px;"></i>

                    <h5 class="mt-3 fw-bold">
                        No hay materiales agregados
                    </h5>

                    <p class="mb-0">
                        Agregue materiales al arco
                    </p>

                </div>

            </div>

        `;

        return;
    }

    // ================= GRID =================
    materialesAgregados.forEach((material, index) => {

        contenedor.innerHTML += `
        <div class="material-card-added shadow-sm">
            <button type="button"
                    class="material-delete"
                    onclick="eliminarMaterial(${index})">
                <i class="bi bi-trash"></i>
            </button>

            <div class="material-card-top">
                <div class="material-image-container">
                    ${
                        material.foto
                        ? ` <img src="../uploads/materiales/${material.foto}"
                            class="material-image">`:
                        ` <div class="material-placeholder">
                            <i class="bi bi-box-seam"></i>
                        </div>`
                      }
                </div>

                <div class="flex-grow-1">
                    <div class="material-title text-capitalize">
                        ${material.nombre}
                    </div>
                    <div class="material-subtitle">
                        ${
                            material.medida === 'pz'
                            ? 'Por pieza'
                            :  material.cantidad + ' ' + material.medida
                        }
                    </div>
                </div>
            </div>

            <!-- DATOS -->
            <div class="material-data">

                ${
                    material.serie
                    ?
                    `
                    <div class="material-chip">

                        <i class="bi bi-upc-scan text-primary"></i>

                        <span>
                            ${material.serie}
                        </span>

                    </div>
                    `
                    :
                    ''
                }

            </div>

        </div>

        `;
    });
}


  // ===============================================
  // ELIMINAR MATERIAL
  // ===============================================

  function eliminarMaterial(index){

      materialesAgregados.splice(index, 1);

      renderMateriales();
  }

</script>

<script src="../js/arcos.js"></script>

<?php include('../views/footer.php'); ?>