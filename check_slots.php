<?php
// check_slots.php
require_once 'auth.php';
checkSession();
require_once 'graph_sync.php';

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Fecha requerida']);
    exit;
}

$date = $_GET['date'];
$outlook = new OutlookSync();
$slots = $outlook->getDailyAvailability($date);

echo json_encode($slots);
?>