<?php
require_once 'db.php';

// Force error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = DB::connect();

    $cuit = '20123456789';
    $password = 'admin123';
    $company = 'Admin Peirano';
    $phone = '1122334455';

    // 1. Delete if exists
    $stmt = $pdo->prepare("DELETE FROM users WHERE cuit = ?");
    $stmt->execute([$cuit]);

    // 2. Create fresh
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (cuit, password_hash, company_name, phone) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cuit, $hash, $company, $phone]);

    echo "<h1>✅ Usuario Admin Creado</h1>";
    echo "<p><strong>CUIT:</strong> $cuit</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><a href='index.php'>Ir al Login</a></p>";

} catch (PDOException $e) {
    echo "<h1>❌ Error Fatal</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>