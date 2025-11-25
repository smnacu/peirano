<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

checkSession();

$pdo = DB::connect();
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, b.name as branch_name 
    FROM appointments a 
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.user_id = ? AND a.start_time >= ? 
    ORDER BY a.start_time ASC
");
$stmt->execute([$userId, $today]);
$appointments = $stmt->fetchAll();

$pageTitle = 'Mis Turnos - Peirano Logística';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mis Turnos</h2>
        <div class="d-flex gap-2">
            <?php if (in_array($_SESSION['role'], ['admin', 'operator'])): ?>
                <a href="admin_panel.php" class="btn btn-outline-info"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a>
            <?php endif; ?>
            <a href="reservar.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo Turno</a>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($appointments) > 0): ?>
            <?php foreach ($appointments as $appt): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span
                                    class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($appt['branch_name']); ?></span>
                                <span
                                    class="badge bg-dark border border-secondary"><?php echo date('d/m/Y', strtotime($appt['start_time'])); ?></span>
                            </div>
                            <h3 class="fw-bold text-info mb-1"><?php echo date('H:i', strtotime($appt['start_time'])); ?> hs
                            </h3>
                            <p class="text-muted small mb-3">Duración:
                                <?php echo (strtotime($appt['end_time']) - strtotime($appt['start_time'])) / 60; ?> min</p>

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-truck text-secondary"></i>
                                <span><?php echo $appt['vehicle_type']; ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam text-secondary"></i>
                                <span><?php echo $appt['quantity']; ?> bultos</span>
                            </div>

                            <?php if ($appt['driver_name']): ?>
                                <hr class="border-secondary my-3">
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($appt['driver_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="bi bi-calendar-x display-1"></i></div>
                <h4>No tenés turnos próximos</h4>
                <p class="text-muted">Reservá tu primer turno para comenzar.</p>
                <a href="reservar.php" class="btn btn-primary mt-3">Reservar Ahora</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>