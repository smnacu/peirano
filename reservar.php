<?php
// reservar.php
require_once 'auth.php';
require_once 'graph.php';
checkSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $vehicle_type = $_POST['vehicle_type'];
    $quantity = $_POST['quantity'];
    $needs_forklift = isset($_POST['needs_forklift']) ? 1 : 0;
    $observations = $_POST['observations'];

    if (empty($date) || empty($time) || empty($vehicle_type) || empty($quantity)) {
        $error = "Todos los campos obligatorios deben completarse.";
    } else {
        $start_time = $date . ' ' . $time . ':00';
        $end_time = date('Y-m-d H:i:s', strtotime($start_time) + 3600); // 1 hora por defecto

        $pdo = DB::connect();

        try {
            // Guardar en MySQL
            $sql = "INSERT INTO appointments (user_id, start_time, end_time, vehicle_type, needs_forklift, quantity, observations) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $start_time, $end_time, $vehicle_type, $needs_forklift, $quantity, $observations]);
            $appointment_id = $pdo->lastInsertId();

            // Sincronizar con Graph
            $graph = new GraphHandler();
            $appointmentData = [
                'company_name' => $_SESSION['company_name'],
                'cuit' => $_SESSION['cuit'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'vehicle_type' => $vehicle_type,
                'quantity' => $quantity,
                'needs_forklift' => $needs_forklift,
                'observations' => $observations
            ];

            $event_id = $graph->createEvent($appointmentData);

            if ($event_id) {
                $update = $pdo->prepare("UPDATE appointments SET outlook_event_id = ? WHERE id = ?");
                $update->execute([$event_id, $appointment_id]);
                $success = "Turno reservado y sincronizado con éxito.";
            } else {
                $success = "Turno guardado localmente, pero hubo un error conectando con Outlook.";
            }

        } catch (Exception $e) {
            $error = "Error al procesar la reserva: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Turno - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Peirano Logística</a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Volver</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4" style="max-width: 800px;">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0 fw-bold">📅 Nueva Reserva de Descarga</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success shadow-sm">
                        <h4 class="alert-heading">¡Reserva Exitosa!</h4>
                        <p><?php echo $success; ?></p>
                        <hr>
                        <div class="mt-2"><a href="dashboard.php" class="btn btn-success">Volver al Dashboard</a></div>
                    </div>
                <?php else: ?>
                <form method="POST">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Fecha de Descarga</label>
                            <input type="date" class="form-control form-control-lg" name="date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hora Estimada</label>
                            <input type="time" class="form-control form-control-lg" name="time" required min="07:00" max="18:00">
                            <div class="form-text">Horario de recepción: 07:00 a 18:00</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Tipo de Vehículo</label>
                        <select class="form-select form-select-lg" name="vehicle_type" required>
                            <option value="">Seleccione tipo de camión...</option>
                            <option value="Chasis">Chasis</option>
                            <option value="Balancin">Balancín</option>
                            <option value="Semi">Semi / Acoplado</option>
                            <option value="Utilitario">Utilitario / Camioneta</option>
                        </select>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Cantidad Pallets / Bultos</label>
                            <input type="number" class="form-control form-control-lg" name="quantity" required min="1" placeholder="Ej: 12">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch p-3 border rounded bg-light w-100">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="needs_forklift" id="forklift" style="width: 3em; height: 1.5em; float: none;">
                                <label class="form-check-label fw-bold pt-1" for="forklift">¿Necesita Autoelevador?</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Observaciones / Detalle de Carga</label>
                        <textarea class="form-control form-control-lg" name="observations" rows="3" placeholder="Ej: Mercadería frágil, necesito personal de descarga..."></textarea>
                    </div>

                    <div class="d-grid pt-2">
                        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shadow-sm">CONFIRMAR RESERVA</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>