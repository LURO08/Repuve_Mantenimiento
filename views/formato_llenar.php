<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/formatos_mantenimiento_schema.php';

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
$formatoId = (int)($_GET['formato_id'] ?? 0);
$editData = [];
$editRecord = null;
if ($formatoId > 0) {
    $editStmt = $pdo->prepare("
      SELECT id, arco_id, tipo, datos
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
if ($editRecord) $selectedArcId = (int)$editRecord['arco_id'];
$ubicaciones = $pdo->query("
  SELECT id, nombre
  FROM ubicaciones
  WHERE EXISTS (
    SELECT 1 FROM arcos a
    WHERE a.ubicacion_id = ubicaciones.id AND COALESCE(a.estado, 'Activo') <> 'Baja'
  )
  ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);
$arcos = $pdo->query("
  SELECT a.id, a.nombre, a.ubicacion_id
  FROM arcos a
  WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
  ORDER BY a.nombre
")->fetchAll(PDO::FETCH_ASSOC);
$tecnicos = $pdo->query("
  SELECT nombre FROM tecnicos
  WHERE activo = 1 AND COALESCE(eliminado, 0) = 0
  ORDER BY nombre
")->fetchAll(PDO::FETCH_COLUMN);
$selectedArc = null;
foreach ($arcos as $arcOption) {
    if ((int)$arcOption['id'] === $selectedArcId) {
        $selectedArc = $arcOption;
        break;
    }
}
$serviceTimestamp = strtotime($editData['fecha_servicio'] ?? '') ?: $localNow->getTimestamp();
$selectedTechnician = $editData['tecnico'] ?? '';
$selectedServiceType = $editData['tipo_mantenimiento'] ?? ($editData['tipo_servicio'] ?? 'Correctivo');
?>

<link rel="stylesheet" href="../css/formatos.css">
<script>document.body.classList.add('format-editor-page');</script>

<main class="format-editor">
  <header class="editor-heading editor-heading--compact">
    <a href="formatos.php<?= $selectedArcId ? '?arco_id=' . $selectedArcId : '' ?>" class="btn btn-outline-secondary btn-sm" title="Volver">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="editor-heading__title">
      <span><i class="bi <?= htmlspecialchars($formato['icon']) ?>"></i></span>
      <div>
        <h1><?= $formatoId ? 'Editar ' : '' ?><?= htmlspecialchars($formato['title']) ?></h1>
        <p><?= $formatoId ? 'Actualiza el documento vinculado al arco.' : 'Documento previo vinculado directamente al arco.' ?></p>
      </div>
    </div>
  </header>

  <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form class="format-form js-stepped-form" action="../controllers/formatos_controller.php" method="post">
    <input type="hidden" name="action" value="generate">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="formato_id" value="<?= $formatoId ?>">

    <nav class="format-step-nav" aria-label="Secciones del formato"></nav>

    <div class="format-step-content">
      <section class="form-section js-form-section" data-step-title="Datos">
        <div class="form-section__heading">
          <span>1</span>
          <div>
            <h2>Datos del servicio</h2>
            <p>Selecciona ubicación, arco y técnico responsable.</p>
          </div>
        </div>

        <div class="service-data-grid">
          <div>
            <label class="form-label" for="formato_ubicacion">Ubicación</label>
            <select class="form-select" id="formato_ubicacion" required>
              <option value="">Selecciona...</option>
              <?php foreach ($ubicaciones as $ubicacion): ?>
                <option value="<?= $ubicacion['id'] ?>" <?= $selectedArc && (int)$selectedArc['ubicacion_id'] === (int)$ubicacion['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ubicacion['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label" for="formato_arco">Arco</label>
            <select class="form-select" id="formato_arco" name="arco_id" required>
              <option value="">Selecciona ubicación...</option>
              <?php foreach ($arcos as $arco): ?>
                <option value="<?= $arco['id'] ?>"
                        data-location-id="<?= $arco['ubicacion_id'] ?>"
                        <?= (int)$arco['id'] === $selectedArcId ? 'selected' : '' ?>>
                  <?= htmlspecialchars($arco['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label" for="formato_tecnico">Técnico</label>
            <select class="form-select" id="formato_tecnico" name="tecnico" required>
              <option value="">Selecciona...</option>
              <?php foreach ($tecnicos as $tecnico): ?>
                <option value="<?= htmlspecialchars($tecnico) ?>" <?= $tecnico === $selectedTechnician ? 'selected' : '' ?>><?= htmlspecialchars($tecnico) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="service-data-grid service-data-grid--secondary">
          <div>
            <label class="form-label" for="formato_fecha">Fecha</label>
            <input class="form-control" id="formato_fecha" name="fecha" type="date" value="<?= date('Y-m-d', $serviceTimestamp) ?>" required>
          </div>
          <div>
            <label class="form-label" for="formato_hora">Hora</label>
            <input class="form-control" id="formato_hora" name="hora" type="time" value="<?= date('H:i', $serviceTimestamp) ?>" required>
          </div>

        <?php if ($type === 'checklist' || $type === 'quality'): ?>
          <div class="service-type">
            <span class="form-label mb-0">Servicio previsto</span>
            <label><input type="radio" name="tipo_mantenimiento" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
            <label><input type="radio" name="tipo_mantenimiento" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
          </div>
        <?php else: ?>
          <div class="service-type">
            <span class="form-label mb-0">Tipo de servicio</span>
            <label><input type="radio" name="tipo_servicio" value="Nueva Instalacion" <?= $selectedServiceType === 'Nueva Instalacion' ? 'checked' : '' ?> required> Nueva instalación</label>
            <label><input type="radio" name="tipo_servicio" value="Preventivo" <?= $selectedServiceType === 'Preventivo' ? 'checked' : '' ?> required> Preventivo</label>
            <label><input type="radio" name="tipo_servicio" value="Correctivo" <?= $selectedServiceType === 'Correctivo' ? 'checked' : '' ?> required> Correctivo</label>
          </div>
        <?php endif; ?>
        </div>
      </section>

      <?php if ($type === 'checklist'): ?>
        <section class="form-section js-form-section" data-step-title="Diagnóstico">
          <div class="form-section__heading">
            <span>2</span><div><h2>Material utilizado y diagnóstico</h2><p>Selecciona únicamente los componentes instalados que aplican al servicio.</p></div>
          </div>
          <div id="materialSelectorChecklist" class="checklist-material-selector">
            <div class="text-muted text-center py-3">Selecciona un arco para cargar sus materiales.</div>
          </div>
          <div class="checklist-table mt-2">
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
              </div>
              <div class="status-options">
                <label class="status-option status-option--good"><input class="status-good" type="radio" value="Bueno" checked> Bueno</label>
                <label class="status-option status-option--bad"><input class="status-bad" type="radio" value="Malo"> Malo</label>
              </div>
              <input class="form-control form-control-sm checklist-observation" placeholder="Observaciones">
              <label class="changed-check"><input class="checklist-changed" type="checkbox" value="1"> Sí</label>
            </div>
          </template>
        </section>
      <?php endif; ?>

      <?php if ($type === 'quality'): ?>
        <section class="form-section js-form-section" data-step-title="Prueba">
          <div class="form-section__heading">
            <span>2</span>
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
                <fieldset><legend>Energía operando</legend><label><input type="checkbox" name="energia_luz" value="1"> Luz 1+1</label><label><input type="checkbox" name="energia_solar" value="1"> Solar / baterías</label></fieldset>
                <fieldset><legend>Enlace</legend><label><input type="radio" name="enlace" value="Si"> Sí</label><label><input type="radio" name="enlace" value="No"> No</label></fieldset>
                <fieldset><legend>Monitoreo</legend><label><input type="radio" name="sistema_monitoreo" value="Si"> Sí</label><label><input type="radio" name="sistema_monitoreo" value="No"> No</label></fieldset>
                <fieldset><legend>Resultado</legend><label><input type="radio" name="resultado" value="Exitosa"> Exitosa</label><label><input type="radio" name="resultado" value="Fallida"> Fallida</label></fieldset>
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
              <input class="form-control form-control-sm lane-observation" placeholder="Observaciones">
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
              <span><?= $groupIndex + 2 ?></span><div><h2><?= htmlspecialchars($group['title']) ?></h2><p>Selecciona los elementos requeridos.</p></div>
              <button class="btn btn-outline-success btn-sm ms-auto js-toggle-group" type="button">Seleccionar todo</button>
            </div>
            <div class="selection-grid selection-grid--dense">
              <?php foreach ($group['items'] as $index => $pair): ?>
                <?php foreach (['izq' => $pair[0], 'der' => $pair[1]] as $side => $label): ?>
                  <label class="selection-item"><input type="checkbox" name="<?= $group['prefix'] ?>_<?= $index ?>_<?= $side ?>" value="1"><span><i class="bi bi-check2"></i><?= htmlspecialchars($label) ?></span></label>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
            <?php if ($group['prefix'] === 'cons'): ?>
              <div class="yagi-quantity"><label class="form-label" for="yagi_cantidad">Cantidad de antenas Yagi</label><input class="form-control form-control-sm" id="yagi_cantidad" name="yagi_cantidad" type="number" min="0" value="0"></div>
            <?php endif; ?>
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
<script src="../js/formatos.js"></script>
<?php include('../views/footer.php'); ?>
