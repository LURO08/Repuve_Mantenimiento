<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$rootDir = dirname(__DIR__);
$formatos = require $rootDir . '/config/formatos_servicio.php';
require $rootDir . '/config/db.php';
require_once $rootDir . '/config/formatos_mantenimiento_schema.php';
require_once $rootDir . '/config/tecnicos_schema.php';
asegurarTablaFormatosMantenimiento($pdo);
asegurarRelacionTecnicos($pdo);
$type = $_REQUEST['type'] ?? '';

if (!isset($formatos[$type])) {
    http_response_code(400);
    exit('Formato no válido.');
}

$config = $formatos[$type];
$templateDir = $rootDir . '/templates/formatos/';
$localNow = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));

function sendDocx(string $path, string $downloadName, bool $deleteAfter = false): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($path);

    if ($deleteAfter) {
        @unlink($path);
    }
    exit;
}

function cleanValue($value): string
{
    $value = preg_replace('/\s+/u', ' ', trim((string)$value));
    return $value;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'download_blank') {
    $blankNames = [
        'checklist' => 'CHECK_LIST_DIAGNOSTICO_INICIAL.docx',
        'quality' => 'FORMATO_PRUEBAS_CALIDAD.docx',
        'tools' => 'FORMATO_HERRAMIENTAS.docx',
    ];
    sendDocx($templateDir . $config['blank'], $blankNames[$type]);
}

if ($action !== 'generate' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/formatos.php');
    exit;
}

$arcoId = (int)($_POST['arco_id'] ?? 0);
$formatoId = (int)($_POST['formato_id'] ?? 0);
$tecnicoId = (int)($_POST['tecnico_id'] ?? $_POST['tecnico'] ?? 0);
$tecnicoRow = obtenerTecnicoPorId($pdo, $tecnicoId);
$tecnico = $tecnicoRow['nombre'] ?? '';
$fecha = cleanValue($_POST['fecha'] ?? '');
$hora = cleanValue($_POST['hora'] ?? '');
if ($arcoId <= 0 || !$tecnicoRow || $fecha === '' || $hora === '') {
    $message = urlencode('Selecciona ubicación, arco, técnico, fecha y hora.');
    header("Location: ../views/formato_llenar.php?type={$type}&error={$message}");
    exit;
}

$arcoStmt = $pdo->prepare("
    SELECT
        a.nombre AS arco,
        COALESCE(u.nombre, '') AS ubicacion
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE a.id = ? AND COALESCE(a.estado, 'Activo') <> 'Baja'
");
$arcoStmt->execute([$arcoId]);
$arco = $arcoStmt->fetch(PDO::FETCH_ASSOC);

if (!$arco) {
    $message = urlencode('El arco seleccionado no existe o está dado de baja.');
    header("Location: ../views/formato_llenar.php?type={$type}&error={$message}");
    exit;
}

$fechaServicio = $fecha . ' ' . $hora . ':00';
$datos = [
    'arco' => $arco['arco'],
    'ubicacion' => $arco['ubicacion'],
    'tecnico_id' => $tecnicoId,
    'tecnico' => $tecnico,
    'fecha_servicio' => $fechaServicio,
    'tipo_mantenimiento' => cleanValue($_POST['tipo_mantenimiento'] ?? 'Correctivo'),
];

if ($type === 'checklist') {
    $datos['componentes'] = [];
    foreach (array_values($_POST['componente'] ?? []) as $component) {
        $nombre = cleanValue($component['nombre'] ?? '');
        if ($nombre === '') continue;
        $datos['componentes'][] = [
            'relacion_id' => (int)($component['relacion_id'] ?? 0),
            'nombre' => $nombre,
            'serie' => cleanValue($component['serie'] ?? ''),
            'cantidad' => max(0, (float)($component['cantidad'] ?? 1)),
            'medida' => cleanValue($component['medida'] ?? 'pz'),
            'estado' => cleanValue($component['estado'] ?? '') === 'Malo' ? 'Malo' : 'Bueno',
            'observacion' => cleanValue($component['observacion'] ?? ''),
            'cambiado' => !empty($component['cambiado']),
        ];
    }
}

if ($type === 'quality') {
    $carriles = $_POST['carril'] ?? [];
    $datos['carriles'] = [];
    foreach (array_values($carriles) as $lane) {
        $datos['carriles'][] = [
            'nombre' => cleanValue($lane['nombre'] ?? ''),
            'lectura' => cleanValue($lane['lectura'] ?? ''),
            'monitoreo' => cleanValue($lane['monitoreo'] ?? ''),
            'observacion' => cleanValue($lane['observacion'] ?? ''),
        ];
    }
    $datos['energia_fuente'] = cleanValue($_POST['energia_fuente'] ?? '');
    $datos['energia_luz'] = $datos['energia_fuente'] === 'luz';
    $datos['energia_solar'] = $datos['energia_fuente'] === 'solar';
    $datos['enlace'] = cleanValue($_POST['enlace'] ?? '');
    $datos['sistema_monitoreo'] = cleanValue($_POST['sistema_monitoreo'] ?? '');
    $datos['resultado'] = cleanValue($_POST['resultado'] ?? '');
    $datos['acciones_correctivas'] = cleanValue($_POST['acciones_correctivas'] ?? '');
}

if ($type === 'tools') {
    $datos['tipo_servicio'] = cleanValue($_POST['tipo_servicio'] ?? 'Correctivo');
    $datos['tipo_mantenimiento'] = $datos['tipo_servicio'];
    $datos['herramientas'] = [];
    $datos['consumibles'] = [];
    $datos['epp'] = [];

    foreach ($config['tools'] as $index => $pair) {
        if (isset($_POST["herr_{$index}_izq"])) $datos['herramientas'][] = $pair[0];
        if (isset($_POST["herr_{$index}_der"])) $datos['herramientas'][] = $pair[1];
    }
    foreach ($config['consumables'] as $index => $pair) {
        foreach (['izq' => $pair[0], 'der' => $pair[1]] as $side => $label) {
            if (!isset($_POST["cons_{$index}_{$side}"])) continue;
            $isQuantifiable = in_array($label, $config['quantifiable_consumables'] ?? [], true);
            $datos['consumibles'][] = [
                'nombre' => $label,
                'cantidad' => $isQuantifiable
                    ? max(1, (int)($_POST["cons_qty_{$index}_{$side}"] ?? 1))
                    : null,
                'unidad' => $isQuantifiable ? 'pza' : '',
            ];
        }
    }
    foreach ($config['epp'] as $index => $pair) {
        if (isset($_POST["epp_{$index}_izq"])) $datos['epp'][] = $pair[0];
        if (isset($_POST["epp_{$index}_der"])) $datos['epp'][] = $pair[1];
    }
}

try {
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if ($formatoId > 0) {
        $update = $pdo->prepare("
            UPDATE formatos_mantenimiento
            SET arco_id = ?, tipo = ?, datos = CAST(? AS JSONB), tecnico_id = ?, creado_por = ?, created_at = CURRENT_TIMESTAMP
            WHERE id = ? AND tipo = ?
        ");
        $update->execute([$arcoId, $type, $json, $tecnicoId, $_SESSION['user'] ?? null, $formatoId, $type]);
        if ($update->rowCount() === 0) {
            throw new RuntimeException('El formato no existe.');
        }
    } else {
        $insert = $pdo->prepare("
            INSERT INTO formatos_mantenimiento (arco_id, tipo, datos, tecnico_id, creado_por)
            VALUES (?, ?, CAST(? AS JSONB), ?, ?)
            RETURNING id
        ");
        $insert->execute([$arcoId, $type, $json, $tecnicoId, $_SESSION['user'] ?? null]);
        $formatoId = (int)$insert->fetchColumn();
    }
    header("Location: formato_servicio_pdf.php?id={$formatoId}");
    exit;
} catch (Throwable $e) {
    $message = urlencode('No fue posible generar el formato: ' . $e->getMessage());
    header("Location: ../views/formato_llenar.php?type={$type}&arco_id={$arcoId}&formato_id={$formatoId}&error={$message}");
    exit;
}
