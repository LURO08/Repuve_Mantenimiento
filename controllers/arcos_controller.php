<?php
include('../config/db.php');

$action = $_REQUEST['action'] ?? '';

try {

  switch ($action) {

    /* =========================================================
       🟢 AGREGAR ARCO
    ========================================================= */
    case 'add':

      $nombre            = trim($_POST['nombre'] ?? '');
      $ubicacion_id      = $_POST['ubicacion_id'] ?? null;
      $fecha_instalacion = $_POST['fecha_instalacion'] ?? null;
      $materiales      = $_POST['material_id'] ?? [];
      $cantidades        = $_POST['cantidad'] ?? [];
      $series           = $_POST['serie'] ?? [];
      $lat               = $_POST['lat'] ?? null;
      $lng               = $_POST['lng'] ?? null;

      if (empty($nombre) || empty($ubicacion_id) || empty($fecha_instalacion)) {
        header("Location: ../views/arcos.php?error=Faltan datos obligatorios&type=error");
        exit;
      }

      // 🔍 VALIDAR DUPLICADO
      $check = $pdo->prepare("SELECT COUNT(*) FROM arcos WHERE nombre = ?");
      $check->execute([$nombre]); 

      if ($check->fetchColumn() > 0) {
        header("Location: ../views/arcos.php?error=El arco '$nombre' ya existe&type=error");
        exit;
      }

      $pdo->beginTransaction();

      // Crear arco
      $stmt = $pdo->prepare("INSERT INTO arcos (nombre, ubicacion_id, fecha_instalacion, lat, lng) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$nombre, $ubicacion_id, $fecha_instalacion, $lat, $lng]);
      $arco_id = $pdo->lastInsertId();
      

      // Materiales asociados
      if (!empty($materiales)) {
        $stmtMat = $pdo->prepare("INSERT INTO arco_material (arco_id, material_id, cantidad, serie) VALUES (?, ?, ?, ?)");
        foreach ($materiales as $i => $mat_id) {
          $cant = (float)($cantidades[$i] ?? 1.0);
          $serie = $series[$i] ?? null;
          $stmtMat->execute([$arco_id, $mat_id, $cant, $serie]);
        }
      }

      $pdo->commit();
    
      header("Location: ../views/arcos.php?msg=Arco registrado correctamente&type=success");
      exit;



    /* =========================================================
       🟡 OBTENER ARCO (AJAX)
    ========================================================= */
    case 'get':

      header('Content-Type: application/json');
      $id = (int)($_GET['id'] ?? 0);

      if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
      }

      $arco = $pdo->query("SELECT * FROM arcos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
      if (!$arco) {
        echo json_encode(['error' => 'Arco no encontrado']);
        exit;
      }
      $ubicaciones = $pdo->query("SELECT id, nombre FROM ubicaciones ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
      $todos_materiales = $pdo->query("SELECT id, nombre, medida FROM materiales ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
      $materiales = $pdo->query("
        SELECT material_id, cantidad, materiales.medida, arco_material.serie
        FROM arco_material 
        JOIN materiales ON materiales.id = arco_material.material_id
        WHERE arco_id=$id
      ")->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode([
        'id' => $arco['id'],
        'nombre' => $arco['nombre'],
        'ubicacion_id' => $arco['ubicacion_id'],
        'fecha_instalacion' => $arco['fecha_instalacion'],
        'lat' => $arco['lat'] ?? null,
        'lng' => $arco['lng'] ?? null,
        'ubicaciones' => $ubicaciones,
        'materiales' => $materiales,
        'todos_materiales' => $todos_materiales
      ]);
      exit;



    /* =========================================================
       🟡 ACTUALIZAR ARCO
    ========================================================= */
    case 'update':

      $id                = $_POST['id'] ?? null;
      $nombre            = trim($_POST['nombre'] ?? '');
      $ubicacion_id      = $_POST['ubicacion_id'] ?? null;
      $fecha_instalacion = $_POST['fecha_instalacion'] ?? null;
      $lat               = $_POST['lat'] ?? null;
      $lng               = $_POST['lng'] ?? null;
      $material_ids      = $_POST['material_id'] ?? [];
      $cantidades        = $_POST['cantidad'] ?? [];
      $series           = $_POST['serie'] ?? [];

      if (empty($id) || empty($nombre) || empty($ubicacion_id) || empty($fecha_instalacion)) {
        header("Location: ../views/arcos.php?error=Datos incompletos&type=error");
        exit;
      }

      // 🔍 VALIDAR DUPLICADO excepto el mismo ID
      $check = $pdo->prepare("SELECT COUNT(*) FROM arcos WHERE nombre = ? AND id != ?");
      $check->execute([$nombre, $id]);

      if ($check->fetchColumn() > 0) {
        header("Location: ../views/arcos.php?error=Otro arco ya se llama '$nombre'&type=error");
        exit;
      }

      $pdo->beginTransaction();

      // Actualizar arco (incluyendo lat/lng)
      $stmt = $pdo->prepare("UPDATE arcos SET nombre=?, ubicacion_id=?, fecha_instalacion=?, lat=?, lng=? WHERE id=?");
      $stmt->execute([$nombre, $ubicacion_id, $fecha_instalacion, $lat, $lng, $id]);

      // Borrar y reinsertar materiales
      $pdo->prepare("DELETE FROM arco_material WHERE arco_id=?")->execute([$id]);

      if (!empty($material_ids)) {
        $stmtMat = $pdo->prepare("INSERT INTO arco_material (arco_id, material_id, cantidad, serie) VALUES (?, ?, ?, ?)");
        foreach ($material_ids as $i => $mat_id) {
         $cant = (float)($cantidades[$i] ?? 1.0);
          $serie = $series[$i] ?? null;
           $stmtMat->execute([$id, $mat_id, $cant, $serie]);
        }
      }

      $pdo->commit();
      header("Location: ../views/arcos.php?msg=Arco actualizado correctamente&type=success");
      exit;




    /* =========================================================
       🔴 ELIMINAR ARCO
    ========================================================= */
    case 'delete':

      $id = $_GET['id'] ?? 0;

      if (empty($id)) {
        header("Location: ../views/arcos.php?error=ID inválido&type=error");
        exit;
      }

      $pdo->beginTransaction();

      $pdo->prepare("DELETE FROM arco_material WHERE arco_id=?")->execute([$id]);
      $pdo->prepare("DELETE FROM arcos WHERE id=?")->execute([$id]);

      $pdo->commit();

      header("Location: ../views/arcos.php?msg=Arco eliminado correctamente&type=success");
      exit;


      case 'get_arcos':

        $ubicacion_id = intval($_GET['ubicacion_id']);

        $stmt = $pdo->prepare("
            SELECT 
                arcos.id,
                arcos.nombre,
                arcos.lat,
                arcos.lng,
                COUNT(revisiones.id) AS fallas
            FROM arcos
            LEFT JOIN revisiones ON arcos.id = revisiones.arco_id
            WHERE arcos.ubicacion_id = ?
            GROUP BY arcos.id, arcos.nombre, arcos.lat, arcos.lng
            ORDER BY arcos.nombre ASC

        ");
        $stmt->execute([$ubicacion_id]);
        $arcos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($arcos);
        exit;

    /* =========================================================
       🚫 ACCIÓN DESCONOCIDA
    ========================================================= */
    default:
      header("Location: ../views/arcos.php");
      exit;
  }

} catch (Exception $e) {

  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  header("Location: ../views/arcos.php?msg=" . urlencode($e->getMessage()) . "&type=error");
  exit;
}
?>
