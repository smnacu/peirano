<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Error de seguridad (CSRF). Por favor recargue la página.";
    } else {
        $cuit = $_POST['cuit'];
        $password = $_POST['password'];

        $result = loginUser($cuit, $password);

        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login - Peirano Logística';
require_once __DIR__ . '/templates/header.php';
?>

<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <i class="bi bi-box-seam-fill text-primary display-1"></i>
            <h3 class="fw-bold mt-3">Peirano Logística</h3>
            <p class="text-muted">Gestión de Turnos</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label for="cuit" class="form-label">CUIT</label>
                <input type="text" class="form-control bg-dark text-light border-secondary" id="cuit" name="cuit"
                    required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control bg-dark text-light border-secondary" id="password"
                    name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Ingresar</button>
        </form>

        <div class="text-center mt-4">
            <a href="register.php" class="text-decoration-none text-muted small hover-text-light">¿No tenés cuenta?
                Registrate acá</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>