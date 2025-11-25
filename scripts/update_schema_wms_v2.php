<?php
require_once 'db.php';

try {
    $pdo = DB::connect();
    echo "Conectado a la base de datos.<br>";

    // Add columns if they don't exist
    $columns = [
        'driver_name' => "VARCHAR(255) DEFAULT NULL",
        'driver_dni' => "VARCHAR(50) DEFAULT NULL",
        'helper_name' => "VARCHAR(255) DEFAULT NULL",
        'helper_dni' => "VARCHAR(50) DEFAULT NULL"
    ];

    foreach ($columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE appointments ADD COLUMN $col $def");
            echo "Columna '$col' agregada exitosamente.<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "La columna '$col' ya existe.<br>";
            } else {
                throw $e;
            }
        }
    }

    echo "Actualización de esquema completada.";

} catch (PDOException $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage());
}
?>