<?php
session_start();
//Validacion token anti-csrf
    if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if(isset($_SESSION['user']) || isset($_SESSION['ross-user'])){
        header('Location: ../plataforma/home');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!--CONFIGURACION DEFAULT-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion</title>
    <link rel="icon" type="image/x-icon" href="/recursos/imagenes/yancuno_logo.ico">
    <meta name="title" content="Ross-Archive">
    <meta name="description" content="Inicio de Sesión de Ross-archive, vuelve a nuestra comunidad garifuna">
    <meta name="author" content="Jared Javier Ramos Castillo">
    <meta name="keywords" content="garifuna, cultura, preservacion, educacion, interaccion, arte, registro">
    <meta name="robots" content="index, follow">
    <!--CONFIGURACION DEFAULT-->
    <!--OpenGraph-->
    <meta property="og:title" content="Iniciar Sesion">
    <meta property="og:description" content="Sesion de Ross-archive, unete a nuestra comunidad garifuna">
    <meta property="og:image" content="/recursos/imagenes/yancuno_logo.png">
    <meta property="og:url" content="https://www.ross-archive.org/formularios/sesion">
    <!--OpenGraph-->
    <!--Dependencias-->
    <link rel="stylesheet" href="/formularios/formulario.css">
    <link rel="stylesheet" href="/global/global.css">
    <link rel="stylesheet" href="/recursos/fonts/Poppins/poppins.css">
    <link rel="stylesheet" href="/recursos/fonts/Cinzel/cinzel.css">
    <link rel="stylesheet" href="/recursos/fonts/Akatab/akatab.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body class="sesion_body">
   <header class="form_header">
    <a href="../index.html" class= "header_title sesion_title">Ross-Archive</a>
   </header>
   <form action="api_sesion.php" method="post" class="sesion_form">
   <h1 class="form_titulo">Inicia Sesión</h1>
    <?php
    if(isset($_GET["error"])){
    echo "<h3 class='error_content'> {$_GET['error']}</h3>";
    }
    ?>
    <!--INFORMACION BASICA-->
    <label for="user">Correo</label>
    <input type="email" class="text_input" required autocomplete="email" name = "gmail">

    <label for="contraseña">Contraseña</label>
    <input type="password" class="text_input" required autocomplete="new-password" name="pass">

    <a href="restablecer_clave" class="link_contraseña">Olvide mi contraseña</a>
     <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="submit" class="submit_button" value="iniciar sesión">
    <!--INFORMACION BASICA-->

    <!--GOOGLE AUTH-->
    <p class="form_p">Inicia sesion con</p>
    <div class="g_container">
    <a href="URL_auth_create.php" class="google_bt">Acceder con Google</a>
    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="35" height="35" viewBox="0 0 48 48" class = "google_icon">
    <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path>
    </svg>
    </div>
    <!--GOOGLE AUTH-->
    <a href="/formularios/registro" class="link_contraseña">¿No tienes una cuenta?</a>
   </form>
</body>
</html>