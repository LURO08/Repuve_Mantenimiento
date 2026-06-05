<?php
$host = "localhost";
$user = "jlromero";
$pass = "0806";
$dbname = "repuve_db";
$port = 3307;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}


// $host = "dpg-d8fqc5d7vvec739gc6k0-a.oregon-postgres.render.com";
// $user = "jlromero"; // Cambia esto por tu nombre de usuario de la base de datos
// $pass = "jY9vqAVL552bbvUOMvzMRo5SMmW25UxL";
// $dbname = "mantenimiento_08cb";
// $port = 5432;


// try {
//     $pdo = new PDO(
//         "pgsql:host=$host;port=$port;dbname=$dbname",
//         $user,
//         $pass
//     );

//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     // Opcional: para que devuelva arrays asociativos por defecto
//     $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// } catch (PDOException $e) {
//     die("Error de conexión PostgreSQL: " . $e->getMessage());
// }

?>
