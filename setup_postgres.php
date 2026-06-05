<?php

function pgEnv(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function pgDsn(string $database): string
{
    $host = pgEnv('PGHOST', 'dpg-d8fqc5d7vvec739gc6k0-a.oregon-postgres.render.com');
    $port = pgEnv('PGPORT', '5432');
    $sslmode = pgEnv('PGSSLMODE', 'prefer');

    return "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";
}

function pgConnect(string $database): PDO
{
    return new PDO(pgDsn($database), pgEnv('PGUSER', 'jlromero'), pgEnv('PGPASSWORD', 'jY9vqAVL552bbvUOMvzMRo5SMmW25UxL'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function pgQuoteIdent(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

$database = pgEnv('PGDATABASE', 'mantenimiento_08cb');
$maintenanceDb = pgEnv('PGMAINTENANCE_DB', 'postgres');
$schemaPath = __DIR__ . '/database.sql';

if (!is_file($schemaPath)) {
    die("No se encontro database.sql");
}

try {
    try {
        $adminPdo = pgConnect($maintenanceDb);
    } catch (Throwable $e) {
        $adminPdo = pgConnect($database);
    }

    $stmt = $adminPdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
    $stmt->execute([$database]);

    if (!$stmt->fetchColumn()) {
        $adminPdo->exec("CREATE DATABASE " . pgQuoteIdent($database));
        echo "Base de datos creada: {$database}\n";
    } else {
        echo "Base de datos existente: {$database}\n";
    }

    $pdo = pgConnect($database);
    $pdo->beginTransaction();
    $pdo->exec(file_get_contents($schemaPath));
    $pdo->commit();

    echo "Esquema PostgreSQL aplicado correctamente.\n";

    $adminUser = pgEnv('ADMIN_USER');
    $adminPass = pgEnv('ADMIN_PASSWORD');
    if ($adminUser !== '' && $adminPass !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role)
            VALUES (?, ?, 'admin')
            ON CONFLICT (username) DO NOTHING
        ");
        $stmt->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);
        echo "Usuario admin verificado: {$adminUser}\n";
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die("Error al preparar PostgreSQL: " . $e->getMessage());
}
