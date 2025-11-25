<?php
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuit = $_POST['cuit'];
    $password = $_POST['password'];

    $result = loginUser($cuit, $password);
    if ($result['success']) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = $result['message'];
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
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin-top: 100px;
        }
    </style>
</head>

<body>
    <div class="container login-container py-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Peirano Logística</h3>
                <h5 class="text-center text-muted mb-4">Acceso Proveedores</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="cuit" class="form-label">CUIT</label>
                        <input type="text" class="form-control form-control-lg" id="cuit" name="cuit" required
                            placeholder="Sin guiones">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                            required>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">INGRESAR</button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-outline-secondary w-100">Crear Cuenta Nueva</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>