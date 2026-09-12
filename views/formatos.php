<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/formatos_mantenimiento_schema.php';

$formatos = require '../config/formatos_servicio.php';
asegurarTablaFormatosMantenimiento($pdo);

$ubicacionId = (int)($_GET['ubicacion_id'] ?? 0);
$arcoId = (int)($_GET['arco_id'] ?? 0);
$infraId = (int)($_GET['infraestructura_id'] ?? 0);

// Obtener todas las ubicaciones
$ubicaciones = $pdo->query("SELECT id, nombre FROM ubicaciones ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los arcos y nodos de infraestructura
$objetivos = $pdo->query("
    SELECT a.id, a.nombre, a.ubicacion_id, COALESCE(u.nombre, '') AS ubicacion_nombre, 'arco' AS tipo_entidad
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
    UNION ALL
    SELECT n.id, n.nombre, n.ubicacion_id, COALESCE(u.nombre, '') AS ubicacion_nombre, 'infra' AS tipo_entidad
    FROM infraestructura_nodos n
    LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
    ORDER BY ubicacion_nombre ASC, nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Si se recibe un arco o sitio específico pero no la ubicación, auto-detectarla
if ($arcoId > 0 && $ubicacionId <= 0) {
    foreach ($objetivos as $obj) {
        if ($obj['tipo_entidad'] === 'arco' && (int)$obj['id'] === $arcoId) {
            $ubicacionId = (int)$obj['ubicacion_id'];
            break;
        }
    }
} elseif ($infraId > 0 && $ubicacionId <= 0) {
    foreach ($objetivos as $obj) {
        if ($obj['tipo_entidad'] === 'infra' && (int)$obj['id'] === $infraId) {
            $ubicacionId = (int)$obj['ubicacion_id'];
            break;
        }
    }
}

// Obtener todos los formatos guardados en el sistema con su información completa
$todosFormatos = $pdo->query("
    SELECT
        fm.id,
        fm.tipo,
        COALESCE(fm.arco_id, r.arco_id) AS arco_id,
        COALESCE(fm.infraestructura_id, ir.infraestructura_id) AS infraestructura_id,
        fm.revision_id,
        fm.infraestructura_revision_id,
        fm.creado_por,
        fm.created_at,
        COALESCE(fm.datos->>'fecha_servicio', r.fecha_mantenimiento::text, ir.fecha_mantenimiento::text, fm.created_at::text) AS fecha_servicio,
        COALESCE(tf.nombre, tr.nombre, tir.nombre, fm.datos->>'tecnico', '') AS tecnico_nombre,
        COALESCE(a.nombre, n.nombre, fm.datos->>'arco', 'Arco / Sitio') AS objetivo_nombre,
        COALESCE(u.id, un.id, 0) AS ubicacion_id,
        COALESCE(u.nombre, un.nombre, fm.datos->>'ubicacion', 'Sin ubicación') AS ubicacion_nombre,
        CASE WHEN fm.infraestructura_id IS NOT NULL OR fm.infraestructura_revision_id IS NOT NULL OR ir.id IS NOT NULL THEN 'infra' ELSE 'arco' END AS tipo_entidad
    FROM formatos_mantenimiento fm
    LEFT JOIN revisiones r ON r.id = fm.revision_id
    LEFT JOIN arcos a ON a.id = COALESCE(fm.arco_id, r.arco_id)
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN infraestructura_revisiones ir ON ir.id = fm.infraestructura_revision_id
    LEFT JOIN infraestructura_nodos n ON n.id = COALESCE(fm.infraestructura_id, ir.infraestructura_id)
    LEFT JOIN ubicaciones un ON un.id = n.ubicacion_id
    LEFT JOIN tecnicos tf ON tf.id = fm.tecnico_id
    LEFT JOIN tecnicos tr ON tr.id = r.tecnico_id
    LEFT JOIN tecnicos tir ON tir.id = ir.tecnico_id
    ORDER BY COALESCE(fm.datos->>'fecha_servicio', fm.created_at::text) DESC, fm.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$formatosJsVersion = file_exists(__DIR__ . '/../css/formatos.css') ? filemtime(__DIR__ . '/../css/formatos.css') : time();
?>

<link rel="stylesheet" href="../css/formatos.css?v=<?= $formatosJsVersion ?>">

<main class="formats-page">
  <!-- ENCABEZADO PRINCIPAL -->
  <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pt-2">
    <div class="d-flex align-items-center gap-3">
      <div class="formats-heading__icon shadow-xs">
        <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
      </div>
      <div>
        <h1 class="fs-4 fw-bold mb-0 text-dark">Formatos de Servicio</h1>
        <p class="text-muted small mb-0">Consulta, filtrado por ubicación y descarga de formatos por arco.</p>
      </div>
    </div>

    <!-- ACCIONES DE CABECERA (PLANTILLAS EN BLANCO) -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle shadow-xs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-file-earmark-arrow-down me-1"></i> Plantillas en blanco (DOCX)
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <?php foreach ($formatos as $fKey => $fCfg): ?>
          <li>
            <a class="dropdown-item small d-flex align-items-center gap-2" href="../controllers/formatos_controller.php?action=download_blank&type=<?= urlencode($fKey) ?>">
              <i class="bi <?= htmlspecialchars($fCfg['icon']) ?> text-secondary"></i>
              <span><?= htmlspecialchars($fCfg['title']) ?></span>
              <span class="badge bg-light text-muted border ms-auto"><?= htmlspecialchars($fCfg['code']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </header>

  <!-- PANEL DE FILTRADO Y DESCARGA POR ARCO -->
  <section class="card border shadow-xs mb-3 p-3 bg-white rounded-3">
    <div class="row g-2 align-items-end">
      <!-- FILTRO UBICACIÓN -->
      <div class="col-12 col-md-3">
        <label for="filtroUbicacion" class="form-label fw-bold small text-secondary mb-1">
          <i class="bi bi-geo-alt-fill me-1 text-danger"></i> Ubicación
        </label>
        <select id="filtroUbicacion" class="form-select form-select-sm">
          <option value="">Todas las ubicaciones</option>
          <?php foreach ($ubicaciones as $ub): ?>
            <option value="<?= htmlspecialchars((string)$ub['id']) ?>" <?= (int)$ub['id'] === $ubicacionId ? 'selected' : '' ?>>
              <?= htmlspecialchars($ub['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- FILTRO ARCO / SITIO -->
      <div class="col-12 col-md-4">
        <label for="filtroArco" class="form-label fw-bold small text-secondary mb-1">
          <i class="bi bi-diagram-3-fill me-1 text-success"></i> Arco / Sitio
        </label>
        <select id="filtroArco" class="form-select form-select-sm">
          <option value="">Todos los arcos y sitios</option>
          <?php foreach ($objetivos as $obj): ?>
            <?php
              $val = $obj['tipo_entidad'] . '_' . $obj['id'];
              $isSelected = ($obj['tipo_entidad'] === 'arco' && (int)$obj['id'] === $arcoId)
                         || ($obj['tipo_entidad'] === 'infra' && (int)$obj['id'] === $infraId);
              $tagTipo = $obj['tipo_entidad'] === 'infra' ? '[Sitio] ' : '[Arco] ';
            ?>
            <option value="<?= htmlspecialchars($val) ?>"
                    data-location-id="<?= htmlspecialchars((string)$obj['ubicacion_id']) ?>"
                    data-tipo="<?= htmlspecialchars($obj['tipo_entidad']) ?>"
                    data-id="<?= htmlspecialchars((string)$obj['id']) ?>"
                    data-nombre="<?= htmlspecialchars($obj['nombre']) ?>"
                    <?= $isSelected ? 'selected' : '' ?>>
              <?= htmlspecialchars($tagTipo . $obj['nombre'] . ' (' . $obj['ubicacion_nombre'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- BUSCADOR RÁPIDO -->
      <div class="col-12 col-md-3">
        <label for="buscadorFormatos" class="form-label fw-bold small text-secondary mb-1">
          <i class="bi bi-search me-1 text-primary"></i> Búsqueda rápida
        </label>
        <div class="input-group input-group-sm">
          <input type="text" id="buscadorFormatos" class="form-control" placeholder="Buscar por arco, técnico, fecha...">
          <button class="btn btn-outline-secondary" type="button" id="btnBorrarBusqueda" title="Borrar búsqueda">
            <i class="bi bi-x"></i>
          </button>
        </div>
      </div>

      <!-- BOTONES DE ACCIÓN DE FILTRO -->
      <div class="col-12 col-md-2 d-flex gap-1">
        <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary btn-sm flex-fill" title="Restablecer filtros">
          <i class="bi bi-arrow-counterclockwise"></i> Limpiar
        </button>
      </div>
    </div>

    <!-- BOTÓN DESCARGA MASIVA DEL ARCO -->
    <div id="panelDescargaArco" class="mt-2.5 pt-2 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="small text-muted">
        <i class="bi bi-info-circle me-1 text-info"></i>
        <span id="labelEstadoFiltro">Mostrando todos los formatos disponibles.</span>
      </div>
      <button type="button" id="btnDescargarTodosArco" class="btn btn-danger btn-sm fw-bold shadow-xs px-3" disabled>
        <i class="bi bi-download me-1"></i> Descargar formatos de este arco (<span id="countDescargaArco">0</span>)
      </button>
    </div>
  </section>

  <!-- CATÁLOGO DE FORMATOS GENERADOS -->
  <section class="saved-formats border-0 p-0 bg-transparent">
    <div class="saved-formats__heading d-flex justify-content-between align-items-center mb-2.5 px-1">
      <div>
        <h2 class="fs-5 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-ruled-fill text-success"></i> Formatos Generados
        </h2>
      </div>
      <span id="contadorFormatosBadge" class="badge bg-secondary px-2.5 py-1.5 fs-7 shadow-xs">
        <?= count($todosFormatos) ?> archivo(s)
      </span>
    </div>

    <!-- MENSAJE SIN RESULTADOS -->
    <div id="sinFormatosMensaje" class="saved-formats__empty py-5 text-center bg-white border rounded-3 shadow-xs <?= empty($todosFormatos) ? '' : 'd-none' ?>">
      <i class="bi bi-folder-x fs-1 text-secondary d-block mb-2"></i>
      <h5 class="fw-bold text-secondary mb-1">No se encontraron formatos</h5>
      <p class="small text-muted mb-0">No hay formatos generados que coincidan con la ubicación o arco seleccionados.</p>
    </div>

    <!-- LISTA DE TARJETAS DE FORMATOS -->
    <div id="listaFormatosGuardados" class="saved-formats__list">
      <?php foreach ($todosFormatos as $guardado): ?>
        <?php
          $savedConfig = $formatos[$guardado['tipo']] ?? null;
          if (!$savedConfig) continue;
          $fechaFormatoRaw = !empty($guardado['fecha_servicio']) ? $guardado['fecha_servicio'] : $guardado['created_at'];
          $fechaFormatoTs = strtotime($fechaFormatoRaw);
          $fechaMostrar = $fechaFormatoTs ? date('d/m/Y H:i', $fechaFormatoTs) : htmlspecialchars($fechaFormatoRaw);

          $colorIcono = match($guardado['tipo']) {
              'checklist' => 'text-success',
              'quality'   => 'text-primary',
              'tools'     => 'text-warning',
              default     => 'text-danger'
          };

          $bgIcono = match($guardado['tipo']) {
              'checklist' => 'bg-success-subtle',
              'quality'   => 'bg-primary-subtle',
              'tools'     => 'bg-warning-subtle',
              default     => 'bg-danger-subtle'
          };

          $pdfUrl = "../controllers/formato_servicio_pdf.php?id=" . $guardado['id'];
          $pdfDownloadUrl = "../controllers/formato_servicio_pdf.php?id=" . $guardado['id'] . "&download=1";

          $targetIdKey = $guardado['tipo_entidad'] === 'infra' ? 'infra_' . $guardado['infraestructura_id'] : 'arco_' . $guardado['arco_id'];
        ?>
        <div class="saved-format-card d-flex flex-column justify-content-between p-3 border rounded-3 bg-white shadow-xs"
             data-id="<?= htmlspecialchars((string)$guardado['id']) ?>"
             data-tipo="<?= htmlspecialchars($guardado['tipo']) ?>"
             data-location-id="<?= htmlspecialchars((string)$guardado['ubicacion_id']) ?>"
             data-target-key="<?= htmlspecialchars($targetIdKey) ?>"
             data-target-tipo="<?= htmlspecialchars($guardado['tipo_entidad']) ?>"
             data-target-id="<?= htmlspecialchars((string)($guardado['tipo_entidad'] === 'infra' ? $guardado['infraestructura_id'] : $guardado['arco_id'])) ?>"
             data-pdf-url="<?= htmlspecialchars($pdfUrl) ?>"
             data-pdf-download="<?= htmlspecialchars($pdfDownloadUrl) ?>"
             data-search-text="<?= htmlspecialchars(mb_strtolower($savedConfig['title'] . ' ' . $guardado['objetivo_nombre'] . ' ' . $guardado['ubicacion_nombre'] . ' ' . $guardado['tecnico_nombre'] . ' ' . $fechaMostrar)) ?>">
          
          <!-- Top: Icono, Título y Código -->
          <div class="d-flex align-items-start gap-2.5 mb-2.5">
            <div class="p-2 rounded-2 <?= $bgIcono ?> <?= $colorIcono ?> fs-4 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
              <i class="bi <?= htmlspecialchars($savedConfig['icon'] ?? 'bi-file-earmark-pdf') ?>"></i>
            </div>
            <div class="min-w-0 flex-grow-1">
              <div class="d-flex justify-content-between align-items-start gap-1">
                <strong class="d-block text-truncate fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.25;">
                  <?= htmlspecialchars($savedConfig['title']) ?>
                </strong>
                <span class="badge bg-light text-muted border text-nowrap" style="font-size: 0.68rem;">
                  <?= htmlspecialchars($savedConfig['code'] ?? '') ?>
                </span>
              </div>
              <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                <span class="badge bg-light text-dark border fw-semibold">
                  <i class="bi <?= $guardado['tipo_entidad'] === 'infra' ? 'bi-broadcast-pin text-primary' : 'bi-diagram-3 text-success' ?> me-1"></i>
                  <?= htmlspecialchars($guardado['objetivo_nombre']) ?>
                </span>
                <span class="badge bg-light text-secondary border">
                  <i class="bi bi-geo-alt-fill text-danger me-0.5"></i> <?= htmlspecialchars($guardado['ubicacion_nombre']) ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Mid: Metadatos (Fecha y Técnico) -->
          <div class="p-2 rounded-2 bg-light border border-light-subtle small mb-3 text-secondary" style="font-size: 0.8rem;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span><i class="bi bi-calendar3 me-1 text-primary"></i> <strong>Fecha:</strong> <?= $fechaMostrar ?></span>
            </div>
            <?php if (!empty($guardado['tecnico_nombre'])): ?>
              <div class="text-truncate">
                <i class="bi bi-person-fill me-1 text-secondary"></i> <strong>Técnico:</strong> <?= htmlspecialchars($guardado['tecnico_nombre']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Bottom: Acciones de Ver y Descargar -->
          <div class="d-flex gap-2 mt-auto pt-2 border-top">
            <a href="<?= $pdfUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm flex-fill fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-xs">
              <i class="bi bi-eye"></i> Ver PDF
            </a>
            <a href="<?= $pdfDownloadUrl ?>" class="btn btn-danger btn-sm flex-fill fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-xs">
              <i class="bi bi-download"></i> Descargar
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const selectUbicacion = document.getElementById('filtroUbicacion');
  const selectArco = document.getElementById('filtroArco');
  const inputBusqueda = document.getElementById('buscadorFormatos');
  const btnBorrarBusqueda = document.getElementById('btnBorrarBusqueda');
  const btnLimpiar = document.getElementById('btnLimpiarFiltros');
  const btnDescargarTodos = document.getElementById('btnDescargarTodosArco');
  const countDescargaSpan = document.getElementById('countDescargaArco');
  const contadorBadge = document.getElementById('contadorFormatosBadge');
  const labelEstadoFiltro = document.getElementById('labelEstadoFiltro');
  const emptyMessage = document.getElementById('sinFormatosMensaje');

  const arcOptions = selectArco ? [...selectArco.querySelectorAll('option[data-location-id]')].map(opt => opt.cloneNode(true)) : [];

  // Filtrar las opciones del selector de arcos según la ubicación
  function filterArcOptions() {
    if (!selectUbicacion || !selectArco) return;
    const selectedLocation = selectUbicacion.value;
    const currentArcValue = selectArco.value;

    selectArco.innerHTML = `<option value="">${selectedLocation ? 'Todos los arcos y sitios de esta ubicación' : 'Todos los arcos y sitios'}</option>`;

    arcOptions.forEach(opt => {
      if (!selectedLocation || opt.dataset.locationId === selectedLocation) {
        selectArco.appendChild(opt.cloneNode(true));
      }
    });

    if ([...selectArco.options].some(opt => opt.value === currentArcValue)) {
      selectArco.value = currentArcValue;
    } else {
      selectArco.value = '';
    }
  }

  // Filtrar tarjetas de formatos en tiempo real
  function applyFilters() {
    const selectedLocation = selectUbicacion.value;
    const selectedArcVal = selectArco.value;
    const query = (inputBusqueda.value || '').trim().toLowerCase();

    const cards = document.querySelectorAll('.saved-format-card');
    let visibleCount = 0;
    const visibleDownloadUrls = [];

    cards.forEach(card => {
      const cardLocId = card.dataset.locationId;
      const cardTargetKey = card.dataset.targetKey;
      const searchText = card.dataset.searchText || '';

      const matchLocation = !selectedLocation || cardLocId === selectedLocation;
      const matchArc = !selectedArcVal || cardTargetKey === selectedArcVal;
      const matchSearch = !query || searchText.includes(query);

      if (matchLocation && matchArc && matchSearch) {
        card.classList.remove('d-none');
        visibleCount++;
        if (card.dataset.pdfDownload) {
          visibleDownloadUrls.push(card.dataset.pdfDownload);
        }
      } else {
        card.classList.add('d-none');
      }
    });

    // Actualizar badge de contador
    if (contadorBadge) {
      contadorBadge.textContent = `${visibleCount} archivo(s)`;
    }

    // Mostrar/ocultar mensaje de sin resultados
    if (emptyMessage) {
      emptyMessage.classList.toggle('d-none', visibleCount > 0);
    }

    // Actualizar botón de descarga del arco seleccionado
    if (btnDescargarTodos) {
      const hasSelectedArc = Boolean(selectedArcVal);
      btnDescargarTodos.disabled = !hasSelectedArc || visibleCount === 0;
      if (countDescargaSpan) {
        countDescargaSpan.textContent = hasSelectedArc ? visibleCount : '0';
      }
      btnDescargarTodos.dataset.urls = JSON.stringify(visibleDownloadUrls);
    }

    // Actualizar texto de estado
    if (labelEstadoFiltro) {
      if (selectedArcVal) {
        const selectedOpt = selectArco.options[selectArco.selectedIndex];
        labelEstadoFiltro.innerHTML = `Mostrando formatos de <strong>${selectedOpt?.text || 'arco seleccionado'}</strong> (${visibleCount} encontrados).`;
      } else if (selectedLocation) {
        const selectedLocOpt = selectUbicacion.options[selectUbicacion.selectedIndex];
        labelEstadoFiltro.innerHTML = `Filtrado por ubicación <strong>${selectedLocOpt?.text || ''}</strong> (${visibleCount} encontrados).`;
      } else {
        labelEstadoFiltro.textContent = `Mostrando todos los formatos disponibles (${visibleCount} en total).`;
      }
    }
  }

  // Event Listeners
  selectUbicacion?.addEventListener('change', () => {
    filterArcOptions();
    applyFilters();
  });

  selectArco?.addEventListener('change', () => {
    applyFilters();
  });

  inputBusqueda?.addEventListener('input', () => {
    applyFilters();
  });

  btnBorrarBusqueda?.addEventListener('click', () => {
    if (inputBusqueda) {
      inputBusqueda.value = '';
      applyFilters();
      inputBusqueda.focus();
    }
  });

  btnLimpiar?.addEventListener('click', () => {
    if (selectUbicacion) selectUbicacion.value = '';
    filterArcOptions();
    if (selectArco) selectArco.value = '';
    if (inputBusqueda) inputBusqueda.value = '';
    applyFilters();
  });

  // Descarga secuencial de todos los PDFs del arco seleccionado
  btnDescargarTodos?.addEventListener('click', () => {
    const urls = JSON.parse(btnDescargarTodos.dataset.urls || '[]');
    if (!urls.length) return;

    btnDescargarTodos.disabled = true;
    const originalHtml = btnDescargarTodos.innerHTML;
    btnDescargarTodos.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Descargando...';

    urls.forEach((url, idx) => {
      setTimeout(() => {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.download = '';
        document.body.appendChild(link);
        link.click();
        link.remove();

        if (idx === urls.length - 1) {
          setTimeout(() => {
            btnDescargarTodos.disabled = false;
            btnDescargarTodos.innerHTML = originalHtml;
          }, 600);
        }
      }, idx * 250);
    });
  });

  // Inicializar estado de opciones y filtros
  filterArcOptions();
  applyFilters();
});
</script>

<?php include('../views/footer.php'); ?>

