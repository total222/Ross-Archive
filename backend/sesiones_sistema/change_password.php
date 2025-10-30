<?php
include __DIR__ . '/../global_scripts.php';
session_start();

$db = db_connect();
if ($_SERVER['REQUEST_METHOD']==="POST") {
    $password = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    if (!hash_equals($_SESSION['csrf_token'], $_POST["csrf_token"])) {
        $_SESSION['http_response'] = 401;
        header('Location: /../excepciones/error');
        exit;
    }
        //Validacion submit
    if (!isset($_POST["contraseña"])) {
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion de vacio
      if(empty($_POST["contraseña"])){
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }

     if(strlen($_POST["contraseña"]) > 20 || strlen($_POST["contraseña"]) <= 5) {
        header("Location: cambiar_contraseña.php?error=Contraseña invalida, debe ser de 8-10 caracteres");
        exit;
    }
    else {
        try {
            $edit = $db->prepare("UPDATE usuarios SET clave = :pass WHERE correo = :gmail");
            $edit->execute(
                [
                ':pass' => $password,
                ':gmail' => $_SESSION['correo_restablecimiento']
                ]
                );
                unset($_SESSION['correo_restablecimiento']);
                header('Location: sesion.php');
                exit;
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            header('Location: /../excepciones/error');
            exit;
        }
    }
}
?>