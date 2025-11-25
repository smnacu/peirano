<?php
require_once 'db.php';

// Habilitar reporte de errores para ver qué pasa
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = DB::connect();
    echo "<h1>🔧 Reparando Base de Datos Peirano...</h1>";

    // 1. Intentar agregar needs_helper
    try {
        $sql = "ALTER TABLE appointments ADD COLUMN needs_helper TINYINT(1) DEFAULT 0 AFTER vehicle_type";
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Columna 'needs_helper' (Peón) agregada con éxito.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠️ La columna 'needs_helper' ya existía o hubo un aviso: " . $e->getMessage() . "</p>";
    }

    // 2. Intentar agregar needs_forklift (por las dudas)
    try {
        $sql = "ALTER TABLE appointments ADD COLUMN needs_forklift TINYINT(1) DEFAULT 0 AFTER vehicle_type";
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Columna 'needs_forklift' (Autoelevador) agregada con éxito.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠️ La columna 'needs_forklift' ya existía.</p>";
    }

    echo "<hr><h3>🎉 ¡Listo! Ahora podés volver a reservar.</h3>";
    echo "<a href='reservar.php'>Ir al formulario</a>";

} catch (Exception $e) {
    echo "<h1 style='color:red'>❌ Error Fatal</h1>";
    echo $e->getMessage();
}
?>