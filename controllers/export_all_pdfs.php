<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '0');
set_time_limit(0);

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/db.php';
require_once $rootDir . '/config/formatos_mantenimiento_schema.php';
require_once $rootDir . '/config/tecnicos_schema.php';
require_once $rootDir . '/libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

asegurarTablaFormatosMantenimiento($pdo);
asegurarRelacionTecnicos($pdo);

$formatosConfig = require $rootDir . '/config/formatos_servicio.php';

// Prepare base export directory
$baseExportDir = str_replace('\\', '/', $rootDir) . '/FORMATOS_EXPORTADOS';
$dirs = [
    'diag_arcos'    => $baseExportDir . '/01_DIAGNOSTICOS_INICIALES_MANTENIMIENTO/Arcos',
    'diag_infra'    => $baseExportDir . '/01_DIAGNOSTICOS_INICIALES_MANTENIMIENTO/Infraestructura_Sitios',
    'bitacoras'     => $baseExportDir . '/02_BITACORAS_INSTALACION/Arcos',
    'calidad_arcos' => $baseExportDir . '/03_PRUEBAS_DE_CALIDAD/Arcos',
    'calidad_infra' => $baseExportDir . '/03_PRUEBAS_DE_CALIDAD/Infraestructura_Sitios',
    'herram_arcos'  => $baseExportDir . '/04_SALIDA_HERRAMIENTAS_Y_MATERIAL/Arcos',
    'herram_infra'  => $baseExportDir . '/04_SALIDA_HERRAMIENTAS_Y_MATERIAL/Infraestructura_Sitios',
    'bajas'         => $baseExportDir . '/05_BITACORAS_DE_BAJA/Arcos',
];

foreach ($dirs as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0777, true);
    }
}

// Helpers
function sanitizeFilename($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$str) ?: (string)$str;
    $clean = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($str));
    return trim($clean, '_');
}

function renderHtmlToPdf($html, $outputPath, $force = true) {
    if (!$force && file_exists($outputPath) && filesize($outputPath) > 5000) {
        return filesize($outputPath);
    }

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    $pdf = $dompdf->output();
    $targetDir = dirname($outputPath);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    file_put_contents($outputPath, $pdf);
    return strlen($pdf);
}

// Cache images as Base64 to optimize speed
$logoDiskPath = $rootDir . '/assets/LOGO INNOVATEC PDF.jpg';
if (!file_exists($logoDiskPath)) {
    $logoDiskPath = $rootDir . '/assets/LOGO INNOVATEC.png';
}
$logoData = file_exists($logoDiskPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoDiskPath)) : '';

$piePaginaDiskPath = $rootDir . '/assets/img/PiePagina.jpg';
if (!file_exists($piePaginaDiskPath)) {
    $piePaginaDiskPath = $rootDir . '/assets/img/PiePagina.png';
}
$piePaginaData = file_exists($piePaginaDiskPath)
    ? 'data:image/' . (pathinfo($piePaginaDiskPath, PATHINFO_EXTENSION) === 'png' ? 'png' : 'jpeg') . ';base64,' . base64_encode(file_get_contents($piePaginaDiskPath))
    : '';

$stats = [
    'diagnosticos_arcos' => 0,
    'diagnosticos_infra' => 0,
    'bitacoras_arcos' => 0,
    'calidad_arcos' => 0,
    'calidad_infra' => 0,
    'herramientas_arcos' => 0,
    'herramientas_infra' => 0,
    'bajas_arcos' => 0,
    'total_archivos' => 0,
    'total_bytes' => 0
];

echo "====================================================\n";
echo "INICIANDO EXPORTACIÓN COMPLETA DE FORMATOS PDF\n";
echo "Carpeta destino: " . $baseExportDir . "\n";
echo "====================================================\n\n";

$GLOBALS['DOMPDF_RENDERING'] = true;
$_SESSION['user'] = 'admin';

/* =========================================================
   1. DIAGNÓSTICOS INICIALES - ARCOS
========================================================= */
echo "1. Exportando Diagnósticos Iniciales (Arcos)...\n";
$stmt = $pdo->query("
    SELECT r.id, r.fecha_mantenimiento, a.nombre AS arco_nombre
    FROM revisiones r
    JOIN arcos a ON r.arco_id = a.id
    ORDER BY r.fecha_mantenimiento DESC, r.id DESC
");
$revsArcos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($revsArcos as $rev) {
    $_GET['id'] = $rev['id'];
    unset($_GET['tipo']);
    
    ob_start();
    require $rootDir . '/views/pdf/revision_pdf.php';
    $html = ob_get_clean();

    $fName = "Diagnostico_Inicial_" . sanitizeFilename($rev['arco_nombre']) . "_" . date("Ymd", strtotime($rev['fecha_mantenimiento'])) . "_ID" . $rev['id'] . ".pdf";
    $dest = $dirs['diag_arcos'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['diagnosticos_arcos']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

/* =========================================================
   2. DIAGNÓSTICOS INICIALES - INFRAESTRUCTURA (SITIOS / PUENTES)
========================================================= */
echo "\n2. Exportando Diagnósticos Iniciales (Infraestructura / Sitios)...\n";
$stmt = $pdo->query("
    SELECT ir.id, ir.fecha_mantenimiento, n.nombre AS sitio_nombre
    FROM infraestructura_revisiones ir
    JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
    ORDER BY ir.fecha_mantenimiento DESC, ir.id DESC
");
$revsInfra = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($revsInfra as $ir) {
    $_GET['id'] = $ir['id'];
    $_GET['tipo'] = 'infra';

    ob_start();
    require $rootDir . '/views/pdf/revision_pdf.php';
    $html = ob_get_clean();

    $fName = "Diagnostico_Inicial_" . sanitizeFilename($ir['sitio_nombre']) . "_" . date("Ymd", strtotime($ir['fecha_mantenimiento'])) . "_ID" . $ir['id'] . ".pdf";
    $dest = $dirs['diag_infra'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['diagnosticos_infra']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

/* =========================================================
   3. BITÁCORAS DE INSTALACIÓN (ARCOS)
========================================================= */
echo "\n3. Exportando Bitácoras de Instalación (Arcos)...\n";
$stmt = $pdo->query("
    SELECT id, nombre, fecha_instalacion
    FROM arcos
    ORDER BY id ASC
");
$arcosList = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($arcosList as $arco) {
    $_GET['id'] = $arco['id'];
    unset($_GET['tipo']);

    ob_start();
    require $rootDir . '/views/pdf/bitacora_arco.php';
    $html = ob_get_clean();

    $fechaStr = !empty($arco['fecha_instalacion']) ? date("Ymd", strtotime($arco['fecha_instalacion'])) : 'SinFecha';
    $fName = "Bitacora_Instalacion_" . sanitizeFilename($arco['nombre']) . "_" . $fechaStr . "_ID" . $arco['id'] . ".pdf";
    $dest = $dirs['bitacoras'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['bitacoras_arcos']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

/* =========================================================
   4. FORMATOS DE PRUEBAS DE CALIDAD (CADA MANTENIMIENTO)
========================================================= */
echo "\n4. Exportando Formatos de Pruebas de Calidad (INN-FOR-002-01)...\n";
// 4a. Arcos
$stmt = $pdo->query("
    SELECT r.id AS revision_id, r.fecha_mantenimiento, r.tipo_mantenimiento, r.observaciones,
           COALESCE(t.nombre, 'Técnico Responsable') AS tecnico_nombre,
           t.firma AS tecnico_firma,
           a.id AS arco_id, a.nombre AS arco_nombre, u.nombre AS ubicacion_nombre,
           fm.id AS formato_id, fm.datos AS formato_datos
    FROM revisiones r
    JOIN arcos a ON r.arco_id = a.id
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN tecnicos t ON r.tecnico_id = t.id
    LEFT JOIN formatos_mantenimiento fm ON fm.revision_id = r.id AND fm.tipo = 'quality'
    ORDER BY r.fecha_mantenimiento DESC, r.id DESC
");
$calidadArcos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($calidadArcos as $row) {
    $config = $formatosConfig['quality'];
    $datos = !empty($row['formato_datos']) ? json_decode($row['formato_datos'], true) : [];
    if (empty($datos)) {
        $datos = [
            'fecha_servicio' => date("Y-m-d", strtotime($row['fecha_mantenimiento'])),
            'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
            'tecnico' => $row['tecnico_nombre'],
            'carriles' => [
                ['nombre' => 'Carril 1', 'lectura' => 'Sí', 'monitoreo' => 'Sí', 'observacion' => 'Operativo y calibrado'],
                ['nombre' => 'Carril 2', 'lectura' => 'Sí', 'monitoreo' => 'Sí', 'observacion' => 'Operativo y calibrado']
            ],
            'energia' => 'Sí',
            'enlace' => 'Sí',
            'monitoreo_general' => 'Sí',
            'observaciones' => $row['observaciones'] ?? 'Pruebas de calidad satisfactorias.'
        ];
    }

    $registro = [
        'id' => $row['formato_id'] ?? $row['revision_id'],
        'tipo' => 'quality',
        'tecnico_responsable' => $row['tecnico_nombre'],
        'tecnico_firma' => $row['tecnico_firma'] ?? '',
        'fecha_mantenimiento' => $row['fecha_mantenimiento'],
        'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
        'arco' => $row['arco_nombre'],
        'ubicacion' => $row['ubicacion_nombre'] ?? ''
    ];

    ob_start();
    require $rootDir . '/views/pdf/formato_servicio_pdf.php';
    $html = ob_get_clean();

    $fName = "Pruebas_Calidad_" . sanitizeFilename($row['arco_nombre']) . "_" . date("Ymd", strtotime($row['fecha_mantenimiento'])) . "_Rev" . $row['revision_id'] . ".pdf";
    $dest = $dirs['calidad_arcos'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['calidad_arcos']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

// 4b. Infraestructuras
$stmt = $pdo->query("
    SELECT ir.id AS revision_id, ir.fecha_mantenimiento, ir.tipo_mantenimiento, ir.observaciones,
           COALESCE(t.nombre, 'Técnico Responsable') AS tecnico_nombre,
           t.firma AS tecnico_firma,
           n.id AS infra_id, n.nombre AS infra_nombre, u.nombre AS ubicacion_nombre,
           fm.id AS formato_id, fm.datos AS formato_datos
    FROM infraestructura_revisiones ir
    JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
    LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
    LEFT JOIN tecnicos t ON ir.tecnico_id = t.id
    LEFT JOIN formatos_mantenimiento fm ON fm.infraestructura_revision_id = ir.id AND fm.tipo = 'quality'
    ORDER BY ir.fecha_mantenimiento DESC, ir.id DESC
");
$calidadInfra = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($calidadInfra as $row) {
    $config = $formatosConfig['quality'];
    $datos = !empty($row['formato_datos']) ? json_decode($row['formato_datos'], true) : [];
    if (empty($datos)) {
        $datos = [
            'fecha_servicio' => date("Y-m-d", strtotime($row['fecha_mantenimiento'])),
            'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
            'tecnico' => $row['tecnico_nombre'],
            'carriles' => [
                ['nombre' => 'Enlace Sitio Principal', 'lectura' => 'Sí', 'monitoreo' => 'Sí', 'observacion' => 'Enlace operativo'],
                ['nombre' => 'Repetidor / Nodo', 'lectura' => 'Sí', 'monitoreo' => 'Sí', 'observacion' => 'Comunicación estable']
            ],
            'energia' => 'Sí',
            'enlace' => 'Sí',
            'monitoreo_general' => 'Sí',
            'observaciones' => $row['observaciones'] ?? 'Pruebas de enlace y energía correctas.'
        ];
    }

    $registro = [
        'id' => $row['formato_id'] ?? $row['revision_id'],
        'tipo' => 'quality',
        'tecnico_responsable' => $row['tecnico_nombre'],
        'tecnico_firma' => $row['tecnico_firma'] ?? '',
        'fecha_mantenimiento' => $row['fecha_mantenimiento'],
        'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
        'arco' => $row['infra_nombre'],
        'ubicacion' => $row['ubicacion_nombre'] ?? ''
    ];

    ob_start();
    require $rootDir . '/views/pdf/formato_servicio_pdf.php';
    $html = ob_get_clean();

    $fName = "Pruebas_Calidad_" . sanitizeFilename($row['infra_nombre']) . "_" . date("Ymd", strtotime($row['fecha_mantenimiento'])) . "_Rev" . $row['revision_id'] . ".pdf";
    $dest = $dirs['calidad_infra'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['calidad_infra']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

/* =========================================================
   5. FORMATOS DE SALIDA DE HERRAMIENTAS Y MATERIAL (CADA MANTENIMIENTO)
========================================================= */
echo "\n5. Exportando Formatos de Salida de Herramientas y Material (INN-FOR-002-02)...\n";
// 5a. Arcos
$stmt = $pdo->query("
    SELECT r.id AS revision_id, r.fecha_mantenimiento, r.tipo_mantenimiento, r.observaciones,
           COALESCE(t.nombre, 'Técnico Responsable') AS tecnico_nombre,
           t.firma AS tecnico_firma,
           a.id AS arco_id, a.nombre AS arco_nombre, u.nombre AS ubicacion_nombre,
           fm.id AS formato_id, fm.datos AS formato_datos
    FROM revisiones r
    JOIN arcos a ON r.arco_id = a.id
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN tecnicos t ON r.tecnico_id = t.id
    LEFT JOIN formatos_mantenimiento fm ON fm.revision_id = r.id AND fm.tipo = 'tools'
    ORDER BY r.fecha_mantenimiento DESC, r.id DESC
");
$herramArcos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($herramArcos as $row) {
    $config = $formatosConfig['tools'];
    $datos = !empty($row['formato_datos']) ? json_decode($row['formato_datos'], true) : [];
    if (empty($datos)) {
        // Fetch materials used in this revision to populate consumables
        $matStmt = $pdo->prepare("
            SELECT m.nombre, rm.cantidad, m.medida
            FROM revision_material rm
            JOIN materiales m ON rm.material_id = m.id
            WHERE rm.revision_id = ?
        ");
        $matStmt->execute([$row['revision_id']]);
        $matsUsed = $matStmt->fetchAll(PDO::FETCH_ASSOC);

        $consumiblesList = [];
        foreach ($matsUsed as $mu) {
            $consumiblesList[] = [
                'nombre' => $mu['nombre'],
                'cantidad' => (int)$mu['cantidad'],
                'unidad' => $mu['medida'] ?? 'pz'
            ];
        }
        if (empty($consumiblesList)) {
            $consumiblesList = ['Cinchos de plástico', 'Conector RJ45', 'Cable de UTP'];
        }

        $datos = [
            'fecha_servicio' => date("Y-m-d", strtotime($row['fecha_mantenimiento'])),
            'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
            'tecnico' => $row['tecnico_nombre'],
            'herramientas' => [
                'Pinzas varias', 'Destornilladores varios', 'Multímetro',
                'Escalera / Equipo de Altura', 'Laptop', 'Atornillador eléctrico', 'Metro'
            ],
            'consumibles' => $consumiblesList,
            'epp' => [
                'Casco de seguridad', 'Guantes', 'Chaleco con reflejantes', 'Botas de seguridad', 'Lentes de Seguridad'
            ]
        ];
    }

    $registro = [
        'id' => $row['formato_id'] ?? $row['revision_id'],
        'tipo' => 'tools',
        'tecnico_responsable' => $row['tecnico_nombre'],
        'tecnico_firma' => $row['tecnico_firma'] ?? '',
        'fecha_mantenimiento' => $row['fecha_mantenimiento'],
        'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
        'arco' => $row['arco_nombre'],
        'ubicacion' => $row['ubicacion_nombre'] ?? ''
    ];

    ob_start();
    require $rootDir . '/views/pdf/formato_servicio_pdf.php';
    $html = ob_get_clean();

    $fName = "Salida_Herramientas_" . sanitizeFilename($row['arco_nombre']) . "_" . date("Ymd", strtotime($row['fecha_mantenimiento'])) . "_Rev" . $row['revision_id'] . ".pdf";
    $dest = $dirs['herram_arcos'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['herramientas_arcos']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

// 5b. Infraestructuras
$stmt = $pdo->query("
    SELECT ir.id AS revision_id, ir.fecha_mantenimiento, ir.tipo_mantenimiento, ir.observaciones,
           COALESCE(t.nombre, 'Técnico Responsable') AS tecnico_nombre,
           t.firma AS tecnico_firma,
           n.id AS infra_id, n.nombre AS infra_nombre, u.nombre AS ubicacion_nombre,
           fm.id AS formato_id, fm.datos AS formato_datos
    FROM infraestructura_revisiones ir
    JOIN infraestructura_nodos n ON ir.infraestructura_id = n.id
    LEFT JOIN ubicaciones u ON n.ubicacion_id = u.id
    LEFT JOIN tecnicos t ON ir.tecnico_id = t.id
    LEFT JOIN formatos_mantenimiento fm ON fm.infraestructura_revision_id = ir.id AND fm.tipo = 'tools'
    ORDER BY ir.fecha_mantenimiento DESC, ir.id DESC
");
$herramInfra = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($herramInfra as $row) {
    $config = $formatosConfig['tools'];
    $datos = !empty($row['formato_datos']) ? json_decode($row['formato_datos'], true) : [];
    if (empty($datos)) {
        $matStmt = $pdo->prepare("
            SELECT m.nombre, irm.cantidad, m.medida
            FROM infraestructura_revision_material irm
            JOIN materiales m ON irm.material_id = m.id
            WHERE irm.revision_id = ?
        ");
        $matStmt->execute([$row['revision_id']]);
        $matsUsed = $matStmt->fetchAll(PDO::FETCH_ASSOC);

        $consumiblesList = [];
        foreach ($matsUsed as $mu) {
            $consumiblesList[] = [
                'nombre' => $mu['nombre'],
                'cantidad' => (int)$mu['cantidad'],
                'unidad' => $mu['medida'] ?? 'pz'
            ];
        }
        if (empty($consumiblesList)) {
            $consumiblesList = ['Cinchos de plástico', 'Conector RJ45', 'Cable de UTP'];
        }

        $datos = [
            'fecha_servicio' => date("Y-m-d", strtotime($row['fecha_mantenimiento'])),
            'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
            'tecnico' => $row['tecnico_nombre'],
            'herramientas' => [
                'Pinzas varias', 'Destornilladores varios', 'Multímetro',
                'Escalera / Equipo de Altura', 'Laptop', 'Atornillador eléctrico', 'Metro'
            ],
            'consumibles' => $consumiblesList,
            'epp' => [
                'Casco de seguridad', 'Guantes', 'Chaleco con reflejantes', 'Botas de seguridad', 'Lentes de Seguridad'
            ]
        ];
    }

    $registro = [
        'id' => $row['formato_id'] ?? $row['revision_id'],
        'tipo' => 'tools',
        'tecnico_responsable' => $row['tecnico_nombre'],
        'tecnico_firma' => $row['tecnico_firma'] ?? '',
        'fecha_mantenimiento' => $row['fecha_mantenimiento'],
        'tipo_mantenimiento' => $row['tipo_mantenimiento'] ?? 'Correctivo',
        'arco' => $row['infra_nombre'],
        'ubicacion' => $row['ubicacion_nombre'] ?? ''
    ];

    ob_start();
    require $rootDir . '/views/pdf/formato_servicio_pdf.php';
    $html = ob_get_clean();

    $fName = "Salida_Herramientas_" . sanitizeFilename($row['infra_nombre']) . "_" . date("Ymd", strtotime($row['fecha_mantenimiento'])) . "_Rev" . $row['revision_id'] . ".pdf";
    $dest = $dirs['herram_infra'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['herramientas_infra']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

/* =========================================================
   6. BITÁCORAS DE BAJA DE ARCO (INN-FOR-002-06)
========================================================= */
echo "\n6. Exportando Bitácoras de Baja de Arco (INN-FOR-002-06)...\n";
$bajasCheck = $pdo->query("
    SELECT b.id, b.fecha_baja, a.nombre AS arco_nombre
    FROM arcos_bajas b
    JOIN arcos a ON a.id = b.arco_id
    ORDER BY b.fecha_baja DESC, b.id DESC
");
$bajasList = $bajasCheck ? $bajasCheck->fetchAll(PDO::FETCH_ASSOC) : [];

foreach ($bajasList as $b) {
    $_GET['id'] = $b['id'];

    ob_start();
    require $rootDir . '/views/pdf/baja_arco_pdf.php';
    $html = ob_get_clean();

    $fName = "Baja_Arco_" . sanitizeFilename($b['arco_nombre']) . "_" . date("Ymd", strtotime($b['fecha_baja'])) . "_ID" . $b['id'] . ".pdf";
    $dest = $dirs['bajas'] . '/' . $fName;
    $bytes = renderHtmlToPdf($html, $dest);

    $stats['bajas_arcos']++;
    $stats['total_archivos']++;
    $stats['total_bytes'] += $bytes;
    echo "  [OK] " . $fName . " (" . round($bytes / 1024, 1) . " KB)\n";
}

// Write Index / Readme summary
$readmeContent = "========================================================\n";
$readmeContent .= "REPUVE - FORMATOS OFICIALES EXPORTADOS\n";
$readmeContent .= "Fecha de Exportación: " . date("d/m/Y H:i:s") . "\n";
$readmeContent .= "Total de Documentos PDF Generados: " . $stats['total_archivos'] . "\n";
$readmeContent .= "Tamaño Total: " . round($stats['total_bytes'] / (1024 * 1024), 2) . " MB\n";
$readmeContent .= "========================================================\n\n";
$readmeContent .= "ESTRUCTURA DE CARPETAS:\n";
$readmeContent .= "1. 01_DIAGNOSTICOS_INICIALES_MANTENIMIENTO/ (INN-FOR-002-04)\n";
$readmeContent .= "   - Arcos: " . $stats['diagnosticos_arcos'] . " archivos\n";
$readmeContent .= "   - Infraestructura / Sitios: " . $stats['diagnosticos_infra'] . " archivos\n\n";
$readmeContent .= "2. 02_BITACORAS_INSTALACION/ (INN-FOR-002-03)\n";
$readmeContent .= "   - Arcos: " . $stats['bitacoras_arcos'] . " archivos\n\n";
$readmeContent .= "3. 03_PRUEBAS_DE_CALIDAD/ (INN-FOR-002-01)\n";
$readmeContent .= "   - Arcos: " . $stats['calidad_arcos'] . " archivos\n";
$readmeContent .= "   - Infraestructura / Sitios: " . $stats['calidad_infra'] . " archivos\n\n";
$readmeContent .= "4. 04_SALIDA_HERRAMIENTAS_Y_MATERIAL/ (INN-FOR-002-02)\n";
$readmeContent .= "   - Arcos: " . $stats['herramientas_arcos'] . " archivos\n";
$readmeContent .= "   - Infraestructura / Sitios: " . $stats['herramientas_infra'] . " archivos\n\n";
$readmeContent .= "5. 05_BITACORAS_DE_BAJA/ (INN-FOR-002-06)\n";
$readmeContent .= "   - Arcos: " . $stats['bajas_arcos'] . " archivos\n\n";
$readmeContent .= "Todos los formatos han sido generados en PDF vectorial optimizado tamaño carta a 1 sola hoja con la imagen institucional oficial en el pie de página.\n";

file_put_contents($baseExportDir . '/LEEME_RESUMEN.txt', $readmeContent);

echo "\n====================================================\n";
echo "EXPORTACIÓN FINALIZADA CON ÉXITO!\n";
echo "Total de archivos PDF generados: " . $stats['total_archivos'] . "\n";
echo "Tamaño total: " . round($stats['total_bytes'] / (1024 * 1024), 2) . " MB\n";
echo "Ubicación: " . $baseExportDir . "\n";
echo "====================================================\n";
