<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once '../config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/perfil.php');
    exit;
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['perfil_csrf']) || !hash_equals($_SESSION['perfil_csrf'], $csrfToken)) {
    header('Location: ../views/perfil.php?error=' . urlencode('La sesión del formulario expiró. Intenta nuevamente.'));
    exit;
}

$usernameActual = (string)$_SESSION['user'];
$usernameNuevo = trim($_POST['username'] ?? '');
$passwordActual = (string)($_POST['password_actual'] ?? '');
$passwordNueva = (string)($_POST['password_nueva'] ?? '');
$passwordConfirmacion = (string)($_POST['password_confirmacion'] ?? '');

if ($usernameNuevo === '' || $passwordActual === '') {
    header('Location: ../views/perfil.php?error=' . urlencode('El usuario y la contraseña actual son obligatorios.'));
    exit;
}

if (strlen($usernameNuevo) < 3 || strlen($usernameNuevo) > 100) {
    header('Location: ../views/perfil.php?error=' . urlencode('El nombre de usuario debe tener entre 3 y 100 caracteres.'));
    exit;
}

if ($passwordNueva !== '' && strlen($passwordNueva) < 8) {
    header('Location: ../views/perfil.php?error=' . urlencode('La nueva contraseña debe tener al menos 8 caracteres.'));
    exit;
}

if ($passwordNueva !== $passwordConfirmacion) {
    header('Location: ../views/perfil.php?error=' . urlencode('La confirmación de la nueva contraseña no coincide.'));
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->execute([$usernameActual]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($passwordActual, trim((string)$usuario['password']))) {
    header('Location: ../views/perfil.php?error=' . urlencode('La contraseña actual es incorrecta.'));
    exit;
}

$check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
$check->execute([$usernameNuevo, $usuario['id']]);
if ($check->fetchColumn()) {
    header('Location: ../views/perfil.php?error=' . urlencode('Ese nombre de usuario ya está registrado.'));
    exit;
}

if ($passwordNueva !== '') {
    $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ? WHERE id = ?');
    $stmt->execute([$usernameNuevo, password_hash($passwordNueva, PASSWORD_DEFAULT), $usuario['id']]);
} else {
    $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
    $stmt->execute([$usernameNuevo, $usuario['id']]);
}

$_SESSION['user'] = $usernameNuevo;
$_SESSION['role'] = $usuario['role'];
$_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
session_regenerate_id(true);

header('Location: ../views/perfil.php?msg=' . urlencode('Datos de la cuenta actualizados correctamente.'));
exit;
