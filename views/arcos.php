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

<?php
$arcosCssVersion = file_exists(__DIR__ . '/../css/arcos.css') ? filemtime(__DIR__ . '/../css/arcos.css') : time();
$arcosJsVersion = file_exists(__DIR__ . '/../js/arcos.js') ? filemtime(__DIR__ . '/../js/arcos.js') : time();
?>

<link rel="stylesheet" href="../css/arcos.css?v=<?= $arcosCssVersion ?>">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="../assets/bootstrap.budle.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="text-center arcos-page-heading">
  <h1 class="fw-bold text-dark">
    <i class="bi bi-bounding-box-circles text-success"></i> Lista de Arcos
  </h1>
  <hr class="mt-2 mx-auto" style="width:60%;border-top:3px solid #28a745;">
</div>

<div class="arcos-table-switch d-flex justify-content-center mb-3">
  <div class="btn-group shadow-sm" role="group" aria-label="Cambiar tabla">
    <button type="button" class="btn btn-success active tabla-toggle-btn" data-table-view-target="tableViewArcos">
      <i class="bi bi-bounding-box-circles"></i> Arcos
    </button>
    <button type="button" class="btn btn-outline-primary tabla-toggle-btn" data-table-view-target="tableViewInfra">
      <i class="bi bi-broadcast-pin"></i> Puentes / Sitios
    </button>
  </div>
</div>

<div class="card table-responsive shadow-sm rounded arcos-table-view" id="tableViewArcos">

  <div class="interfaz card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex justify-content-between align-items-center mb-0">
      <button type="button" class="btn btn-success" id="btnModalAgregarArco"
        data-bs-toggle="modal" data-bs-target="#modalAgregarArco">
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

  <div class="tabla-scroll">
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
        COALESCE(rv.fallas, 0) AS fallas
    FROM arcos a
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN (
        SELECT arco_id, COUNT(*) AS fallas
        FROM revisiones
        GROUP BY arco_id
    ) rv ON rv.arco_id = a.id
    ORDER BY a.fecha_instalacion $order, a.id DESC
");


      $arcos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <?php if (count($arcos) === 0): ?>
        <tr>
          <td colspan="8" class="text-center text-muted py-3">
            <i class="bi bi-info-circle"></i> No hay arcos registrados.
          </td>
        </tr>

      <?php else: ?>
        <?php foreach ($arcos as $r): ?>
          <?php
          // Cargar materiales de este arco
          $mats = $pdo->prepare("
                  SELECT am.*, am.id AS relacion_id, m.nombre AS material, m.medida AS medida, m.foto AS foto, a.fecha_instalacion AS fecha_instalacion
                  FROM arco_material am
                  JOIN materiales m ON am.material_id = m.id
                  JOIN arcos a ON am.arco_id = a.id
                  WHERE am.arco_id = ?
                  ORDER BY am.id ASC
              ");
          $mats->execute([$r['id']]);
          $materiales_actuales = $mats->fetchAll();

          $cambiados = $pdo->prepare("
              SELECT rm.*, rm.id AS revision_material_id, COALESCE(rm.arco_material_id, rm.id) AS relacion_id,
                     m.nombre AS material, m.medida AS medida, m.foto AS foto, r.fecha_mantenimiento AS fecha_mantenimiento
              FROM revision_material rm
              JOIN materiales m ON rm.material_id = m.id
              JOIN revisiones r ON rm.revision_id = r.id
              WHERE r.arco_id = ?
              ORDER BY r.fecha_mantenimiento ASC, rm.id ASC
          ");
          $cambiados->execute([$r['id']]); // 👈 importante
          $materiales_cambiados = $cambiados->fetchAll(PDO::FETCH_ASSOC);

          $infraStmt = $pdo->prepare("
              SELECT
                n.id,
                n.tipo,
                n.nombre,
                n.ubicacion_id,
                u.nombre AS ubicacion,
                n.lat,
                n.lng,
                n.descripcion,
                im.id AS relacion_id,
                im.material_id,
                im.cantidad,
                im.serie,
                im.fecha_instalacion,
                m.nombre AS material,
                m.medida,
                m.foto
              FROM arco_infraestructura ai
              JOIN infraestructura_nodos n ON n.id = ai.infraestructura_id
              LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
              LEFT JOIN infraestructura_material im ON im.infraestructura_id = n.id
              LEFT JOIN materiales m ON m.id = im.material_id
              WHERE ai.arco_id = ?
              ORDER BY n.tipo ASC, n.nombre ASC, im.id ASC
          ");
          $infraStmt->execute([$r['id']]);
          $infraRows = $infraStmt->fetchAll(PDO::FETCH_ASSOC);
          $infraestructuras = [];
          foreach ($infraRows as $infraRow) {
            $infraId = (int)$infraRow['id'];
            if (!isset($infraestructuras[$infraId])) {
              $infraestructuras[$infraId] = [
                'id' => $infraId,
                'tipo' => $infraRow['tipo'],
                'nombre' => $infraRow['nombre'],
                'ubicacion_id' => $infraRow['ubicacion_id'],
                'ubicacion' => $infraRow['ubicacion'],
                'lat' => $infraRow['lat'],
                'lng' => $infraRow['lng'],
                'descripcion' => $infraRow['descripcion'],
                'materiales' => []
              ];
            }

            if (!empty($infraRow['material_id'])) {
              $infraestructuras[$infraId]['materiales'][] = [
                'relacion_id' => $infraRow['relacion_id'],
                'material_id' => $infraRow['material_id'],
                'material' => $infraRow['material'],
                'medida' => $infraRow['medida'],
                'cantidad' => $infraRow['cantidad'],
                'serie' => $infraRow['serie'],
                'foto' => $infraRow['foto'],
                'fecha_instalacion' => $infraRow['fecha_instalacion']
              ];
            }
          }
          $infraestructuras = array_values($infraestructuras);

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
          $baseProximoMantenimiento = $ultimoMantenimiento ?: ($r['fecha_instalacion'] ?? null);

          ?>
          <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
        <td class="text-primary fw-semibold cursor-pointer abrirMapaArco"
            data-lat="<?= htmlspecialchars($r['lat'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            data-lng="<?= htmlspecialchars($r['lng'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            data-fallas="<?= htmlspecialchars($r['fallas'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"
            data-nombre="<?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>"
            data-ubic="<?= htmlspecialchars($r['ubic'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            data-bs-toggle="modal"
            data-bs-target="#modalMapaArcos">

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
              if ($baseProximoMantenimiento) {
                $proximo = date("d-m-Y", strtotime($baseProximoMantenimiento . " +12 months"));
                echo $proximo;
              } else {
                echo "<span class='text-muted'>N/A</span>";
              }
              ?>
            </td>
            <td>
              <button class="btn btn-sm btn-info verMaterialesBtn"
                    data-id="<?= $r['id'] ?>"
                    data-nuevos='<?= htmlspecialchars(json_encode($materiales_cambiados, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8') ?>'
                    data-anteriores='<?= htmlspecialchars(json_encode($materiales_actuales, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8') ?>'
                    data-infraestructura='<?= htmlspecialchars(json_encode($infraestructuras, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8') ?>'
                    data-bs-toggle="modal"
                    data-bs-target="#modalMateriales">

                <i class="bi bi-box-seam"></i>
                Componentes
              </button>
            </td>

            <td class="text-center">
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-warning d-flex align-items-center justify-content-center editarArcoBtn"
                  data-id="<?= $r['id'] ?>" title="Editar arco"
                  data-bs-toggle="modal"
                  data-bs-target="#modalEditarArco">
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
                  <button type="button" class="btn btn-primary btn-sm generarBitacoraBtn p-2" data-id="<?= $r['id'] ?>"
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
  </div>

  <div id="pagination-Arcos" class="mt-2 d-flex justify-content-center"></div>
</div>

<div class="card table-responsive shadow-sm rounded arcos-table-view d-none" id="tableViewInfra">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0 fw-bold text-primary">
      <i class="bi bi-broadcast-pin"></i> Puentes / Sitios / Torres
    </h5>
    <span class="badge bg-primary">Infraestructura conectada</span>
  </div>

  <div class="tabla-scroll">
  <table class="table table-striped align-middle mb-0">
    <thead class="table-dark text-center">
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Tipo</th>
        <th>UbicaciÃ³n</th>
        <th>Arcos vinculados</th>
        <th>Componentes</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <?php
      $infraListado = $pdo->query("
        SELECT
          n.*,
          u.nombre AS ubicacion,
          COUNT(DISTINCT ai.arco_id) AS arcos_count,
          GROUP_CONCAT(DISTINCT a.nombre ORDER BY a.nombre SEPARATOR ', ') AS arcos_nombres,
          COUNT(DISTINCT im.id) AS materiales_count
        FROM infraestructura_nodos n
        LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
        LEFT JOIN arco_infraestructura ai ON ai.infraestructura_id = n.id
        LEFT JOIN arcos a ON a.id = ai.arco_id
        LEFT JOIN infraestructura_material im ON im.infraestructura_id = n.id
        GROUP BY n.id
        ORDER BY u.nombre ASC, n.tipo ASC, n.nombre ASC
      ")->fetchAll(PDO::FETCH_ASSOC);

      if (count($infraListado) === 0): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-4">
            <i class="bi bi-info-circle"></i> No hay puentes, postes o sitios registrados.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($infraListado as $infra):
          $infraMatsStmt = $pdo->prepare("
            SELECT im.id AS relacion_id, im.material_id, im.cantidad, im.serie, im.fecha_instalacion,
                   m.nombre AS material, m.medida, m.foto
            FROM infraestructura_material im
            JOIN materiales m ON m.id = im.material_id
            WHERE im.infraestructura_id = ?
            ORDER BY im.id ASC
          ");
          $infraMatsStmt->execute([$infra['id']]);
          $infraMateriales = $infraMatsStmt->fetchAll(PDO::FETCH_ASSOC);
          $infraPayload = [[
            'id' => $infra['id'],
            'tipo' => $infra['tipo'],
            'nombre' => $infra['nombre'],
            'ubicacion_id' => $infra['ubicacion_id'],
            'ubicacion' => $infra['ubicacion'],
            'lat' => $infra['lat'],
            'lng' => $infra['lng'],
            'descripcion' => $infra['descripcion'],
            'materiales' => $infraMateriales
          ]];
        ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($infra['id']) ?></td>
            <td><?= htmlspecialchars($infra['nombre']) ?></td>
            <td><span class="badge bg-primary"><?= htmlspecialchars($infra['tipo']) ?></span></td>
            <td><?= htmlspecialchars($infra['ubicacion'] ?? 'Sin ubicaciÃ³n') ?></td>
            <td>
              <span class="badge bg-secondary"><?= (int)$infra['arcos_count'] ?></span>
              <small class="d-block text-muted"><?= htmlspecialchars($infra['arcos_nombres'] ?? 'Sin arcos vinculados') ?></small>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-info verInfraComponentesBtn"
                data-infraestructura='<?= htmlspecialchars(json_encode($infraPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8') ?>'
                data-bs-toggle="modal" data-bs-target="#modalMateriales">
                <i class="bi bi-box-seam"></i> <?= (int)$infra['materiales_count'] ?>
              </button>
            </td>
            <td>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-warning editarInfraBtn"
                  data-id="<?= htmlspecialchars($infra['id'], ENT_QUOTES, 'UTF-8') ?>"
                  data-bs-toggle="modal" data-bs-target="#modalEditarInfraestructura"
                  title="Editar puente/sitio">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <a href="../controllers/arcos_controller.php?action=delete_infra&id=<?= htmlspecialchars($infra['id'], ENT_QUOTES, 'UTF-8') ?>"
                  class="btn btn-danger"
                  onclick="return confirm('Â¿Seguro que deseas eliminar este puente/sitio y sus mantenimientos?')"
                  title="Eliminar puente/sitio">
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
</div>

<!-- MODAL AGREGAR ARCO -->
<div class="modal fade modalAgregarArco" id="modalAgregarArco" tabindex="-1" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Arco</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="../controllers/arcos_controller.php" enctype="multipart/form-data">
        <div class="modal-body" style="  max-height: 80vh; overflow-y: auto;">
          <div class="row g-3">
            <div id="modalFormulario" class="col-12 col-lg-6">
              <input type="hidden" name="action" value="add">

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="checkPuenteSitio" name="es_infraestructura" value="1">
                <label class="form-check-label fw-semibold" for="checkPuenteSitio">
                  Registrar como Puente/Sitio
                </label>
              </div>

              <div id="camposPuenteSitio" class="row mb-3 d-none">
                <div class="col-md-5">
                  <label class="form-label">Tipo</label>
                  <select name="infra_tipo_principal" id="infraTipoPrincipal" class="form-select">
                    <option value="Puente/Poste">Puente/Poste</option>
                    <option value="Sitio/Torre">Sitio/Torre</option>
                  </select>
                </div>
                <div class="col-md-7 d-none" id="infraArcosGroup">
                  <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label mb-0">Arcos vinculados</label>
                    <span class="badge bg-primary" id="infraArcosSeleccionados">0 seleccionados</span>
                  </div>
                  <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="buscarInfraArcos" placeholder="Buscar arco...">
                  </div>
                  <div id="infraArcosVinculados" class="infra-arcos-selector">
                    <?php foreach ($pdo->query('SELECT id, nombre, ubicacion_id FROM arcos ORDER BY nombre') as $arcoOption): ?>
                      <label class="infra-arco-option" data-ubicacion-id="<?= htmlspecialchars($arcoOption['ubicacion_id'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox" class="form-check-input infra-arco-check"
                          name="infra_arcos_vinculados[]"
                          value="<?= htmlspecialchars($arcoOption['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <span><?= htmlspecialchars($arcoOption['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                      </label>
                    <?php endforeach; ?>
                    <div class="infra-arcos-empty" id="infraArcosEmpty">
                      Seleccione una ubicaciÃ³n para ver los arcos.
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mb-3 ">
                <div class="col-md-4">
                  <label class="form-label" id="nombrePrincipalLabel">Nombre del Arco</label>
                  <input name="nombre" id="nombrePrincipalInput" class="form-control" required>
                </div>
                <div class="col-md-4 " id="ubicacionPrincipalGroup">
                  <label class="form-label">Ubicación</label>
                  <select name="ubicacion_id" id="ubicacionPrincipalSelect" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($pdo->query('SELECT * FROM ubicaciones ORDER BY nombre') as $u): ?>
                      <option value="<?= $u['id'] ?>" data-lat="<?= htmlspecialchars($u['lat'] ?? '') ?>"
                        data-lng="<?= htmlspecialchars($u['lng'] ?? '') ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4 d-none" id="tipoPuenteSitioGroup"></div>
              </div>

              <div class="row mb-1" id="filaFechaCoordenadas">
                <div class="col-md-4">
                  <label class="form-label">Fecha de Instalación</label>
                  <input type="datetime-local" name="fecha_instalacion" class="form-control" required>
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
                     id="btnAgregarMaterial">
                  <i class="bi bi-plus-lg"></i>
                  <span>Agregar material</span>
                </button>

              </div>
              <div id="materialesContainer" class="materiales-container-added">
                <div id="listaMaterialesAgregados" class="materiales-grid-added">
                  <div class="empty-materials-state">
                    <i class="bi bi-box-seam"></i>
                    <span class="fw-semibold">Ningún material agregado</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 d-none">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-3 border-top pt-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center"
                    style="width:36px; height:36px;">
                    <i class="bi bi-broadcast-pin"></i>
                  </div>
                  <h5 class="mb-0 fw-semibold text-primary">Puentes / Sitios</h5>
                </div>

                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 shadow-sm"
                  id="btnAgregarInfraestructura">
                  <i class="bi bi-plus-lg"></i>
                  <span>Agregar puente o sitio</span>
                </button>
              </div>

              <div id="listaInfraestructurasArco" class="infra-list">
                <div class="empty-materials-state">
                  <i class="bi bi-broadcast-pin"></i>
                  <span class="fw-semibold">NingÃºn puente o sitio agregado</span>
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

<template id="infraMaterialOptionsTemplate">
  <option value="">Seleccione material...</option>
  <?php foreach ($pdo->query('SELECT * FROM materiales ORDER BY nombre') as $m): ?>
    <option value="<?= htmlspecialchars($m['id'], ENT_QUOTES, 'UTF-8') ?>"
      data-medida="<?= htmlspecialchars($m['medida'], ENT_QUOTES, 'UTF-8') ?>"
      data-foto="<?= htmlspecialchars($m['foto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>
    </option>
  <?php endforeach; ?>
</template>

<datalist id="infraNodosExistentes">
  <?php foreach ($pdo->query('SELECT tipo, nombre FROM infraestructura_nodos ORDER BY tipo, nombre') as $infra): ?>
    <option value="<?= htmlspecialchars($infra['nombre'], ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($infra['tipo'], ENT_QUOTES, 'UTF-8') ?>
    </option>
  <?php endforeach; ?>
</datalist>

<!-- MODAL AGREGAR MATERIAL -->
<!-- ===================== MODAL AGREGAR MATERIAL ===================== -->
<div id="modalAgregarMaterial" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <!-- HEADER -->
      <div class="modal-header bg-success text-white">
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
                <div class="col-md-6 col-xl-3 material-item" data-id="<?= htmlspecialchars($m['id'], ENT_QUOTES, 'UTF-8') ?>"
                  data-nombre="<?= htmlspecialchars(strtolower($m['nombre']), ENT_QUOTES, 'UTF-8') ?>" data-medida="<?= htmlspecialchars($m['medida'], ENT_QUOTES, 'UTF-8') ?>"
                  data-label="<?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                  data-foto="<?= htmlspecialchars($m['foto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                    class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                    style="width:70px;height:70px;">
                    <i class="bi bi-sliders text-success fs-3"></i>
                  </div>
                  <h5 class="fw-bold text-success mb-1">
                    Configuración
                  </h5>
                  <small class="text-muted">
                    Complete los datos del material
                  </small>
                </div>
                <!-- MATERIAL SELECCIONADO -->
                <div class="alert alert-success py-2 text-center fw-semibold" id="materialSeleccionado">
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
        <button type="button" id="guardarMaterialModal" class="btn btn-success px-5 shadow">
          <i class="bi bi-check-circle me-2"></i>
          Agregar Material
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL VER MATERIALES ===================== -->
<div class="modal fade" id="modalMateriales" tabindex="-1"  aria-hidden="true">
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

<!-- ===================== MODAL EDITAR PUENTE / SITIO ===================== -->
<div class="modal fade" id="modalEditarInfraestructura" tabindex="-1" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-broadcast-pin"></i> Editar Puente / Sitio / Torre</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="post" action="../controllers/arcos_controller.php" id="formEditarInfraestructura">
        <input type="hidden" name="action" value="update_infra">
        <input type="hidden" name="id" id="editarInfraId">

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Nombre</label>
              <input type="text" name="nombre" id="editarInfraNombre" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">UbicaciÃ³n</label>
              <select name="ubicacion_id" id="editarInfraUbicacion" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($pdo->query('SELECT * FROM ubicaciones ORDER BY nombre') as $u): ?>
                  <option value="<?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tipo</label>
              <select name="tipo" id="editarInfraTipo" class="form-select" required>
                <option value="Puente/Poste">Puente/Poste</option>
                <option value="Sitio/Torre">Sitio/Torre</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Latitud</label>
              <input type="text" name="lat" id="editarInfraLat" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Longitud</label>
              <input type="text" name="lng" id="editarInfraLng" class="form-control">
            </div>
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">Arcos vinculados</label>
                <span class="badge bg-primary" id="editarInfraArcosCount">0 seleccionados</span>
              </div>
              <input type="search" class="form-control form-control-sm mb-2" id="buscarEditarInfraArcos" placeholder="Buscar arco...">
              <div id="editarInfraArcosLista" class="infra-arcos-selector">
                <?php foreach ($pdo->query('SELECT id, nombre, ubicacion_id FROM arcos ORDER BY nombre') as $arcoOption): ?>
                  <label class="infra-arco-option" data-ubicacion-id="<?= htmlspecialchars($arcoOption['ubicacion_id'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="checkbox" class="form-check-input editar-infra-arco-check"
                      name="arcos_vinculados[]"
                      value="<?= htmlspecialchars($arcoOption['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($arcoOption['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                  </label>
                <?php endforeach; ?>
                <div class="infra-arcos-empty d-none" id="editarInfraArcosEmpty">No hay arcos para esta ubicaciÃ³n.</div>
              </div>
            </div>

              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">Componentes</label>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditarInfraAddMaterial">
                  <i class="bi bi-plus-lg"></i> Material
                </button>
              </div>
              <div id="editarInfraMateriales" class="materiales-grid-added"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar cambios</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===================== MODAL EDITAR ARCO ===================== -->
<div class="modal fade modalEditarArco" id="modalEditarArco" tabindex="-1" aria-hidden="true">
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
                  id="editarAddMaterial"
                  data-material-context="editar">
                  <i class="bi bi-plus-lg"></i>
                  <span>Agregar material</span>
                </button>
              </div>

              <div id="editarMaterialesContainer" class="materiales-container-added">
                <div id="listaMaterialesEditar" class="materiales-grid-added">
                  <div class="empty-materials-state">
                    <div class="spinner-border text-warning" role="status"></div>
                    <span class="fw-semibold">Cargando materiales...</span>
                  </div>
                </div>
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

<div class="modal fade" id="modalBitacora" tabindex="-1" aria-hidden="true">
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
        <div id="mapEditar" style="height: 400px; width: 100%;"></div>
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
  const modalAgregarMaterial =
  document.getElementById("modalAgregarMaterial");

  if (modalAgregarMaterial) {

      modalAgregarMaterial.addEventListener("shown.bs.modal", () => {

          const buscar =
          document.getElementById("buscarMaterial");

          if (buscar) {
              buscar.focus();
          }

      });

  }

</script>


<script src="../js/arcos.js?v=<?= $arcosJsVersion ?>"></script>

<?php include('../views/footer.php'); ?>
