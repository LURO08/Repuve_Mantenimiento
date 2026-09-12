<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/db.php';
require_once $rootDir . '/config/tecnicos_schema.php';
require_once $rootDir . '/libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'bitacora':
            generarBitacora($pdo);
            break;

        case 'mantenimiento':
        case 'revision_pdf':
            generarMantenimiento($pdo);
            break;

        case 'bitacora_pdf':
            generarBitacoraPdf($pdo);
            break;

        case 'baja_pdf':
            generarBajaPdf($pdo);
            break;

        case 'revision':
            generarRevision($pdo);
            break;

        default:
            die("Acción no válida");
    }
} catch (Throwable $e) {
    die("Error: " . $e->getMessage());
}

/* =========================
   BITÁCORA (GUARDAR POST Y REDIRIGIR)
========================= */
function generarBitacora($pdo)
{
    asegurarRelacionTecnicos($pdo);
    $arco_id = $_POST['arco_id'] ?? $_GET['arco_id'] ?? null;
    $tecnicoId = (int)($_POST['encargado'] ?? $_POST['tecnico_id'] ?? 0);
    $tecnico = obtenerTecnicoPorId($pdo, $tecnicoId);
    $observaciones = trim($_POST['observaciones'] ?? '');
    $checks = $_POST['checklist'] ?? [];

    if (!$arco_id || !$tecnico) {
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
            tecnico_id,
            observaciones
        ) VALUES (?, ?, ?)
        RETURNING id
    ");

    $stmt->execute([
        $arco_id,
        $tecnicoId,
        $observaciones
    ]);

    $bitacora_id = (int)$stmt->fetchColumn();

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
   MANTENIMIENTO / DIAGNÓSTICO (PDF)
========================= */
function generarMantenimiento($pdo)
{
    $revision_id = (int)($_GET['id'] ?? 0);
    $tipo = $_GET['tipo'] ?? '';
    $download = isset($_GET['download']) && in_array(strtolower((string)$_GET['download']), ['1', 'true', 'yes'], true);

    if ($revision_id <= 0) {
        die("ID de revisión no válido");
    }

    if ($tipo === 'infra') {
        $stmt = $pdo->prepare("
            SELECT ir.*, n.nombre AS arco, fecha_mantenimiento AS fecha_mantenimiento
            FROM infraestructura_revisiones ir
            JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
            WHERE ir.id = ?
        ");
        $stmt->execute([$revision_id]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, a.nombre AS arco, fecha_mantenimiento AS fecha_mantenimiento
            FROM revisiones r
            JOIN arcos a ON r.arco_id = a.id
            WHERE r.id = ?
        ");
        $stmt->execute([$revision_id]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$revision) {
            $stmt = $pdo->prepare("
                SELECT ir.*, n.nombre AS arco, fecha_mantenimiento AS fecha_mantenimiento
                FROM infraestructura_revisiones ir
                JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
                WHERE ir.id = ?
            ");
            $stmt->execute([$revision_id]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($revision) $tipo = 'infra';
        }
    }

    $fechaRegistroMantenimiento = $revision ? date("d/m/Y H:i A", strtotime($revision['fecha_mantenimiento'])) : '';

    if (!$revision) {
        die("No existe la revisión");
    }

    $_GET['id'] = $revision_id;
    $_GET['tipo'] = $tipo;
    $id = $revision_id;

    ob_start();
    include __DIR__ . '/../views/pdf/revision_pdf.php';
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    if (ob_get_length()) {
        ob_end_clean();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($revision['arco'] ?? 'arco'));
    $filename = "Diagnostico_Inicial_{$safeName}_{$fechaRegistroMantenimiento}.pdf";

    $dompdf->stream($filename, [
        "Attachment" => $download
    ]);
    exit;
}

/* =========================
   BITÁCORA (PDF STREAM / DOWNLOAD)
========================= */
function generarBitacoraPdf($pdo)
{
    $arco_id = (int)($_GET['id'] ?? $_GET['arco_id'] ?? 0);
    $tipo = $_GET['tipo'] ?? '';
    $download = isset($_GET['download']) && in_array(strtolower((string)$_GET['download']), ['1', 'true', 'yes'], true);

    if ($arco_id <= 0) {
        die("ID de arco no válido");
    }

    if ($tipo === 'infra') {
        $stmt = $pdo->prepare("SELECT id, nombre, CURRENT_TIMESTAMP AS fecha_instalacion FROM infraestructura_nodos WHERE id = ?");
        $stmt->execute([$arco_id]);
        $arco = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre, fecha_instalacion AS fecha_instalacion FROM arcos WHERE id = ?");
        $stmt->execute([$arco_id]);
        $arco = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$arco) {
            $stmt = $pdo->prepare("SELECT id, nombre, CURRENT_TIMESTAMP AS fecha_instalacion FROM infraestructura_nodos WHERE id = ?");
            $stmt->execute([$arco_id]);
            $arco = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($arco) $tipo = 'infra';
        }
    }

    $fechaInstalacion = $arco && !empty($arco['fecha_instalacion']) ? date("d/m/Y", strtotime($arco['fecha_instalacion'])) : date("d/m/Y");

    if (!$arco) {
        die("Arco o sitio no encontrado");
    }

    $_GET['id'] = $arco_id;
    $_GET['tipo'] = $tipo;
    $id = $arco_id;

    ob_start();
    include __DIR__ . '/../views/pdf/bitacora_arco.php';
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    if (ob_get_length()) {
        ob_end_clean();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($arco['nombre'] ?? 'arco'));
    $filename = "Bitacora_{$safeName}_{$fechaInstalacion}.pdf";

    $dompdf->stream($filename, [
        "Attachment" => $download
    ]);
    exit;
}

/* =========================
   BAJA DE ARCO (PDF STREAM / DOWNLOAD)
========================= */
function generarBajaPdf($pdo)
{
    $baja_id = (int)($_GET['id'] ?? 0);
    $download = isset($_GET['download']) && in_array(strtolower((string)$_GET['download']), ['1', 'true', 'yes'], true);

    if ($baja_id <= 0) {
        die("ID de baja no válido");
    }

    $stmt = $pdo->prepare("
        SELECT b.id, a.nombre AS arco
        FROM arcos_bajas b
        JOIN arcos a ON a.id = b.arco_id
        WHERE b.id = ?
    ");
    $stmt->execute([$baja_id]);
    $baja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$baja) {
        die("Baja no encontrada");
    }

    $_GET['id'] = $baja_id;
    $id = $baja_id;

    ob_start();
    include __DIR__ . '/../views/pdf/baja_arco_pdf.php';
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    if (ob_get_length()) {
        ob_end_clean();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($baja['arco'] ?? 'arco'));
    $filename = "Baja_Arco_{$safeName}_{$baja_id}.pdf";

    $dompdf->stream($filename, [
        "Attachment" => $download
    ]);
    exit;
}

/* =========================
   REVISIÓN (REDIRECT)
========================= */
function generarRevision($pdo)
{
    $revision_id = (int)($_GET['revision_id'] ?? $_GET['id'] ?? 0);

    if ($revision_id <= 0) {
        die("Revisión no válida");
    }

    header("Location: ../views/pdf/revision_pdf.php?id=$revision_id");
    exit;
}
