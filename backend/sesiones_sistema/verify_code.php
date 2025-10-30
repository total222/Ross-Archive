<?php
include __DIR__ . '/../global_scripts.php';
session_start();

if($_SERVER['REQUEST_METHOD'] === "POST"){
        $code = htmlspecialchars($_POST["code"]);
        if (!hash_equals($_SESSION['csrf_token'], $_POST["csrf_token"])) {
        $_SESSION['http_response'] = 401;
        header('Location: /../excepciones/error');
        exit;
    }
        //Validacion submit
    if (!isset($_POST["code"])) {
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }
        //Validacion de vacio
    if(empty($_POST["code"])){
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }

    else if(isset($_POST["code"])){
        if ($code == $_SESSION['verify_code']) {
            unset($_SESSION['verify_code']);
            header('Location: cambiar_contraseña');
        }
        else if($code != $_SESION['verify_code']){
            header("Location: confirmar_codigo.php?error=Codigo incorrecto");
            exit;
        }
    }
}



?>