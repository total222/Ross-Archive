<?php
include __DIR__ . '/../global_scripts.php';

session_start();
$db = db_connect();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $admin_user = htmlspecialchars($_POST["admin_user"] ?? '');
    $admin_pass = $_POST["admin_pass"] ?? '';

    // CSRF validation
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST["csrf_token"] ?? '')) {
        $_SESSION['http_response'] = 401;
        header('Location: /excepciones/error.php');
        exit;
    }

    // Validation
    if (empty($admin_user) || empty($admin_pass)) {
        header('Location: /admin_dashboard/admin_login.php?error=Campos vacíos');
        exit;
    }

    try {
        // Check admin credentials
        // For security, admin users are stored in usuarios table with a special flag
        // or you can create a separate admins table
        // Using environment variable for admin credentials as additional security
        $env_admin_user = getenv('ADMIN_USERNAME') ?: 'admin';
        $env_admin_pass = getenv('ADMIN_PASSWORD') ?: 'admin123'; // Change in production!

        if ($admin_user === $env_admin_user && $admin_pass === $env_admin_pass) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $admin_user;
            $_SESSION['admin_login_time'] = time();

            header('Location: /admin_dashboard/admin.php');
            exit;
        } else {
            header('Location: /admin_dashboard/admin_login.php?error=Credenciales incorrectas');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Admin auth error: " . $e->getMessage());
        header('Location: /excepciones/error.php');
        exit;
    }
} else {
    header('Location: /admin_dashboard/admin_login.php');
    exit;
}

db_disconnect($db);
?>
