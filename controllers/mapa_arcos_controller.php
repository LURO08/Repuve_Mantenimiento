<?php
include('../config/db.php');

$stmt = $pdo->query("
    SELECT 
        a.id,
        a.nombre AS arco,
        a.lat,
        a.lng,
        u.nombre AS ciudad,
        COUNT(r.id) AS fallas
    FROM arcos a
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN revisiones r ON r.arco_id = a.id
    GROUP BY a.id, a.nombre, u.nombre, a.lat, a.lng
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
