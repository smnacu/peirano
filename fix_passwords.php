<?php
// fix_passwords.php
require_once 'db.php';

echo "<h1>Restableciendo contraseñas...</h1>";

try {
    $pdo = DB::connect();

    // Generar hash válido para '123456'
    $newHash = password_hash('123456', PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password_hash = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newHash]);

    echo "<div style='color: green; font-weight: bold;'>ÉXITO: Se han actualizado todas las contraseñas a '123456'.</div>";
    echo "<p>Hash generado: " . htmlspecialchars($newHash) . "</p>";
    echo "<br><a href='index.php'>Ir al Login</a>";

} catch (PDOException $e) {
    echo "<div style='color: red;'>ERROR: " . $e->getMessage() . "</div>";
}
?>