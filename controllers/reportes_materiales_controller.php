<?php
require_once('../config/db.php');

// Reporte general por material
$sql = "
    SELECT 
        m.id AS material_id,
        m.nombre AS componente,
        COUNT(rm.id) AS total_usos,
        MIN(r.fecha_mantenimiento) AS primera,
        MAX(r.fecha_mantenimiento) AS ultima,
        -- promedio de días entre mantenimientos (si sólo 1 uso => NULL)
        CASE WHEN COUNT(rm.id) > 1
             THEN ROUND(DATEDIFF(MAX(r.fecha_mantenimiento), MIN(r.fecha_mantenimiento)) / (COUNT(rm.id) - 1))
             ELSE NULL END AS avg_interval_days,
        -- estimación del próximo mantenimiento: siempre +1 año desde la última fecha
        DATE_ADD(MAX(r.fecha_mantenimiento), INTERVAL 1 YEAR) AS proxima_estimacion
    FROM revision_material rm
    JOIN materiales m ON rm.material_id = m.id
    JOIN revisiones r ON rm.revision_id = r.id
    GROUP BY m.id, m.nombre
    ORDER BY total_usos DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
