<?php
require_once __DIR__ . '/../config/db.php';

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

$kpis['total_arcos'] = (int)$pdo->query("SELECT COUNT(*) FROM arcos WHERE COALESCE(estado, 'Activo') <> 'Baja'")->fetchColumn();
$totalesInstalados = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE am.cantidad END), 0) AS piezas,
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN am.cantidad ELSE 0 END), 0) AS metros
    FROM arco_material am
    JOIN materiales m ON m.id = am.material_id
    JOIN arcos a ON a.id = am.arco_id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetch(PDO::FETCH_ASSOC);
$kpis['material_total_arcos'] = (float)$totalesInstalados['piezas'];
$kpis['material_metros_arcos'] = (float)$totalesInstalados['metros'];

$totalesCambiados = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN 0 ELSE rm.cantidad END), 0) AS piezas,
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(m.medida, '')) IN ('m', 'mt', 'ml') OR LOWER(m.nombre) LIKE '%cable%' THEN rm.cantidad ELSE 0 END), 0) AS metros
    FROM revision_material rm
    JOIN materiales m ON m.id = rm.material_id
    JOIN revisiones r ON r.id = rm.revision_id
    JOIN arcos a ON a.id = r.arco_id
    WHERE COALESCE(rm.accion, 'cambio') <> 'retiro'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetch(PDO::FETCH_ASSOC);
$kpis['componentes_cambiados'] = (float)$totalesCambiados['piezas'];
$kpis['metros_cambiados'] = (float)$totalesCambiados['metros'];
$kpis['correctivos_90'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento = 'Correctivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '90 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();
$kpis['correctivos_60'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento = 'Correctivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();
$kpis['preventivos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones r JOIN arcos a ON a.id = r.arco_id WHERE r.tipo_mantenimiento = 'Preventivo' AND COALESCE(a.estado, 'Activo') <> 'Baja'")->fetchColumn();
$kpis['correctivos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones r JOIN arcos a ON a.id = r.arco_id WHERE r.tipo_mantenimiento = 'Correctivo' AND COALESCE(a.estado, 'Activo') <> 'Baja'")->fetchColumn();
$kpis['mantenimientos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM revisiones r JOIN arcos a ON a.id = r.arco_id WHERE r.tipo_mantenimiento IN ('Preventivo', 'Correctivo') AND COALESCE(a.estado, 'Activo') <> 'Baja'")->fetchColumn();

$kpis['arcos_sin_mantenimiento'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM arcos a
    LEFT JOIN revisiones r ON r.arco_id = a.id
    WHERE r.id IS NULL
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();

$kpis['mantenimientos_vencidos'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT a.id, COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) AS base_mantenimiento
        FROM arcos a
        LEFT JOIN revisiones r ON r.arco_id = a.id
        WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
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
        WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
        GROUP BY a.id, a.fecha_instalacion
    ) x
    WHERE x.base_mantenimiento + INTERVAL '12 months' BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'
")->fetchColumn();

$kpis['preventivos_60'] = (int)$pdo->query("
    SELECT COUNT(*)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento = 'Preventivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();
$kpis['arcos_preventivos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT r.arco_id)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento = 'Preventivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();
$kpis['arcos_correctivos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT r.arco_id)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento = 'Correctivo'
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
")->fetchColumn();
$kpis['arcos_mantenimientos_60'] = (int)$pdo->query("
    SELECT COUNT(DISTINCT r.arco_id)
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    WHERE r.tipo_mantenimiento IN ('Preventivo', 'Correctivo')
      AND r.fecha_mantenimiento >= CURRENT_DATE - INTERVAL '60 days'
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
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
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
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
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
    GROUP BY a.id, a.nombre, u.nombre, a.fecha_instalacion
    HAVING COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) + INTERVAL '12 months' < CURRENT_DATE
    ORDER BY
        CASE WHEN MAX(r.fecha_mantenimiento) IS NULL THEN 0 ELSE 1 END ASC,
        COALESCE(MAX(r.fecha_mantenimiento), a.fecha_instalacion) + INTERVAL '12 months' ASC,
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
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
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
        t.nombre AS tecnico_responsable,
        r.observaciones,
        a.nombre AS arco,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COALESCE(mat.componentes, 0) AS componentes,
        COALESCE(mat.piezas, 0) AS piezas,
        COALESCE(mat.metros, 0) AS metros
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    LEFT JOIN tecnicos t ON t.id = r.tecnico_id
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
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
    ORDER BY r.fecha_mantenimiento DESC
")->fetchAll(PDO::FETCH_ASSOC);

$mantenimientosReporte = $pdo->query("
    SELECT
        r.id,
        r.arco_id,
        r.fecha_mantenimiento,
        r.tipo_mantenimiento,
        t.nombre AS tecnico_responsable,
        r.observaciones,
        a.nombre AS arco,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COALESCE(mat.componentes, 0) AS componentes,
        COALESCE(mat.piezas, 0) AS piezas
    FROM revisiones r
    JOIN arcos a ON a.id = r.arco_id
    LEFT JOIN tecnicos t ON t.id = r.tecnico_id
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
      AND COALESCE(a.estado, 'Activo') <> 'Baja'
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
    WITH revision_timeline AS (
        SELECT 
            rm.id AS rm_id,
            rm.revision_id,
            rm.arco_material_id,
            rm.material_id AS material_colocado_id,
            rm.cantidad,
            rm.serie AS nueva_serie,
            rm.accion,
            r.arco_id,
            r.fecha_mantenimiento,
            r.tipo_mantenimiento,
            r.observaciones,
            r.tecnico_id,
            am.material_id AS base_material_id,
            COALESCE(
                LAG(rm.material_id) OVER (
                    PARTITION BY rm.arco_material_id 
                    ORDER BY r.fecha_mantenimiento ASC, rm.id ASC
                ),
                am.material_id,
                rm.material_id
            ) AS material_retirado_id
        FROM revision_material rm
        JOIN revisiones r ON r.id = rm.revision_id
        LEFT JOIN arco_material am ON am.id = rm.arco_material_id
        JOIN arcos a ON a.id = r.arco_id
        WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
          AND COALESCE(rm.accion, 'cambio') <> 'retiro'
    )
    SELECT
        m.id AS material_id,
        m.nombre AS componente,
        m.medida,
        m.foto,
        COALESCE(inst.total_instalado, 0) AS total_instalado,
        COALESCE(inst.arcos_instalado, 0) AS arcos_instalado,
        COALESCE(inst.series_instaladas, 0) AS series_instaladas,
        
        -- Intervenciones Correctivas (Fallas / Averías sufridas por este componente)
        COALESCE(corr.fallas_correctivas, 0) AS fallas_correctivas,
        COALESCE(corr.piezas_falla, 0) AS piezas_falla,
        COALESCE(corr.arcos_fallados, 0) AS arcos_fallados,
        corr.primera_falla,
        corr.ultima_falla,
        
        -- Intervenciones Preventivas (Renovaciones / Retiros en Preventivo)
        COALESCE(prev.cambios_preventivos, 0) AS cambios_preventivos,
        COALESCE(prev.piezas_preventivas, 0) AS piezas_preventivas,
        COALESCE(prev.arcos_preventivos, 0) AS arcos_preventivos,
        prev.primer_preventivo,
        prev.ultimo_preventivo,
        
        -- Colocaciones de este material como equipo nuevo / reemplazo
        COALESCE(nuevos.veces_colocado, 0) AS veces_colocado_nuevo,
        COALESCE(nuevos.piezas_colocadas, 0) AS piezas_colocadas_nuevo,
        
        -- Totales Combinados (veces que fue intervenido, retirado o colocado)
        COALESCE(tot.total_usos, 0) AS total_usos,
        COALESCE(tot.piezas_cambiadas, 0) AS piezas_cambiadas,
        COALESCE(tot.arcos_afectados, 0) AS arcos_afectados,
        tot.primera,
        tot.ultima,
        CASE WHEN COALESCE(tot.total_usos, 0) > 1 AND tot.ultima IS NOT NULL AND tot.primera IS NOT NULL
             THEN ROUND((DATE_PART('day', tot.ultima::timestamp - tot.primera::timestamp) / (tot.total_usos - 1))::numeric)
             ELSE NULL END AS avg_interval_days,
        CASE WHEN tot.ultima IS NULL THEN NULL ELSE tot.ultima + INTERVAL '1 year' END AS proxima_estimacion
    FROM materiales m
    LEFT JOIN (
        SELECT
            material_id,
            SUM(cantidad) AS total_instalado,
            COUNT(DISTINCT arco_id) AS arcos_instalado,
            SUM(CASE WHEN serie IS NOT NULL AND TRIM(serie) <> '' THEN 1 ELSE 0 END) AS series_instaladas
        FROM arco_material am
        JOIN arcos a ON a.id = am.arco_id
        WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
        GROUP BY am.material_id
    ) inst ON inst.material_id = m.id
    LEFT JOIN (
        SELECT
            material_retirado_id AS material_id,
            COUNT(rm_id) AS fallas_correctivas,
            SUM(cantidad) AS piezas_falla,
            COUNT(DISTINCT arco_id) AS arcos_fallados,
            MIN(fecha_mantenimiento) AS primera_falla,
            MAX(fecha_mantenimiento) AS ultima_falla
        FROM revision_timeline
        WHERE tipo_mantenimiento = 'Correctivo'
        GROUP BY material_retirado_id
    ) corr ON corr.material_id = m.id
    LEFT JOIN (
        SELECT
            material_retirado_id AS material_id,
            COUNT(rm_id) AS cambios_preventivos,
            SUM(cantidad) AS piezas_preventivas,
            COUNT(DISTINCT arco_id) AS arcos_preventivos,
            MIN(fecha_mantenimiento) AS primer_preventivo,
            MAX(fecha_mantenimiento) AS ultimo_preventivo
        FROM revision_timeline
        WHERE tipo_mantenimiento = 'Preventivo'
        GROUP BY material_retirado_id
    ) prev ON prev.material_id = m.id
    LEFT JOIN (
        SELECT
            material_colocado_id AS material_id,
            COUNT(rm_id) AS veces_colocado,
            SUM(cantidad) AS piezas_colocadas
        FROM revision_timeline
        GROUP BY material_colocado_id
    ) nuevos ON nuevos.material_id = m.id
    LEFT JOIN (
        SELECT
            mat_id AS material_id,
            COUNT(DISTINCT rm_id) AS total_usos,
            SUM(cantidad) AS piezas_cambiadas,
            COUNT(DISTINCT arco_id) AS arcos_afectados,
            MIN(fecha_mantenimiento) AS primera,
            MAX(fecha_mantenimiento) AS ultima
        FROM (
            SELECT material_retirado_id AS mat_id, rm_id, cantidad, arco_id, fecha_mantenimiento FROM revision_timeline
            UNION
            SELECT material_colocado_id AS mat_id, rm_id, cantidad, arco_id, fecha_mantenimiento FROM revision_timeline
        ) u
        GROUP BY mat_id
    ) tot ON tot.material_id = m.id
    WHERE (
        LOWER(COALESCE(m.medida, '')) NOT IN ('m', 'mt', 'ml')
        OR COALESCE(corr.fallas_correctivas, 0) > 0
        OR COALESCE(tot.total_usos, 0) > 0
    )
    ORDER BY
        fallas_correctivas DESC,
        piezas_falla DESC,
        cambios_preventivos DESC,
        veces_colocado_nuevo DESC,
        total_instalado DESC,
        m.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$topUbicaciones = $pdo->query("
    SELECT
        u.id AS ubicacion_id,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        COUNT(a.id) AS arcos
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
    GROUP BY u.id, u.nombre
    HAVING COUNT(a.id) > 0
    ORDER BY arcos DESC, ubicacion ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalArcosTopUbicaciones = array_sum(array_map(static fn($u) => (int)($u['arcos'] ?? 0), $topUbicaciones));
$totalUbicacionesConArcos = count($topUbicaciones);

$arcosUbicacionRows = $pdo->query("
    WITH ultimo_mantenimiento AS (
        SELECT
            r.arco_id,
            MAX(r.fecha_mantenimiento) AS ultima_mantenimiento
        FROM revisiones r
        GROUP BY r.arco_id
    )
    SELECT
        CASE WHEN u.id IS NULL THEN 'sin_ubicacion' ELSE CONCAT('u_', u.id) END AS ubicacion_key,
        COALESCE(u.nombre, 'Sin ubicacion') AS ubicacion,
        a.id,
        a.nombre,
        a.fecha_instalacion,
        um.ultima_mantenimiento,
        CASE
            WHEN um.ultima_mantenimiento IS NULL THEN (a.fecha_instalacion::date + INTERVAL '1 year')::date
            ELSE (um.ultima_mantenimiento::date + INTERVAL '1 year')::date
        END AS proximo_mantenimiento,
        COALESCE(a.estado, 'Activo') AS estado,
        COALESCE(materiales.total_componentes, 0) AS total_componentes
    FROM arcos a
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN ultimo_mantenimiento um ON um.arco_id = a.id
    LEFT JOIN (
        SELECT arco_id, COUNT(*) AS total_componentes
        FROM arco_material
        GROUP BY arco_id
    ) materiales ON materiales.arco_id = a.id
    WHERE COALESCE(a.estado, 'Activo') <> 'Baja'
    ORDER BY COALESCE(u.nombre, 'Sin ubicacion') ASC, a.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$arcosPorUbicacion = [];
foreach ($arcosUbicacionRows as $row) {
    $key = $row['ubicacion_key'];
    if (!isset($arcosPorUbicacion[$key])) {
        $arcosPorUbicacion[$key] = [
            'ubicacion' => $row['ubicacion'],
            'arcos' => [],
        ];
    }
    $arcosPorUbicacion[$key]['arcos'][] = [
        'id' => (int)$row['id'],
        'nombre' => $row['nombre'],
        'fecha_instalacion' => $row['fecha_instalacion'],
        'ultima_mantenimiento' => $row['ultima_mantenimiento'],
        'proximo_mantenimiento' => $row['proximo_mantenimiento'],
        'estado' => $row['estado'],
        'total_componentes' => (int)$row['total_componentes'],
    ];
}
