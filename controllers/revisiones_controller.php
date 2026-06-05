<?php
include('../config/db.php');

$action = $_REQUEST['action'] ?? '';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS infraestructura_revision_evidencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        revision_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        mimetype VARCHAR(100) DEFAULT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_infra_revision_evidencias_revision_id (revision_id),
        FOREIGN KEY (revision_id) REFERENCES infraestructura_revisiones(id) ON DELETE CASCADE
    )
");

function guardarEvidenciasRevision(PDO $pdo, int $revision_id, string $tabla): array
{
    if (!in_array($tabla, ['revision_evidencias', 'infraestructura_revision_evidencias'], true)) {
        return [];
    }

    if (empty($_FILES['evidencias']) || empty($_FILES['evidencias']['name'][0])) {
        return [];
    }

    $uploadDir = __DIR__ . '/../uploads/revisiones/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf'
    ];
    $maxSize = 20 * 1024 * 1024;
    $stmt = $pdo->prepare("
        INSERT INTO {$tabla} (revision_id, filename, mimetype)
        VALUES (?, ?, ?)
    ");
    $savedFiles = [];

    foreach ($_FILES['evidencias']['tmp_name'] as $index => $tmp) {
        if ($_FILES['evidencias']['error'][$index] !== UPLOAD_ERR_OK || !file_exists($tmp)) {
            continue;
        }

        $name = $_FILES['evidencias']['name'][$index];
        $size = $_FILES['evidencias']['size'][$index];
        $type = mime_content_type($tmp);

        if ($size > $maxSize || !in_array($type, $allowed, true)) {
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeName = time() . '_' . uniqid('', true) . '.' . $ext;
        $dest = $uploadDir . $safeName;

        if (move_uploaded_file($tmp, $dest)) {
            $stmt->execute([$revision_id, $safeName, $type]);
            $savedFiles[] = $safeName;
        }
    }

    return $savedFiles;
}

switch ($action) {


/* =========================================================
   OBTENER MATERIALES DEL ARCO (AJAX)
========================================================= */
case 'get_materiales':
    $arco_id = $_GET['arco_id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT am.id AS relacion_id, am.material_id AS id, m.medida AS medida, m.nombre AS material, am.cantidad, am.serie, m.foto
        FROM arco_material am
        JOIN materiales m ON am.material_id = m.id
        WHERE am.arco_id = ?
        ORDER BY am.id ASC
    ");
    $stmt->execute([$arco_id]);

    $materialesBase = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $historialStmt = $pdo->prepare("
        SELECT rm.arco_material_id AS relacion_id, rm.material_id AS id, rm.cantidad, rm.serie, rm.accion,
               m.medida AS medida, m.nombre AS material, m.foto
        FROM revision_material rm
        JOIN revisiones r ON rm.revision_id = r.id
        JOIN materiales m ON rm.material_id = m.id
        WHERE r.arco_id = ?
          AND rm.arco_material_id IS NOT NULL
        ORDER BY rm.arco_material_id ASC, r.fecha_mantenimiento DESC, rm.id DESC
    ");
    $historialStmt->execute([$arco_id]);

    $ultimoPorRelacion = [];
    foreach ($historialStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $relacionId = (int)($row['relacion_id'] ?? 0);
        if ($relacionId && !isset($ultimoPorRelacion[$relacionId])) {
            $ultimoPorRelacion[$relacionId] = $row;
        }
    }

    $materialesActuales = [];
    foreach ($materialesBase as $base) {
        $relacionId = (int)($base['relacion_id'] ?? 0);
        $actual = $ultimoPorRelacion[$relacionId] ?? $base;

        if (($actual['accion'] ?? 'cambio') === 'retiro') {
            continue;
        }

        $actual['relacion_id'] = $relacionId;
        $materialesActuales[] = $actual;
    }

    echo json_encode($materialesActuales);
    exit;

case 'get_infraestructuras':
    $ubicacion_id = intval($_GET['ubicacion_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id, tipo, nombre, lat, lng
        FROM infraestructura_nodos
        WHERE ubicacion_id = ?
        ORDER BY tipo ASC, nombre ASC
    ");
    $stmt->execute([$ubicacion_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;

case 'get_infra_materiales':
    $infraestructura_id = intval($_GET['infraestructura_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT im.material_id AS id, m.medida AS medida, m.nombre AS material, im.cantidad, im.serie, m.foto
        FROM infraestructura_material im
        JOIN materiales m ON im.material_id = m.id
        WHERE im.infraestructura_id = ?
        ORDER BY im.id ASC
    ");
    $stmt->execute([$infraestructura_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;



/* =========================================================
   AGREGAR REVISION / MANTENIMIENTO
========================================================= */

case 'get_all_materiales':
    $stmt = $pdo->query("SELECT id, nombre, medida, foto FROM materiales ORDER BY nombre ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;  
    
case 'get_material_info':
    $material_id = intval($_GET['material_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, nombre, medida, foto FROM materiales WHERE id = ?");
    $stmt->execute([$material_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;

case 'add_infra':
    try {
        $infraestructura_id = $_POST['infraestructura_id'] ?? null;
        $fecha = $_POST['fecha_mantenimiento'] ?? date('Y-m-d H:i:s');
        $tipo_mantenimiento = $_POST['tipo_mantenimiento'] ?? 'Correctivo';
        $observaciones = $_POST['observaciones'] ?? '';
        $tecnicoresponsable = $_POST['tecnicoresponsable'] ?? '';
        $materiales = $_POST['infra_material_id'] ?? [];
        $cantidades = $_POST['infra_cantidad'] ?? [];
        $series = $_POST['infra_serie'] ?? [];

        if (!in_array($tipo_mantenimiento, ['Preventivo', 'Correctivo'], true)) {
            $tipo_mantenimiento = 'Correctivo';
        }

        if (!$infraestructura_id) {
            header("Location: ../views/revisiones.php?error=" . urlencode("Debes seleccionar un puente o sitio."));
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM infraestructura_nodos WHERE id = ?");
        $stmt->execute([$infraestructura_id]);
        if (!$stmt->fetchColumn()) {
            header("Location: ../views/revisiones.php?error=" . urlencode("El puente o sitio no existe."));
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO infraestructura_revisiones (infraestructura_id, fecha_mantenimiento, tipo_mantenimiento, observaciones, tecnico_responsable)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$infraestructura_id, $fecha, $tipo_mantenimiento, $observaciones, $tecnicoresponsable]);
        $revision_id = $pdo->lastInsertId();

        $stmtMat = $pdo->prepare("
            INSERT INTO infraestructura_revision_material (revision_id, material_id, cantidad, serie)
            VALUES (?, ?, ?, ?)
        ");

        if (is_array($materiales)) {
            foreach ($materiales as $i => $material_id) {
                if (empty($material_id)) {
                    continue;
                }

                $cantidad = (float)($cantidades[$i] ?? 1);
                if ($cantidad <= 0) {
                    $cantidad = 1;
                }

                $serie = trim($series[$i] ?? '');
                $stmtMat->execute([$revision_id, $material_id, $cantidad, $serie !== '' ? $serie : null]);
            }
        }

        guardarEvidenciasRevision($pdo, (int)$revision_id, 'infraestructura_revision_evidencias');

        $pdo->commit();
        header("Location: ../views/revisiones.php?msg=" . urlencode("Mantenimiento de puente/sitio registrado correctamente."));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../views/revisiones.php?error=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }

case 'add':
    try {

        $arco_id = $_POST['arco_id'] ?? null;
        $es_infraestructura_revision = ($_POST['es_infraestructura_revision'] ?? '') === '1';
        $infraestructura_id = $_POST['infraestructura_id'] ?? null;
        $fecha = $_POST['fecha_mantenimiento'] ?? date('Y-m-d');
        $tipo_mantenimiento = $_POST['tipo_mantenimiento'] ?? 'Correctivo';
        $observaciones = $_POST['observaciones'] ?? '';
        $materiales = $_POST['materiales'] ?? [];
        $tecnicoresponsable = $_POST['tecnicoresponsable'] ?? '';

        if (!in_array($tipo_mantenimiento, ['Preventivo', 'Correctivo'], true)) {
            $tipo_mantenimiento = 'Correctivo';
        }

        if ($es_infraestructura_revision) {
            if (!$infraestructura_id) {
                header("Location: ../views/revisiones.php?error=" . urlencode("Debes seleccionar un puente o sitio."));
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM infraestructura_nodos WHERE id = ?");
            $stmt->execute([$infraestructura_id]);
            if (!$stmt->fetchColumn()) {
                header("Location: ../views/revisiones.php?error=" . urlencode("El puente o sitio no existe."));
                exit;
            }

            if($materiales && !is_array($materiales)) {
                header("Location: ../views/revisiones.php?error=" . urlencode("Formato de materiales inválido."));
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO infraestructura_revisiones (infraestructura_id, fecha_mantenimiento, tipo_mantenimiento, observaciones, tecnico_responsable)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$infraestructura_id, $fecha, $tipo_mantenimiento, $observaciones, $tecnicoresponsable]);
            $revision_id = $pdo->lastInsertId();

            $stmtMat = $pdo->prepare("
                INSERT INTO infraestructura_revision_material (revision_id, material_id, cantidad, serie)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($materiales as $mat) {
                $material_id = $mat['material_id'] ?? null;
                if (empty($material_id)) {
                    continue;
                }

                $cantidad = (float)($mat['cantidad'] ?? 1);
                if ($cantidad <= 0) {
                    $cantidad = 1;
                }
                $serie = $mat['serie'] ?? null;
                $stmtMat->execute([$revision_id, $material_id, $cantidad, $serie]);
            }

            guardarEvidenciasRevision($pdo, (int)$revision_id, 'infraestructura_revision_evidencias');

            $pdo->commit();

            header("Location: ../views/revisiones.php?msg=" . urlencode("Mantenimiento de puente/sitio registrado correctamente."));
            exit;
        }

        if (!$arco_id) {
            header("Location: ../views/revisiones.php?error=" . urlencode("❌ Debes seleccionar un arco."));
            exit;
        }

        /* =====================================================
           VALIDACIÓN: NO PERMITIR REGISTRO DUPLICADO
           - Mismo arco
           - Misma ubicación
           - Misma fecha
        ===================================================== */

        // Obtener ubicación del arco
        $stmt = $pdo->prepare("SELECT ubicacion_id FROM arcos WHERE id = ?");
        $stmt->execute([$arco_id]);
        $arco = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$arco) {
            header("Location: ../views/revisiones.php?error=" . urlencode("❌ El arco no existe."));
            exit;
        }

        $ubicacion_id = $arco['ubicacion_id'];

        // Buscar si ya existe revisión igual
        $stmt = $pdo->prepare("
            SELECT r.id
            FROM revisiones r
            JOIN arcos a ON r.arco_id = a.id
            WHERE r.arco_id = ?
            AND a.ubicacion_id = ?
            AND r.fecha_mantenimiento = ?
            LIMIT 1
        ");
        $stmt->execute([$arco_id, $ubicacion_id, $fecha]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../views/revisiones.php?error=" . urlencode("❌ Ya existe una revisión con el MISMO ARCO, misma UBICACIÓN y misma FECHA."));
            exit;
        }

        /* =====================================================
           SI PASA LA VALIDACIÓN → GUARDAR
        ===================================================== */
        $pdo->beginTransaction();

        // Insertar revisión
        $stmt = $pdo->prepare("
            INSERT INTO revisiones (arco_id, fecha_mantenimiento, tipo_mantenimiento, observaciones, tecnico_responsable)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$arco_id, $fecha, $tipo_mantenimiento, $observaciones, $tecnicoresponsable]);

        $revision_id = $pdo->lastInsertId();

        if($materiales && !is_array($materiales)) {
            header("Location: ../views/revisiones.php?error=" . urlencode("❌ Formato de materiales inválido."));
            exit;
        }

        // Insertar materiales
        $stmt = $pdo->prepare("
            INSERT INTO revision_material (revision_id, arco_material_id, material_id, cantidad, serie, accion)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($materiales as $mat) {
            $material_id = $mat['material_id'] ?? null;
            if (empty($material_id)) {
                continue;
            }

            $arco_material_id = !empty($mat['arco_material_id']) ? (int)$mat['arco_material_id'] : null;
            $cantidad = (float)($mat['cantidad'] ?? 1);
            if ($cantidad <= 0) {
                $cantidad = 1;
            }
            $serie = trim($mat['serie'] ?? '');
            $accion = $mat['accion'] ?? 'cambio';
            if (!in_array($accion, ['cambio', 'retiro'], true)) {
                $accion = 'cambio';
            }

            $stmt->execute([
                $revision_id,
                $arco_material_id,
                $material_id,
                $cantidad,
                $serie !== '' ? $serie : null,
                $accion
            ]);
        }

        guardarEvidenciasRevision($pdo, (int)$revision_id, 'revision_evidencias');


        $pdo->commit();

        header("Location: ../views/revisiones.php?msg=" . urlencode("✅ Revisión registrada correctamente."));
        exit;

    } catch (Exception $e) {

           if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        header(header: "Location: ../views/revisiones.php?error=" . urlencode("❌ Error: " . $e->getMessage()));
        exit;
    }



/* =========================================================
   ELIMINAR REVISION + MATERIALES
========================================================= */
case 'delete':
    $id = $_POST['id'] ?? 0;

    try {
        $pdo->beginTransaction();

        // Obtener todos los archivos
        $stmt = $pdo->prepare("
            SELECT filename 
            FROM revision_evidencias 
            WHERE revision_id = ?
        ");
        $stmt->execute([$id]);

        $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $uploadDir = realpath(__DIR__ . '/../uploads/revisiones');

        foreach ($archivos as $archivo) {
            if (empty($archivo['filename'])) continue;

            $ruta = $uploadDir . DIRECTORY_SEPARATOR . $archivo['filename'];

            // verificar existencia y borrar
            if (file_exists($ruta) && is_file($ruta)) {
                unlink($ruta);
            }
        }

        // eliminar registros evidencias
        $stmt = $pdo->prepare("
            DELETE FROM revision_evidencias 
            WHERE revision_id = ?
        ");
        $stmt->execute([$id]);

        // eliminar materiales
        $stmt = $pdo->prepare("
            DELETE FROM revision_material 
            WHERE revision_id = ?
        ");
        $stmt->execute([$id]);

        // eliminar revisión
        $stmt = $pdo->prepare("
            DELETE FROM revisiones 
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $pdo->commit();

        header("Location: ../views/revisiones.php?msg=" . urlencode("🗑 Revisión eliminada correctamente."));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header("Location: ../views/revisiones.php?error=" . urlencode("❌ Error al eliminar: " . $e->getMessage()));
        exit;
    }


/* =========================================================
   ELIMINAR MANTENIMIENTO DE PUENTE / SITIO
========================================================= */
case 'delete_infra':
    $id = intval($_POST['id'] ?? 0);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT filename FROM infraestructura_revision_evidencias WHERE revision_id = ?");
        $stmt->execute([$id]);
        $evidencias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $uploadDir = realpath(__DIR__ . '/../uploads/revisiones');

        $stmt = $pdo->prepare("
            DELETE FROM infraestructura_revision_material
            WHERE revision_id = ?
        ");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("
            DELETE FROM infraestructura_revisiones
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $pdo->commit();

        if ($uploadDir) {
            foreach ($evidencias as $filename) {
                $path = $uploadDir . DIRECTORY_SEPARATOR . basename($filename);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        header("Location: ../views/revisiones.php?msg=" . urlencode("Mantenimiento de puente/sitio eliminado correctamente."));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header("Location: ../views/revisiones.php?error=" . urlencode("Error al eliminar mantenimiento de puente/sitio: " . $e->getMessage()));
        exit;
    }



/* =========================================================
   OBTENER EVIDENCIAS DE UNA REVISION (AJAX)
========================================================= */
case 'get_evidencias':
    $revision_id = intval($_GET['revision_id'] ?? 0);
    $tipo = $_GET['tipo'] ?? 'arco';
    $tabla = $tipo === 'infra' ? 'infraestructura_revision_evidencias' : 'revision_evidencias';
    $stmt = $pdo->prepare("SELECT id, filename, mimetype, uploaded_at FROM {$tabla} WHERE revision_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$revision_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['tipo'] = $tipo === 'infra' ? 'infra' : 'arco';
    }
    echo json_encode($rows);
    exit;


/* =========================================================
   OBTENER ARCOS POR UBICACIÓN (AJAX)
========================================================= */
/* =========================================================
   MINIATURA LIGERA DE EVIDENCIA
========================================================= */
case 'thumb_evidencia':
    $id = intval($_GET['id'] ?? 0);
    $width = max(120, min(420, intval($_GET['w'] ?? 260)));
    $tipo = $_GET['tipo'] ?? 'arco';
    $tabla = $tipo === 'infra' ? 'infraestructura_revision_evidencias' : 'revision_evidencias';

    $stmt = $pdo->prepare("SELECT filename, mimetype FROM {$tabla} WHERE id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ev || empty($ev['filename']) || empty($ev['mimetype']) || strpos($ev['mimetype'], 'image/') !== 0) {
        http_response_code(404);
        exit;
    }

    $uploadDir = realpath(__DIR__ . '/../uploads/revisiones');
    if (!$uploadDir) {
        http_response_code(404);
        exit;
    }

    $filename = basename($ev['filename']);
    $source = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!is_file($source)) {
        http_response_code(404);
        exit;
    }

    $thumbDir = $uploadDir . DIRECTORY_SEPARATOR . '_thumbs';
    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0775, true);
    }

    $cacheName = ($tipo === 'infra' ? 'infra_' : 'arco_') . $id . '_' . filemtime($source) . '_' . $width . '.jpg';
    $cachePath = $thumbDir . DIRECTORY_SEPARATOR . $cacheName;

    if (is_file($cachePath)) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=604800');
        readfile($cachePath);
        exit;
    }

    if (!function_exists('imagecreatetruecolor')) {
        header('Content-Type: ' . $ev['mimetype']);
        header('Cache-Control: public, max-age=3600');
        readfile($source);
        exit;
    }

    $mime = $ev['mimetype'];
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        $img = @imagecreatefromjpeg($source);
    } elseif ($mime === 'image/png') {
        $img = @imagecreatefrompng($source);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($source);
    } else {
        $img = false;
    }

    if (!$img) {
        header('Content-Type: ' . $ev['mimetype']);
        header('Cache-Control: public, max-age=3600');
        readfile($source);
        exit;
    }

    $srcW = imagesx($img);
    $srcH = imagesy($img);
    $height = max(1, intval(($srcH / max(1, $srcW)) * $width));

    $thumb = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $width, $height, $srcW, $srcH);

    if (is_dir($thumbDir) && is_writable($thumbDir)) {
        @imagejpeg($thumb, $cachePath, 62);
    }

    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=604800');
    imagejpeg($thumb, null, 62);

    imagedestroy($img);
    imagedestroy($thumb);
    exit;

case 'get_arcos':
    $ubicacion_id = intval($_GET['ubicacion_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id, nombre, lat, lng
        FROM arcos
        WHERE ubicacion_id = ?
        ORDER BY nombre ASC
    ");
    $stmt->execute([$ubicacion_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;




/* =========================================================
   DEFAULT
========================================================= */
default:
    header("Location: ../views/revisiones.php");
    exit;
}

?>
