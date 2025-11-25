<?php
require_once 'db.php';

class OutlookSync
{
    private $pdo;

    public function __construct()
    {
        // Conectar a la base de datos local
        $this->pdo = DB::connect();
    }

    // Método simulado para mantener compatibilidad
    // Ya no conecta a Microsoft, pero devuelve un ID local
    public function createEvent($subject, $startTime, $endTime, $description)
    {
        // Como reservar.php ya guardó el turno en la BD antes de llamar a esto,
        // simplemente devolvemos un ID de confirmación local.
        return "LOCAL_DB_" . uniqid();
    }

    // Verifica disponibilidad consultando la tabla 'appointments'
    public function checkAvailability($startTime, $endTime, $branchId = null)
    {
        // Lógica de solapamiento:
        // (StartExisting < EndNew) AND (EndExisting > StartNew)
        // Y coincidir en branch_id (si se provee)

        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE start_time < ? AND end_time > ?";

        $params = [$endTime, $startTime];

        if ($branchId) {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return false;
        }

        return true;
    }

    // Genera la grilla de disponibilidad diaria basada en la BD local
    public function getDailyAvailability($date, $branchId = null)
    {
        // 1. Obtener todos los turnos de ese día y sucursal
        $sql = "SELECT start_time, end_time FROM appointments 
                WHERE DATE(start_time) = ?";
        $params = [$date];

        if ($branchId) {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        // 2. Generar slots de 07:00 a 18:00
        $slots = [];
        for ($hour = 7; $hour <= 18; $hour++) {
            $time = sprintf('%02d:00', $hour);

            // Definir el rango del slot (1 hora)
            $slotStartStr = "$date $time";
            $slotEndStr = date('Y-m-d H:i:s', strtotime($slotStartStr) + 3600);

            $slotStartTs = strtotime($slotStartStr);
            $slotEndTs = strtotime($slotEndStr);

            $isBusy = false;

            // 3. Verificar colisiones con turnos existentes
            foreach ($appointments as $appt) {
                $apptStartTs = strtotime($appt['start_time']);
                $apptEndTs = strtotime($appt['end_time']);

                if ($apptStartTs < $slotEndTs && $apptEndTs > $slotStartTs) {
                    $isBusy = true;
                    break;
                }
            }

            $slots[] = [
                'time' => $time,
                'available' => !$isBusy
            ];
        }

        return $slots;
    }
}
?>