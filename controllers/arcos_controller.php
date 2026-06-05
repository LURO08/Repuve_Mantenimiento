<?php
include('../config/db.php');

$action = $_REQUEST['action'] ?? '';

function normalizarTipoInfraestructura($tipo)
{
  return in_array($tipo, ['Puente/Poste', 'Sitio/Torre'], true) ? $tipo : 'Puente/Poste';
}

function obtenerOCrearInfraestructura($pdo, $tipo, $nombre, $ubicacion_id, $lat, $lng, $descripcion)
{
  $stmt = $pdo->prepare("SELECT id FROM infraestructura_nodos WHERE tipo = ? AND nombre = ? LIMIT 1");
  $stmt->execute([$tipo, $nombre]);
  $id = $stmt->fetchColumn();

  if ($id) {
    $upd = $pdo->prepare("
      UPDATE infraestructura_nodos
      SET ubicacion_id = COALESCE(NULLIF(?, ''), ubicacion_id),
          lat = COALESCE(NULLIF(?, ''), lat),
          lng = COALESCE(NULLIF(?, ''), lng),
          descripcion = COALESCE(NULLIF(?, ''), descripcion)
      WHERE id = ?
    ");
    $upd->execute([$ubicacion_id, $lat, $lng, $descripcion, $id]);
    return (int)$id;
  }

  $ins = $pdo->prepare("
    INSERT INTO infraestructura_nodos (tipo, nombre, ubicacion_id, lat, lng, descripcion)
    VALUES (?, ?, ?, ?, ?, ?)
    RETURNING id
  ");
  $ins->execute([$tipo, $nombre, $ubicacion_id ?: null, $lat ?: null, $lng ?: null, $descripcion ?: null]);

  return (int)$ins->fetchColumn();
}

function guardarInfraestructuraArco($pdo, $arco_id, $fecha_instalacion, $post)
{
  $tipos = $post['infra_tipo'] ?? [];
  $nombres = $post['infra_nombre'] ?? [];
  $lats = $post['infra_lat'] ?? [];
  $lngs = $post['infra_lng'] ?? [];
  $descripciones = $post['infra_descripcion'] ?? [];
  $materiales = $post['infra_material_id'] ?? [];
  $cantidades = $post['infra_cantidad'] ?? [];
  $series = $post['infra_serie'] ?? [];

  if (!is_array($tipos)) {
    return;
  }

  $stmtRel = $pdo->prepare("
    INSERT INTO arco_infraestructura (arco_id, infraestructura_id)
    VALUES (?, ?)
    ON CONFLICT (arco_id, infraestructura_id) DO NOTHING
  ");
  $stmtMat = $pdo->prepare("
    INSERT INTO infraestructura_material (infraestructura_id, material_id, cantidad, serie, fecha_instalacion)
    VALUES (?, ?, ?, ?, ?)
  ");

  foreach ($tipos as $i => $tipoRaw) {
    $nombre = trim($nombres[$i] ?? '');
    if ($nombre === '') {
      continue;
    }

    $tipo = normalizarTipoInfraestructura($tipoRaw);
    $lat = trim($lats[$i] ?? '');
    $lng = trim($lngs[$i] ?? '');
    $descripcion = trim($descripciones[$i] ?? '');

    $infra_id = obtenerOCrearInfraestructura($pdo, $tipo, $nombre, null, $lat, $lng, $descripcion);
    $stmtRel->execute([$arco_id, $infra_id]);

    $matIds = $materiales[$i] ?? [];
    if (!is_array($matIds)) {
      continue;
    }

    foreach ($matIds as $j => $mat_id) {
      if (empty($mat_id)) {
        continue;
      }

      $cant = (float)($cantidades[$i][$j] ?? 1);
      if ($cant <= 0) {
        $cant = 1;
      }

      $serie = trim($series[$i][$j] ?? '');
      $stmtMat->execute([$infra_id, $mat_id, $cant, $serie !== '' ? $serie : null, $fecha_instalacion ?: null]);
    }
  }
}

function guardarMaterialesInfraestructura($pdo, $infra_id, $fecha_instalacion, $materiales, $cantidades, $series)
{
  if (empty($materiales) || !is_array($materiales)) {
    return;
  }

  $stmtMat = $pdo->prepare("
    INSERT INTO infraestructura_material (infraestructura_id, material_id, cantidad, serie, fecha_instalacion)
    VALUES (?, ?, ?, ?, ?)
  ");

  foreach ($materiales as $i => $mat_id) {
    if (empty($mat_id)) {
      continue;
    }

    $cant = (float)($cantidades[$i] ?? 1.0);
    if ($cant <= 0) {
      $cant = 1.0;
    }

    $serie = trim($series[$i] ?? '');
    $stmtMat->execute([$infra_id, $mat_id, $cant, $serie !== '' ? $serie : null, $fecha_instalacion ?: null]);
  }
}

try {

  switch ($action) {

      case 'get_infra':
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
          echo json_encode(['error' => 'ID inválido']);
          exit;
        }

        $stmt = $pdo->prepare("
          SELECT n.*, u.nombre AS ubicacion
          FROM infraestructura_nodos n
          LEFT JOIN ubicaciones u ON u.id = n.ubicacion_id
          WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $infra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$infra) {
          echo json_encode(['error' => 'Puente/Sitio no encontrado']);
          exit;
        }

        $stmt = $pdo->prepare("
          SELECT im.id AS relacion_id, im.material_id, im.cantidad, im.serie, im.fecha_instalacion,
                 m.nombre, m.medida, m.foto
          FROM infraestructura_material im
          JOIN materiales m ON m.id = im.material_id
          WHERE im.infraestructura_id = ?
          ORDER BY im.id ASC
        ");
        $stmt->execute([$id]);
        $materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
          SELECT a.id
          FROM arco_infraestructura ai
          JOIN arcos a ON a.id = ai.arco_id
          WHERE ai.infraestructura_id = ?
          ORDER BY a.nombre ASC
        ");
        $stmt->execute([$id]);
        $arcos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
          'id' => $infra['id'],
          'tipo' => $infra['tipo'],
          'nombre' => $infra['nombre'],
          'ubicacion_id' => $infra['ubicacion_id'],
          'lat' => $infra['lat'],
          'lng' => $infra['lng'],
          'descripcion' => $infra['descripcion'],
          'materiales' => $materiales,
          'arcos' => $arcos
        ]);
        exit;

      case 'update_infra':
        $id = (int)($_POST['id'] ?? 0);
        $tipo = normalizarTipoInfraestructura($_POST['tipo'] ?? 'Puente/Poste');
        $nombre = trim($_POST['nombre'] ?? '');
        $ubicacion_id = $_POST['ubicacion_id'] ?? null;
        $lat = trim($_POST['lat'] ?? '');
        $lng = trim($_POST['lng'] ?? '');
        $arcos_vinculados = $_POST['arcos_vinculados'] ?? [];
        $material_ids = $_POST['material_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $series = $_POST['serie'] ?? [];

        if ($id <= 0 || $nombre === '' || empty($ubicacion_id)) {
          header("Location: ../views/arcos.php?error=Datos incompletos del puente/sitio&type=error");
          exit;
        }

        if (!is_array($arcos_vinculados)) {
          $arcos_vinculados = [];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
          UPDATE infraestructura_nodos
          SET tipo = ?, nombre = ?, ubicacion_id = ?, lat = ?, lng = ?
          WHERE id = ?
        ");
        $stmt->execute([$tipo, $nombre, $ubicacion_id, $lat ?: null, $lng ?: null, $id]);

        $pdo->prepare("DELETE FROM arco_infraestructura WHERE infraestructura_id = ?")->execute([$id]);
        $stmtRel = $pdo->prepare("
          INSERT INTO arco_infraestructura (arco_id, infraestructura_id)
          VALUES (?, ?)
          ON CONFLICT (arco_id, infraestructura_id) DO NOTHING
        ");
        foreach ($arcos_vinculados as $arco_id) {
          if (!empty($arco_id)) {
            $stmtRel->execute([$arco_id, $id]);
          }
        }

        $pdo->prepare("DELETE FROM infraestructura_material WHERE infraestructura_id = ?")->execute([$id]);
        $stmtMat = $pdo->prepare("
          INSERT INTO infraestructura_material (infraestructura_id, material_id, cantidad, serie, fecha_instalacion)
          VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        if (is_array($material_ids)) {
          foreach ($material_ids as $i => $material_id) {
            if (empty($material_id)) {
              continue;
            }
            $cant = (float)($cantidades[$i] ?? 1);
            if ($cant <= 0) {
              $cant = 1;
            }
            $serie = trim($series[$i] ?? '');
            $stmtMat->execute([$id, $material_id, $cant, $serie !== '' ? $serie : null]);
          }
        }

        $pdo->commit();
        header("Location: ../views/arcos.php?msg=Puente/Sitio actualizado correctamente&type=success");
        exit;

      case 'delete_infra':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
          header("Location: ../views/arcos.php?error=ID inválido&type=error");
          exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM infraestructura_revisiones WHERE infraestructura_id = ?");
        $stmt->execute([$id]);
        $revisionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($revisionIds) {
          $placeholders = implode(',', array_fill(0, count($revisionIds), '?'));
          $pdo->prepare("DELETE FROM infraestructura_revision_material WHERE revision_id IN ($placeholders)")->execute($revisionIds);
        }

        $pdo->prepare("DELETE FROM infraestructura_revisiones WHERE infraestructura_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM infraestructura_material WHERE infraestructura_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM arco_infraestructura WHERE infraestructura_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM infraestructura_nodos WHERE id = ?")->execute([$id]);

        $pdo->commit();
        header("Location: ../views/arcos.php?msg=Puente/Sitio eliminado correctamente&type=success");
        exit;

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
      $es_infraestructura = ($_POST['es_infraestructura'] ?? '') === '1';

      if (empty($nombre) || empty($ubicacion_id) || empty($fecha_instalacion)) {
        header("Location: ../views/arcos.php?error=Faltan datos obligatorios&type=error");
        exit;
      }

      // 🔍 VALIDAR DUPLICADO
      if ($es_infraestructura) {
        $tipo_infra = normalizarTipoInfraestructura($_POST['infra_tipo_principal'] ?? 'Puente/Poste');
        $arcos_vinculados = $_POST['infra_arcos_vinculados'] ?? [];
        if (!is_array($arcos_vinculados)) {
          $arcos_vinculados = [];
        }

        $pdo->beginTransaction();

        $infra_id = obtenerOCrearInfraestructura($pdo, $tipo_infra, $nombre, $ubicacion_id, $lat, $lng, '');

        $stmtRel = $pdo->prepare("
          INSERT INTO arco_infraestructura (arco_id, infraestructura_id)
          VALUES (?, ?)
          ON CONFLICT (arco_id, infraestructura_id) DO NOTHING
        ");
        foreach ($arcos_vinculados as $arco_vinculado_id) {
          if (!empty($arco_vinculado_id)) {
            $stmtRel->execute([$arco_vinculado_id, $infra_id]);
          }
        }

        guardarMaterialesInfraestructura($pdo, $infra_id, $fecha_instalacion, $materiales, $cantidades, $series);

        $pdo->commit();

        header("Location: ../views/arcos.php?msg=Puente/Sitio registrado correctamente&type=success");
        exit;
      }

      $check = $pdo->prepare("SELECT COUNT(*) FROM arcos WHERE nombre = ?");
      $check->execute([$nombre]); 

      if ($check->fetchColumn() > 0) {
        header("Location: ../views/arcos.php?error=El arco '$nombre' ya existe&type=error");
        exit;
      }

      $pdo->beginTransaction();

      // Crear arco
      $stmt = $pdo->prepare("
        INSERT INTO arcos (nombre, ubicacion_id, fecha_instalacion, lat, lng)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
      ");
      $stmt->execute([$nombre, $ubicacion_id, $fecha_instalacion, $lat, $lng]);
      $arco_id = (int)$stmt->fetchColumn();
      

      // Materiales asociados
      if (!empty($materiales)) {
        $stmtMat = $pdo->prepare("INSERT INTO arco_material (arco_id, material_id, cantidad, serie) VALUES (?, ?, ?, ?)");
        foreach ($materiales as $i => $mat_id) {
          if (empty($mat_id)) {
            continue;
          }

          $cant = (float)($cantidades[$i] ?? 1.0);
          if ($cant <= 0) {
            $cant = 1.0;
          }

          $serie = trim($series[$i] ?? '');
          $serie = $serie !== '' ? $serie : null;
          $stmtMat->execute([$arco_id, $mat_id, $cant, $serie]);
        }
      }

      guardarInfraestructuraArco($pdo, $arco_id, $fecha_instalacion, $_POST);

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
      $todos_materiales = $pdo->query("SELECT id, nombre, medida, foto FROM materiales ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
      $materiales = $pdo->query("
        SELECT arco_material.id AS relacion_id, material_id, materiales.nombre, cantidad, materiales.medida, arco_material.serie, materiales.foto
        FROM arco_material 
        JOIN materiales ON materiales.id = arco_material.material_id
        WHERE arco_id=$id
        ORDER BY arco_material.id ASC
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
      $relacion_ids      = $_POST['relacion_id'] ?? [];
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

      $relacionesConservar = [];
      $relacionesConservarMap = [];
      if (!empty($material_ids)) {
        $stmtUpdateMat = $pdo->prepare("
          UPDATE arco_material
          SET material_id = ?, cantidad = ?, serie = ?
          WHERE id = ? AND arco_id = ?
        ");
        $stmtUpdateMatDatos = $pdo->prepare("
          UPDATE arco_material
          SET cantidad = ?, serie = ?
          WHERE id = ? AND arco_id = ?
        ");
        $stmtInsertMat = $pdo->prepare("INSERT INTO arco_material (arco_id, material_id, cantidad, serie) VALUES (?, ?, ?, ?) RETURNING id");
        $stmtBuscarMatLibre = $pdo->prepare("
          SELECT am.id
          FROM arco_material am
          WHERE am.arco_id = ?
            AND am.material_id = ?
            AND ((? IS NULL AND am.serie IS NULL) OR am.serie = ?)
            AND NOT EXISTS (
              SELECT 1
              FROM revision_material rm
              WHERE rm.arco_material_id = am.id
            )
          ORDER BY am.id ASC
        ");
        $stmtExisteMat = $pdo->prepare("
          SELECT
            am.id,
            am.material_id,
            COUNT(rm.id) AS usos_mantenimiento
          FROM arco_material am
          LEFT JOIN revision_material rm ON rm.arco_material_id = am.id
          WHERE am.id = ? AND am.arco_id = ?
          GROUP BY am.id, am.material_id
        ");

        foreach ($material_ids as $i => $mat_id) {
          if (empty($mat_id)) {
            continue;
          }

          $cant = (float)($cantidades[$i] ?? 1.0);
          if ($cant <= 0) {
            $cant = 1.0;
          }

          $serie = trim($series[$i] ?? '');
          $serie = $serie !== '' ? $serie : null;
          $relacion_id = (int)($relacion_ids[$i] ?? 0);

          $relacionActual = false;
          if ($relacion_id > 0) {
            $stmtExisteMat->execute([$relacion_id, $id]);
            $relacionActual = $stmtExisteMat->fetch(PDO::FETCH_ASSOC);
          }

          if ($relacion_id > 0 && $relacionActual) {
            if ((int)$relacionActual['usos_mantenimiento'] > 0 && (int)$relacionActual['material_id'] !== (int)$mat_id) {
              $stmtUpdateMatDatos->execute([$cant, $serie, $relacion_id, $id]);
            } else {
              $stmtUpdateMat->execute([$mat_id, $cant, $serie, $relacion_id, $id]);
            }
            $relacionesConservar[] = $relacion_id;
            $relacionesConservarMap[$relacion_id] = true;
          } else {
            $stmtBuscarMatLibre->execute([$id, $mat_id, $serie, $serie]);
            $relacionLibre = 0;

            foreach ($stmtBuscarMatLibre->fetchAll(PDO::FETCH_COLUMN) as $candidatoId) {
              $candidatoId = (int)$candidatoId;
              if (empty($relacionesConservarMap[$candidatoId])) {
                $relacionLibre = $candidatoId;
                break;
              }
            }

            if ($relacionLibre > 0) {
              $stmtUpdateMat->execute([$mat_id, $cant, $serie, $relacionLibre, $id]);
              $relacionesConservar[] = $relacionLibre;
              $relacionesConservarMap[$relacionLibre] = true;
            } else {
              $stmtInsertMat->execute([$id, $mat_id, $cant, $serie]);
              $nuevoId = (int)$stmtInsertMat->fetchColumn();
              $relacionesConservar[] = $nuevoId;
              $relacionesConservarMap[$nuevoId] = true;
            }
          }
        }
      }

      if (!empty($relacionesConservar)) {
        $placeholders = implode(',', array_fill(0, count($relacionesConservar), '?'));
        $params = array_merge([$id], $relacionesConservar);
        $pdo->prepare("
          DELETE FROM arco_material
          WHERE arco_id = ?
            AND id NOT IN ($placeholders)
            AND NOT EXISTS (
              SELECT 1
              FROM revision_material rm
              WHERE rm.arco_material_id = arco_material.id
            )
        ")->execute($params);
      } else {
        $pdo->prepare("
          DELETE FROM arco_material
          WHERE arco_id = ?
            AND NOT EXISTS (
              SELECT 1
              FROM revision_material rm
              WHERE rm.arco_material_id = arco_material.id
            )
        ")->execute([$id]);
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

      $pdo->prepare("DELETE FROM arco_infraestructura WHERE arco_id=?")->execute([$id]);
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
