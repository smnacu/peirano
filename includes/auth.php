<?php
// auth.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($cuit, $password)
{
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE cuit = ?");
    $stmt->execute([$cuit]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Check status for providers
        if ($user['role'] === 'provider' && $user['status'] !== 'approved') {
            return 'pending_approval';
        }

        // Check status for others (optional)
        if ($user['status'] === 'rejected') {
            return 'rejected';
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['company_name'] = $user['company_name'];
        $_SESSION['cuit'] = $user['cuit'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['branch_id'] = $user['branch_id'];
        $_SESSION['default_duration'] = $user['default_duration'];
        return true;
    }
    return false;
}

function loginUser($cuit, $password)
{
    $result = login($cuit, $password);
    if ($result === true) {
        return ['success' => true, 'message' => 'Login exitoso'];
    } elseif ($result === 'pending_approval') {
        return ['success' => false, 'message' => 'Su cuenta está pendiente de aprobación por un administrador.'];
    } elseif ($result === 'rejected') {
        return ['success' => false, 'message' => 'Su cuenta ha sido rechazada. Contacte a la administración.'];
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
    // Default role: provider, Default status: pending
    $sql = "INSERT INTO users (cuit, password_hash, company_name, phone, role, status) VALUES (?, ?, ?, ?, 'provider', 'pending')";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$cuit, $password_hash, $company_name, $phone]);
        return ['success' => true, 'message' => 'Registro exitoso. Espere la aprobación del administrador.'];
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