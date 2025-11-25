<?php
require_once 'auth.php';
checkSession();
require_once 'graph_sync.php';

$error = '';
$success = '';

// Fetch last appointment for defaults
try {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $last_appointment = $stmt->fetch();

    $default_vehicle = $last_appointment['vehicle_type'] ?? '';
    $default_quantity = $last_appointment['quantity'] ?? '';
    $default_forklift = $last_appointment['needs_forklift'] ?? 0;
    $default_helper = $last_appointment['needs_helper'] ?? 0;
} catch (Exception $e) {
    // Fail silently for defaults
    $default_vehicle = '';
    $default_quantity = '';
    $default_forklift = 0;
    $default_helper = 0;
}

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
        
        // Lógica de duración dinámica
        if ($vehicle_type === 'Utilitario') {
            $block_minutes = 30;
            $real_minutes = 25;
        } else {
            $block_minutes = 60;
            $real_minutes = 55;
        }

        // Tiempo para verificar disponibilidad (bloque completo)
        $check_end_time = date('Y-m-d H:i:s', strtotime($start_time) + ($block_minutes * 60));
        
        // Tiempo real del turno (con buffer)
        $event_end_time = date('Y-m-d H:i:s', strtotime($start_time) + ($real_minutes * 60));

        $outlook = new OutlookSync();
        $isAvailable = $outlook->checkAvailability($start_time, $check_end_time);

        if ($isAvailable === false) {
            $error = "El horario seleccionado no está disponible en el calendario.";
        } else {
            try {
                $sql = "INSERT INTO appointments (user_id, start_time, end_time, vehicle_type, needs_forklift, needs_helper, quantity, observations) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $start_time, $event_end_time, $vehicle_type, $needs_forklift, $needs_helper, $quantity, $observations]);

                $appointment_id = $pdo->lastInsertId();

                $subject = "Turno: " . $_SESSION['company_name'];
                $description = "Vehículo: $vehicle_type<br>Bultos: $quantity<br>Autoelevador: " . ($needs_forklift ? 'SI' : 'NO') . "<br>Peón: " . ($needs_helper ? 'SI' : 'NO') . "<br>Obs: $observations";

                $event_id = $outlook->createEvent($subject, $start_time, $event_end_time, $description);

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
    <title>Reservar Turno - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .navbar {
            background-color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            color: #2c3e50 !important;
            font-weight: 700;
        }
        .main-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background: #ffffff;
            overflow: hidden;
        }
        .card-header-custom {
            background: #ffffff;
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .card-header-custom h2 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 1.75rem;
        }
        .card-header-custom p {
            color: #6c757d;
            margin-top: 0.5rem;
            margin-bottom: 0;
        }
        .form-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        .btn-time {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        .btn-primary-custom {
            background-color: #3182ce;
            border-color: #3182ce;
            border-radius: 10px;
            font-weight: 600;
            padding: 1rem;
            font-size: 1.1rem;
            transition: transform 0.1s;
        }
        .btn-primary-custom:hover {
            background-color: #2c5282;
            border-color: #2c5282;
            transform: translateY(-1px);
        }
        .option-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.2s;
        }
        .option-card:hover {
            border-color: #cbd5e0;
            background-color: #f8fafc;
        }
        .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #3182ce;
            border-color: #3182ce;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Peirano Logística</a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm me-2">Volver</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="main-card">
                    <div class="card-header-custom">
                        <h2>Reservar Turno</h2>
                        <p>Complete los datos para agendar su descarga.</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 bg-danger-subtle text-danger mb-4 rounded-3"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 bg-success-subtle text-success mb-4 rounded-3">
                                <h5 class="alert-heading fw-bold mb-2">¡Reserva Exitosa!</h5>
                                <p class="mb-0"><?php echo $success; ?></p>
                                <div class="mt-3">
                                    <a href="dashboard.php" class="btn btn-success btn-sm px-4 rounded-pill">Volver al Dashboard</a>
                                </div>
                            </div>
                        <?php else: ?>

                            <form method="POST">
                                <!-- Sección 1: Fecha y Hora -->
                                <h5 class="mb-4 text-primary fw-bold">1. Fecha y Hora</h5>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label for="date" class="form-label">Seleccione Fecha</label>
                                        <input type="date" class="form-control" id="date" name="date" required
                                            min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Horarios Disponibles</label>
                                        <input type="hidden" id="time" name="time" required>
                                        <div id="time-slots" class="d-flex flex-wrap gap-2">
                                            <div class="text-muted small">Seleccione una fecha primero.</div>
                                        </div>
                                        <div id="time-error" class="text-danger small mt-2" style="display:none;">Seleccione un horario.</div>
                                    </div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <!-- Sección 2: Detalles de Carga -->
                                <h5 class="mb-4 text-primary fw-bold">2. Detalles de Carga</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="vehicle_type" class="form-label">Tipo de Vehículo</label>
                                        <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                            <option value="">Seleccione...</option>
                                            <option value="Utilitario" <?php echo $default_vehicle === 'Utilitario' ? 'selected' : ''; ?>>Utilitario (Camioneta)</option>
                                            <option value="Chasis" <?php echo $default_vehicle === 'Chasis' ? 'selected' : ''; ?>>Chasis</option>
                                            <option value="Balancin" <?php echo $default_vehicle === 'Balancin' ? 'selected' : ''; ?>>Balancín</option>
                                            <option value="Semi_Acoplado" <?php echo $default_vehicle === 'Semi_Acoplado' ? 'selected' : ''; ?>>Semi / Acoplado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">Cantidad (Bultos/Pallets)</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required
                                            min="1" value="<?php echo htmlspecialchars($default_quantity); ?>">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-2">
                                        <div class="option-card d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold" for="needs_forklift">Autoelevador</label>
                                                <div class="text-muted small">¿Requiere máquina?</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="needs_forklift" name="needs_forklift" <?php echo $default_forklift ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="option-card d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold" for="needs_helper">Peón de Descarga</label>
                                                <div class="text-muted small">¿Necesita ayuda?</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="needs_helper" name="needs_helper" <?php echo $default_helper ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label for="observations" class="form-label">Observaciones Adicionales</label>
                                    <textarea class="form-control" id="observations" name="observations" rows="3"
                                        placeholder="Ej: Mercadería frágil, llegaré antes..."></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary-custom text-white shadow-sm">
                                        CONFIRMAR RESERVA
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('date').addEventListener('change', function() {
            const date = this.value;
            const slotsContainer = document.getElementById('time-slots');
            const timeInput = document.getElementById('time');
            
            // Reset
            slotsContainer.innerHTML = '<div class="spinner-border text-primary spinner-border-sm" role="status"></div><span class="ms-2 text-muted small">Buscando horarios...</span>';
            timeInput.value = '';

            fetch(`check_slots.php?date=${date}`)
                .then(response => response.json())
                .then(slots => {
                    slotsContainer.innerHTML = '';
                    
                    if (slots.error) {
                        slotsContainer.innerHTML = `<div class="text-danger small">${slots.error}</div>`;
                        return;
                    }

                    if (slots.length === 0) {
                        slotsContainer.innerHTML = '<div class="text-warning small">No hay horarios disponibles.</div>';
                        return;
                    }

                    slots.forEach(slot => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `btn btn-time ${slot.available ? 'btn-outline-primary' : 'btn-outline-secondary disabled'} flex-grow-1`;
                        btn.textContent = slot.time;
                        
                        if (!slot.available) {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                            btn.style.textDecoration = 'line-through';
                        } else {
                            btn.onclick = function() {
                                // Remove active class from all
                                document.querySelectorAll('#time-slots button').forEach(b => {
                                    if (!b.disabled) {
                                        b.classList.remove('btn-primary', 'text-white');
                                        b.classList.add('btn-outline-primary');
                                    }
                                });
                                // Add active class to clicked
                                this.classList.remove('btn-outline-primary');
                                this.classList.add('btn-primary', 'text-white');
                                
                                timeInput.value = slot.time;
                                document.getElementById('time-error').style.display = 'none';
                            };
                        }
                        
                        slotsContainer.appendChild(btn);
                    });
                })
                .catch(err => {
                    console.error(err);
                    slotsContainer.innerHTML = '<div class="text-danger small">Error de conexión.</div>';
                });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const timeInput = document.getElementById('time');
            if (!timeInput.value) {
                e.preventDefault();
                document.getElementById('time-error').style.display = 'block';
                // Scroll to error
                document.getElementById('time-error').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>