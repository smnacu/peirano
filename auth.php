<?php
// auth.php
require_once 'db.php';

session_start();

function login($cuit, $password)
{
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE cuit = ?");
    $stmt->execute([$cuit]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['company_name'] = $user['company_name'];
        $_SESSION['cuit'] = $user['cuit'];
        return true;
    }
    return false;
}

function loginUser($cuit, $password)
{
    if (login($cuit, $password)) {
        return ['success' => true, 'message' => 'Login exitoso'];
    }
    return ['success' => false, 'message' => 'CUIT o contraseña incorrectos.'];
}

function register($cuit, $password, $company_name, $phone)
{
    $pdo = DB::connect();

    // Check if CUIT exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE cuit = ?");
    $stmt->execute([$cuit]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El CUIT ya está registrado.'];
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (cuit, password_hash, company_name, phone) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$cuit, $password_hash, $company_name, $phone]);
        return ['success' => true, 'message' => 'Registro exitoso.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()];
    }
}

function checkSession()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function logout()
{
    session_destroy();
    header("Location: index.php");
    exit();
}
?>