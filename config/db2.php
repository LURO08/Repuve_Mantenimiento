<?php

$host = getenv('PGHOST') ?: 'dpg-da4ugt3m8hqs73arpaq0-a';
$user = getenv('PGUSER') ?: 'repuvebd_user';
$pass = getenv('PGPASSWORD') ?: 'TD9eVqoRqNPfOKjZQctCp0kzBZwJCKm7';
$dbname = getenv('PGDATABASE') ?: 'repuvebd';
$port = getenv('PGPORT') ?: '5432';
$sslmode = getenv('PGSSLMODE') ?: 'require';

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
