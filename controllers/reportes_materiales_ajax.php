<?php
require_once('../config/db.php');

if (!isset($_GET['material_id'])) {
    http_response_code(400);
    exit('Material no válido');
}

$material_id = (int) $_GET['material_id'];

$sql = "
SELECT 
    a.nombre AS arco,
    u.nombre AS ubicacion,
    COUNT(*) AS total_fallas,
    MAX(r.fecha_mantenimiento) AS ultima_fecha,
    GROUP_CONCAT(DISTINCT r.observaciones SEPARATOR ' | ') AS motivos
FROM revision_material rm
JOIN revisiones r ON rm.revision_id = r.id
JOIN arcos a ON r.arco_id = a.id
JOIN ubicaciones u ON a.ubicacion_id = u.id
WHERE rm.material_id = ?
GROUP BY a.id, u.nombre
ORDER BY total_fallas DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([$material_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);



if (!$registros) {
    echo "<p class='text-center text-muted'>No hay registros para este componente.</p>";
    exit;
}
?>

<?php
$total_general = array_sum(array_column($registros, 'total_fallas'));
$arco_mas_fallas = $registros[0]['arco'] ?? 'N/A';
?>

<div class="alert alert-info">
  <strong>Resumen:</strong><br>
  🔧 Total de fallas: <strong><?= $total_general ?></strong><br>
  📍 Arco más afectado: <strong><?= htmlspecialchars($arco_mas_fallas) ?></strong>
</div>



<table class="table table-bordered table-hover align-middle">
  <thead class="table-secondary text-center">
    <tr>
      <th>Arco</th>
      <th>Ubicación</th>
      <th>Veces Dañado</th>
      <th>Última Falla</th>
      <th>Motivo(s)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($registros as $r): ?>
    <tr>
      <td class="fw-semibold"><?= htmlspecialchars($r['arco']) ?></td>
      <td><?= htmlspecialchars($r['ubicacion']) ?></td>

      <td class="text-center fw-bold">
        <span class="badge bg-danger"><?= $r['total_fallas'] ?></span>
      </td>

      <td class="text-center">
        <?= date("d-m-Y", strtotime($r['ultima_fecha'])) ?>
      </td>

      <td>
        <?= nl2br(htmlspecialchars($r['motivos'])) ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

