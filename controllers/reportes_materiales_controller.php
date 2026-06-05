<?php
require_once('../config/db.php');

$hoy = date('Y-m-d');

$kpis = [
    'total_arcos' => 0,
    'arcos_sin_mantenimiento' => 0,
    'mantenimientos_vencidos' => 0,
    'mantenimientos_proximos' => 0,
    'preventivos_60' => 0,
    'correctivos_60' => 0,
    'arcos_preventivos_60' => 0,
    'arcos_correctivos_60' => 0,
    'arcos_mantenimientos_60' => 0,
    'porcentaje_preventivos_60' => 0,
    'porcentaje_correctivos_60' => 0,
    'porcentaje_mantenimientos_60' => 0,
    'material_total_arcos' => 0,
    'material_metros_arcos' => 0,
    'componentes_cambiados' => 0,
    'metros_cambiados' => 0,
    'correctivos_90' => 0,
    'preventivos_total' => 0,
    'correctivos_total' => 0,
    'mantenimientos_total' => 0,
    'arcos_sin_material' => 0,
];

$kpis['total_arcos'] = (int)$pdo->query("SELECT COUNT(*) FROM arcos")->fetchColumn();
$totalesInstalados = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE am.cantidad END), 0) AS piezas,
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN am.cantidad ELSE 0 END), 0) AS metros
    FROM arco_material am
    JOIN materiales m ON m.id = am.material_id
")->fetch(PDO::FETCH_ASSOC);
$kpis['material_total_arcos'] = (float)$totalesInstalados['piezas'];
$kpis['material_metros_arcos'] = (float)$totalesInstalados['metros'];

$totalesCambiados = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE rm.cantidad END), 0) AS piezas,
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN rm.cantidad ELSE 0 END), 0) AS metros
    FROM revision_material rm
    JOIN materiales m ON m.id = rm.material_id
    WHERE COALESCE(rm.accion, 'cambio') <> 'retiro'
")->fetch(PDO::FETCH_ASSOC);
$kpis['componentes_cambiados'] = (float)$totalesCambiados['piezas'];
$kpis['metros_cambiados'] = (float)$totalesCambiados['metros'];
$kpis['correctivos_90'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones
    WHERE tipo_mantenimiento = 'Correctivo'
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '90 days'
")->fetchColumn();
$kpis['correctivos_60'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones
    WHERE tipo_mantenimiento = 'Correctivo'
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
")->fetchColumn();
$kpis['preventivos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones WHERE tipo_mantenimiento = 'Preventivo'")->fetchColumn();
$kpis['correctivos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones WHERE tipo_mantenimiento = 'Correctivo'")->fetchColumn();
$kpis['mantenimientos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones WHERE tipo_mantenimiento IN ('Preventivo', 'Correctivo')")->fetchColumn();

$kpis['arcos_sin_mantenimiento'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM arcos a
    LEFT JOIN revisiones r ON r.arco_id = a.id
    WHERE r.id IS NULL
")->fetchColumn();

$kpis['mantenimientos_vencidos'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT a.id, COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) AS base_mantenimiento
        FROM arcos a
        LEFT JOIN revisiones r ON r.arco_id = a.id
        GROUP BY a.id, a.fecha_instalacion
    ) x
    WHERE x.base_mantenimiento IS NOT NULL
      AND x.base_mantenimiento + INTERVAL '12 months' < CURRENT_DATE
")->fetchColumn();

$kpis['mantenimientos_proximos'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT a.id, COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) AS base_mantenimiento
        FROM arcos a
        LEFT JOIN revisiones r ON r.arco_id = a.id
        GROUP BY a.id, a.fecha_instalacion
    ) x
    WHERE x.base_mantenimiento + INTERVAL '12 months' BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'
")->fetchColumn();

$kpis['preventivos_60'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones
    WHERE tipo_mantenimiento = 'Preventivo'
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
")->fetchColumn();
$kpis['arcos_preventivos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT arco_id)
    FROM revisiones
    WHERE tipo_mantenimiento = 'Preventivo'
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
")->fetchColumn();
$kpis['arcos_correctivos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT arco_id)
    FROM revisiones
    WHERE tipo_mantenimiento = 'Correctivo'
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
")->fetchColumn();
$kpis['arcos_mantenimientos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT arco_id)
    FROM revisiones
    WHERE tipo_mantenimiento IN ('Preventivo', 'Correctivo')
      AND fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
")->fetchColumn();
$totalArcosPorcentaje = max(1, (int)$kpis['total_arcos']);
$kpis['porcentaje_preventivos_60'] = round(((int)$kpis['preventivos_60'] / $totalArcosPorcentaje) * 100, 1);
$kpis['porcentaje_correctivos_60'] = round(((int)$kpis['correctivos_60'] / $totalArcosPorcentaje) * 100, 1);
$kpis['porcentaje_mantenimientos_60'] = round((((int)$kpis['preventivos_60'] + (int)$kpis['correctivos_60']) / $totalArcosPorcentaje) * 100, 1);

$kpis['arcos_sin_material'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM arcos a
    LEFT JOIN arco_material am ON am.arco_id = a.id
    WHERE am.id IS NULL
")->fetchColumn();

$arcosCriticosMantenimiento = $pdo->query("
    SELECT
        a.id,
        a.nombre AS arco,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        a.fecha_instalacion,
        MAX(r.fecha_mantenimiento) AS ultima_mantenimiento,
        COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) AS base_mantenimiento,
        CASE
            WHEN MAX(r.fecha_mantenimiento) IS NULL THEN 'Sin mantenimiento vencido'
            WHEN MAX(r.fecha_mantenimiento) + INTERVAL '12 months' < CURRENT_DATE THEN 'Mantenimiento vencido'
            ELSE 'Al dia'
        END AS estado,
        COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) + INTERVAL '12 months' AS fecha_requerida
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN revisiones r ON r.arco_id = a.id
    GROUP BY a.id, a.nombre, u.nombre, a.fecha_instalacion
    HAVING COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) + INTERVAL '12 months' < CURRENT_DATE
    ORDER BY
        CASE WHEN ultima_mantenimiento IS NULL THEN 0 ELSE 1 END ASC,
        fecha_requerida ASC,
        a.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$reporteArcosMaterial = $pdo->query("
    SELECT
        a.id,
        a.nombre AS arco,
        u.nombre AS ubicacion,
        a.fecha_instalacion,
        COALESCE(inst.total_piezas, 0) AS total_piezas,
        COALESCE(inst.total_metros, 0) AS total_metros,
        COALESCE(inst.componentes_distintos, 0) AS componentes_distintos,
        COALESCE(inst.series_registradas, 0) AS series_registradas,
        COALESCE(cambios.total_cambios, 0) AS total_cambios,
        COALESCE(cambios.piezas_cambiadas, 0) AS piezas_cambiadas,
        COALESCE(cambios.metros_cambiados, 0) AS metros_cambiados,
        COALESCE(cambios.correctivos, 0) AS correctivos,
        COALESCE(cambios.preventivos, 0) AS preventivos,
        cambios.ultima_mantenimiento,
        COALESCE(cambios.ultima_mantenimiento, a.fecha_instalacion) + INTERVAL '12 months' AS proximo_mantenimiento,
        cambios.ultimo_preventivo
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN (
        SELECT
            am.arco_id,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE am.cantidad END) AS total_piezas,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN am.cantidad ELSE 0 END) AS total_metros,
            COUNT(DISTINCT am.material_id) AS componentes_distintos,
            SUM(CASE WHEN am.serie IS NOT NULL AND TRIM(am.serie) <> '' THEN 1 ELSE 0 END) AS series_registradas
        FROM arco_material am
        JOIN materiales m ON m.id = am.material_id
        GROUP BY am.arco_id
    ) inst ON inst.arco_id = a.id
    LEFT JOIN (
        SELECT
            r.arco_id,
            COUNT(rm.id) AS total_cambios,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE rm.cantidad END) AS piezas_cambiadas,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN rm.cantidad ELSE 0 END) AS metros_cambiados,
            SUM(CASE WHEN r.tipo_mantenimiento = 'Correctivo' THEN 1 ELSE 0 END) AS correctivos,
            SUM(CASE WHEN r.tipo_mantenimiento = 'Preventivo' THEN 1 ELSE 0 END) AS preventivos,
            MAX(r.fecha_mantenimiento) AS ultima_mantenimiento,
            MAX(CASE WHEN r.tipo_mantenimiento = 'Preventivo' THEN r.fecha_mantenimiento END) AS ultimo_preventivo
        FROM revisiones r
        LEFT JOIN revision_material rm ON rm.revision_id = r.id AND COALESCE(rm.accion, 'cambio') <> 'retiro'
        LEFT JOIN materiales m ON m.id = rm.material_id
        GROUP BY r.arco_id
    ) cambios ON cambios.arco_id = a.id
    ORDER BY
        CASE
            WHEN cambios.ultima_mantenimiento IS NULL THEN 0
            WHEN cambios.correctivos >= 2 THEN 1
            ELSE 2
        END ASC,
        total_cambios DESC,
        a.fecha_instalacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

$preventivosRecientes = $pdo->query("
    SELECT
        r.id,
        r.arco_id,
        r.fecha_mantenimiento,
        r.tecnico_responsable,
        r.observaciones,
        a.nombre AS arco,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COALESCE(mat.componentes, 0) AS componentes,
        COALESCE(mat.piezas, 0) AS piezas,
        COALESCE(mat.metros, 0) AS metros
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN (
        SELECT
            rm.revision_id,
            COUNT(rm.id) AS componentes,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE rm.cantidad END) AS piezas,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN rm.cantidad ELSE 0 END) AS metros
        FROM revision_material rm
        JOIN materiales m ON m.id = rm.material_id
        WHERE COALESCE(rm.accion, 'cambio') <> 'retiro'
        GROUP BY rm.revision_id
    ) mat ON mat.revision_id = r.id
    WHERE r.tipo_mantenimiento = 'Preventivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
    ORDER BY r.fecha_mantenimiento DESC
")->fetchAll(PDO::FETCH_ASSOC);

$mantenimientosReporte = $pdo->query("
    SELECT
        r.id,
        r.arco_id,
        r.fecha_mantenimiento,
        r.tipo_mantenimiento,
        r.tecnico_responsable,
        r.observaciones,
        a.nombre AS arco,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COALESCE(mat.componentes, 0) AS componentes,
        COALESCE(mat.piezas, 0) AS piezas
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN (
        SELECT
            rm.revision_id,
            COUNT(rm.id) AS componentes,
            SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE rm.cantidad END) AS piezas
        FROM revision_material rm
        JOIN materiales m ON m.id = rm.material_id
        WHERE COALESCE(rm.accion, 'cambio') <> 'retiro'
        GROUP BY rm.revision_id
    ) mat ON mat.revision_id = r.id
    WHERE r.tipo_mantenimiento IN ('Preventivo', 'Correctivo')
    ORDER BY r.fecha_mantenimiento DESC, r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$mantenimientosPreventivos = array_values(array_filter($mantenimientosReporte, fn($row) => ($row['tipo_mantenimiento'] ?? '') === 'Preventivo'));
$mantenimientosCorrectivos = array_values(array_filter($mantenimientosReporte, fn($row) => ($row['tipo_mantenimiento'] ?? '') === 'Correctivo'));

$mesActual = (int)date('n');
$anioActual = (int)date('Y');
$mesInicioBimestre = ((int)ceil($mesActual / 2) - 1) * 2 + 1;
$inicioBimestreActual = date('Y-m-d', mktime(0, 0, 0, $mesInicioBimestre, 1, $anioActual));
$finBimestreActual = date('Y-m-t', mktime(0, 0, 0, $mesInicioBimestre + 1, 1, $anioActual));
$filtrarBimestreActual = static function (array $rows) use ($inicioBimestreActual, $finBimestreActual) {
    return array_values(array_filter($rows, static function ($row) use ($inicioBimestreActual, $finBimestreActual) {
        if (empty($row['fecha_mantenimiento'])) {
            return false;
        }
        $fecha = date('Y-m-d', strtotime($row['fecha_mantenimiento']));
        return $fecha >= $inicioBimestreActual && $fecha <= $finBimestreActual;
    }));
};
$preventivosBimestreActual = $filtrarBimestreActual($mantenimientosPreventivos);
$correctivosBimestreActual = $filtrarBimestreActual($mantenimientosCorrectivos);
$kpis['preventivos_bimestre'] = count($preventivosBimestreActual);
$kpis['correctivos_bimestre'] = count($correctivosBimestreActual);
$kpis['arcos_preventivos_bimestre'] = count(array_unique(array_filter(array_column($preventivosBimestreActual, 'arco_id'))));
$kpis['arcos_correctivos_bimestre'] = count(array_unique(array_filter(array_column($correctivosBimestreActual, 'arco_id'))));
$kpis['porcentaje_preventivos_bimestre'] = round(((int)$kpis['preventivos_bimestre'] / $totalArcosPorcentaje) * 100, 1);
$kpis['porcentaje_correctivos_bimestre'] = round(((int)$kpis['correctivos_bimestre'] / $totalArcosPorcentaje) * 100, 1);

$materiales = $pdo->query("
    SELECT
        m.id AS material_id,
        m.nombre AS componente,
        m.medida,
        m.foto,
        COALESCE(inst.total_instalado, 0) AS total_instalado,
        COALESCE(inst.arcos_instalado, 0) AS arcos_instalado,
        COALESCE(inst.series_instaladas, 0) AS series_instaladas,
        COALESCE(cambios.total_cambios, 0) AS total_usos,
        COALESCE(cambios.piezas_cambiadas, 0) AS piezas_cambiadas,
        COALESCE(cambios.arcos_afectados, 0) AS arcos_afectados,
        cambios.primera,
        cambios.ultima,
        CASE WHEN COALESCE(cambios.total_cambios, 0) > 1
             THEN ROUND((DATE_PART('day', cambios.ultima::timestamp - cambios.primera::timestamp) / (cambios.total_cambios - 1))::numeric)
             ELSE NULL END AS avg_interval_days,
        CASE WHEN cambios.ultima IS NULL THEN NULL ELSE cambios.ultima + INTERVAL '1 year' END AS proxima_estimacion
    FROM materiales m
    LEFT JOIN (
        SELECT
            material_id,
            SUM(cantidad) AS total_instalado,
            COUNT(DISTINCT arco_id) AS arcos_instalado,
            SUM(CASE WHEN serie IS NOT NULL AND TRIM(serie) <> '' THEN 1 ELSE 0 END) AS series_instaladas
        FROM arco_material
        GROUP BY material_id
    ) inst ON inst.material_id = m.id
    LEFT JOIN (
        SELECT
            rm.material_id,
            COUNT(rm.id) AS total_cambios,
            SUM(rm.cantidad) AS piezas_cambiadas,
            COUNT(DISTINCT r.arco_id) AS arcos_afectados,
            MIN(r.fecha_mantenimiento) AS primera,
            MAX(r.fecha_mantenimiento) AS ultima
        FROM revision_material rm
        JOIN revisiones r ON r.id = rm.revision_id
        WHERE COALESCE(rm.accion, 'cambio') <> 'retiro'
        GROUP BY rm.material_id
    ) cambios ON cambios.material_id = m.id
    WHERE LOWER(COALESCE(m.medida, '')) NOT IN ('m', 'mt', 'ml')
      AND LOWER(m.nombre) NOT LIKE '%cable%'
    ORDER BY total_usos DESC, total_instalado DESC, m.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$topUbicaciones = $pdo->query("
    SELECT
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COUNT(a.id) AS arcos
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    GROUP BY u.id, u.nombre
    HAVING COUNT(a.id) > 0
    ORDER BY arcos DESC, ubicacion ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalArcosTopUbicaciones = array_sum(array_map(static fn($u) => (int)($u['arcos'] ?? 0), $topUbicaciones));
$totalUbicacionesConArcos = count($topUbicaciones);
