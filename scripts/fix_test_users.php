<?php
require_once 'db.php';

try {
    $pdo = DB::connect();

    // Contraseña por defecto para todos los usuarios de prueba
    $new_password = 'password123';
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Actualizar todos los usuarios
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?");
    $stmt->execute([$password_hash]);

    echo "✅ Contraseñas actualizadas correctamente.\n";
    echo "Nueva contraseña para TODOS los usuarios: $new_password\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>