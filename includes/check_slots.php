<?php
// check_slots.php
require_once __DIR__ . '/auth.php';
checkSession();
require_once __DIR__ . '/graph_sync.php';

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Fecha requerida']);
    exit;
}

$date = $_GET['date'];
$branchId = $_GET['branch_id'] ?? null;
$userDuration = $_SESSION['default_duration'] ?? 60;

// Si el usuario tiene un tiempo asignado menor a 15 minutos,
// puede reservar en cualquier horario superponiéndose.
// Por lo tanto, devolvemos todos los slots como disponibles.
if ($userDuration < 15) {
    $slots = [];
    for ($hour = 7; $hour <= 18; $hour++) {
        $time = sprintf('%02d:00', $hour);
        $slots[] = [
            'time' => $time,
            'available' => true
        ];
    }
    echo json_encode($slots);
    exit;
}

// Si no, verificamos disponibilidad normal por sucursal
$outlook = new OutlookSync();
try {
    $slots = $outlook->getDailyAvailability($date, $branchId);
    echo json_encode($slots);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>