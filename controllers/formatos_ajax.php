<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no válida']);
    exit;
}

require '../config/db.php';
require_once '../config/formatos_mantenimiento_schema.php';
asegurarTablaFormatosMantenimiento($pdo);

$action = $_GET['action'] ?? 'list';
$arcoId = (int)($_GET['arco_id'] ?? 0);
if ($arcoId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Arco no válido']);
    exit;
}

$arcStmt = $pdo->prepare("
    SELECT a.id, a.nombre, COALESCE(u.nombre, '') AS ubicacion,
           EXISTS (SELECT 1 FROM bitacoras_arco b WHERE b.arco_id = a.id) AS tiene_bitacora
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE a.id = ?
");
$arcStmt->execute([$arcoId]);
$arco = $arcStmt->fetch(PDO::FETCH_ASSOC);

if (!$arco) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Arco no encontrado']);
    exit;
}

if ($action === 'materials') {
    $baseStmt = $pdo->prepare("
        SELECT am.id AS relacion_id, am.material_id, m.nombre AS material, m.medida,
               am.cantidad, am.serie
        FROM arco_material am
        JOIN materiales m ON m.id = am.material_id
        WHERE am.arco_id = ?
        ORDER BY am.id
    ");
    $baseStmt->execute([$arcoId]);
    $materialesBase = $baseStmt->fetchAll(PDO::FETCH_ASSOC);

    $historyStmt = $pdo->prepare("
        SELECT rm.arco_material_id AS relacion_id, rm.material_id, rm.cantidad, rm.serie,
               rm.accion, m.nombre AS material, m.medida
        FROM revision_material rm
        JOIN revisiones r ON r.id = rm.revision_id
        JOIN materiales m ON m.id = rm.material_id
        WHERE r.arco_id = ? AND rm.arco_material_id IS NOT NULL
        ORDER BY rm.arco_material_id, r.fecha_mantenimiento DESC, rm.id DESC
    ");
    $historyStmt->execute([$arcoId]);
    $latestByRelation = [];
    foreach ($historyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $relationId = (int)$row['relacion_id'];
        if ($relationId && !isset($latestByRelation[$relationId])) {
            $latestByRelation[$relationId] = $row;
        }
    }

    $materials = [];
    foreach ($materialesBase as $base) {
        $relationId = (int)$base['relacion_id'];
        $current = $latestByRelation[$relationId] ?? $base;
        if (($current['accion'] ?? 'cambio') === 'retiro') continue;
        $current['relacion_id'] = $relationId;
        $materials[] = $current;
    }

    echo json_encode(['ok' => true, 'materials' => $materials], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, tipo, creado_por, created_at
    FROM formatos_mantenimiento
    WHERE arco_id = ?
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([$arcoId]);

echo json_encode([
    'ok' => true,
    'arco' => $arco,
    'formatos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
