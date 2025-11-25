<?php
// index.php
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuit = $_POST['cuit'];
    $password = $_POST['password'];

    if (login($cuit, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'CUIT o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Peirano Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .login-header { background-color: #0d6efd; color: white; border-radius: 10px 10px 0 0; padding: 30px 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="card login-card shadow-lg">
        <div class="login-header">
            <h3 class="mb-1">Peirano Logística</h3>
            <p class="mb-0 opacity-75">Portal de Proveedores</p>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="cuit" class="form-label fw-bold">CUIT</label>
                    <input type="text" class="form-control form-control-lg" id="cuit" name="cuit" required placeholder="Sin guiones">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-bold">Contraseña</label>
                    <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">INGRESAR</button>
                </div>
            </form>
            <div class="text-center mt-4">
                <a href="register.php" class="text-decoration-none">¿No tienes cuenta? Regístrate</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>