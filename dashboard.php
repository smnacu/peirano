<?php
// dashboard.php
require_once 'auth.php';
checkSession();

$user_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'];

$pdo = DB::connect();
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY start_time DESC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Peirano Logística</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3 text-light d-none d-md-block">Hola,
                    <strong><?php echo htmlspecialchars($company_name); ?></strong></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h3 class="fw-bold text-secondary">Mis Turnos</h3>
            </div>
            <div class="col-auto">
                <a href="reservar.php" class="btn btn-primary btn-lg shadow-sm fw-bold">+ Nuevo Turno</a>
            </div>
        </div>

        <?php if (count($appointments) > 0): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 ps-3">Fecha</th>
                                    <th class="py-3">Hora</th>
                                    <th class="py-3">Vehículo</th>
                                    <th class="py-3">Bultos</th>
                                    <th class="py-3 pe-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt): ?>
                                    <?php $date = new DateTime($appt['start_time']); ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?php echo $date->format('d/m/Y'); ?></td>
                                        <td><?php echo $date->format('H:i'); ?></td>
                                        <td><?php echo $appt['vehicle_type']; ?></td>
                                        <td><?php echo $appt['quantity']; ?></td>
                                        <td class="pe-3">
                                            <?php if ($appt['outlook_event_id']): ?>
                                                <span class="badge bg-success rounded-pill px-3">Confirmado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-3">Pendiente Sync</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info shadow-sm border-0">
                <h5 class="alert-heading">👋 ¡Bienvenido!</h5>
                <p class="mb-0">Aún no tienes turnos registrados. Presiona el botón <strong>+ Nuevo Turno</strong> para
                    comenzar.</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>