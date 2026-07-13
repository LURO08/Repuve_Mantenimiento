<?php
include('../config/db.php');
require_once '../config/tecnicos_schema.php';

$action = $_REQUEST['action'] ?? '';

function destinoTecnicos(string $param = ''): string
{
    $destinos = [
        'usuarios' => '../views/usuarios.php?tab=tecnicos',
        'tecnicos' => '../views/tecnicos.php',
    ];
    $clave = $_REQUEST['redirect'] ?? $param;
    return $destinos[$clave] ?? $destinos['usuarios'];
}

function redirigirTecnicos(string $tipo, string $mensaje): void
{
    $separador = str_contains(destinoTecnicos(), '?') ? '&' : '?';
    header('Location: ' . destinoTecnicos() . $separador . $tipo . '=' . urlencode($mensaje));
    exit;
}

function asegurarTablaTecnicos(PDO $pdo): void
{
    asegurarRelacionTecnicos($pdo);
}

asegurarTablaTecnicos($pdo);

try {
    switch ($action) {
        case 'add':
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $puesto = trim($_POST['puesto'] ?? '');

            if ($nombre === '') {
                redirigirTecnicos('error', 'Nombre obligatorio');
            }

            $stmt = $pdo->prepare("
                INSERT INTO tecnicos (nombre, telefono, puesto, activo)
                VALUES (?, ?, ?, 1)
                ON CONFLICT (nombre) DO UPDATE
                SET telefono = EXCLUDED.telefono,
                    puesto = EXCLUDED.puesto,
                    activo = 1,
                    eliminado = 0
            ");
            $stmt->execute([$nombre, $telefono ?: null, $puesto ?: null]);

            redirigirTecnicos('msg', 'Tecnico registrado correctamente');

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $puesto = trim($_POST['puesto'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($id <= 0 || $nombre === '') {
                redirigirTecnicos('error', 'Datos incompletos');
            }

            $stmt = $pdo->prepare("
                UPDATE tecnicos
                SET nombre = ?, telefono = ?, puesto = ?, activo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $telefono ?: null, $puesto ?: null, $activo, $id]);

            redirigirTecnicos('msg', 'Tecnico actualizado correctamente');

        case 'toggle':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                redirigirTecnicos('error', 'ID invalido');
            }

            $stmt = $pdo->prepare("UPDATE tecnicos SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->execute([$id]);

            redirigirTecnicos('msg', 'Estado actualizado');

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                redirigirTecnicos('error', 'ID invalido');
            }

            $stmt = $pdo->prepare("UPDATE tecnicos SET activo = 0, eliminado = 1 WHERE id = ?");
            $stmt->execute([$id]);

            redirigirTecnicos('msg', 'Tecnico eliminado');

        default:
            header('Location: ' . destinoTecnicos('tecnicos'));
            exit;
    }
} catch (Exception $e) {
    redirigirTecnicos('error', $e->getMessage());
}
