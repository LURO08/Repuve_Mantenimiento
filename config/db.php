<?php

$host = getenv('PGHOST') ?: 'dpg-d8fqc5d7vvec739gc6k0-a.oregon-postgres.render.com';
$user = getenv('PGUSER') ?: 'jlromero';
$pass = getenv('PGPASSWORD') ?: 'jY9vqAVL552bbvUOMvzMRo5SMmW25UxL';
$dbname = getenv('PGDATABASE') ?: 'mantenimiento_08cb';
$port = getenv('PGPORT') ?: '5432';
$sslmode = getenv('PGSSLMODE') ?: 'prefer';

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    die("Error de conexion PostgreSQL: " . $e->getMessage());
}

?>
