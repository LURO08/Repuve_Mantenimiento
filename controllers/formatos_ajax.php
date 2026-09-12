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

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/formatos_mantenimiento_schema.php';
asegurarTablaFormatosMantenimiento($pdo);

/**
 * Obtiene el inventario instalado en un arco hasta antes de un mantenimiento específico
 * o el inventario activo actual si no se proporciona $revisionId.
 */
function obtenerMaterialesArcoPrevios(PDO $pdo, int $arcoId, ?int $revisionId = null, ?string $targetFecha = null): array {
    $stmtBase = $pdo->prepare("
        SELECT am.id AS relacion_id, am.material_id, m.nombre AS material, m.medida,
               am.cantidad, am.serie, am.ip, am.mac, am.fecha_instalacion
        FROM arco_material am
        JOIN materiales m ON m.id = am.material_id
        WHERE am.arco_id = ?
        ORDER BY am.id ASC
    ");
    $stmtBase->execute([$arcoId]);
    $materialesBase = $stmtBase->fetchAll(PDO::FETCH_ASSOC);

    if ($revisionId > 0 && empty($targetFecha)) {
        $stRev = $pdo->prepare("SELECT fecha_mantenimiento FROM revisiones WHERE id = ?");
        $stRev->execute([$revisionId]);
        $targetFecha = $stRev->fetchColumn() ?: null;
    }

    if ($revisionId > 0 && $targetFecha) {
        $histStmt = $pdo->prepare("
            SELECT rm.arco_material_id AS relacion_id, rm.material_id, rm.cantidad, rm.serie, rm.ip, rm.mac, rm.accion,
                   m.medida, m.nombre AS material, r.id AS rev_id, r.fecha_mantenimiento
            FROM revision_material rm
            JOIN revisiones r ON r.id = rm.revision_id
            JOIN materiales m ON m.id = rm.material_id
            WHERE r.arco_id = ?
              AND rm.arco_material_id IS NOT NULL
              AND (r.fecha_mantenimiento < ? OR (r.fecha_mantenimiento = ? AND r.id < ?))
            ORDER BY r.fecha_mantenimiento DESC, rm.id DESC
        ");
        $histStmt->execute([$arcoId, $targetFecha, $targetFecha, $revisionId]);
    } else {
        $histStmt = $pdo->prepare("
            SELECT rm.arco_material_id AS relacion_id, rm.material_id, rm.cantidad, rm.serie, rm.ip, rm.mac, rm.accion,
                   m.medida, m.nombre AS material, r.id AS rev_id, r.fecha_mantenimiento
            FROM revision_material rm
            JOIN revisiones r ON r.id = rm.revision_id
            JOIN materiales m ON m.id = rm.material_id
            WHERE r.arco_id = ?
              AND rm.arco_material_id IS NOT NULL
            ORDER BY r.fecha_mantenimiento DESC, rm.id DESC
        ");
        $histStmt->execute([$arcoId]);
    }

    $historial = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    $ultimoPorRelacion = [];
    foreach ($historial as $row) {
        $relId = (int)$row['relacion_id'];
        if ($relId && !isset($ultimoPorRelacion[$relId])) {
            $ultimoPorRelacion[$relId] = $row;
        }
    }

    $resultado = [];
    foreach ($materialesBase as $base) {
        $relId = (int)$base['relacion_id'];
        $actual = $ultimoPorRelacion[$relId] ?? $base;

        if (($actual['accion'] ?? 'cambio') === 'retiro') {
            continue;
        }

        $resultado[] = [
            'relacion_id' => $relId,
            'material_id' => (int)$actual['material_id'],
            'material'    => $actual['material'],
            'medida'      => $actual['medida'] ?: ($base['medida'] ?? 'pz'),
            'cantidad'    => $actual['cantidad'] ?: ($base['cantidad'] ?? 1),
            'serie'       => $actual['serie'] ?? '',
            'ip'          => $actual['ip'] ?? '',
            'mac'         => $actual['mac'] ?? '',
        ];
    }

    usort($resultado, fn($a, $b) => strcasecmp($a['material'], $b['material']) ?: ($a['relacion_id'] <=> $b['relacion_id']));

    return $resultado;
}

/**
 * Obtiene el inventario instalado en un nodo de infraestructura (sitio/puente/torre)
 * hasta antes de un mantenimiento específico o el inventario activo actual si no se proporciona $infraRevisionId.
 */
function obtenerMaterialesInfraPrevios(PDO $pdo, int $infraId, ?int $infraRevisionId = null, ?string $targetFecha = null): array {
    $stmtBase = $pdo->prepare("
        SELECT im.id AS relacion_id, im.material_id, m.nombre AS material, m.medida,
               im.cantidad, im.serie, im.ip, im.mac
        FROM infraestructura_material im
        JOIN materiales m ON m.id = im.material_id
        WHERE im.infraestructura_id = ?
        ORDER BY im.id ASC
    ");
    $stmtBase->execute([$infraId]);
    $materialesBase = $stmtBase->fetchAll(PDO::FETCH_ASSOC);

    if ($infraRevisionId > 0 && empty($targetFecha)) {
        $stRev = $pdo->prepare("SELECT fecha_mantenimiento FROM infraestructura_revisiones WHERE id = ?");
        $stRev->execute([$infraRevisionId]);
        $targetFecha = $stRev->fetchColumn() ?: null;
    }

    if ($infraRevisionId > 0 && $targetFecha) {
        $histStmt = $pdo->prepare("
            SELECT COALESCE(irm.infraestructura_material_id, irm.id) AS relacion_id, irm.material_id, irm.cantidad,
                   irm.serie, irm.ip, irm.mac, irm.accion,
                   m.medida, m.nombre AS material, ir.id AS rev_id, ir.fecha_mantenimiento
            FROM infraestructura_revision_material irm
            JOIN infraestructura_revisiones ir ON ir.id = irm.revision_id
            JOIN materiales m ON m.id = irm.material_id
            WHERE ir.infraestructura_id = ?
              AND irm.infraestructura_material_id IS NOT NULL
              AND (ir.fecha_mantenimiento < ? OR (ir.fecha_mantenimiento = ? AND ir.id < ?))
            ORDER BY ir.fecha_mantenimiento DESC, irm.id DESC
        ");
        $histStmt->execute([$infraId, $targetFecha, $targetFecha, $infraRevisionId]);
    } else {
        $histStmt = $pdo->prepare("
            SELECT COALESCE(irm.infraestructura_material_id, irm.id) AS relacion_id, irm.material_id, irm.cantidad,
                   irm.serie, irm.ip, irm.mac, irm.accion,
                   m.medida, m.nombre AS material, ir.id AS rev_id, ir.fecha_mantenimiento
            FROM infraestructura_revision_material irm
            JOIN infraestructura_revisiones ir ON ir.id = irm.revision_id
            JOIN materiales m ON m.id = irm.material_id
            WHERE ir.infraestructura_id = ?
              AND irm.infraestructura_material_id IS NOT NULL
            ORDER BY ir.fecha_mantenimiento DESC, irm.id DESC
        ");
        $histStmt->execute([$infraId]);
    }

    $historial = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    $ultimoPorRelacion = [];
    foreach ($historial as $row) {
        $relId = (int)$row['relacion_id'];
        if ($relId && !isset($ultimoPorRelacion[$relId])) {
            $ultimoPorRelacion[$relId] = $row;
        }
    }

    $resultado = [];
    foreach ($materialesBase as $base) {
        $relId = (int)$base['relacion_id'];
        $actual = $ultimoPorRelacion[$relId] ?? $base;

        if (($actual['accion'] ?? 'cambio') === 'retiro') {
            continue;
        }

        $resultado[] = [
            'relacion_id' => $relId,
            'material_id' => (int)$actual['material_id'],
            'material'    => $actual['material'],
            'medida'      => $actual['medida'] ?: ($base['medida'] ?? 'pz'),
            'cantidad'    => $actual['cantidad'] ?: ($base['cantidad'] ?? 1),
            'serie'       => $actual['serie'] ?? '',
            'ip'          => $actual['ip'] ?? '',
            'mac'         => $actual['mac'] ?? '',
        ];
    }

    usort($resultado, fn($a, $b) => strcasecmp($a['material'], $b['material']) ?: ($a['relacion_id'] <=> $b['relacion_id']));

    return $resultado;
}

$action = $_GET['action'] ?? 'list';
$revisionId = (int)($_GET['revision_id'] ?? 0);
$infraRevisionId = (int)($_GET['infraestructura_revision_id'] ?? 0);
$arcoId = (int)($_GET['arco_id'] ?? 0);
$infraId = (int)($_GET['infraestructura_id'] ?? $_GET['infra_id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';

if ($revisionId > 0 && ($tipo === 'infra' || $infraRevisionId > 0)) {
    $infraRevisionId = $revisionId;
    $revisionId = 0;
}

if ($revisionId <= 0 && $infraRevisionId <= 0 && $arcoId <= 0 && $infraId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Identificador no válido']);
    exit;
}

// 1. Contexto: Mantenimiento de Infraestructura (Sitio/Puente/Torre)
if ($infraRevisionId > 0) {
    $revStmt = $pdo->prepare("
        SELECT ir.id, ir.infraestructura_id, ir.fecha_mantenimiento, ir.tipo_mantenimiento, ir.tecnico_id,
               n.nombre AS objetivo, n.tipo AS tipo_infra, COALESCE(u.nombre, '') AS ubicacion,
               t.nombre AS tecnico
        FROM infraestructura_revisiones ir
        JOIN infraestructura_nodos n ON n.id = ir.infraestructura_id
        LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
        LEFT JOIN tecnicos t ON t.id = ir.tecnico_id
        WHERE ir.id = ?
    ");
    $revStmt->execute([$infraRevisionId]);
    $rev = $revStmt->fetch(PDO::FETCH_ASSOC);

    if (!$rev) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Mantenimiento de sitio no encontrado']);
        exit;
    }

    $infraId = (int)$rev['infraestructura_id'];

    if ($action === 'materials') {
        $materials = obtenerMaterialesInfraPrevios($pdo, $infraId, $infraRevisionId, $rev['fecha_mantenimiento']);
        echo json_encode(['ok' => true, 'materials' => $materials], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, tipo, creado_por, created_at, revision_id, infraestructura_revision_id,
               COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
        FROM formatos_mantenimiento
        WHERE infraestructura_revision_id = ? OR (infraestructura_id = ? AND infraestructura_revision_id IS NULL)
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute([$infraRevisionId, $infraId]);

    echo json_encode([
        'ok' => true,
        'revision' => [
            'id' => $rev['id'],
            'infraestructura_id' => $rev['infraestructura_id'],
            'objetivo' => $rev['objetivo'],
            'ubicacion' => $rev['ubicacion'],
            'tipo_infra' => $rev['tipo_infra'],
            'fecha' => $rev['fecha_mantenimiento'],
            'tipo_mantenimiento' => $rev['tipo_mantenimiento'],
            'tecnico_id' => $rev['tecnico_id'],
            'tecnico' => $rev['tecnico'],
            'es_infra' => true
        ],
        'formatos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2. Contexto: Mantenimiento de Arco
if ($revisionId > 0) {
    $revStmt = $pdo->prepare("
        SELECT r.id, r.arco_id, r.fecha_mantenimiento, r.tipo_mantenimiento, r.tecnico_id,
               a.nombre AS objetivo, COALESCE(u.nombre, '') AS ubicacion,
               t.nombre AS tecnico
        FROM revisiones r
        JOIN arcos a ON a.id = r.arco_id
        LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
        LEFT JOIN tecnicos t ON t.id = r.tecnico_id
        WHERE r.id = ?
    ");
    $revStmt->execute([$revisionId]);
    $rev = $revStmt->fetch(PDO::FETCH_ASSOC);

    if (!$rev) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Mantenimiento no encontrado']);
        exit;
    }

    $arcoId = (int)$rev['arco_id'];

    if ($action === 'materials') {
        $materials = obtenerMaterialesArcoPrevios($pdo, $arcoId, $revisionId, $rev['fecha_mantenimiento']);
        echo json_encode(['ok' => true, 'materials' => $materials], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, tipo, creado_por, created_at, revision_id, infraestructura_revision_id,
               COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
        FROM formatos_mantenimiento
        WHERE revision_id = ? OR (arco_id = ? AND revision_id IS NULL)
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute([$revisionId, $arcoId]);

    echo json_encode([
        'ok' => true,
        'revision' => [
            'id' => $rev['id'],
            'arco_id' => $rev['arco_id'],
            'objetivo' => $rev['objetivo'],
            'ubicacion' => $rev['ubicacion'],
            'fecha' => $rev['fecha_mantenimiento'],
            'tipo_mantenimiento' => $rev['tipo_mantenimiento'],
            'tecnico_id' => $rev['tecnico_id'],
            'tecnico' => $rev['tecnico'],
            'es_infra' => false
        ],
        'formatos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 3. Contexto: Sitio/Puente directo (infraId)
if ($infraId > 0) {
    $infStmt = $pdo->prepare("
        SELECT n.id, n.nombre, n.tipo, COALESCE(u.nombre, '') AS ubicacion,
               EXISTS (SELECT 1 FROM bitacoras_arco b WHERE b.infraestructura_id = n.id) AS tiene_bitacora
        FROM infraestructura_nodos n
        LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
        WHERE n.id = ?
    ");
    $infStmt->execute([$infraId]);
    $infra = $infStmt->fetch(PDO::FETCH_ASSOC);

    if (!$infra) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Puente/Sitio no encontrado']);
        exit;
    }

    if ($action === 'materials') {
        $materials = obtenerMaterialesInfraPrevios($pdo, $infraId, null, null);
        echo json_encode(['ok' => true, 'materials' => $materials], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, tipo, creado_por, created_at, revision_id, infraestructura_revision_id,
               COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
        FROM formatos_mantenimiento
        WHERE infraestructura_id = ?
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute([$infraId]);

    echo json_encode([
        'ok' => true,
        'arco' => [
            'id' => $infra['id'],
            'nombre' => $infra['nombre'],
            'ubicacion' => $infra['ubicacion'],
            'tipo' => $infra['tipo'],
            'es_infra' => true,
            'tiene_bitacora' => (bool)$infra['tiene_bitacora']
        ],
        'formatos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 4. Contexto: Arco directo (arcoId)
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
    $materials = obtenerMaterialesArcoPrevios($pdo, $arcoId, null, null);
    echo json_encode(['ok' => true, 'materials' => $materials], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, tipo, creado_por, created_at, revision_id, infraestructura_revision_id,
           COALESCE(datos->>'fecha_servicio', created_at::text) AS fecha_servicio
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
