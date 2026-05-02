<?php
include('../config/db.php');

$action = $_REQUEST['action'] ?? '';

switch ($action) {


/* =========================================================
   OBTENER MATERIALES DEL ARCO (AJAX)
========================================================= */
case 'get_materiales':
    $arco_id = $_GET['arco_id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT am.material_id AS id, m.medida AS medida, m.nombre AS material, am.cantidad, am.serie, m.foto
        FROM arco_material am
        JOIN materiales m ON am.material_id = m.id
        WHERE am.arco_id = ?
    ");
    $stmt->execute([$arco_id]);

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

case 'add':
    try {

        $arco_id = $_POST['arco_id'] ?? null;
        $fecha = $_POST['fecha_mantenimiento'] ?? date('Y-m-d');
        $observaciones = $_POST['observaciones'] ?? '';
        $materiales = $_POST['materiales'] ?? [];
        $tecnicoresponsable = $_POST['tecnicoresponsable'] ?? '';

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
            INSERT INTO revisiones (arco_id, fecha_mantenimiento, observaciones, tecnico_responsable)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$arco_id, $fecha, $observaciones, $tecnicoresponsable]);

        $revision_id = $pdo->lastInsertId();

        if($materiales && !is_array($materiales)) {
            header("Location: ../views/revisiones.php?error=" . urlencode("❌ Formato de materiales inválido."));
            exit;
        }

        // Insertar materiales
        foreach ($materiales as $mat) {
            $material_id = $mat['material_id'] ?? null;
            $cantidad    = intval($mat['cantidad'] ?? 0);
            $serie       = $mat['serie'] ?? null;;

            $stmt = $pdo->prepare("
                INSERT INTO revision_material (revision_id, material_id, cantidad, serie)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$revision_id, $material_id, $cantidad || 1, $serie]);
        }

        // Procesar evidencias (archivos) si se enviaron
        // Procesar evidencias (archivos)
        $savedFiles = [];


        if (!empty($_FILES['evidencias']) && !empty($_FILES['evidencias']['name'][0])) {

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

            $maxSize = 20 * 1024 * 1024; // 15MB

            foreach ($_FILES['evidencias']['tmp_name'] as $index => $tmp) {

                if ($_FILES['evidencias']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                if (!file_exists($tmp)) {
                    continue;
                }

                $name = $_FILES['evidencias']['name'][$index];
                $size = $_FILES['evidencias']['size'][$index];

                $type = mime_content_type($tmp);

                if ($size > $maxSize) {
                    continue;
                }

                if (!in_array($type, $allowed)) {
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $safeName = time() . '_' . uniqid() . '.' . $ext;

                $dest = $uploadDir . $safeName;

                if (move_uploaded_file($tmp, $dest)) {

                    $stmt = $pdo->prepare("
                        INSERT INTO revision_evidencias 
                        (revision_id, filename, mimetype)
                        VALUES (?, ?, ?)
                    ");

                    $stmt->execute([
                        $revision_id,
                        $safeName,
                        $type
                    ]);

                    $savedFiles[] = $safeName;
                }
            }
        }


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
   OBTENER EVIDENCIAS DE UNA REVISION (AJAX)
========================================================= */
case 'get_evidencias':
    $revision_id = intval($_GET['revision_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, filename, mimetype, uploaded_at FROM revision_evidencias WHERE revision_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$revision_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;


/* =========================================================
   OBTENER ARCOS POR UBICACIÓN (AJAX)
========================================================= */
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
