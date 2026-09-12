<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$rootDir = dirname(__DIR__);
require $rootDir . '/config/db.php';
require_once $rootDir . '/config/formatos_mantenimiento_schema.php';
require_once $rootDir . '/config/tecnicos_schema.php';
require_once $rootDir . '/libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

asegurarTablaFormatosMantenimiento($pdo);
asegurarRelacionTecnicos($pdo);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        fm.*,
        r.fecha_mantenimiento,
        r.tipo_mantenimiento,
        COALESCE(tf.nombre, tr.nombre) AS tecnico_nombre,
        COALESCE(a.nombre, n.nombre, 'Sitio / Arco') AS arco,
        COALESCE(u.nombre, un.nombre, '') AS ubicacion
    FROM formatos_mantenimiento fm
    LEFT JOIN revisiones r ON r.id = fm.revision_id
    LEFT JOIN tecnicos tf ON tf.id = fm.tecnico_id
    LEFT JOIN tecnicos tr ON tr.id = r.tecnico_id
    LEFT JOIN arcos a ON a.id = COALESCE(fm.arco_id, r.arco_id)
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN infraestructura_nodos n ON n.id = fm.infraestructura_id
    LEFT JOIN ubicaciones un ON un.id = n.ubicacion_id
    WHERE fm.id = ?
");
$stmt->execute([$id]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
    http_response_code(404);
    exit('Formato no encontrado.');
}

$formatos = require $rootDir . '/config/formatos_servicio.php';
if (!isset($formatos[$registro['tipo']])) {
    http_response_code(400);
    exit('Tipo de formato no válido.');
}

$config = $formatos[$registro['tipo']];
$datos = json_decode($registro['datos'], true) ?: [];
$registro['fecha_mantenimiento'] = $datos['fecha_servicio'] ?? $registro['fecha_mantenimiento'];
$registro['tipo_mantenimiento'] = $datos['tipo_mantenimiento'] ?? $registro['tipo_mantenimiento'] ?? 'Correctivo';
$registro['tecnico_responsable'] = $registro['tecnico_nombre'] ?? $datos['tecnico'] ?? '';
$logoPath = $rootDir . '/assets/LOGO INNOVATEC PDF.jpg';
$logoData = is_file($logoPath)
    ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
    : '';

ob_start();
require $rootDir . '/views/pdf/formato_servicio_pdf.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
$boldFont = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'bold');
$blue = [0, 0.22, 0.40];
$canvas->line(306, 724, 306, 770, $blue, 2);
$canvas->page_text(105, 731, 'RFC: ITC090904G64', $boldFont, 8.5, [0.20, 0.20, 0.20]);
$canvas->page_text(111, 746, 'TEL. 747 141 5434', $boldFont, 8.5, [0.20, 0.20, 0.20]);
$canvas->page_text(367, 731, 'GONZALO N. RAMÍREZ, MANZANA 1', $font, 8, [0.20, 0.20, 0.20]);
$canvas->page_text(394, 746, 'LOTE 167, COL. TRIBUNA', $font, 8, [0.20, 0.20, 0.20]);

$prefijoFormato = $config['file_prefix'] ?? match ($registro['tipo']) {
    'checklist' => 'Check_List_Diagnostico_Inicial',
    'quality'   => 'Formato_Pruebas_Calidad',
    'tools'     => 'Formato_Herramientas',
    default     => 'Formato_Servicio'
};

$safeArc = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $registro['arco'] ?: 'Arco') ?: 'Arco';
$safeArc = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '_', $safeArc), '_');

$fechaTimestamp = !empty($registro['fecha_mantenimiento'])
    ? strtotime($registro['fecha_mantenimiento'])
    : (!empty($registro['created_at']) ? strtotime($registro['created_at']) : time());
$fechaFormato = date('Y-m-d', $fechaTimestamp);

$filename = "{$prefijoFormato}_{$safeArc}_{$fechaFormato}.pdf";

$download = isset($_GET['download']) && in_array(strtolower((string)$_GET['download']), ['1', 'true', 'yes'], true);

$dompdf->stream($filename, ['Attachment' => $download ? true : false]);
exit;
