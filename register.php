<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Error de seguridad (CSRF).";
    } else {
        $cuit = $_POST['cuit'];
        $password = $_POST['password'];
        $company_name = $_POST['company_name'];
        $phone = $_POST['phone'];

        if (empty($cuit) || empty($password) || empty($company_name) || empty($phone)) {
            $error = "Todos los campos son obligatorios.";
        } else {
            $result = register($cuit, $password, $company_name, $phone);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Registro - Peirano Logística';
require_once __DIR__ . '/templates/header.php';
?>

<div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Crear Cuenta</h3>
            <p class="text-muted">Unite a la plataforma de turnos</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div class="mt-2"><a href="index.php" class="btn btn-sm btn-success">Ir al Login</a></div>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-3">
                    <label class="form-label">Razón Social / Nombre</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="company_name"
                        required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CUIT</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="cuit" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="phone" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control bg-dark text-light border-secondary" name="password"
                        required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Registrarse</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none text-muted small hover-text-light">¿Ya tenés cuenta? Ingresá
                acá</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>