<?php

require_once __DIR__ . '/config/db.php';

function envValue(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function quoteIdent(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

function normalizeValue(string $column, mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    $dateColumns = [
        'fecha_instalacion',
        'fecha_mantenimiento',
        'fecha_registro',
        'uploaded_at',
        'created_at',
        'creado_at',
    ];

    if (in_array($column, $dateColumns, true)) {
        $text = trim((string)$value);
        if ($text === '' || str_starts_with($text, '0000-00-00')) {
            return null;
        }
    }

    return $value;
}

function mysqlIdSet(PDO $mysql, string $table): array
{
    static $cache = [];
    if (!isset($cache[$table])) {
        $ids = $mysql->query("SELECT id FROM `" . str_replace('`', '``', $table) . "`")->fetchAll(PDO::FETCH_COLUMN);
        $cache[$table] = array_fill_keys(array_map('strval', $ids), true);
    }
    return $cache[$table];
}

function normalizeForeignKeys(PDO $mysql, string $table, string $column, mixed $value): mixed
{
    if ($table === 'revision_material' && $column === 'arco_material_id' && $value !== null && $value !== '') {
        $validArcoMaterialIds = mysqlIdSet($mysql, 'arco_material');
        if (empty($validArcoMaterialIds[(string)$value])) {
            return null;
        }
    }

    return $value;
}

function columns(PDO $pdo, string $table, string $driver): array
{
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
            ORDER BY ordinal_position
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = ?
            ORDER BY ordinal_position
        ");
    }
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function tableExists(PDO $pdo, string $table, string $driver): bool
{
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_name = ?
        ");
    }
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function ensurePostgresCompatibility(PDO $pg): void
{
    $pg->exec("ALTER TABLE materiales ADD COLUMN IF NOT EXISTS serie VARCHAR(120)");
    $pg->exec("ALTER TABLE revision_evidencias ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE");
    $pg->exec("ALTER TABLE infraestructura_nodos ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE");
    $pg->exec("ALTER TABLE infraestructura_revisiones ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE");
    $pg->exec("ALTER TABLE arco_infraestructura ADD COLUMN IF NOT EXISTS id INTEGER");
}

function backupPostgres(PDO $pg, array $tables): string
{
    $backup = [];
    foreach ($tables as $table) {
        if (!tableExists($pg, $table, 'pgsql')) {
            continue;
        }
        $backup[$table] = $pg->query("SELECT * FROM " . quoteIdent($table))->fetchAll(PDO::FETCH_ASSOC);
    }

    $dir = __DIR__ . '/migration_backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $dir . '/pg_before_import_' . date('Ymd_His') . '.json';
    file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $path;
}

function importTable(PDO $mysql, PDO $pg, string $table): int
{
    if (!tableExists($mysql, $table, 'mysql') || !tableExists($pg, $table, 'pgsql')) {
        return 0;
    }

    $mysqlColumns = columns($mysql, $table, 'mysql');
    $pgColumns = columns($pg, $table, 'pgsql');
    $commonColumns = array_values(array_intersect($mysqlColumns, $pgColumns));

    if (!$commonColumns) {
        return 0;
    }

    $selectColumns = implode(', ', array_map(fn($col) => '`' . str_replace('`', '``', $col) . '`', $commonColumns));
    $rows = $mysql->query("SELECT {$selectColumns} FROM `" . str_replace('`', '``', $table) . "`")->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        return 0;
    }

    $pgColumnsSql = implode(', ', array_map('quoteIdent', $commonColumns));
    $placeholders = implode(', ', array_fill(0, count($commonColumns), '?'));
    $insert = $pg->prepare("INSERT INTO " . quoteIdent($table) . " ({$pgColumnsSql}) VALUES ({$placeholders})");

    foreach ($rows as $row) {
        $values = [];
        foreach ($commonColumns as $column) {
            $rawValue = $row[$column] ?? null;
            if ($column === 'uploaded_at' && ($rawValue === null || $rawValue === '') && !empty($row['created_at'])) {
                $rawValue = $row['created_at'];
            }
            $value = normalizeValue($column, $rawValue);
            $values[] = normalizeForeignKeys($mysql, $table, $column, $value);
        }
        $insert->execute($values);
    }

    return count($rows);
}

function resetSequences(PDO $pg, array $tables): void
{
    foreach ($tables as $table) {
        $stmt = $pg->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = ?
              AND column_name = 'id'
        ");
        $stmt->execute([$table]);
        if (!$stmt->fetchColumn()) {
            continue;
        }

        $seqStmt = $pg->prepare("SELECT pg_get_serial_sequence(?, 'id')");
        $seqStmt->execute(['public.' . $table]);
        $sequence = $seqStmt->fetchColumn();
        if (!$sequence) {
            continue;
        }

        $maxId = (int)$pg->query("SELECT COALESCE(MAX(id), 0) FROM " . quoteIdent($table))->fetchColumn();
        if ($maxId > 0) {
            $setStmt = $pg->prepare("SELECT setval(?, ?, true)");
            $setStmt->execute([$sequence, $maxId]);
        } else {
            $setStmt = $pg->prepare("SELECT setval(?, 1, false)");
            $setStmt->execute([$sequence]);
        }
    }
}

$mysqlDsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    envValue('MYSQL_HOST', '127.0.0.1'),
    envValue('MYSQL_PORT', '3307'),
    envValue('MYSQL_DATABASE', 'repuve_db')
);

$mysql = new PDO($mysqlDsn, envValue('MYSQL_USER', 'jlromero'), envValue('MYSQL_PASSWORD', '0806'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = [
    'users',
    'ubicaciones',
    'materiales',
    'arcos',
    'arco_material',
    'revisiones',
    'revision_material',
    'revision_evidencias',
    'infraestructura_nodos',
    'arco_infraestructura',
    'infraestructura_material',
    'infraestructura_revisiones',
    'infraestructura_revision_material',
    'infraestructura_revision_evidencias',
    'bitacoras_arco',
    'checklist_conceptos',
    'bitacora_checklist',
];

try {
    $schemaPath = __DIR__ . '/database.sql';
    if (is_file($schemaPath)) {
        $pdo->exec(file_get_contents($schemaPath));
    }
    ensurePostgresCompatibility($pdo);

    $backupPath = backupPostgres($pdo, $tables);
    echo "Respaldo PostgreSQL previo: {$backupPath}\n";

    $pdo->beginTransaction();
    $truncateTables = implode(', ', array_map('quoteIdent', $tables));
    $pdo->exec("TRUNCATE {$truncateTables} RESTART IDENTITY CASCADE");

    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = importTable($mysql, $pdo, $table);
        echo "{$table}: {$counts[$table]} registros importados\n";
    }

    resetSequences($pdo, $tables);
    $pdo->commit();

    echo "Importacion finalizada correctamente.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Error al importar: " . $e->getMessage() . "\n");
    exit(1);
}
