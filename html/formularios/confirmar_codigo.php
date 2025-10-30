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
    <title>Verificar Codigo</title>
    <!--DEPENDENCIAS-->
    <link rel="stylesheet" href="/global/global.css">
    <link rel="stylesheet" href="formulario.css">
    <link rel="stylesheet" href="/recursos/fonts/Akatab/akatab.css">
</head>
<body class = "restablecer_body">
    <header class="form_header">
    <a href="restablecer_clave" class= "header_title sesion_title restablecer_title">Regresar</a>
   </header>
   <form action="api_confirmar_codigo.php" class = "restablecer_form" method = "POST">
    <h1 class = "restablecer_form_title">Verificar codigo</h1>
    <p class = "restablecer_info">Ingresa el codigo enviado a tu bandeja.</p>
    <label for="mail_res" class = "label_res" id = "id_label">Codigo de verificacion</label>
    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="text" class="restablecer_mail code" name="code" id="code_input" required>
    <input type="submit" class ="restablecer_submit verify_submit" id = "verify_bt" value = "Verificar">
    <?php
    if(isset($_GET["error"])){
    echo "<a href ='registro.php' class='error_content'> {$_GET['error']}</a>";
    }
    ?>
   </form>
</body>
</html>