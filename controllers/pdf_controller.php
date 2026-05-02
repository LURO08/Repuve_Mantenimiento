<?php
include('../config/db.php');
require_once '../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$action = $_GET['action'] ?? '';

try {

    switch ($action) {

        case 'bitacora':
            generarBitacora($pdo);
            break;

        case 'mantenimiento':
            generarMantenimiento($pdo);
            break;

        case 'revision':
            generarRevision($pdo);
            break;

        default:
            die("Acción no válida");
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


/* =========================
   BITÁCORA
========================= */
function generarBitacora($pdo)
{
    $arco_id = $_POST['arco_id'] ?? $_GET['arco_id'] ?? null;
    $encargado = trim($_POST['encargado'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $checks = $_POST['checklist'] ?? [];

    if (!$arco_id) {
        die("Arco no válido");
    }

    $validar = $pdo->prepare("
        SELECT id
        FROM bitacoras_arco
        WHERE arco_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $validar->execute([$arco_id]);

    $existente = $validar->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        header("Location: ../views/pdf/bitacora_arco.php?id=$arco_id");
        exit;
    }

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
}


/* =========================
   MANTENIMIENTO
========================= */
function generarMantenimiento($pdo)
{
    $revision_id = $_GET['id'] ?? null;

    if (!$revision_id) {
        die("ID de revisión no válido");
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM revisiones
        WHERE id = ?
    ");
    $stmt->execute([$revision_id]);

    $revision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$revision) {
        die("No existe la revisión");
    }

    // capturar HTML
    ob_start();
    include '../views/pdf/revision_pdf.php';
    $html = ob_get_clean();

    // configurar DOMPDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    // limpiar buffer antes de enviar PDF
    if (ob_get_length()) {
        ob_end_clean();
    }

    $dompdf->stream("mantenimiento_{$revision_id}.pdf", [
        "Attachment" => false
    ]);

    exit;
}


/* =========================
   REVISIÓN
========================= */
function generarRevision($pdo)
{
    $revision_id = $_GET['revision_id'] ?? null;

    if (!$revision_id) {
        die("Revisión no válida");
    }

    header("Location: ../views/pdf/revision.php?id=$revision_id");
    exit;
}
?>
