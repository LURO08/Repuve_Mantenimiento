 
 <?php
 
include('../config/db.php');

 $data = [];

 $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

      $stmt = $pdo->query("
    SELECT 
        a.*,
        u.nombre AS ubic,
        COUNT(r.id) AS fallas
    FROM arcos a
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    LEFT JOIN revisiones r ON r.arco_id = a.id
    GROUP BY a.id
    ORDER BY a.fecha_instalacion $order
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = [
        $row["id"],
        $row["nombre"],
        $row["ubic"],
        $row["fecha_instalacion"],
        $row["ultima_mantenimiento"],
        $row["proximo_mantenimiento"],
        $row["Componentes"]
    ];
}

echo json_encode(["data" => $data]);

?>