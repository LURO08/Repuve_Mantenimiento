<?php
require_once "../config/db.php";

class UsersController {

    // Obtener todos los usuarios
    public static function getAll() {
        global $pdo;
        $stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear usuario
    public static function create($username, $pass1, $role) {
        global $pdo;

        if (empty($username) || empty($pass1)) {
            return ["error" => "❌ Debes llenar todos los campos."];
        }

        // Existe usuario?
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);

        if ($check->rowCount() > 0) {
            return ["error" => "❌ El usuario ya existe."];
        }

        // Hash de contraseña
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $role]);

        return ["msg" => "✅ Usuario creado correctamente."];
    }

    // Obtener usuario por ID
    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function update($id, $username, $role, $password = "") {
        global $pdo;

        // Validar duplicado excepto el mismo
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);

        if ($check->rowCount() > 0) {
            return ["error" => "❌ Ya existe otro usuario con ese nombre."];
        }

        if (!empty(trim($password))) {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users 
                                   SET username=?, role=?, password=? 
                                   WHERE id=?");
            $stmt->execute([$username, $role, $hash, $id]);

            return ["msg" => "🔐 Usuario actualizado y contraseña cambiada."];
        }

        $stmt = $pdo->prepare("UPDATE users 
                               SET username=?, role=? 
                               WHERE id=?");
        $stmt->execute([$username, $role, $id]);

        return ["msg" => "✅ Usuario actualizado."];
    }

    // Eliminar usuario
    public static function delete($id) {
        global $pdo;

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        return ["msg" => "🗑 Usuario eliminado."];
    }
}


// =========================================================
// 🔄 MANEJO DE ACCIONES
// =========================================================
if (isset($_GET["action"])) {

    $action = $_GET["action"];

    // 🟢 AGREGAR USUARIO
    if ($action === "add") {

        $result = UsersController::create($_POST["username"], $_POST["pass1"], $_POST["role"]);

        if (isset($result["error"])) {
            header("Location: ../views/usuarios.php?error=" . urlencode($result["error"]));
        } else {
            header("Location: ../views/usuarios.php?msg=" . urlencode($result["msg"]));
        }
        exit;
    }

    // 🟡 EDITAR USUARIO (sin cambiar contraseña)
    if ($action === "edit") {

        $result = UsersController::update($_POST["id"], $_POST["username"], $_POST["role"]);

        if (isset($result["error"])) {
            header("Location: ../views/usuarios.php?error=" . urlencode($result["error"]));
        } else {
            header("Location: ../views/usuarios.php?msg=" . urlencode($result["msg"]));
        }
        exit;
    }

    // 🔴 ELIMINAR USUARIO
    if ($action === "delete") {

        $result = UsersController::delete($_POST["id"]);

        header("Location: ../views/usuarios.php?msg=" . urlencode($result["msg"]));
        exit;
    }
}

?>
