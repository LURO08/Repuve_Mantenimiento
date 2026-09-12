<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/formatos_mantenimiento_schema.php';
require_once '../config/tecnicos_schema.php';

$formatos = require '../config/formatos_servicio.php';
$type = $_GET['type'] ?? '';
if (!isset($formatos[$type])) {
    echo '<div class="alert alert-danger">El formato solicitado no existe.</div>';
    include('../views/footer.php');
    exit;
}

$formato = $formatos[$type];
$localNow = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
asegurarTablaFormatosMantenimiento($pdo);
asegurarRelacionTecnicos($pdo);
$formatoId = (int)($_GET['formato_id'] ?? 0);
$editData = [];
$editRecord = null;
if ($formatoId > 0) {
    $editStmt = $pdo->prepare("
      SELECT id, arco_id, infraestructura_id, revision_id, infraestructura_revision_id, tipo, datos, tecnico_id
      FROM formatos_mantenimiento
      WHERE id = ?
    ");
    $editStmt->execute([$formatoId]);
    $editRecord = $editStmt->fetch(PDO::FETCH_ASSOC);
    if (!$editRecord || $editRecord['tipo'] !== $type) {
        echo '<div class="alert alert-danger">El formato solicitado no está disponible para edición.</div>';
        include('../views/footer.php');
        exit;
    }
    $editData = json_decode($editRecord['datos'], true) ?: [];
}
$selectedArcId = (int)($_GET['arco_id'] ?? 0);
$selectedInfraId = (int)($_GET['infraestructura_id'] ?? 0);
$selectedRevisionId = (int)($_GET['revision_id'] ?? 0);
$selectedInfraRevisionId = (int)($_GET['infraestructura_revision_id'] ?? $_GET['infra_revision_id'] ?? 0);

if ($editRecord) {
    $selectedArcId = (int)($editRecord['arco_id'] ?? 0);
    $selectedInfraId = (int)($editRecord['infraestructura_id'] ?? 0);
    $selectedRevisionId = (int)($editRecord['revision_id'] ?? 0);
    $selectedInfraRevisionId = (int)($editRecord['infraestructura_revision_id'] ?? 0);
}

$incomingTecnicoId = (int)($_GET['tecnico_id'] ?? 0);

if (!$editRecord) {
    // 1. Auto-resolver desde mantenimiento vinculado
    if ($selectedRevisionId > 0) {
        $revLookup = $pdo->prepare("SELECT arco_id, tecnico_id, fecha_mantenimiento, tipo_mantenimiento, observaciones FROM revisiones WHERE id = ?");
        $revLookup->execute([$selectedRevisionId]);
        if ($revData = $revLookup->fetch(PDO::FETCH_ASSOC)) {
            if ($selectedArcId <= 0) $selectedArcId = (int)$revData['arco_id'];
            if (empty($editData['tecnico_id']) && !empty($revData['tecnico_id'])) {
                $editData['tecnico_id'] = (int)$revData['tecnico_id'];
            }
            if (empty($editData['tipo_mantenimiento']) && !empty($revData['tipo_mantenimiento'])) {
                $editData['tipo_mantenimiento'] = $revData['tipo_mantenimiento'];
            }
            if (empty($editData['fecha_servicio']) && !empty($revData['fecha_mantenimiento'])) {
                $editData['fecha_servicio'] = $revData['fecha_mantenimiento'];
            }
        }
    } elseif ($selectedInfraRevisionId > 0) {
        $infRevLookup = $pdo->prepare("SELECT infraestructura_id, tecnico_id, fecha_mantenimiento, tipo_mantenimiento, observaciones FROM infraestructura_revisiones WHERE id = ?");
        $infRevLookup->execute([$selectedInfraRevisionId]);
        if ($infRevData = $infRevLookup->fetch(PDO::FETCH_ASSOC)) {
            if ($selectedInfraId <= 0) $selectedInfraId = (int)$infRevData['infraestructura_id'];
            if (empty($editData['tecnico_id']) && !empty($infRevData['tecnico_id'])) {
                $editData['tecnico_id'] = (int)$infRevData['tecnico_id'];
            }
            if (empty($editData['tipo_mantenimiento']) && !empty($infRevData['tipo_mantenimiento'])) {
                $editData['tipo_mantenimiento'] = $infRevData['tipo_mantenimiento'];
            }
            if (empty($editData['fecha_servicio']) && !empty($infRevData['fecha_mantenimiento'])) {
                $editData['fecha_servicio'] = $infRevData['fecha_mantenimiento'];
            }
        }
    }

    if ($incomingTecnicoId > 0) {
        $editData['tecnico_id'] = $incomingTecnicoId;
    }

    // 2. Reutilizar datos de formatos hermanos del mismo mantenimiento
    if ($selectedRevisionId > 0 || $selectedInfraRevisionId > 0) {
        $siblingStmt = $selectedRevisionId > 0
            ? $pdo->prepare("SELECT tipo, datos, tecnico_id FROM formatos_mantenimiento WHERE revision_id = ? ORDER BY id DESC")
            : $pdo->prepare("SELECT tipo, datos, tecnico_id FROM formatos_mantenimiento WHERE infraestructura_revision_id = ? ORDER BY id DESC");
        $siblingStmt->execute([$selectedRevisionId ?: $selectedInfraRevisionId]);
        $siblings = $siblingStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($siblings as $sib) {
            $sibData = json_decode($sib['datos'], true) ?: [];
            if (empty($editData['tecnico_id']) && !empty($sib['tecnico_id'])) {
                $editData['tecnico_id'] = (int)$sib['tecnico_id'];
            }
            if (empty($editData['fecha_servicio']) && !empty($sibData['fecha_servicio'])) {
                $editData['fecha_servicio'] = $sibData['fecha_servicio'];
            }
            if (empty($editData['tipo_mantenimiento']) && !empty($sibData['tipo_mantenimiento'])) {
                $editData['tipo_mantenimiento'] = $sibData['tipo_mantenimiento'];
            }
        }
    }

    // 3. Si es Formato de Calidad: precargar carriles y enlaces de pruebas previas del objetivo
    if ($type === 'quality') {
        $prevQualityStmt = null;
        if ($selectedArcId > 0) {
            $prevQualityStmt = $pdo->prepare("SELECT datos FROM formatos_mantenimiento WHERE tipo = 'quality' AND arco_id = ? ORDER BY id DESC LIMIT 1");
            $prevQualityStmt->execute([$selectedArcId]);
        } elseif ($selectedInfraId > 0) {
            $prevQualityStmt = $pdo->prepare("SELECT datos FROM formatos_mantenimiento WHERE tipo = 'quality' AND infraestructura_id = ? ORDER BY id DESC LIMIT 1");
            $prevQualityStmt->execute([$selectedInfraId]);
        }
        if ($prevQualityStmt && ($prevRow = $prevQualityStmt->fetch(PDO::FETCH_ASSOC))) {
            $prevData = json_decode($prevRow['datos'], true) ?: [];
            if (empty($editData['carriles']) && !empty($prevData['carriles'])) {
                $editData['carriles'] = $prevData['carriles'];
            }
            if (empty($editData['energia_fuente']) && !empty($prevData['energia_fuente'])) {
                $editData['energia_fuente'] = $prevData['energia_fuente'];
            }
            if (empty($editData['enlace']) && !empty($prevData['enlace'])) {
                $editData['enlace'] = $prevData['enlace'];
            }
            if (empty($editData['sistema_monitoreo']) && !empty($prevData['sistema_monitoreo'])) {
                $editData['sistema_monitoreo'] = $prevData['sistema_monitoreo'];
            }
        }
    }

    // 4. Si es Formato de Herramientas: precargar configuración previa del objetivo
    if ($type === 'tools') {
        $prevToolsStmt = null;
        if ($selectedArcId > 0) {
            $prevToolsStmt = $pdo->prepare("SELECT datos FROM formatos_mantenimiento WHERE tipo = 'tools' AND arco_id = ? ORDER BY id DESC LIMIT 1");
            $prevToolsStmt->execute([$selectedArcId]);
        } elseif ($selectedInfraId > 0) {
            $prevToolsStmt = $pdo->prepare("SELECT datos FROM formatos_mantenimiento WHERE tipo = 'tools' AND infraestructura_id = ? ORDER BY id DESC LIMIT 1");
            $prevToolsStmt->execute([$selectedInfraId]);
        }
        if ($prevToolsStmt && ($prevRow = $prevToolsStmt->fetch(PDO::FETCH_ASSOC))) {
            $prevData = json_decode($prevRow['datos'], true) ?: [];
            if (empty($editData['herramientas']) && !empty($prevData['herramientas'])) {
                $editData['herramientas'] = $prevData['herramientas'];
            }
            if (empty($editData['consumibles']) && !empty($prevData['consumibles'])) {
                $editData['consumibles'] = $prevData['consumibles'];
            }
            if (empty($editData['epp']) && !empty($prevData['epp'])) {
                $editData['epp'] = $prevData['epp'];
            }
        }
    }

    // 5. Si técnico sigue vacío, precargar el del último mantenimiento registrado
    if (empty($editData['tecnico_id'])) {
        if ($selectedArcId > 0) {
            $techLookup = $pdo->prepare("SELECT tecnico_id FROM revisiones WHERE arco_id = ? AND tecnico_id IS NOT NULL ORDER BY fecha_mantenimiento DESC LIMIT 1");
            $techLookup->execute([$selectedArcId]);
            if ($tId = $techLookup->fetchColumn()) {
                $editData['tecnico_id'] = (int)$tId;
            }
        } elseif ($selectedInfraId > 0) {
            $techLookup = $pdo->prepare("SELECT tecnico_id FROM infraestructura_revisiones WHERE infraestructura_id = ? AND tecnico_id IS NOT NULL ORDER BY fecha_mantenimiento DESC LIMIT 1");
            $techLookup->execute([$selectedInfraId]);
            if ($tId = $techLookup->fetchColumn()) {
                $editData['tecnico_id'] = (int)$tId;
            }
        }
    }
}

$ubicaciones = $type === 'checklist' ? $pdo->query("
  SELECT id, nombre
  FROM ubicaciones
  WHERE EXISTS (
    SELECT 1
    FROM arcos a
    WHERE a.ubicacion_id = ubicaciones.id
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
  ) OR EXISTS (
    SELECT 1
    FROM infraestructura_nodos n
    WHERE n.ubicacion_id = ubicaciones.id
  )
  ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC) : [];

$arcos = $pdo->query("
  SELECT a.id, a.nombre, a.ubicacion_id, COALESCE(u.nombre, '') AS ubicacion
  FROM arcos a
  LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
  WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
  ORDER BY u.nombre, a.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$infras = $pdo->query("
  SELECT n.id, n.nombre, n.tipo, n.ubicacion_id, COALESCE(u.nombre, '') AS ubicacion
  FROM infraestructura_nodos n
  LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
  ORDER BY u.nombre, n.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$tecnicos = $pdo->query("
  SELECT id, nombre FROM tecnicos
  WHERE activo = 1 AND COALESCE(eliminado, 0) = 0
  ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

$serviceTimestamp = strtotime($editData['fecha_servicio'] ?? '') ?: $localNow->getTimestamp();
$selectedTechnicianId = (int)($editData['tecnico_id'] ?? ($editRecord['tecnico_id'] ?? 0));
$selectedTechnician = $editData['tecnico'] ?? '';
$selectedServiceType = $editData['tipo_mantenimiento'] ?? ($editData['tipo_servicio'] ?? 'Correctivo');
$targetInfo = [
    'name' => '',
    'type' => 'Arco',
    'location' => '',
    'icon' => 'bi-broadcast-pin',
    'badge_class' => 'bg-primary'
];

if ($selectedArcId > 0) {
    foreach ($arcos as $arcOption) {
        if ((int)$arcOption['id'] === $selectedArcId) {
            $selectedLocationId = (int)$arcOption['ubicacion_id'];
            $targetInfo['name'] = $arcOption['nombre'];
            $targetInfo['type'] = 'Arco';
            $targetInfo['location'] = $arcOption['ubicacion'];
            $targetInfo['icon'] = 'bi-broadcast-pin';
            $targetInfo['badge_class'] = 'bg-primary';
            break;
        }
    }
    if (empty($targetInfo['name'])) {
        $st = $pdo->prepare("SELECT a.nombre, COALESCE(u.nombre, '') AS ubicacion FROM arcos a LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id WHERE a.id = ?");
        $st->execute([$selectedArcId]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $targetInfo['name'] = $row['nombre'];
            $targetInfo['type'] = 'Arco';
            $targetInfo['location'] = $row['ubicacion'];
        }
    }
} elseif ($selectedInfraId > 0) {
    foreach ($infras as $infraOption) {
        if ((int)$infraOption['id'] === $selectedInfraId) {
            $selectedLocationId = (int)$infraOption['ubicacion_id'];
            $targetInfo['name'] = $infraOption['nombre'];
            $targetInfo['type'] = $infraOption['tipo'] ?: 'Sitio';
            $targetInfo['location'] = $infraOption['ubicacion'];
            $targetInfo['icon'] = 'bi-hdd-rack-fill';
            $targetInfo['badge_class'] = 'bg-info text-dark';
            break;
        }
    }
    if (empty($targetInfo['name'])) {
        $st = $pdo->prepare("SELECT n.nombre, n.tipo, COALESCE(u.nombre, '') AS ubicacion FROM infraestructura_nodos n LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id WHERE n.id = ?");
        $st->execute([$selectedInfraId]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $targetInfo['name'] = $row['nombre'];
            $targetInfo['type'] = $row['tipo'] ?: 'Sitio';
            $targetInfo['location'] = $row['ubicacion'];
        }
    }
}

$techName = '';
foreach ($tecnicos as $t) {
    if ((int)$t['id'] === $selectedTechnicianId || (!$selectedTechnicianId && $t['nombre'] === $selectedTechnician)) {
        $techName = $t['nombre'];
        $selectedTechnicianId = (int)$t['id'];
        break;
    }
}
if (empty($techName) && $selectedTechnicianId > 0) {
    $tRow = obtenerTecnicoPorId($pdo, $selectedTechnicianId);
    if ($tRow) {
        $techName = $tRow['nombre'];
    }
}

$isContextLocked = ($selectedArcId > 0 || $selectedInfraId > 0 || $selectedRevisionId > 0 || $selectedInfraRevisionId > 0 || $formatoId > 0);
?>

<link rel="stylesheet" href="../css/formatos.css?v=<?= file_exists(__DIR__ . '/../css/formatos.css') ? filemtime(__DIR__ . '/../css/formatos.css') : time() ?>">
<script>document.body.classList.add('format-editor-page');</script>

<main class="format-editor">
  <header class="editor-heading editor-heading--ultra-compact mb-2">
    <a href="formatos.php<?= $selectedInfraRevisionId ? '?infraestructura_revision_id=' . $selectedInfraRevisionId : ($selectedRevisionId ? '?revision_id=' . $selectedRevisionId : ($selectedInfraId ? '?infraestructura_id=' . $selectedInfraId : ($selectedArcId ? '?arco_id=' . $selectedArcId : ''))) ?>" class="btn btn-outline-secondary btn-sm" title="Volver">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="editor-heading__title">
      <span><i class="bi <?= htmlspecialchars($formato['icon']) ?>"></i></span>
      <div>
        <h1 class="fs-5 mb-0"><?= $formatoId ? 'Editar ' : '' ?><?= htmlspecialchars($formato['title']) ?></h1>
        <p class="small text-muted mb-0"><?= $formatoId ? 'Actualiza el documento vinculado.' : 'Documento de servicio y diagnóstico.' ?></p>
      </div>
    </div>
  </header>

  <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger py-2 mb-2"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form class="format-form js-stepped-form" action="../controllers/formatos_controller.php" method="post" target="_blank">
    <input type="hidden" name="action" value="generate">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="formato_id" value="<?= $formatoId ?>">
    <input type="hidden" name="arco_id" id="hidden_arco_id" value="<?= $selectedArcId ?: '' ?>">
    <input type="hidden" name="infraestructura_id" id="hidden_infra_id" value="<?= $selectedInfraId ?: '' ?>">
    <input type="hidden" name="revision_id" id="hidden_revision_id" value="<?= $selectedRevisionId ?: '' ?>">
    <input type="hidden" name="infraestructura_revision_id" id="hidden_infra_revision_id" value="<?= $selectedInfraRevisionId ?: '' ?>">

    <?php if ($isContextLocked): ?>
      <!-- Banner compacto de información general reutilizada (Solo lectura para Arco/Sitio y Ubicación) -->
      <div class="format-context-banner mb-2">
        <div class="format-context-banner__main">
          <div class="format-context-banner__icon">
            <i class="bi <?= htmlspecialchars($targetInfo['icon']) ?>"></i>
          </div>
          <div class="format-context-banner__details">
            <div class="format-context-banner__title-row">
              <span class="badge <?= htmlspecialchars($targetInfo['badge_class']) ?>"><?= htmlspecialchars($targetInfo['type']) ?></span>
              <strong class="format-context-banner__title"><?= htmlspecialchars($targetInfo['name'] ?: 'Objetivo no especificado') ?></strong>
              <?php if (!empty($targetInfo['location'])): ?>
                <span class="format-context-banner__location"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($targetInfo['location']) ?></span>
              <?php endif; ?>
            </div>
            <div class="format-context-banner__meta">
              <?php if (!empty($techName)): ?>
                <span class="meta-item"><i class="bi bi-person-badge"></i><strong>Técnico:</strong> <?= htmlspecialchars($techName) ?></span>
              <?php endif; ?>
              <span class="meta-item"><i class="bi bi-calendar-event"></i><strong>Fecha:</strong> <?= date('d/m/Y', $serviceTimestamp) ?> <?= date('H:i', $serviceTimestamp) ?></span>
              <span class="meta-item"><i class="bi bi-wrench-adjustable"></i><strong>Servicio:</strong> <?= htmlspecialchars($selectedServiceType) ?></span>
              <?php if ($selectedRevisionId > 0 || $selectedInfraRevisionId > 0): ?>
                <span class="meta-badge-linked"><i class="bi bi-link-45deg"></i> Vinculado</span>
              <?php endif; ?>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary format-context-banner__toggle" data-bs-toggle="collapse" data-bs-target="#collapseGeneralData" aria-expanded="false" title="Modificar fecha y hora del formato">
            <i class="bi bi-clock-history"></i> <span class="toggle-text">Modificar fecha / hora</span>
          </button>
        </div>

        <div class="collapse format-context-banner__collapse" id="collapseGeneralData">
          <div class="format-context-banner__edit-inner">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div class="flex-grow-1" style="min-width: 170px; max-width: 220px;">
                <label class="form-label form-label-sm mb-1 fw-bold text-secondary" for="formato_fecha"><i class="bi bi-calendar-event me-1"></i>Fecha</label>
                <input class="form-control form-control-sm" id="formato_fecha" name="fecha" type="date" value="<?= date('Y-m-d', $serviceTimestamp) ?>" required>
              </div>
              <div class="flex-grow-1" style="min-width: 140px; max-width: 180px;">
                <label class="form-label form-label-sm mb-1 fw-bold text-secondary" for="formato_hora"><i class="bi bi-clock me-1"></i>Hora</label>
                <input class="form-control form-control-sm" id="formato_hora" name="hora" type="time" value="<?= date('H:i', $serviceTimestamp) ?>" required>
              </div>
              <div class="flex-grow-1" style="min-width: 200px; max-width: 280px;">
                <label class="form-label form-label-sm mb-1 fw-bold text-secondary" for="formato_tecnico"><i class="bi bi-person-badge me-1"></i>Técnico</label>
                <select class="form-select form-select-sm" id="formato_tecnico" name="tecnico_id" required>
                  <option value="">Selecciona...</option>
                  <?php foreach ($tecnicos as $tecnico): ?>
                    <option value="<?= htmlspecialchars((string)$tecnico['id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((int)$tecnico['id'] === $selectedTechnicianId || (!$selectedTechnicianId && $tecnico['nombre'] === $selectedTechnician)) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($tecnico['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if ($type === 'quality'): ?>
                <div class="ms-auto">
                  <span class="form-label form-label-sm mb-1 d-block fw-bold text-secondary">Servicio previsto</span>
                  <div class="d-flex gap-3 mt-1">
                    <label class="small mb-0"><input type="radio" name="tipo_mantenimiento" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                    <label class="small mb-0"><input type="radio" name="tipo_mantenimiento" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
                  </div>
                </div>
              <?php elseif ($type === 'tools'): ?>
                <div class="ms-auto">
                  <span class="form-label form-label-sm mb-1 d-block fw-bold text-secondary">Tipo de servicio</span>
                  <div class="d-flex gap-3 mt-1">
                    <label class="small mb-0"><input type="radio" name="tipo_servicio" value="Nueva Instalacion" <?= $selectedServiceType === 'Nueva Instalacion' ? 'checked' : '' ?> required> Nueva inst.</label>
                    <label class="small mb-0"><input type="radio" name="tipo_servicio" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                    <label class="small mb-0"><input type="radio" name="tipo_servicio" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
                  </div>
                </div>
              <?php elseif ($type === 'checklist'): ?>
                <div class="ms-auto">
                  <span class="form-label form-label-sm mb-1 d-block fw-bold text-secondary">Servicio previsto</span>
                  <div class="d-flex gap-3 mt-1">
                    <label class="small mb-0"><input type="radio" name="tipo_mantenimiento" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                    <label class="small mb-0"><input type="radio" name="tipo_mantenimiento" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <nav class="format-step-nav" aria-label="Secciones del formato"></nav>

    <div class="format-step-content">
      <?php if (!$isContextLocked): ?>
        <section class="form-section js-form-section" data-step-title="Datos">
          <div class="form-section__heading">
            <span>1</span>
            <div>
              <h2>Datos del servicio</h2>
              <p>Selecciona el arco o sitio con su ubicación y el técnico responsable.</p>
            </div>
          </div>

          <div class="service-data-grid service-data-grid--primary <?= $type === 'checklist' ? 'service-data-grid--checklist' : '' ?>">
            <?php if ($type === 'checklist'): ?>
              <div>
                <label class="form-label" for="formato_ubicacion">Ubicación</label>
                <select class="form-select" id="formato_ubicacion" required>
                  <option value="">Selecciona...</option>
                  <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?= $ubicacion['id'] ?>" <?= (int)$ubicacion['id'] === $selectedLocationId ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ubicacion['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <div>
              <label class="form-label" for="formato_arco_o_sitio"><?= $type === 'checklist' ? 'Arco / Sitio' : 'Arco / Sitio / Ubicación' ?></label>
              <select class="form-select" id="formato_arco" required>
                <option value=""><?= $type === 'checklist' ? 'Selecciona una ubicación...' : 'Selecciona un arco o sitio...' ?></option>
                <optgroup label="Arcos">
                  <?php foreach ($arcos as $arco): ?>
                    <option value="arco_<?= $arco['id'] ?>"
                            data-tipo="arco"
                            data-id="<?= $arco['id'] ?>"
                            data-location-id="<?= $arco['ubicacion_id'] ?>"
                            <?= ($selectedArcId === (int)$arco['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($type === 'checklist' ? $arco['nombre'] : $arco['nombre'] . ' - ' . $arco['ubicacion']) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Puentes / Sitios / Torres">
                  <?php foreach ($infras as $infra): ?>
                    <option value="infra_<?= $infra['id'] ?>"
                            data-tipo="infra"
                            data-id="<?= $infra['id'] ?>"
                            data-location-id="<?= $infra['ubicacion_id'] ?>"
                            <?= ($selectedInfraId === (int)$infra['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($type === 'checklist' ? $infra['nombre'] . ' (' . $infra['tipo'] . ')' : $infra['nombre'] . ' (' . $infra['tipo'] . ') - ' . $infra['ubicacion']) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <div>
              <label class="form-label" for="formato_tecnico">Técnico</label>
              <select class="form-select" id="formato_tecnico" name="tecnico_id" required>
                <option value="">Selecciona...</option>
                <?php foreach ($tecnicos as $tecnico): ?>
                  <option value="<?= htmlspecialchars((string)$tecnico['id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((int)$tecnico['id'] === $selectedTechnicianId || (!$selectedTechnicianId && $tecnico['nombre'] === $selectedTechnician)) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tecnico['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($type === 'quality'): ?>
              <div class="service-type">
                <span class="form-label mb-0">Servicio previsto</span>
                <label><input type="radio" name="tipo_mantenimiento" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                <label><input type="radio" name="tipo_mantenimiento" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
              </div>
            <?php elseif ($type === 'tools'): ?>
              <div class="service-type">
                <span class="form-label mb-0">Tipo de servicio</span>
                <label><input type="radio" name="tipo_servicio" value="Nueva Instalacion" <?= $selectedServiceType === 'Nueva Instalacion' ? 'checked' : '' ?> required> Nueva instalación</label>
                <label><input type="radio" name="tipo_servicio" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                <label><input type="radio" name="tipo_servicio" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
              </div>
            <?php endif; ?>
          </div>
          <div class="service-data-grid service-data-grid--secondary <?= $type === 'checklist' ? 'service-data-grid--secondary-checklist' : '' ?>">
            <div>
              <label class="form-label" for="formato_fecha">Fecha</label>
              <input class="form-control" id="formato_fecha" name="fecha" type="date" value="<?= date('Y-m-d', $serviceTimestamp) ?>" required>
            </div>
            <div>
              <label class="form-label" for="formato_hora">Hora</label>
              <input class="form-control" id="formato_hora" name="hora" type="time" value="<?= date('H:i', $serviceTimestamp) ?>" required>
            </div>
            <?php if ($type === 'checklist'): ?>
              <div class="service-type">
                <span class="form-label mb-0">Servicio previsto</span>
                <label><input type="radio" name="tipo_mantenimiento" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
                <label><input type="radio" name="tipo_mantenimiento" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($type === 'checklist'): ?>
        <section class="form-section js-form-section" data-step-title="Diagnóstico">
          <div class="form-section__heading form-section__heading--compact mb-2 pb-1 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span><?= $isContextLocked ? 1 : 2 ?></span>
              <div>
                <h2 class="fs-6 mb-0 fw-bold">Material activo del arco o sitio</h2>
                <p class="small text-muted mb-0">Componentes instalados y estructura metálica.</p>
              </div>
            </div>
            <div id="checklistCounterBadge" class="badge bg-light text-dark border py-1 px-2">
              <i class="bi bi-cpu text-success me-1"></i><span id="checklistCount">Cargando componentes...</span>
            </div>
          </div>
          <div id="materialSelectorChecklist" class="d-none"></div>
          <div class="checklist-table mt-1">
            <div class="checklist-table__head"><span>Componente</span><span>Estado</span><span>Observaciones</span><span>Cambiado</span></div>
            <div id="checklistRows"></div>
          </div>
          <template id="checklistRowTemplate">
            <div class="checklist-row">
              <div class="checklist-row__name">
                <strong class="checklist-material-name"></strong>
                <small class="text-muted checklist-material-series"></small>
                <input type="hidden" class="checklist-relation">
                <input type="hidden" class="checklist-name">
                <input type="hidden" class="checklist-series">
                <input type="hidden" class="checklist-ip">
                <input type="hidden" class="checklist-mac">
                <input type="hidden" class="checklist-quantity">
                <input type="hidden" class="checklist-measure">
              </div>
              <div class="status-options">
                <label class="status-option status-option--good" title="Estado Bueno"><input class="status-good" type="radio" value="Bueno" checked> Bueno</label>
                <label class="status-option status-option--bad" title="Estado Malo"><input class="status-bad" type="radio" value="Malo"> Malo</label>
              </div>
              <input class="form-control form-control-sm checklist-observation" placeholder="Observaciones...">
              <label class="changed-check" title="Marcar si este componente fue cambiado o reemplazado"><input class="checklist-changed" type="checkbox" value="1"> Cambiado</label>
            </div>
          </template>
        </section>
      <?php endif; ?>

      <?php if ($type === 'quality'): ?>
        <section class="form-section js-form-section" data-step-title="Prueba">
          <div class="form-section__heading">
            <span><?= $isContextLocked ? 1 : 2 ?></span>
            <div><h2>Pruebas y resultado</h2><p>Carriles a la izquierda y resumen operativo a la derecha.</p></div>
          </div>
          <div class="quality-workspace">
            <div class="quality-lanes-panel">
              <div class="quality-panel-title">
                <strong>Carriles</strong>
                <button class="btn btn-outline-success btn-sm" id="agregarCarril" type="button"><i class="bi bi-plus-lg"></i> Carril</button>
              </div>
              <div class="lanes-grid" id="carrilesContainer"></div>
            </div>
            <div class="quality-result-panel">
              <strong class="d-block mb-2">Resultado general</strong>
              <div class="quality-summary">
                <fieldset>
                  <legend>Energía operando</legend>
                  <div class="d-flex flex-wrap gap-2">
                    <label class="status-option status-option--good"><input type="radio" name="energia_fuente" value="luz" required> Luz 1+1</label>
                    <label class="status-option status-option--good"><input type="radio" name="energia_fuente" value="solar" required> Solar / baterías</label>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>Enlace</legend>
                  <div class="d-flex gap-2">
                    <label class="status-option status-option--good"><input type="radio" name="enlace" value="Si"> Sí</label>
                    <label class="status-option status-option--bad"><input type="radio" name="enlace" value="No"> No</label>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>Monitoreo</legend>
                  <div class="d-flex gap-2">
                    <label class="status-option status-option--good"><input type="radio" name="sistema_monitoreo" value="Si"> Sí</label>
                    <label class="status-option status-option--bad"><input type="radio" name="sistema_monitoreo" value="No"> No</label>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>Resultado</legend>
                  <div class="d-flex gap-2">
                    <label class="status-option status-option--good"><input type="radio" name="resultado" value="Exitosa"> Exitosa</label>
                    <label class="status-option status-option--bad"><input type="radio" name="resultado" value="Fallida"> Fallida</label>
                  </div>
                </fieldset>
              </div>
              <textarea class="form-control mt-2" name="acciones_correctivas" rows="4" placeholder="Acciones correctivas realizadas"></textarea>
            </div>
          </div>
          <template id="carrilTemplate">
            <article class="lane-card">
              <div class="lane-card__title"><strong>Carril <span class="lane-number"></span></strong><button type="button" class="btn btn-outline-danger btn-sm remove-lane" title="Quitar carril"><i class="bi bi-x-lg"></i></button></div>
              <input class="form-control form-control-sm lane-name" placeholder="Nombre o número">
              <select class="form-select form-select-sm lane-reading"><option value="">Lectura...</option><option value="Sí">Sí</option><option value="No">No</option></select>
              <select class="form-select form-select-sm lane-monitor"><option value="">Monitoreo...</option><option value="Sí">Sí</option><option value="No">No</option></select>
              <input class="form-control form-control-sm lane-observation" placeholder="Observaciones...">
            </article>
          </template>
        </section>
      <?php endif; ?>

      <?php if ($type === 'tools'): ?>
        <?php
        $groups = [
          ['title' => 'Herramientas', 'items' => $formato['tools'], 'prefix' => 'herr'],
          ['title' => 'Consumibles', 'items' => $formato['consumables'], 'prefix' => 'cons'],
          ['title' => 'Protección personal', 'items' => $formato['epp'], 'prefix' => 'epp'],
        ];
        ?>
        <?php foreach ($groups as $groupIndex => $group): ?>
          <section class="form-section js-form-section" data-step-title="<?= htmlspecialchars($group['title']) ?>">
            <div class="form-section__heading">
              <span><?= $groupIndex + ($isContextLocked ? 1 : 2) ?></span><div><h2><?= htmlspecialchars($group['title']) ?></h2><p>Selecciona los elementos requeridos.</p></div>
              <button class="btn btn-outline-success btn-sm ms-auto js-toggle-group" type="button">Seleccionar todo</button>
            </div>
            <div class="selection-grid selection-grid--dense">
              <?php foreach ($group['items'] as $index => $pair): ?>
                <?php foreach (['izq' => $pair[0], 'der' => $pair[1]] as $side => $label): ?>
                  <?php $isQuantifiable = $group['prefix'] === 'cons' && in_array($label, $formato['quantifiable_consumables'] ?? [], true); ?>
                  <div class="selection-item-wrap <?= $isQuantifiable ? 'is-quantifiable' : '' ?>">
                    <label class="selection-item">
                      <input type="checkbox" name="<?= $group['prefix'] ?>_<?= $index ?>_<?= $side ?>" value="1" data-item-label="<?= htmlspecialchars($label) ?>">
                      <span><i class="bi bi-check2"></i><?= htmlspecialchars($label) ?></span>
                    </label>
                    <?php if ($isQuantifiable): ?>
                      <label class="selection-quantity">
                        <input class="form-control form-control-sm" type="number" name="<?= $group['prefix'] ?>_qty_<?= $index ?>_<?= $side ?>" min="1" value="1" disabled>
                        <span>pza</span>
                      </label>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="format-actions">
      <button class="btn btn-outline-secondary js-prev-step" type="button"><i class="bi bi-arrow-left"></i> Anterior</button>
      <button class="btn btn-outline-success js-next-step" type="button">Siguiente <i class="bi bi-arrow-right"></i></button>
      <button class="btn btn-success js-submit-format" type="submit"><i class="bi bi-file-earmark-pdf"></i> Generar PDF</button>
    </div>
  </form>
</main>

<script id="formatoEditData" type="application/json"><?= json_encode($editData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="../js/formatos.js?v=<?= file_exists(__DIR__ . '/../js/formatos.js') ? filemtime(__DIR__ . '/../js/formatos.js') : time() ?>"></script>
<?php include('../views/footer.php'); ?>
