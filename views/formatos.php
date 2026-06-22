<?php
include('../views/header.php');
include('../config/db.php');
require_once '../config/formatos_mantenimiento_schema.php';

$formatos = require '../config/formatos_servicio.php';
asegurarTablaFormatosMantenimiento($pdo);
$arcoId = (int)($_GET['arco_id'] ?? 0);
$arcoSeleccionado = null;
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
      SELECT id, tipo, creado_por, created_at
      FROM formatos_mantenimiento
      WHERE arco_id = ?
      ORDER BY created_at DESC, id DESC
    ");
    $savedStmt->execute([$arcoId]);
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
  <?php endif; ?>

  <section class="formats-grid" aria-label="Formatos disponibles">
    <?php foreach ($formatos as $type => $formato): ?>
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
          <a class="btn btn-success" href="formato_llenar.php?type=<?= urlencode($type) ?><?= $arcoSeleccionado ? '&amp;arco_id=' . $arcoSeleccionado['id'] : '' ?>">
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

  <?php if ($arcoSeleccionado): ?>
    <section class="saved-formats saved-formats--compact">
      <div class="saved-formats__heading">
        <div>
          <h2>PDF vinculados al arco</h2>
          <p>Documentos previos generados para este sitio.</p>
        </div>
        <span><?= count($formatosGuardados) ?> archivo(s)</span>
      </div>
      <?php if (!$formatosGuardados): ?>
        <div class="saved-formats__empty">Todavía no hay formatos de servicio vinculados.</div>
      <?php else: ?>
        <div class="saved-formats__list">
          <?php foreach ($formatosGuardados as $guardado): ?>
            <?php $savedConfig = $formatos[$guardado['tipo']] ?? null; if (!$savedConfig) continue; ?>
            <a href="../controllers/formato_servicio_pdf.php?id=<?= $guardado['id'] ?>" target="_blank">
              <i class="bi bi-file-earmark-pdf-fill"></i>
              <span>
                <strong><?= htmlspecialchars($savedConfig['title']) ?></strong>
                <small><?= date('d/m/Y H:i', strtotime($guardado['created_at'])) ?></small>
              </span>
              <i class="bi bi-box-arrow-up-right"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>

<?php include('../views/footer.php'); ?>
