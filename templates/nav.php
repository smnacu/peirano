<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>dashboard.php"><i
                class="bi bi-box-seam-fill me-2 text-primary"></i>Peirano Logística</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <?php echo htmlspecialchars($_SESSION['company_name']); ?>
                    <?php if (isset($_SESSION['role']))
                        echo '(' . ucfirst($_SESSION['role']) . ')'; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
            </div>
        <?php endif; ?>
    </div>
</nav>