<?php
require_once 'auth.php';
require_once 'debug_mode.php'; // Temporary debug
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
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Turno - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
        }

        .navbar {
            background-color: rgba(30, 41, 59, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
        }

        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step-indicator::before {
            content: '';

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
            < !DOCTYPE html><html lang="es" data-bs-theme="dark"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reservar Turno - Peirano Logística</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"><style> :root {
                --primary-color: #3b82f6;
                --primary-hover: #2563eb;
                --bg-dark: #0f172a;
                --card-bg: #1e293b;
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --border-color: #334155;
            }

            body {
                font-family: 'Outfit', sans-serif;
                background-color: var(--bg-dark);
                color: var(--text-main);
                min-height: 100vh;
            }

            .navbar {
                background-color: rgba(30, 41, 59, 0.8) !important;
                backdrop-filter: blur(10px);
                border-bottom: 1px solid var(--border-color);
            }

            .wizard-container {
                max-width: 800px;
                margin: 0 auto;
            }

            .step-indicator {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2rem;
                position: relative;
            }

            .step-indicator::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                height: 2px;
                background: var(--border-color);
                z-index: 0;
                transform: translateY(-50%);
            }

            .step-dot {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: var(--card-bg);
                border: 2px solid var(--border-color);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1;
                font-weight: 600;
                color: var(--text-muted);
                transition: all 0.3s ease;
            }

            .step-dot.active {
                border-color: var(--primary-color);
                background: var(--primary-color);
                color: white;
                box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
            }

            .step-dot.completed {
                border-color: var(--primary-color);
                background: var(--card-bg);
                color: var(--primary-color);
            }

            .main-card {
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 20px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                overflow: hidden;
            }

            .form-control,
            .form-select {
                background-color: #0f172a;
                border-color: var(--border-color);
                color: white;
                padding: 0.8rem 1rem;
                border-radius: 10px;
            }

            .form-control:focus,
            .form-select:focus {
                background-color: #0f172a;
                border-color: var(--primary-color);
                color: white;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            }

            .btn-primary-custom {
                background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
                border: none;
                padding: 1rem 2rem;
                border-radius: 12px;
                font-weight: 600;
                letter-spacing: 0.5px;
                transition: all 0.3s;
            }

            .btn-primary-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px -10px rgba(59, 130, 246, 0.5);
            }

            .btn-time {
                background-color: #0f172a;
                border: 1px solid var(--border-color);
                color: var(--text-muted);
                border-radius: 10px;
                padding: 0.75rem;
                transition: all 0.2s;
            }

            .btn-time:hover:not(:disabled) {
                border-color: var(--primary-color);
                color: white;
                background-color: rgba(59, 130, 246, 0.1);
            }

            .btn-time.active {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                color: white;
            }

            .option-card {
                background-color: #0f172a;
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 1.25rem;
                transition: all 0.2s;
                cursor: pointer;
            }

            .option-card:hover {
                border-color: var(--primary-color);
                background-color: rgba(59, 130, 246, 0.05);
            }

            .option-card.selected {
                border-color: var(--primary-color);
                background-color: rgba(59, 130, 246, 0.1);
            }

            /* Hide steps initially */
            .step-content {
                display: none;
                animation: fadeIn 0.4s ease;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Loading Overlay */
            #loadingOverlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 23, 42, 0.9);
                z-index: 9999;
                display: none;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(5px);
            }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <h4 class="mt-4 fw-bold text-white">Procesando Reserva...</h4>
        <p class="text-muted">Conectando con Base de Datos</p>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <img src="assets/logo.png" alt="Peirano Logo" style="height: 30px;" class="me-2">
                Peirano Logística
            </a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2 rounded-pill px-3">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container py-5 wizard-container">

        <!-- Step Indicators -->
        <div class="step-indicator px-5">
            <div class="step-dot active" id="dot-1">1</div>
            <div class="step-dot" id="dot-2">2</div>
            <div class="step-dot" id="dot-3">3</div>
        </div>

        <div class="main-card">
            <div class="card-body p-4 p-md-5">

                <?php if ($success): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3">¡Reserva Confirmada!</h2>
                        <p class="text-muted mb-4"><?php echo $success; ?></p>
                        <a href="dashboard.php" class="btn btn-primary-custom text-white">Ir al Dashboard</a>
                    </div>
                <?php else: ?>

                    <form method="POST" id="bookingForm">
                        <?php if ($error): ?>
                            <div class="alert alert-danger bg-danger-subtle border-0 text-danger mb-4 rounded-3">
                                <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- STEP 1: Details -->
                        <div class="step-content active" id="step-1">
                            <h3 class="fw-bold mb-1">Detalles de Carga</h3>
                            <p class="text-muted mb-4">¿Qué vas a descargar hoy?</p>

                            <div class="mb-4">
                                <label class="form-label text-muted small text-uppercase fw-bold">Tipo de Vehículo</label>
                                <div class="row g-3">
                                    <?php
                                    $vehicles = [
                                        'Utilitario' => 'Utilitario (Camioneta)',
                                        'Chasis' => 'Chasis',
                                        'Balancin' => 'Balancín',
                                        'Semi_Acoplado' => 'Semi / Acoplado'
                                    ];
                                    foreach ($vehicles as $val => $label):
                                        $selected = ($default_vehicle === $val) ? 'checked' : '';
                                        ?>
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="vehicle_type" id="v_<?php echo $val; ?>"
                                                value="<?php echo $val; ?>" <?php echo $selected; ?> required>
                                            <label class="option-card w-100 d-flex align-items-center justify-content-between"
                                                for="v_<?php echo $val; ?>">
                                                <span class="fw-medium"><?php echo $label; ?></span>
                                                <i class="bi bi-truck text-primary"></i>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="quantity" class="form-label text-muted small text-uppercase fw-bold">Cantidad
                                    (Bultos/Pallets)</label>
                                <input type="number" class="form-control form-control-lg" id="quantity" name="quantity"
                                    required min="1" value="<?php echo htmlspecialchars($default_quantity); ?>">
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="option-card d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold">Autoelevador</div>
                                            <div class="text-muted small">¿Requiere máquina?</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="needs_forklift"
                                                name="needs_forklift" <?php echo $default_forklift ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="option-card d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold">Peón de Descarga</div>
                                            <div class="text-muted small">¿Necesita ayuda?</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="needs_helper"
                                                name="needs_helper" <?php echo $default_helper ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-5">
                                <button type="button" class="btn btn-primary-custom text-white" onclick="nextStep(2)">
                                    Siguiente <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: Date & Time -->
                        <div class="step-content" id="step-2">
                            <h3 class="fw-bold mb-1">Fecha y Hora</h3>
                            <p class="text-muted mb-4">Selecciona un turno disponible.</p>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="date"
                                        class="form-label text-muted small text-uppercase fw-bold">Fecha</label>
                                    <input type="date" class="form-control form-control-lg" id="date" name="date" required
                                        min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label text-muted small text-uppercase fw-bold">Horarios</label>
                                    <input type="hidden" id="time" name="time" required>
                                    <div id="time-slots" class="d-grid gap-2"
                                        style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));">
                                        <div class="text-muted small grid-column-span-all">Selecciona una fecha...</div>
                                    </div>
                                    <div id="time-error" class="text-danger small mt-2" style="display:none;">Selecciona un
                                        horario.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-outline-light px-4 rounded-pill"
                                    onclick="prevStep(1)">Atrás</button>
                                <button type="button" class="btn btn-primary-custom text-white" onclick="nextStep(3)">
                                    Siguiente <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: Confirm -->
                        <div class="step-content" id="step-3">
                            <h3 class="fw-bold mb-1">Confirmar Reserva</h3>
                            <p class="text-muted mb-4">Revisa los datos antes de confirmar.</p>

                            <div class="bg-dark bg-opacity-50 p-4 rounded-3 mb-4 border border-secondary">
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Fecha:</div>
                                    <div class="col-6 fw-bold text-end" id="summary-date">-</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Hora:</div>
                                    <div class="col-6 fw-bold text-end" id="summary-time">-</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Vehículo:</div>
                                    <div class="col-6 fw-bold text-end" id="summary-vehicle">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-6 text-muted">Bultos:</div>
                                    <div class="col-6 fw-bold text-end" id="summary-qty">-</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="observations"
                                    class="form-label text-muted small text-uppercase fw-bold">Observaciones
                                    (Opcional)</label>
                                <textarea class="form-control" id="observations" name="observations" rows="3"
                                    placeholder="Ej: Mercadería frágil..."></textarea>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-outline-light px-4 rounded-pill"
                                    onclick="prevStep(2)">Atrás</button>
                                <button type="submit" class="btn btn-primary-custom text-white w-50">
                                    CONFIRMAR <i class="bi bi-check-lg ms-2"></i>
                                </button>
                            </div>
                        </div>

                    </form>
                    <script>
                        document.getElementById('bookingForm').addEventListener('submit', function () {
                            document.getElementById('loadingOverlay').style.display = 'flex';
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wizard Navigation
        function nextStep(step) {
            // Validation Step 1
            if (step === 2) {
                const vehicle = document.querySelector('input[name="vehicle_type"]:checked');
                const qty = document.getElementById('quantity').value;
                if (!vehicle || !qty) {
                    alert('Por favor complete el tipo de vehículo y la cantidad.');
                    return;
                }
            }
            // Validation Step 2
            if (step === 3) {
                const date = document.getElementById('date').value;
                const time = document.getElementById('time').value;
                if (!date || !time) {
                    alert('Por favor seleccione fecha y hora.');
                    return;
                }
                // Update Summary
                document.getElementById('summary-date').textContent = date;
                document.getElementById('summary-time').textContent = time;
                document.getElementById('summary-vehicle').textContent = document.querySelector('input[name="vehicle_type"]:checked').value;
                document.getElementById('summary-qty').textContent = document.getElementById('quantity').value;
            }

            // Hide all
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-dot').forEach(el => el.classList.remove('active'));

            // Show current
            document.getElementById(`step-${step}`).classList.add('active');

            // Update dots
            for (let i = 1; i <= step; i++) {
                const dot = document.getElementById(`dot-${i}`);
                if (i === step) dot.classList.add('active');
                else dot.classList.add('completed');
            }
        }

        function prevStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-${step}`).classList.add('active');

            // Reset dots forward
            for (let i = step + 1; i <= 3; i++) {
                document.getElementById(`dot-${i}`).classList.remove('active', 'completed');
            }
            document.getElementById(`dot-${step}`).classList.add('active');
            document.getElementById(`dot-${step}`).classList.remove('completed');
        }

        // Time Slot Logic
        document.getElementById('date').addEventListener('change', function () {
            const date = this.value;
            const slotsContainer = document.getElementById('time-slots');
            const timeInput = document.getElementById('time');

            slotsContainer.innerHTML = '<div class="spinner-border text-primary spinner-border-sm" role="status"></div>';
            timeInput.value = '';

            fetch(`check_slots.php?date=${date}`)
                .then(response => response.json())
                .then(slots => {
                    slotsContainer.innerHTML = '';

                    if (slots.error) {
                        slotsContainer.innerHTML = `<div class="text-danger small grid-column-span-all">${slots.error}</div>`;
                        return;
                    }

                    if (slots.length === 0) {
                        slotsContainer.innerHTML = '<div class="text-warning small grid-column-span-all">No hay horarios disponibles.</div>';
                        return;
                    }

                    slots.forEach(slot => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `btn btn-time ${slot.available ? '' : 'disabled'}`;
                        btn.textContent = slot.time;

                        if (!slot.available) {
                            btn.disabled = true;
                            btn.style.opacity = '0.3';
                            btn.style.textDecoration = 'line-through';
                        } else {
                            btn.onclick = function () {
                                document.querySelectorAll('#time-slots button').forEach(b => b.classList.remove('active'));
                                this.classList.add('active');
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
    </script>
</body>

</html>