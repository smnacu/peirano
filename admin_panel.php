<?php
// admin_panel.php
require_once 'auth.php';
// checkSession(); // Idealmente, verificar que sea admin. Por ahora, acceso logueado.

$date = $_GET['date'] ?? date('Y-m-d');

$pdo = DB::connect();
$sql = "SELECT a.*, u.company_name, u.cuit 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        WHERE DATE(a.start_time) = ? 
        ORDER BY a.start_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$date]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
        }

        .table-dark {
            --bs-table-bg: #1e293b;
            --bs-table-border-color: #334155;
        }

        .card {
            background-color: #1e293b;
            border-color: #334155;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-shield-lock me-2"></i>Panel de Control</h2>
            <form class="d-flex gap-2">
                <input type="date" name="date" class="form-control bg-dark text-white border-secondary"
                    value="<?php echo $date; ?>">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </div>

        <div class="card shadow-lg">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="py-3 ps-3">Hora</th>
                                <th class="py-3">Empresa</th>
                                <th class="py-3">Vehículo</th>
                                <th class="py-3">Carga</th>
                                <th class="py-3 text-center">Autoelevador</th>
                                <th class="py-3 text-center">Peón</th>
                                <th class="py-3">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($appointments) > 0): ?>
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-info">
                                            <?php echo date('H:i', strtotime($appt['start_time'])); ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($appt['company_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($appt['cuit']); ?></div>
                                        </td>
                                        <td><?php echo $appt['vehicle_type']; ?></td>
                                        <td><?php echo $appt['quantity']; ?> bultos</td>
                                        <td class="text-center">
                                            <?php if ($appt['needs_forklift']): ?>
                                                <span class="badge bg-warning text-dark">SI</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($appt['needs_helper']): ?>
                                                <span class="badge bg-info text-dark">SI</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted fst-italic">
                                            <?php echo htmlspecialchars($appt['observations'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No hay turnos para esta fecha.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>