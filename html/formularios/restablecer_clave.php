<?php
session_start();
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <!--DEPENDENCIAS-->
    <link rel="stylesheet" href="/global/global.css">
    <link rel="stylesheet" href="formulario.css">
    <link rel="stylesheet" href="/recursos/fonts/Akatab/akatab.css">
</head>
<body class = "restablecer_body">
    <header class="form_header">
    <a href="../index.html" class= "header_title sesion_title restablecer_title">Ross-Archive</a>
   </header>
   <form action="api_restablecer.php" class = "restablecer_form" method = "POST">
    <h1 class = "restablecer_form_title">Restablecer Contraseña</h1>
    <p class = "restablecer_info">Escriba su correo Electrónico e ingrese el codigo enviado a su bandeja.</p>
    <label for="mail_res" class = "label_res" id = "id_label">Correo Electrónico</label>
    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="email" class = "restablecer_mail" name ="mail_res" required id ="mail_input">
    <input type="submit" class = "restablecer_submit" id = "submit_bt" value = "enviar codigo">
    <?php
    if(isset($_GET["error"])){
    echo "<a href ='registro.php' class='error_content'> {$_GET['error']}</a>";
    }
    if(isset($_GET["mensaje"])){
        echo "<h3 class ='error_content mensaje_content'>{$_GET['mensaje']}</h3>";
        echo "<a href='confirmar_codigo.php' class = 'buton_advance'>Siguiente</a>";
    }
    ?>
   </form>
</body>
</html>