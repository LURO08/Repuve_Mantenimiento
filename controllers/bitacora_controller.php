<?php
include('../config/db.php');

$arco_id = $_POST['arco_id'];
$encargado = trim($_POST['encargado']);
$observaciones = trim($_POST['observaciones']);
$checks = $_POST['checklist'] ?? [];

try {
    /* verificar si ya existe */
    $validar = $pdo->prepare("
        SELECT id
        FROM bitacoras_arco
        WHERE arco_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $validar->execute([$arco_id]);

    $existente = $validar->fetch(PDO::FETCH_ASSOC);

    /* si ya existe -> solo abrir PDF */
    if ($existente) {
        header("Location: ../views/pdf/bitacora_arco.php?id=$arco_id");
        exit;
    }

    /* crear bitácora */
    $stmt = $pdo->prepare("
        INSERT INTO bitacoras_arco (
            arco_id,
            encargado,
            observaciones
        ) VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $arco_id,
        $encargado,
        $observaciones
    ]);

    $bitacora_id = $pdo->lastInsertId();

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

    header("Location: ../views/pdf/bitacora_arco.php?id=$arco_id");
    exit;

} catch (PDOException $e) {
    die("Error al generar bitácora: " . $e->getMessage());
}
