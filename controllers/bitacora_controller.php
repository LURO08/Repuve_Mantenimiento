<?php
include('../config/db.php');
require_once '../config/tecnicos_schema.php';
asegurarRelacionTecnicos($pdo);

$arco_id = !empty($_POST['arco_id']) ? (int)$_POST['arco_id'] : null;
$infra_id = !empty($_POST['infraestructura_id']) ? (int)$_POST['infraestructura_id'] : null;
$tecnicoId = (int)($_POST['encargado'] ?? $_POST['tecnico_id'] ?? 0);
$tecnico = obtenerTecnicoPorId($pdo, $tecnicoId);
$observaciones = trim($_POST['observaciones'] ?? '');
$checks = $_POST['checklist'] ?? [];

if (!$tecnico) {
    die("Debes seleccionar un técnico válido.");
}

if (!$arco_id && !$infra_id) {
    die("Debes especificar un arco o sitio válido.");
}

try {
    /* verificar si ya existe */
    if ($infra_id) {
        $validar = $pdo->prepare("
            SELECT id
            FROM bitacoras_arco
            WHERE infraestructura_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $validar->execute([$infra_id]);
    } else {
        $validar = $pdo->prepare("
            SELECT id
            FROM bitacoras_arco
            WHERE arco_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $validar->execute([$arco_id]);
    }

    $existente = $validar->fetch(PDO::FETCH_ASSOC);

    /* si ya existe -> solo abrir PDF */
    if ($existente) {
        if ($infra_id) {
            header("Location: ../views/pdf/bitacora_arco.php?tipo=infra&id=$infra_id");
        } else {
            header("Location: ../views/pdf/bitacora_arco.php?id=$arco_id");
        }
        exit;
    }

    /* crear bitácora */
    $stmt = $pdo->prepare("
        INSERT INTO bitacoras_arco (
            arco_id,
            infraestructura_id,
            tecnico_id,
            observaciones
        ) VALUES (?, ?, ?, ?)
        RETURNING id
    ");

    $stmt->execute([
        $arco_id,
        $infra_id,
        $tecnicoId,
        $observaciones
    ]);

    $bitacora_id = (int)$stmt->fetchColumn();

    /* guardar checklist */
    foreach ($checks as $concepto_id) {
        $stmt = $pdo->prepare("
            INSERT INTO bitacora_checklist (
                bitacora_id,
                concepto_id,
                realizado
            ) VALUES (?, ?, 1)
        ");

        $stmt->execute([
            $bitacora_id,
            $concepto_id
        ]);
    }

    if ($infra_id) {
        header("Location: ../views/pdf/bitacora_arco.php?tipo=infra&id=$infra_id");
    } else {
        header("Location: ../views/pdf/bitacora_arco.php?id=$arco_id");
    }
    exit;

} catch (PDOException $e) {
    die("Error al generar bitácora: " . $e->getMessage());
}
