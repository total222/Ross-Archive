<?php
session_start();

// Generate CSRF token
if(empty($_SESSION['csrf_token'])){
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    } catch(Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(24));
    }
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ross-Archive</title>
    <link rel="icon" type="image/x-icon" href="../recursos/imagenes/yancuno_logo.ico">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="../global/global.css">
    <link rel="stylesheet" href="../recursos/fonts/Akatab/akatab.css">
</head>
<body>
    <div class="login_container">
        <div class="login_card">
            <h1 class="login_title">Panel de Administración</h1>

            <?php if($error): ?>
            <div class="error_message">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form action="api_login_admin.php" method="POST" class="login_form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form_group">
                    <label for="admin_user" class="form_label">Usuario</label>
                    <input type="text" id="admin_user" name="admin_user" class="form_input" required autofocus>
                </div>

                <div class="form_group">
                    <label for="admin_pass" class="form_label">Contraseña</label>
                    <input type="password" id="admin_pass" name="admin_pass" class="form_input" required>
                </div>

                <button type="submit" class="login_submit">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>
