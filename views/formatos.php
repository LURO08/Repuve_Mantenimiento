<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/formatos_mantenimiento_schema.php';

$formatos = require '../config/formatos_servicio.php';
asegurarTablaFormatosMantenimiento($pdo);
$arcoId = (int)($_GET['arco_id'] ?? 0);
$infraId = (int)($_GET['infraestructura_id'] ?? 0);
$arcoSeleccionado = null;
$infraSeleccionada = null;
$formatosGuardados = [];

if ($arcoId > 0) {
  $stmt = $pdo->prepare("
    SELECT a.id, a.nombre AS arco, COALESCE(u.nombre, '') AS ubicacion
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE a.id = ?
  ");
  $stmt->execute([$arcoId]);
  $arcoSeleccionado = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($arcoSeleccionado) {
    $savedStmt = $pdo->prepare("
      SELECT id, tipo, creado_por, created_at, COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
      FROM formatos_mantenimiento
      WHERE arco_id = ?
      ORDER BY created_at DESC, id DESC
    ");
    $savedStmt->execute([$arcoId]);
    $formatosGuardados = $savedStmt->fetchAll(PDO::FETCH_ASSOC);
  }
} elseif ($infraId > 0) {
  $stmt = $pdo->prepare("
    SELECT n.id, n.nombre AS sitio, n.tipo, COALESCE(u.nombre, '') AS ubicacion
    FROM infraestructura_nodos n
    LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
    WHERE n.id = ?
  ");
  $stmt->execute([$infraId]);
  $infraSeleccionada = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($infraSeleccionada) {
    $savedStmt = $pdo->prepare("
      SELECT id, tipo, creado_por, created_at, COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
      FROM formatos_mantenimiento
      WHERE infraestructura_id = ?
      ORDER BY created_at DESC, id DESC
    ");
    $savedStmt->execute([$infraId]);
    $formatosGuardados = $savedStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>

<link rel="stylesheet" href="../css/formatos.css">

<main class="formats-page">
  <header class="formats-heading formats-heading--compact">
    <div class="formats-heading__icon"><i class="bi bi-file-earmark-text"></i></div>
    <div>
      <h1>Formatos de servicio</h1>
      <p>Documentación previa para diagnóstico, pruebas y preparación del servicio.</p>
    </div>
  </header>

  <?php if ($arcoSeleccionado): ?>
    <section class="maintenance-context">
      <div class="maintenance-context__icon"><i class="bi bi-diagram-3"></i></div>
      <div>
        <span class="maintenance-context__label">Arco seleccionado</span>
        <strong><?= htmlspecialchars($arcoSeleccionado['arco']) ?></strong>
        <small><?= htmlspecialchars($arcoSeleccionado['ubicacion']) ?></small>
      </div>
      <a href="arcos.php" class="btn btn-outline-secondary btn-sm">Volver a arcos</a>
    </section>
  <?php elseif ($infraSeleccionada): ?>
    <section class="maintenance-context">
      <div class="maintenance-context__icon"><i class="bi bi-broadcast-pin"></i></div>
      <div>
        <span class="maintenance-context__label">Puente / Sitio seleccionado</span>
        <strong><?= htmlspecialchars($infraSeleccionada['sitio']) ?> <span class="badge bg-primary ms-1"><?= htmlspecialchars($infraSeleccionada['tipo']) ?></span></strong>
        <small><?= htmlspecialchars($infraSeleccionada['ubicacion']) ?></small>
      </div>
      <a href="arcos.php" class="btn btn-outline-secondary btn-sm">Volver a arcos</a>
    </section>
  <?php endif; ?>

  <section class="formats-grid" aria-label="Formatos disponibles">
    <?php foreach ($formatos as $type => $formato): ?>
      <?php
        $targetQuery = '';
        if ($arcoSeleccionado) {
          $targetQuery = '&amp;arco_id=' . $arcoSeleccionado['id'];
        } elseif ($infraSeleccionada) {
          $targetQuery = '&amp;infraestructura_id=' . $infraSeleccionada['id'];
        }
      ?>
      <article class="format-card format-card--compact">
        <div class="format-card__top">
          <span class="format-card__icon"><i class="bi <?= htmlspecialchars($formato['icon']) ?>"></i></span>
          <span class="format-card__tag">PDF</span>
        </div>
        <div class="format-card__content">
          <h2><?= htmlspecialchars($formato['title']) ?></h2>
          <p><?= htmlspecialchars($formato['description']) ?></p>
        </div>
        <div class="format-card__actions">
          <a class="btn btn-success" href="formato_llenar.php?type=<?= urlencode($type) ?><?= $targetQuery ?>">
            <i class="bi bi-pencil-square"></i> Llenar
          </a>
          <a class="btn btn-outline-secondary"
             href="../controllers/formatos_controller.php?action=download_blank&amp;type=<?= urlencode($type) ?>">
            <i class="bi bi-download"></i> Vacío
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <?php if ($arcoSeleccionado || $infraSeleccionada): ?>
    <section class="saved-formats saved-formats--compact">
      <div class="saved-formats__heading">
        <div>
          <h2>PDF vinculados al <?= $arcoSeleccionado ? 'arco' : 'sitio' ?></h2>
          <p>Documentos previos generados para este <?= $arcoSeleccionado ? 'arco' : 'sitio' ?>.</p>
        </div>
        <span><?= count($formatosGuardados) ?> archivo(s)</span>
      </div>
      <?php if (!$formatosGuardados): ?>
        <div class="saved-formats__empty">Todavía no hay formatos de servicio vinculados.</div>
      <?php else: ?>
        <div class="saved-formats__list">
          <?php foreach ($formatosGuardados as $guardado): ?>
            <?php
              $savedConfig = $formatos[$guardado['tipo']] ?? null;
              if (!$savedConfig) continue;
              $fechaFormatoRaw = !empty($guardado['fecha_servicio']) ? $guardado['fecha_servicio'] : $guardado['created_at'];
              $fechaFormatoTs = strtotime($fechaFormatoRaw);
            ?>
            <div class="saved-format-card d-flex align-items-center justify-content-between p-2 border rounded bg-white gap-2">
              <a href="../controllers/formato_servicio_pdf.php?id=<?= $guardado['id'] ?>" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none text-dark flex-grow-1 min-w-0">
                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 flex-shrink-0"></i>
                <span class="min-w-0">
                  <strong class="d-block text-truncate"><?= htmlspecialchars($savedConfig['title']) ?></strong>
                  <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= $fechaFormatoTs ? date('d/m/Y H:i', $fechaFormatoTs) : htmlspecialchars($fechaFormatoRaw) ?></small>
                </span>
              </a>
              <div class="btn-group btn-group-sm flex-shrink-0">
                <a href="../controllers/formato_servicio_pdf.php?id=<?= $guardado['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Ver / Imprimir PDF">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="../controllers/formato_servicio_pdf.php?id=<?= $guardado['id'] ?>&download=1" class="btn btn-outline-danger" title="Descargar PDF">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>

<?php include('../views/footer.php'); ?>
