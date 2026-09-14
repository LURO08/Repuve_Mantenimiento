<?php
include_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/tecnicos_schema.php';

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

function procesarFirmaTecnico(int $tecnicoId, ?string $firmaActual = null): ?string
{
    $uploadDir = dirname(__DIR__) . '/uploads/firmas';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    if (!empty($_POST['eliminar_firma']) && $_POST['eliminar_firma'] === '1') {
        if ($firmaActual && file_exists(dirname(__DIR__) . '/' . $firmaActual)) {
            @unlink(dirname(__DIR__) . '/' . $firmaActual);
        }
        return null;
    }

    // 1. Archivo subido
    if (isset($_FILES['firma_archivo']) && $_FILES['firma_archivo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['firma_archivo']['name'], PATHINFO_EXTENSION));
        $permitidas = ['png', 'jpg', 'jpeg', 'webp'];
        if (in_array($ext, $permitidas, true)) {
            $fileName = 'firma_' . $tecnicoId . '_' . time() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            $destPath = $uploadDir . '/' . $fileName;
            if (move_uploaded_file($_FILES['firma_archivo']['tmp_name'], $destPath)) {
                if ($firmaActual && file_exists(dirname(__DIR__) . '/' . $firmaActual) && dirname(__DIR__) . '/' . $firmaActual !== $destPath) {
                    @unlink(dirname(__DIR__) . '/' . $firmaActual);
                }
                return 'uploads/firmas/' . $fileName;
            }
        }
    }

    // 2. Trazo en canvas (Base64)
    if (!empty($_POST['firma_canvas']) && str_starts_with($_POST['firma_canvas'], 'data:image/')) {
        $data = $_POST['firma_canvas'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                $fileName = 'firma_' . $tecnicoId . '_' . time() . '.png';
                $destPath = $uploadDir . '/' . $fileName;
                file_put_contents($destPath, $decoded);
                if ($firmaActual && file_exists(dirname(__DIR__) . '/' . $firmaActual) && dirname(__DIR__) . '/' . $firmaActual !== $destPath) {
                    @unlink(dirname(__DIR__) . '/' . $firmaActual);
                }
                return 'uploads/firmas/' . $fileName;
            }
        }
    }

    return $firmaActual;
}

if (!empty($action)) {
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
                RETURNING id
            ");
            $stmt->execute([$nombre, $telefono ?: null, $puesto ?: null]);
            $tecnicoId = (int)$stmt->fetchColumn();

            if ($tecnicoId > 0) {
                $firmaPath = procesarFirmaTecnico($tecnicoId);
                if ($firmaPath) {
                    $upd = $pdo->prepare("UPDATE tecnicos SET firma = ? WHERE id = ?");
                    $upd->execute([$firmaPath, $tecnicoId]);
                }
            }

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

            $stmtOld = $pdo->prepare("SELECT firma FROM tecnicos WHERE id = ?");
            $stmtOld->execute([$id]);
            $firmaActual = $stmtOld->fetchColumn() ?: null;

            $nuevaFirma = procesarFirmaTecnico($id, $firmaActual);

            $stmt = $pdo->prepare("
                UPDATE tecnicos
                SET nombre = ?, telefono = ?, puesto = ?, activo = ?, firma = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $telefono ?: null, $puesto ?: null, $activo, $nuevaFirma, $id]);

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
}
