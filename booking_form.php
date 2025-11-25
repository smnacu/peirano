<?php
require_once 'auth.php';
checkSession();
require_once 'graph_sync.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $vehicle_type = $_POST['vehicle_type'];
    $quantity = $_POST['quantity'];
    $needs_forklift = isset($_POST['needs_forklift']) ? 1 : 0;
    $needs_helper = isset($_POST['needs_helper']) ? 1 : 0;
    $observations = $_POST['observations'];

    if (empty($date) || empty($time) || empty($vehicle_type) || empty($quantity)) {
        $error = "Por favor complete todos los campos obligatorios.";
    } else {
        $start_time = $date . ' ' . $time . ':00';
        $end_time = date('Y-m-d H:i:s', strtotime($start_time) + 3600);

        $outlook = new OutlookSync();
        $isAvailable = $outlook->checkAvailability($start_time, $end_time);

        if ($isAvailable === false) {
            $error = "El horario seleccionado no está disponible en el calendario.";
        } else {
            try {
                $sql = "INSERT INTO appointments (user_id, start_time, end_time, vehicle_type, needs_forklift, needs_helper, quantity, observations) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $start_time, $end_time, $vehicle_type, $needs_forklift, $needs_helper, $quantity, $observations]);

                $appointment_id = $pdo->lastInsertId();

                $subject = "Turno: " . $_SESSION['company_name'];
                $description = "Vehículo: $vehicle_type<br>Bultos: $quantity<br>Autoelevador: " . ($needs_forklift ? 'SI' : 'NO') . "<br>Peón: " . ($needs_helper ? 'SI' : 'NO') . "<br>Obs: $observations";

                $event_id = $outlook->createEvent($subject, $start_time, $end_time, $description);

                if ($event_id) {
                    $update = $pdo->prepare("UPDATE appointments SET outlook_event_id = ? WHERE id = ?");
                    $update->execute([$event_id, $appointment_id]);
                }

                $success = "Turno reservado con éxito.";
            } catch (PDOException $e) {
                $error = "Error al guardar el turno: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Turno - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Peirano Logística</a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Volver</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4" style="max-width: 800px;">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Reservar Turno de Descarga</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="dashboard.php" class="btn btn-success btn-sm">Volver al Dashboard</a>
                        </div>
                    </div>
                <?php else: ?>

                    <form method="POST">
                        <option value="Utilitario">Utilitario (Camioneta)</option>
                        <option value="Chasis">Chasis</option>
                        <option value="Balancin">Balancín</option>
                        <option value="Semi_Acoplado">Semi / Acoplado</option>
                        </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label fw-bold">Cantidad de Bultos / Pallets</label>
                    <input type="number" class="form-control form-control-lg" id="quantity" name="quantity" required
                        min="1">
                </div>

                <div class="mb-3 p-3 border rounded bg-light">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="needs_forklift" name="needs_forklift"
                            style="width: 3em; height: 1.5em;">
                        <label class="form-check-label ms-2 pt-1" for="needs_forklift">¿Requiere
                            Autoelevador?</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="needs_helper" name="needs_helper"
                            style="width: 3em; height: 1.5em;">
                        <label class="form-check-label ms-2 pt-1" for="needs_helper">¿Requiere Peón de
                            descarga?</label>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="observations" class="form-label fw-bold">Observaciones / Detalle de Carga</label>
                    <textarea class="form-control form-control-lg" id="observations" name="observations" rows="3"
                        placeholder="Ej: Traigo mercadería frágil..."></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold">CONFIRMAR RESERVA</button>
                </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('date').addEventListener('change', function () {
            const date = this.value;
            const slotsContainer = document.getElementById('time-slots');
            const timeInput = document.getElementById('time');

            // Reset
            slotsContainer.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
            timeInput.value = '';

            fetch(`check_slots.php?date=${date}`)
                .then(response => response.json())
                .then(slots => {
                    slotsContainer.innerHTML = '';

                    if (slots.error) {
                        slotsContainer.innerHTML = `<div class="text-danger">${slots.error}</div>`;
                        return;
                    }

                    if (slots.length === 0) {
                        slotsContainer.innerHTML = '<div class="text-warning">No hay horarios disponibles para esta fecha.</div>';
                        return;
                    }

                    slots.forEach(slot => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `btn ${slot.available ? 'btn-outline-success' : 'btn-outline-danger disabled'} flex-grow-1`;
                        btn.textContent = slot.time;

                        if (!slot.available) {
                            btn.disabled = true;
                            btn.title = 'Ocupado';
                        } else {
                            btn.onclick = function () {
                                // Remove active class from all
                                document.querySelectorAll('#time-slots button').forEach(b => {
                                    if (!b.disabled) {
                                        b.classList.remove('btn-success', 'text-white');
                                        b.classList.add('btn-outline-success');
                                    }
                                });
                                // Add active class to clicked
                                this.classList.remove('btn-outline-success');
                                this.classList.add('btn-success', 'text-white');

                                timeInput.value = slot.time;
                                document.getElementById('time-error').style.display = 'none';
                            };
                        }

                        slotsContainer.appendChild(btn);
                    });
                })
                .catch(err => {
                    console.error(err);
                    slotsContainer.innerHTML = '<div class="text-danger">Error al cargar horarios. Intente nuevamente.</div>';
                });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const timeInput = document.getElementById('time');
            if (!timeInput.value) {
                e.preventDefault();
                document.getElementById('time-error').style.display = 'block';
            }
        });
    </script>
</body>

</html>