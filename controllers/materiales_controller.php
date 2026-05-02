<?php
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'];
    $medida = $_POST['medida'];
    $foto = null;

    $targetDir = "../uploads/materiales/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    /* ============================================================
       🟦 UPDATE MATERIAL
    ============================================================ */
    if (isset($_GET['action']) && $_GET['action'] === 'update') {

        $id = $_POST['id'];

        // Obtener información actual
        $stmt = $pdo->prepare("SELECT foto FROM materiales WHERE id=?");
        $stmt->execute([$id]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);

        /* ---- FOTO NUEVA ---- */
        if (!empty($_FILES['foto']['name'])) {

            // Borrar foto anterior
            if (!empty($actual['foto']) && file_exists($targetDir . $actual['foto'])) {
                unlink($targetDir . $actual['foto']);
            }

            // Guardar nueva foto
            $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
                $foto = $fileName;
            }

            // Actualizar con foto
            $query = "UPDATE materiales SET nombre=?, medida=?, foto=? WHERE id=?";
            $pdo->prepare($query)->execute([$nombre, $medida, $foto, $id]);

        } else {

            // Sin cambiar foto
            $query = "UPDATE materiales SET nombre=?, medida=? WHERE id=?";
            $pdo->prepare($query)->execute([$nombre, $medida, $id]);
        }

        $msg = "🟦 Material actualizado correctamente.";
        header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
        exit;
    }

    /* ============================================================
       🟩 INSERTAR NUEVO MATERIAL
    ============================================================ */
    else {

        if (!empty($_FILES['foto']['name'])) {
            $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
                $foto = $fileName;
            }
        }

        $query = "INSERT INTO materiales (nombre, medida, foto) VALUES (?, ?, ?)";
        $pdo->prepare($query)->execute([$nombre, $medida, $foto]);

        $msg = "🟩 Material agregado correctamente.";
        header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
        exit;
    }
}


/* ============================================================
   🟥 ELIMINAR MATERIAL
============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {

    $id = $_GET['id'];

    // OBTENER REGISTROS RELACIONADOS
    $stmtRel = $pdo->prepare("SELECT COUNT(*) AS cnt FROM arco_material WHERE material_id=?");
    $stmtRel->execute([$id]);
    $rel = $stmtRel->fetch(PDO::FETCH_ASSOC);

    // Si hay registros relacionados, no se permite eliminar
    if ($rel['cnt'] > 0) {
        $error = "No se puede eliminar el material porque está asociado a uno o más arcos.";
        header("Location: ../views/materiales_ubicaciones.php?error=" . urlencode($error));
        exit;
    }

    // Obtener datos
    $stmt = $pdo->prepare("SELECT foto FROM materiales WHERE id=?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    // Borrar foto
    if (!empty($material['foto'])) {
        $fotoPath = "../uploads/materiales/" . $material['foto'];
        if (file_exists($fotoPath)) unlink($fotoPath);
    }

    // Borrar registro
    $pdo->prepare("DELETE FROM materiales WHERE id=?")->execute([$id]);

    $msg = "🗑 Material eliminado correctamente.";
    header("Location: ../views/materiales_ubicaciones.php?msg=" . urlencode($msg));
    exit;
}

?>
