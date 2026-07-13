<?php

$host = getenv('PGHOST') ?: 'dpg-d9a9htgk1i2s73fa59lg-a.oregon-postgres.render.com';
$user = getenv('PGUSER') ?: 'jlromero';
$pass = getenv('PGPASSWORD') ?: '2G3XIajD62KAaUGIKysn1rfIypQ2FI2U';
$dbname = getenv('PGDATABASE') ?: 'repuve_mantenimiento';
$port = getenv('PGPORT') ?: '5432';
$sslmode = getenv('PGSSLMODE') ?: 'disable';

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
