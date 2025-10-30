<?php
include __DIR__ . '/../global_scripts.php';

session_start();
$db = db_connect();
if($_SERVER['REQUEST_METHOD'] ==='POST'){
    $gmail = htmlspecialchars($_POST["gmail"]);
    $password = password_hash($_POST["pass"], PASSWORD_DEFAULT);
    if (!hash_equals($_SESSION['csrf_token'], $_POST["csrf_token"])) {
         $_SESSION['http_response'] = 401;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion submit
    if (!isset($_POST["gmail"], $_POST["pass"])) {
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }

        //Validacion de vacio
    if(empty($_POST["gmail"]) || empty($_POST["pass"])){
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }

    if(!filter_var($gmail, FILTER_VALIDATE_EMAIL)){
        header("Location: sesion.php?error=Correo invalido, verifique el formato");
        exit;
    }

    else if  (isset($_POST["gmail"], $_POST["pass"]))
    {
    try {
        $comprobation = $db->prepare("SELECT clave FROM usuarios WHERE correo = :gmail");
        $comprobation->bindParam(':gmail', $gmail);
        $comprobation->execute();
        $result = $comprobation->fetch(PDO::FETCH_ASSOC);
        if($result && password_verify($_POST["pass"], $result['clave'])){
            header('location: /../plataforma/home');
            exit;
        }
        else {
            header("Location: sesion.php?error=Cuenta inexistente o contraseña equivocada.");
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
?>