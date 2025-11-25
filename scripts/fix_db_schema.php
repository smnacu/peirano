<?php
require_once 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = DB::connect();
    echo "<h1>Reparando Base de Datos...</h1>";

    // 1. Check/Add 'needs_helper'
    try {
        $pdo->query("SELECT needs_helper FROM appointments LIMIT 1");
        echo "<p>✅ Columna 'needs_helper' ya existe.</p>";
    } catch (PDOException $e) {
        echo "<p>⚠️ Columna 'needs_helper' no encontrada. Agregando...</p>";
        $pdo->exec("ALTER TABLE appointments ADD COLUMN needs_helper TINYINT(1) DEFAULT 0 AFTER vehicle_type");
        echo "<p>✅ Columna 'needs_helper' agregada con éxito.</p>";
    }

    // 2. Check/Add 'needs_forklift' (Just in case)
    try {
        $pdo->query("SELECT needs_forklift FROM appointments LIMIT 1");
        echo "<p>✅ Columna 'needs_forklift' ya existe.</p>";
    } catch (PDOException $e) {
        echo "<p>⚠️ Columna 'needs_forklift' no encontrada. Agregando...</p>";
        $pdo->exec("ALTER TABLE appointments ADD COLUMN needs_forklift TINYINT(1) DEFAULT 0 AFTER vehicle_type");
        echo "<p>✅ Columna 'needs_forklift' agregada con éxito.</p>";
    }

    echo "<h2>¡Reparación Completada!</h2>";
    echo "<p><a href='reservar.php'>Volver a Reservar</a></p>";

} catch (PDOException $e) {
    echo "<h1>❌ Error Fatal</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>