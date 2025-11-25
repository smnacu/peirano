<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom border-secondary-subtle mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>dashboard.php">
            <img src="https://griferiapeirano.com/wp-content/uploads/2023/09/logo-griferia-peirano.png" alt="Peirano" height="40">
            <span class="ms-2 d-none d-sm-inline" style="color: #000; font-size: 0.9em; letter-spacing: -0.5px;">LOGÍSTICA</span>
        </a>
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