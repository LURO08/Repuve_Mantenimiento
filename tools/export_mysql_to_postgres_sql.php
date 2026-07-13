<?php
declare(strict_types=1);

function envValue(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function quoteIdent(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

function quotePgValue(PDO $mysql, mixed $value, array $column): string
{
    if ($value === null) {
        return 'NULL';
    }

    $type = strtolower((string)$column['COLUMN_TYPE']);
    $name = strtolower((string)$column['COLUMN_NAME']);
    $text = (string)$value;

    if ($text === '' && preg_match('/int|decimal|float|double|numeric|datetime|timestamp|date/', $type)) {
        return 'NULL';
    }

    if (preg_match('/datetime|timestamp|date/', $type) && str_starts_with($text, '0000-00-00')) {
        return 'NULL';
    }

    if ($name === 'uploaded_at' && trim($text) === '') {
        return 'CURRENT_TIMESTAMP';
    }

    return $mysql->quote($text);
}

function mysqlColumns(PDO $mysql, string $table): array
{
    $stmt = $mysql->prepare("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
        ORDER BY ordinal_position
    ");
    $stmt->execute([$table]);

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['COLUMN_NAME']] = $column;
    }

    return $columns;
}

function mysqlTableExists(PDO $mysql, string $table): bool
{
    $stmt = $mysql->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function mysqlTables(PDO $mysql): array
{
    $allTables = $mysql->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    $preferred = [
        'users',
        'ubicaciones',
        'materiales',
        'tecnicos',
        'arcos',
        'arco_material',
        'revisiones',
        'revision_material',
        'revision_evidencias',
        'formatos_mantenimiento',
        'arcos_bajas',
        'arcos_bajas_evidencias',
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

    $ordered = [];
    foreach ($preferred as $table) {
        if (in_array($table, $allTables, true)) {
            $ordered[] = $table;
        }
    }

    if (!in_array('tecnicos', $ordered, true) && technicianMap($mysql)) {
        $afterMateriales = array_search('materiales', $ordered, true);
        if ($afterMateriales === false) {
            array_unshift($ordered, 'tecnicos');
        } else {
            array_splice($ordered, $afterMateriales + 1, 0, ['tecnicos']);
        }
    }

    foreach ($allTables as $table) {
        if (!in_array($table, $ordered, true)) {
            $ordered[] = $table;
        }
    }

    return $ordered;
}

function legacyTechnicianColumn(string $table): ?string
{
    return [
        'revisiones' => 'tecnico_responsable',
        'infraestructura_revisiones' => 'tecnico_responsable',
        'bitacoras_arco' => 'encargado',
        'arcos_bajas' => 'tecnico_responsable',
    ][$table] ?? null;
}

function technicianMap(PDO $mysql): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    $nextId = 1;
    $tableStmt = $mysql->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'tecnicos'
    ");
    $tableStmt->execute();
    if ($tableStmt->fetchColumn()) {
        foreach ($mysql->query("SELECT id, TRIM(nombre) AS nombre FROM tecnicos WHERE nombre IS NOT NULL AND TRIM(nombre) <> ''")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int)($row['id'] ?? 0);
            $name = (string)($row['nombre'] ?? '');
            if ($id > 0 && $name !== '') {
                $map[$name] = $id;
                $nextId = max($nextId, $id + 1);
            }
        }
    }

    $names = array_fill_keys(array_keys($map), true);
    foreach ([
        ['revisiones', 'tecnico_responsable'],
        ['infraestructura_revisiones', 'tecnico_responsable'],
        ['bitacoras_arco', 'encargado'],
        ['arcos_bajas', 'tecnico_responsable'],
    ] as [$table, $column]) {
        $stmt = $mysql->prepare("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
        ");
        $stmt->execute([$table, $column]);
        if (!$stmt->fetchColumn()) {
            continue;
        }

        foreach ($mysql->query("SELECT DISTINCT TRIM(`{$column}`) FROM `{$table}` WHERE `{$column}` IS NOT NULL AND TRIM(`{$column}`) <> ''")->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $names[(string)$name] = true;
        }
    }

    foreach (array_keys($names) as $name) {
        if (!isset($map[$name])) {
            $map[$name] = $nextId++;
        }
    }

    return $map;
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

function normalizeRowValue(PDO $mysql, string $table, string $column, mixed $value): mixed
{
    if ($table === 'revision_material' && $column === 'arco_material_id' && $value !== null && $value !== '') {
        $validArcoMaterialIds = mysqlIdSet($mysql, 'arco_material');
        if (empty($validArcoMaterialIds[(string)$value])) {
            return null;
        }
    }

    return $value;
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

$root = dirname(__DIR__);
$schemaPath = $root . DIRECTORY_SEPARATOR . 'database.sql';
if (!is_file($schemaPath)) {
    throw new RuntimeException('No se encontro database.sql.');
}

$backupDir = $root . DIRECTORY_SEPARATOR . 'migration_backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    throw new RuntimeException('No se pudo crear migration_backups.');
}

$timestamp = date('Ymd_His');
$outputPath = $backupDir . DIRECTORY_SEPARATOR . "mysql_to_postgres_repuve_db_{$timestamp}.sql";
$tables = mysqlTables($mysql);

$fh = fopen($outputPath, 'wb');
if (!$fh) {
    throw new RuntimeException('No se pudo crear el archivo SQL convertido.');
}

fwrite($fh, "-- Conversion MySQL a PostgreSQL para Panel REPuve\n");
fwrite($fh, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
fwrite($fh, "-- Origen MySQL: " . envValue('MYSQL_DATABASE', 'repuve_db') . "\n\n");
fwrite($fh, "BEGIN;\n\n");
fwrite($fh, "-- Estructura PostgreSQL\n");
fwrite($fh, file_get_contents($schemaPath) . "\n\n");
fwrite($fh, "-- Limpieza de tablas importadas\n");
fwrite($fh, 'TRUNCATE TABLE ' . implode(', ', array_map('quoteIdent', array_reverse($tables))) . " RESTART IDENTITY CASCADE;\n\n");
fwrite($fh, "-- Datos convertidos\n");

$counts = [];
foreach ($tables as $table) {
    $columns = mysqlColumns($mysql, $table);
    if (!$columns && $table === 'tecnicos') {
        $columns = [
            'id' => ['COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int'],
            'nombre' => ['COLUMN_NAME' => 'nombre', 'COLUMN_TYPE' => 'varchar(150)'],
            'telefono' => ['COLUMN_NAME' => 'telefono', 'COLUMN_TYPE' => 'varchar(30)'],
            'puesto' => ['COLUMN_NAME' => 'puesto', 'COLUMN_TYPE' => 'varchar(80)'],
            'activo' => ['COLUMN_NAME' => 'activo', 'COLUMN_TYPE' => 'int'],
            'eliminado' => ['COLUMN_NAME' => 'eliminado', 'COLUMN_TYPE' => 'int'],
            'created_at' => ['COLUMN_NAME' => 'created_at', 'COLUMN_TYPE' => 'timestamp'],
        ];
    }
    if (!$columns) {
        continue;
    }

    $legacyTecnicoColumn = legacyTechnicianColumn($table);
    if ($table === 'tecnicos') {
        $rows = mysqlTableExists($mysql, $table)
            ? $mysql->query("SELECT * FROM `" . str_replace('`', '``', $table) . "`")->fetchAll(PDO::FETCH_ASSOC)
            : [];
        $existingNames = array_fill_keys(array_map(static fn($row) => (string)($row['nombre'] ?? ''), $rows), true);
        foreach (technicianMap($mysql) as $name => $id) {
            if (!isset($existingNames[$name])) {
                $rows[] = [
                    'id' => $id,
                    'nombre' => $name,
                    'telefono' => null,
                    'puesto' => null,
                    'activo' => 1,
                    'eliminado' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        $columns = array_intersect_key($columns, array_flip(['id', 'nombre', 'telefono', 'puesto', 'activo', 'eliminado', 'created_at']));
        foreach (['telefono', 'puesto', 'activo', 'eliminado', 'created_at'] as $extraColumn) {
            if (!isset($columns[$extraColumn])) {
                $columns[$extraColumn] = [
                    'COLUMN_NAME' => $extraColumn,
                    'COLUMN_TYPE' => $extraColumn === 'created_at' ? 'timestamp' : 'varchar(150)',
                ];
            }
        }
    } else {
        $columnNamesForSelect = array_keys($columns);
        $mysqlColumnSql = implode(', ', array_map(fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', $columnNamesForSelect));
        $rows = $mysql->query("SELECT {$mysqlColumnSql} FROM `" . str_replace('`', '``', $table) . "`")->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($legacyTecnicoColumn && isset($columns[$legacyTecnicoColumn])) {
        unset($columns[$legacyTecnicoColumn]);
        $columns['tecnico_id'] = [
            'COLUMN_NAME' => 'tecnico_id',
            'COLUMN_TYPE' => 'int',
        ];
    }

    $columnNames = array_keys($columns);
    $counts[$table] = count($rows);

    fwrite($fh, "-- {$table}: {$counts[$table]} registro(s)\n");
    if (!$rows) {
        fwrite($fh, "\n");
        continue;
    }

    $pgColumnSql = implode(', ', array_map('quoteIdent', $columnNames));
    foreach ($rows as $row) {
        if ($table === 'revision_evidencias' && empty($row['uploaded_at']) && !empty($row['created_at'])) {
            $row['uploaded_at'] = $row['created_at'];
        }
        if ($legacyTecnicoColumn) {
            $legacyName = trim((string)($row[$legacyTecnicoColumn] ?? ''));
            $row['tecnico_id'] = $legacyName !== '' ? (technicianMap($mysql)[$legacyName] ?? null) : null;
        }

        $values = [];
        foreach ($columnNames as $columnName) {
            $value = normalizeRowValue($mysql, $table, $columnName, $row[$columnName] ?? null);
            $values[] = quotePgValue($mysql, $value, $columns[$columnName]);
        }

        fwrite($fh, 'INSERT INTO ' . quoteIdent($table) . " ({$pgColumnSql}) VALUES (" . implode(', ', $values) . ");\n");
    }
    fwrite($fh, "\n");
}

fwrite($fh, "-- Ajuste de secuencias identity/serial\n");
foreach ($tables as $table) {
    $columns = mysqlColumns($mysql, $table);
    if (!isset($columns['id']) || $table === 'arco_infraestructura') {
        continue;
    }

    $tableLiteral = "'public." . str_replace("'", "''", $table) . "'";
    fwrite(
        $fh,
        "SELECT setval(seq, max_id, is_called)\n" .
        "FROM (\n" .
        "  SELECT pg_get_serial_sequence({$tableLiteral}, 'id') AS seq,\n" .
        "         GREATEST((SELECT COALESCE(MAX(id), 0) FROM " . quoteIdent($table) . "), 1) AS max_id,\n" .
        "         (SELECT COALESCE(MAX(id), 0) > 0 FROM " . quoteIdent($table) . ") AS is_called\n" .
        ") s\n" .
        "WHERE seq IS NOT NULL;\n"
    );
}

fwrite($fh, "\nCOMMIT;\n");
fclose($fh);

echo "Archivo PostgreSQL generado: {$outputPath}\n";
foreach ($counts as $table => $count) {
    echo "{$table}: {$count}\n";
}
