<?php
session_start();

// Prevent Google OAuth users from accessing password change page
if(!empty($_SESSION['user'])){
    header('Location: ../profile/edit_profile.php?error=' . urlencode('No puedes cambiar la contraseña de una cuenta de Google'));
    exit;
}

if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    <!--DEPENDENCIAS-->
    <link rel="stylesheet" href="/global/global.css">
    <link rel="stylesheet" href="formulario.css">
    <link rel="stylesheet" href="/recursos/fonts/Akatab/akatab.css">
</head>
<body class = "restablecer_body">
    <header class="form_header">
    <a href="restablecer_clave" class= "header_title sesion_title restablecer_title">Regresar</a>
   </header>
   <form action="api_cambiar_clave.php" class = "restablecer_form" method = "POST">
    <h1 class = "restablecer_form_title">Cambiar contraseña</h1>
    <p class = "restablecer_info">Ingrese su nueva contraseña.</p>
    <label for="mail_res" class = "label_res" id = "id_label">Contraseña</label>
    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="text" class="restablecer_mail" name="contraseña"  required>
    <input type="submit" class ="restablecer_submit verify_submit" id = "verify_bt" value = "Verificar">
    <?php
    if(isset($_GET["error"])){
    echo "<a href ='registro.php' class='error_content'> {$_GET['error']}</a>";
    }
    ?>
   </form>
</body>
</html>