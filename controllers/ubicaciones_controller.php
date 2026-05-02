<?php
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);

    /* ============================================================
       🟦 ACTUALIZAR UBICACIÓN
    ============================================================ */
    if (isset($_GET['action']) && $_GET['action'] === 'update') {

        $id = $_POST['id'];

        // Verificar duplicado (excepto el mismo ID)
        $check = $pdo->prepare("SELECT id FROM ubicaciones WHERE nombre = ? AND id != ?");
        $check->execute([$nombre, $id]);

        if ($check->rowCount() > 0) {
            $error = "❌ Ya existe otra ubicación con ese nombre.";
            header("Location: ../views/materiales_ubicaciones.php?error=" . urlencode($error));
            exit;
        }

        // Obtener coordenadas de la petición (validar que existan)
        $lat = isset($_POST['lateditar']) ? $_POST['lateditar'] : null;
        $lng = isset($_POST['lngeditar']) ? $_POST['lngeditar'] : null;

        // Actualizar
        $stmt = $pdo->prepare("UPDATE ubicaciones SET nombre=?, lat=?, lng=? WHERE id=?");
        $stmt->execute([$nombre, $lat, $lng, $id]);

        $msg = "🟦 Ubicación actualizada correctamente.";
        header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
        exit;
    }

    /* ============================================================
       🟩 INSERTAR NUEVA UBICACIÓN
    ============================================================ */
    else {

        // Validar duplicado
        $check = $pdo->prepare("SELECT id FROM ubicaciones WHERE nombre=?");
        $check->execute([$nombre]);

        if ($check->rowCount() > 0) {
            $error = "❌ La ubicación ya existe.";
            header("Location: ../views/materiales_ubicaciones.php?error=" . urlencode($error));
            exit;
        }

        // Obtener coordenadas del formulario
        $lat = isset($_POST['latAgregar']) ? $_POST['latAgregar'] : null;
        $lng = isset($_POST['lngAgregar']) ? $_POST['lngAgregar'] : null;

        // Validar que se haya seleccionado ubicación
        if (empty($lat) || empty($lng)) {
            $error = "❌ Debe seleccionar una ubicación en el mapa.";
            header("Location: ../views/materiales_ubicaciones.php?error=" . urlencode($error));
            exit;
        }

        // Insertar
        $stmt = $pdo->prepare("INSERT INTO ubicaciones (nombre,lat,lng) VALUES (?,?,?)");
        $stmt->execute([$nombre, $lat, $lng]);

        $msg = "🟩 Ubicación agregada correctamente.";
        header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
        exit;
    }
}


/* ============================================================
   🟥 ELIMINAR UBICACIÓN
============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {

    $id = $_GET['id'];

    // verificar si hay arcos asociados
    $check = $pdo->prepare("SELECT COUNT(*) FROM arcos WHERE ubicacion_id=?");
    $check->execute([$id]);
    $count = $check->fetchColumn(); 
    if ($count > 0) {
        $error = "No se puede eliminar la ubicación porque está asociada a uno o más arcos.";
        header("Location: ../views/materiales_ubicaciones.php?error=" . urlencode($error));
        exit;
    }

    $pdo->prepare("DELETE FROM ubicaciones WHERE id=?")->execute([$id]);

    $msg = "🗑 Ubicación eliminada correctamente.";
    header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
    exit;
}

/* ============================================================
   🔵 OBTENER COORDENADAS (AJAX)
============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'get_coords') {
    header('Content-Type: application/json');
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['lat' => '', 'lng' => '', 'error' => 'ID no proporcionado']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT lat, lng FROM ubicaciones WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['lat' => $row['lat'] ?? '', 'lng' => $row['lng'] ?? '', 'error' => null]);
    } else {
        echo json_encode(['lat' => '', 'lng' => '', 'error' => 'Ubicación no encontrada']);
    }
    exit;
}

?>
