<?php
session_start();
require "./config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $u = trim($_POST["user"]);
    $p = $_POST["pass"];

    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $dbHash = trim($user['password']);

        // 1) password_hash() moderno
        if (password_verify($p, $dbHash)) {

            if (password_needs_rehash($dbHash, PASSWORD_DEFAULT)) {
                $newHash = password_hash($p, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $user['id']]);
            }

            $_SESSION["user"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: ../views/dashboard.php");
            exit;
        }

        // 2) Compatibilidad con MD5
        if (preg_match('/^[0-9a-f]{32}$/i', $dbHash) && hash_equals($dbHash, md5($p))) {
            $newHash = password_hash($p, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([$newHash, $user['id']]);

            $_SESSION["user"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: ../views/dashboard.php");
            exit;
        }

        // 3) Compatibilidad con SHA1
        if (preg_match('/^[0-9a-f]{40}$/i', $dbHash) && hash_equals($dbHash, sha1($p))) {
            $newHash = password_hash($p, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([$newHash, $user['id']]);

            $_SESSION["user"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: ../views/dashboard.php");
            exit;
        }

        $error = "❌ Credenciales incorrectas.";
    } else {
        $error = "❌ El usuario no existe.";
    }
}

return $error;
