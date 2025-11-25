<?php
// register.php
require_once 'auth.php';

// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuit = $_POST['cuit'];
    $password = $_POST['password'];
    $company_name = $_POST['company_name'];
    $phone = $_POST['phone'];

    $result = register($cuit, $password, $company_name, $phone);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<h4 class="mb-0">Registro de Proveedor</h4>
</div>
<div class="card-body p-4">
    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?> <a href="index.php">Iniciar Sesión</a>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="company_name" class="form-label">Razón Social</label>
            <input type="text" class="form-control" id="company_name" name="company_name" required>
        </div>
        <div class="mb-3">
            <label for="cuit" class="form-label">CUIT</label>
            <input type="text" class="form-control" id="cuit" name="cuit" required placeholder="Sin guiones">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-success">Registrarse</button>
        </div>
    </form>
    <div class="text-center mt-3">
        <a href="index.php" class="text-decoration-none">Volver al Login</a>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>