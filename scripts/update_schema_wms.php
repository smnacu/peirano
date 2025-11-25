<?php
// update_schema_wms.php
require_once 'db.php';

try {
    $pdo = DB::connect();

    // 1. Create Branches Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Insert default branches if they don't exist
    $stmt = $pdo->prepare("INSERT IGNORE INTO branches (name) VALUES ('Rivadavia'), ('Monte de Oca')");
    $stmt->execute();
    echo "Branches table created and populated.\n";

    // 2. Alter Users Table
    // Check if columns exist before adding them to avoid errors on re-run
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('provider', 'admin', 'operator') DEFAULT 'provider' AFTER cuit");
        echo "Added 'role' column to users.\n";
    }

    if (!in_array('branch_id', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN branch_id INT NULL AFTER role, ADD FOREIGN KEY (branch_id) REFERENCES branches(id)");
        echo "Added 'branch_id' column to users.\n";
    }

    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER branch_id");
        echo "Added 'status' column to users.\n";
    }

    if (!in_array('default_duration', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN default_duration INT DEFAULT 60 AFTER status");
        echo "Added 'default_duration' column to users.\n";
    }

    // Set existing users to approved and provider role
    $pdo->exec("UPDATE users SET status = 'approved', role = 'provider' WHERE role IS NULL OR status IS NULL");


    // 3. Alter Appointments Table
    $apptColumns = $pdo->query("SHOW COLUMNS FROM appointments")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('branch_id', $apptColumns)) {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN branch_id INT NULL AFTER user_id, ADD FOREIGN KEY (branch_id) REFERENCES branches(id)");
        echo "Added 'branch_id' column to appointments.\n";

        // Assign a default branch to existing appointments (e.g., Rivadavia)
        $rivadaviaId = $pdo->query("SELECT id FROM branches WHERE name = 'Rivadavia'")->fetchColumn();
        if ($rivadaviaId) {
            $pdo->exec("UPDATE appointments SET branch_id = $rivadaviaId WHERE branch_id IS NULL");
        }
    }

    echo "Database schema updated successfully for WMS Lite.\n";

} catch (PDOException $e) {
    die("Error updating schema: " . $e->getMessage());
}
?>