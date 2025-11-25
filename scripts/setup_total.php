<?php
// Intentamos cargar la configuración. Si falla, definimos credenciales a mano.
$configFile = __DIR__ . '/includes/config.php';
$dbFile = __DIR__ . '/includes/db.php';

if (file_exists($configFile) && file_exists($dbFile)) {
    require_once $configFile;
    require_once $dbFile;
    echo "<h3>✅ Archivos de configuración encontrados en /includes</h3>";
} else {
    // Fallback por si las carpetas no se leen bien, usá tus credenciales de Ferozo acá
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'c2031975_peirano'); // <--- CHEQUEÁ ESTO
    define('DB_USER', 'c2031975_peirano'); // <--- CHEQUEÁ ESTO
    define('DB_PASS', 'zoqhvewcbg5Khxi');  // <--- CHEQUEÁ ESTO
    
    class DB {
        public static function connect() {
            return new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        }
    }
    echo "<h3>⚠️ Usando credenciales manuales (revisar carpetas includes/)</h3>";
}

try {
    $pdo = DB::connect();
    echo "<h1>🛠️ Instalación / Actualización WMS Lite</h1>";

    // 1. CREAR TABLA BRANCHES (Sucursales)
    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        address VARCHAR(255),
        active TINYINT(1) DEFAULT 1
    )");
    echo "<p>✅ Tabla 'branches' verificada.</p>";

    // Insertar sucursales si está vacía
    $count = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO branches (name, address) VALUES 
            ('Rivadavia', 'Av. Rivadavia 1234, CABA'),
            ('Monte de Oca', 'Av. Montes de Oca 567, CABA')");
        echo "<p>✅ Sucursales 'Rivadavia' y 'Monte de Oca' insertadas.</p>";
    }

    // 2. ACTUALIZAR TABLA APPOINTMENTS (Datos Chofer y Sucursal)
    $cols = [
        'branch_id' => "INT NULL AFTER user_id",
        'driver_name' => "VARCHAR(100) NULL AFTER vehicle_type",
        'driver_dni' => "VARCHAR(20) NULL AFTER driver_name",
        'helper_name' => "VARCHAR(100) NULL AFTER needs_helper",
        'helper_dni' => "VARCHAR(20) NULL AFTER helper_name",
        'auth_start' => "DATETIME NULL",
        'auth_end' => "DATETIME NULL"
    ];

    foreach ($cols as $col => $def) {
        try {
            $pdo->query("SELECT $col FROM appointments LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE appointments ADD COLUMN $col $def");
            echo "<p>✨ Columna '$col' agregada a turnos.</p>";
        }
    }

    // 3. ACTUALIZAR TABLA USERS (Roles y Código Proveedor)
    $userCols = [
        'role' => "ENUM('provider', 'operator', 'admin') DEFAULT 'provider'",
        'branch_id' => "INT NULL",
        'vendor_code' => "VARCHAR(50) NULL"
    ];

    foreach ($userCols as $col => $def) {
        try {
            $pdo->query("SELECT $col FROM users LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "<p>✨ Columna '$col' agregada a usuarios.</p>";
        }
    }

    echo "<hr><h2 style='color:green'>🚀 ¡Sistema Actualizado!</h2>";
    echo "<p>La base de datos ahora soporta sucursales, choferes y roles.</p>";
    echo "<a href='index.php'>Ir al Login</a>";

} catch (Exception $e) {
    echo "<h1 style='color:red'>❌ Error Fatal</h1>";
    echo $e->getMessage();
}
?>