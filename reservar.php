<?php
require_once 'auth.php';
checkSession();
require_once 'graph_sync.php';

$error = '';
$success = '';

// Cargar defaults del último turno para agilizar
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
    $default_vehicle = '';
    $default_quantity = '';
    $default_forklift = 0;
    $default_helper = 0;
}

// Fetch Branches
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $vehicle_type = $_POST['vehicle_type'];
    $quantity = $_POST['quantity'];
    $needs_forklift = isset($_POST['needs_forklift']) ? 1 : 0;
    $needs_helper = isset($_POST['needs_helper']) ? 1 : 0;
    $observations = $_POST['observations'];

    if (empty($branch_id) || empty($date) || empty($time) || empty($vehicle_type) || empty($quantity)) {
        $error = "Por favor complete todos los campos obligatorios.";
    } else {
        $start_time = $date . ' ' . $time . ':00';

        // Regla de negocio: Tiempos según vehículo o usuario
        // Si el usuario tiene un tiempo definido y es < 15 min, se usa ese.
        // Si no, se usa la lógica de vehículos.
        
        $user_duration = $_SESSION['default_duration'] ?? 60;
        
        if ($user_duration < 15) {
            $block_minutes = $user_duration; // Ej: 10 min
            $real_minutes = $user_duration;
        } else {
            // Lógica estándar
            if ($vehicle_type === 'Utilitario') {
                $block_minutes = 30;
                $real_minutes = 25;
            } else {
                $block_minutes = 60;
                $real_minutes = 55;
            }
            // Si el usuario tiene un tiempo MAYOR o igual a 15 asignado, ¿usamos ese?
            // El requerimiento dice: "tiempo predeterminado asignado a sus turnos (los que tarden menos de 15 min indicados podran tomar turno en cualquier horario...)"
            // Asumo que si es >= 15, se respeta el tiempo del vehículo o el asignado? 
            // Vamos a priorizar el tiempo asignado si es distinto del default (60).
            if ($user_duration != 60) {
                 $block_minutes = $user_duration;
                 $real_minutes = $user_duration - 5;
            }
        }

        $check_end_time = date('Y-m-d H:i:s', strtotime($start_time) + ($block_minutes * 60));
        $event_end_time = date('Y-m-d H:i:s', strtotime($start_time) + ($real_minutes * 60));

        $outlook = new OutlookSync();

        // Validar disponibilidad SOLO si dura >= 15 min.
        // Si dura < 15 min, se permite superposición (según requerimiento).
        $isAvailable = true;
        if ($block_minutes >= 15) {
            // Check Outlook (Global availability check might be too strict if we want per-branch, 
            // but Outlook is usually one calendar. We might need to filter by branch in Outlook? 
            // For now, assume simple availability check).
            $isAvailable = $outlook->checkAvailability($start_time, $check_end_time);
        }

        if ($isAvailable === false) {
            $error = "¡Ups! Ese horario ya no está disponible. Por favor elegí otro.";
        } else {
            try {
                // Guardar en BD Local
                $sql = "INSERT INTO appointments (user_id, branch_id, start_time, end_time, vehicle_type, needs_forklift, needs_helper, quantity, observations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $branch_id, $start_time, $event_end_time, $vehicle_type, $needs_forklift, $needs_helper, $quantity, $observations]);
                $appointment_id = $pdo->lastInsertId();

                // Crear evento
                $branchName = 'Sucursal'; // Buscar nombre
                foreach($branches as $b) { if($b['id'] == $branch_id) $branchName = $b['name']; }
                
                $subject = "Turno ($branchName): " . $_SESSION['company_name'];
                $description = "Prov: {$_SESSION['company_name']} | Vehículo: $vehicle_type | Bultos: $quantity | Sucursal: $branchName";

                $event_id = $outlook->createEvent($subject, $start_time, $event_end_time, $description);

                if ($event_id) {
                    $update = $pdo->prepare("UPDATE appointments SET outlook_event_id = ? WHERE id = ?");
                    $update->execute([$event_id, $appointment_id]);
                }

                $success = "¡Listo! Te esperamos el " . date('d/m', strtotime($date)) . " a las " . $time . "hs en $branchName.";
            } catch (PDOException $e) {
                $error = "Error técnico al guardar: " . $e->getMessage();
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
    <title>Reservar Turno - Peirano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #3b82f6; --bg-dark: #0f172a; --card-bg: #1e293b; --text-main: #f8fafc; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-dark); color: var(--text-main); }
        .wizard-container { max-width: 800px; margin: 0 auto; }
        .main-card { background: var(--card-bg); border: 1px solid #334155; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .step-dot { width: 40px; height: 40px; border-radius: 50%; background: var(--card-bg); border: 2px solid #334155; display: flex; align-items: center; justify-content: center; z-index: 10; transition: all 0.3s; }
        .step-dot.active { background: var(--primary-color); border-color: var(--primary-color); color: white; box-shadow: 0 0 15px rgba(59, 130, 246, 0.5); }
        .step-content { display: none; animation: fadeIn 0.4s ease; }
        .step-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .option-card { border: 1px solid #334155; padding: 1rem; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: rgba(15, 23, 42, 0.5); display: flex; align-items: center; justify-content: space-between; height: 100%; }
        .btn-check:checked+.option-card { background-color: rgba(59, 130, 246, 0.15); border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
        .option-card:has(.form-check-input:checked) { background-color: rgba(59, 130, 246, 0.15); border-color: var(--primary-color); }
        .btn-time { width: 100%; background: #0f172a; border: 1px solid #334155; color: #94a3b8; margin-bottom: 0.5rem; transition: all 0.2s; }
        .btn-time:hover:not(:disabled) { border-color: var(--primary-color); color: white; }
        .btn-time.active { background: var(--primary-color); border-color: var(--primary-color); color: white; }
        .btn-time:disabled { opacity: 0.4; text-decoration: line-through; cursor: not-allowed; }
        #loadingOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); z-index: 9999; backdrop-filter: blur(5px); }
        .loader-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
    </style>
</head>
<body>
    <div id="loadingOverlay">
        <div class="loader-content">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
            <h4 class="fw-bold">Confirmando Turno...</h4>
            <p class="text-muted">Aguarde un instante</p>
        </div>
    </div>

    <nav class="navbar navbar-dark bg-dark border-bottom border-secondary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-box-seam-fill me-2 text-primary"></i>Peirano Logística</a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Volver</a>
        </div>
    </nav>

    <div class="container py-4 wizard-container">
        <div class="position-relative mb-5 px-4">
            <div class="position-absolute top-50 start-0 w-100 border-top border-secondary z-0"></div>
            <div class="d-flex justify-content-between position-relative z-1">
                <div class="step-dot active" id="dot-1">1</div>
                <div class="step-dot" id="dot-2">2</div>
                <div class="step-dot" id="dot-3">3</div>
            </div>
        </div>

        <div class="main-card p-4 p-md-5">
            <?php if ($success): ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-circle-fill text-success display-1"></i>
                    <h2 class="mt-3 fw-bold">¡Turno Confirmado!</h2>
                    <p class="text-muted lead"><?php echo $success; ?></p>
                    <a href="dashboard.php" class="btn btn-primary btn-lg mt-4 px-5 rounded-pill">Ver Mis Turnos</a>
                </div>
            <?php else: ?>
                <form method="POST" id="bookingForm" onsubmit="showLoader()">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><div><?php echo $error; ?></div></div>
                    <?php endif; ?>

                    <div class="step-content active" id="step-1">
                        <h4 class="fw-bold mb-1">Detalles de Carga</h4>
                        <p class="text-muted mb-4">Seleccioná Sucursal y Carga</p>

                        <label class="form-label fw-bold text-uppercase small text-muted mb-2">Sucursal</label>
                        <div class="row g-3 mb-4">
                            <?php foreach ($branches as $branch): ?>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="branch_id" id="br_<?php echo $branch['id'] ?>" value="<?php echo $branch['id'] ?>" required>
                                    <label class="option-card" for="br_<?php echo $branch['id'] ?>">
                                        <span class="fw-medium"><?php echo htmlspecialchars($branch['name']) ?></span>
                                        <i class="bi bi-geo-alt text-primary fs-5"></i>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label class="form-label fw-bold text-uppercase small text-muted mb-2">Vehículo</label>
                        <div class="row g-3 mb-4">
                            <?php $opts = ['Utilitario' => 'Utilitario / Camioneta', 'Chasis' => 'Chasis', 'Balancin' => 'Balancín', 'Semi_Acoplado' => 'Semi / Acoplado'];
                            foreach ($opts as $val => $label): ?>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="vehicle_type" id="v_<?php echo $val ?>" value="<?php echo $val ?>" required <?php echo ($default_vehicle == $val) ? 'checked' : '' ?>>
                                    <label class="option-card" for="v_<?php echo $val ?>">
                                        <span class="fw-medium"><?php echo $label ?></span>
                                        <i class="bi bi-truck text-primary fs-5"></i>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label class="form-label fw-bold text-uppercase small text-muted mb-2">Logística</label>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-floating text-dark">
                                    <input type="number" class="form-control bg-dark text-light border-secondary" id="qty" name="quantity" required min="1" value="<?php echo $default_quantity ?>" placeholder="Cantidad">
                                    <label for="qty" class="text-muted">Cantidad de Bultos / Pallets</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="option-card" for="forklift">
                                    <span class="fw-medium">Necesito Autoelevador</span>
                                    <div class="form-check form-switch"><input class="form-check-input fs-5" type="checkbox" name="needs_forklift" id="forklift" <?php echo $default_forklift ? 'checked' : '' ?>></div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="option-card" for="helper">
                                    <span class="fw-medium">Necesito Peón</span>
                                    <div class="form-check form-switch"><input class="form-check-input fs-5" type="checkbox" name="needs_helper" id="helper" <?php echo $default_helper ? 'checked' : '' ?>></div>
                                </label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-5">
                            <button type="button" class="btn btn-primary px-4 py-2 fw-bold" onclick="nextStep(2)">Siguiente <i class="bi bi-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <div class="step-content" id="step-2">
                        <h4 class="fw-bold mb-1">Fecha y Hora</h4>
                        <p class="text-muted mb-4">Seleccioná un hueco disponible.</p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" class="form-control form-control-lg bg-dark text-light border-secondary" id="date" name="date" required min="<?php echo date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Horarios</label>
                                <input type="hidden" name="time" id="time" required>
                                <div id="time-slots" class="row g-2">
                                    <div class="text-muted small fst-italic p-2"><i class="bi bi-arrow-up-circle me-1"></i> Seleccioná una fecha para ver los horarios.</div>
                                </div>
                                <div id="time-error" class="text-danger small mt-2" style="display:none"><i class="bi bi-x-circle"></i> Seleccioná un horario.</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-5">
                            <button type="button" class="btn btn-outline-light px-4" onclick="prevStep(1)">Atrás</button>
                            <button type="button" class="btn btn-primary px-4 fw-bold" onclick="nextStep(3)">Siguiente <i class="bi bi-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <div class="step-content" id="step-3">
                        <h4 class="fw-bold mb-3">Resumen del Turno</h4>
                        <div class="card bg-dark border-secondary mb-4">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6 text-muted">🏢 Sucursal:</div>
                                    <div class="col-6 fw-bold text-end text-light" id="sum-branch">-</div>
                                    <div class="col-6 text-muted">📅 Fecha:</div>
                                    <div class="col-6 fw-bold text-end text-light" id="sum-date">-</div>
                                    <div class="col-6 text-muted">⏰ Hora:</div>
                                    <div class="col-6 fw-bold text-end text-info" id="sum-time">-</div>
                                    <div class="col-12 border-top border-secondary my-1"></div>
                                    <div class="col-6 text-muted">🚛 Vehículo:</div>
                                    <div class="col-6 fw-bold text-end text-light" id="sum-veh">-</div>
                                    <div class="col-6 text-muted">📦 Bultos:</div>
                                    <div class="col-6 fw-bold text-end text-light" id="sum-qty">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones (Opcional)</label>
                            <textarea class="form-control bg-dark text-light border-secondary" name="observations" rows="2" placeholder="Ej: Necesito entrar marcha atrás..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between mt-5">
                            <button type="button" class="btn btn-outline-light px-4" onclick="prevStep(2)">Atrás</button>
                            <button type="submit" class="btn btn-success w-50 py-2 fw-bold shadow-lg">CONFIRMAR <i class="bi bi-check-lg ms-2"></i></button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoader() { document.getElementById('loadingOverlay').style.display = 'block'; }
        function nextStep(step) {
            if (step === 2) {
                const branch = document.querySelector('input[name="branch_id"]:checked');
                const vehicle = document.querySelector('input[name="vehicle_type"]:checked');
                const qty = document.getElementById('qty').value;
                if (!branch) return alert('⚠️ Seleccioná una sucursal.');
                if (!vehicle) return alert('⚠️ Seleccioná un tipo de vehículo.');
                if (!qty || qty < 1) return alert('⚠️ Ingresá una cantidad válida de bultos.');
            }
            if (step === 3) {
                const d = document.getElementById('date').value;
                const t = document.getElementById('time').value;
                if (!d || !t) return alert('⚠️ Tenés que elegir fecha y hora para seguir.');
                document.getElementById('sum-branch').innerText = document.querySelector('input[name="branch_id"]:checked').nextElementSibling.innerText.trim();
                document.getElementById('sum-date').innerText = d.split('-').reverse().join('/');
                document.getElementById('sum-time').innerText = t + ' hs';
                document.getElementById('sum-veh').innerText = document.querySelector('input[name="vehicle_type"]:checked').nextElementSibling.innerText.trim();
                document.getElementById('sum-qty').innerText = document.getElementById('qty').value;
            }
            document.querySelectorAll('.step-content').forEach(e => e.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
            for (let i = 1; i <= 3; i++) {
                const dot = document.getElementById('dot-' + i);
                if (i <= step) dot.classList.add('active');
                else dot.classList.remove('active');
            }
        }
        function prevStep(step) { nextStep(step); }
        document.getElementById('date').addEventListener('change', function () {
            const date = this.value;
            const branch = document.querySelector('input[name="branch_id"]:checked').value;
            const container = document.getElementById('time-slots');
            container.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> Buscando huecos...</div>';
            fetch(`check_slots.php?date=${date}&branch_id=${branch}`)
                .then(r => r.json())
                .then(slots => {
                    container.innerHTML = '';
                    if (slots.length === 0 || slots.error) {
                        container.innerHTML = '<div class="col-12 text-danger text-center">Sin disponibilidad.</div>';
                        return;
                    }
                    slots.forEach(slot => {
                        const div = document.createElement('div');
                        div.className = 'col-3 col-md-2';
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `btn btn-time ${slot.available ? '' : 'disabled'}`;
                        btn.innerText = slot.time;
                        if (!slot.available) {
                            btn.disabled = true;
                        } else {
                            btn.onclick = () => {
                                document.querySelectorAll('.btn-time').forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                                document.getElementById('time').value = slot.time;
                                document.getElementById('time-error').style.display = 'none';
                            }
                        }
                        div.appendChild(btn);
                        container.appendChild(div);
                    });
                })
                .catch(e => { console.error(e); container.innerHTML = '<div class="col-12 text-danger text-center">Error de conexión.</div>'; });
        });
    </script>
</body>
</html>