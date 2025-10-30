<?php
include __DIR__ . '/../global_scripts.php';

session_start();
$db = db_connect();
//VALIDACION CSRF
if($_SERVER['REQUEST_METHOD'] ==='POST'){
    $user = htmlspecialchars($_POST["user"]);
    $gmail = htmlspecialchars($_POST["gmail"]);
    $password = password_hash($_POST["pass"], PASSWORD_DEFAULT);
    if (!hash_equals($_SESSION['csrf_token'], $_POST["csrf_token"])) {
        $_SESSION['http_response'] = 401;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion submit
    if (!isset($_POST["user"], $_POST["gmail"], $_POST["pass"])) {
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion de vacio
    else if(empty($_POST["user"]) || empty($_POST["gmail"]) || empty($_POST["pass"])){
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }
    else if  (isset($_POST["user"], $_POST["gmail"], $_POST["pass"]))
    {
            if(!filter_var($gmail, FILTER_VALIDATE_EMAIL)){
                header("Location: registro.php?error=Correo invalido, verifique el formato");
                exit;
            }
            if(strlen($_POST["pass"]) > 20 || strlen($_POST["pass"]) < 5) {
                header("Location: registro.php?error=Contraseña invalida, debe ser de 8-10 caracteres");
                exit;
            }
    try {
        $comprobation = $db->prepare("SELECT usuario FROM usuarios WHERE usuario = :user");
        $comprobation->bindParam(':user', $user);
        $comprobation->execute();
        $result = $comprobation->fetchAll(PDO::FETCH_ASSOC);
        if(count($result) > 0){
            header("Location: registro.php?error=¡Ya existe una cuenta con estos datos!");
                exit;
        }
        else {
            //IF NO CONDITION OCCURS, THEN CREATE ACCOUNT
            $account = $db->prepare("INSERT INTO public.usuarios(usuario, correo, clave) VALUES(:user, :gmail, :pass)");
            $account->execute(
                [
                    ':user' => $user,
                    ':gmail'=> $gmail,
                    ':pass' => $password
                ]
            );
        $_SESSION['ross_user'] = [
        'name_ross' => $user,
        'email_ross' => $gmail,
        'password_ross' => $password
        ];
        header('location: /../plataforma/home');
        exit;
        }
    } 
    catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        header('Location: /../excepciones/error');
        exit;
    }
    }
}