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
$sql = "INSERT INTO appointments (user_id, start_time, end_time, vehicle_type, needs_forklift, needs_helper, quantity,
observations) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $start_time, $event_end_time, $vehicle_type, $needs_forklift, $needs_helper,
$quantity, $observations]);

$appointment_id = $pdo->lastInsertId();

$subject = "Turno: " . $_SESSION['company_name'];
$description = "Vehículo: $vehicle_type<br>Bultos: $quantity<br>Autoelevador: " . ($needs_forklift ? 'SI' : 'NO') .
"<br>Peón: " . ($needs_helper ? 'SI' : 'NO') . "<br>Obs: $observations";

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

            <script> // Wizard Navigation

            function nextStep(step) {

                // Validation Step 1
                if (step===2) {
                    const vehicle=document.querySelector('input[name="vehicle_type"]:checked');
                    const qty=document.getElementById('quantity').value;

                    if ( !vehicle || !qty) {
                        alert('Por favor complete el tipo de vehículo y la cantidad.');
                        return;
                    }
                }

                // Validation Step 2
                if (step===3) {
                    const date=document.getElementById('date').value;
                    const time=document.getElementById('time').value;

                    if ( !date || !time) {
                        alert('Por favor seleccione fecha y hora.');
                        return;
                    }

                    // Update Summary
                    document.getElementById('summary-date').textContent=date;
                    document.getElementById('summary-time').textContent=time;
                    document.getElementById('summary-vehicle').textContent=document.querySelector('input[name="vehicle_type"]:checked').value;
                    document.getElementById('summary-qty').textContent=document.getElementById('quantity').value;
                }

                // Hide all
                document.querySelectorAll('.step-content').forEach(el=> el.classList.remove('active'));
                document.querySelectorAll('.step-dot').forEach(el=> el.classList.remove('active'));

                // Show current
                document.getElementById(`step-$ {
                        step
                    }

                    `).classList.add('active');

                // Update dots
                for (let i=1; i <=step; i++) {
                    const dot=document.getElementById(`dot-$ {
                            i
                        }

                        `);
                    if (i===step) dot.classList.add('active');
                    else dot.classList.add('completed');
                }
            }

            function prevStep(step) {
                document.querySelectorAll('.step-content').forEach(el=> el.classList.remove('active'));

                document.getElementById(`step-$ {
                        step
                    }

                    `).classList.add('active');

                // Reset dots forward
                for (let i=step + 1; i <=3; i++) {
                    document.getElementById(`dot-$ {
                            i
                        }

                        `).classList.remove('active', 'completed');
                }

                document.getElementById(`dot-$ {
                        step
                    }

                    `).classList.add('active');

                document.getElementById(`dot-$ {
                        step
                    }

                    `).classList.remove('completed');
            }

            // Time Slot Logic
            document.getElementById('date').addEventListener('change', function () {
                    const date=this.value;
                    const slotsContainer=document.getElementById('time-slots');
                    const timeInput=document.getElementById('time');

                    slotsContainer.innerHTML='<div class="spinner-border text-primary spinner-border-sm" role="status"></div>';
                    timeInput.value='';

                    fetch(`check_slots.php?date=$ {
                            date
                        }

                        `) .then(response=> response.json()) .then(slots=> {
                            slotsContainer.innerHTML='';

                            if (slots.error) {
                                slotsContainer.innerHTML=`<div class="text-danger small grid-column-span-all" >$ {
                                    slots.error
                                }

                                </div>`;
                                return;
                            }

                            if (slots.length===0) {
                                slotsContainer.innerHTML='<div class="text-warning small grid-column-span-all">No hay horarios disponibles.</div>';
                                return;
                            }

                            slots.forEach(slot=> {
                                    const btn=document.createElement('button');
                                    btn.type='button';

                                    btn.className=`btn btn-time $ {
                                        slot.available ? '' : 'disabled'
                                    }

                                    `;
                                    btn.textContent=slot.time;

                                    if ( !slot.available) {
                                        btn.disabled=true;
                                        btn.style.opacity='0.3';
                                        btn.style.textDecoration='line-through';
                                    }

                                    else {
                                        btn.onclick=function () {
                                            document.querySelectorAll('#time-slots button').forEach(b=> b.classList.remove('active'));
                                            this.classList.add('active');
                                            timeInput.value=slot.time;
                                            document.getElementById('time-error').style.display='none';
                                        }

                                        ;
                                    }

                                    slotsContainer.appendChild(btn);
                                });

                        }) .catch(err=> {
                            console.error(err);
                            slotsContainer.innerHTML='<div class="text-danger small">Error de conexión.</div>';
                        });
                });
            </script></body></html>