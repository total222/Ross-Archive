<?php
require_once  __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
include __DIR__ . '/../global_scripts.php';
session_start();
$db = db_connect();

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $res_gmail = htmlspecialchars($_POST["mail_res"]);
    $_SESSION["correo_restablecimiento"] = $res_gmail;
    //Validacion CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST["csrf_token"])) {
        $_SESSION['http_response'] = 401;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion submit
    if (!isset($_POST["mail_res"])) {
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }
    //Validacion de vacio
    if(empty($_POST["mail_res"])){
        $_SESSION['http_response'] = 404;
        header('Location: /../excepciones/error');
        exit;
    }

    else if(isset($_POST["mail_res"])){

        if(!filter_var($res_gmail, FILTER_VALIDATE_EMAIL)){
            header("Location: restablecer_clave?error=Correo invalido, verifique el formato");
            exit;
        }
        try {
            $comprobation = $db->prepare("SELECT correo FROM usuarios WHERE correo = :res_correo");
            $comprobation->bindParam(':res_correo', $res_gmail);
            $comprobation->execute();
            $result = $comprobation->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                header("Location: restablecer_clave?error=Correo inexistente, cree una cuenta aqui");
                exit;
            }
            else {
                $verificationCode = rand(100000, 999999);
                if(empty($_SESSION['verify_code'])){
                 $_SESSION['verify_code'] = $verificationCode;
                }
                $mail = new PHPMailer(true);
                try {
                //$mail->SMTPDebug = 2; // o 3 para más detalle
                //$mail->Debugoutput = 'html';  
                 $mail->isSMTP();
                 $mail->Host       = 'smtp.gmail.com';
                 $mail->SMTPAuth   = true;
                 $mail->Username   = 'ross-email@ross-archive.org'; 
                 $mail->Password   = 'godh lphw ezfl jsih';        
                 $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   
                 $mail->Port       = 465;                           

                // Remitente y destinatario
                $mail->setFrom('ross-email@ross-archive.org', 'Ross Archive');
                $mail->addAddress($res_gmail);

                // Contenido
                $mail->isHTML(true);
                $mail->Subject = 'Ross-Archive - Restablecimiento de contraseña';
                $mail->Body    = 'Tu código de verificación es: ' . '<b>' . $_SESSION['verify_code'] . '</b>';
                $mail->AltBody = 'Tu código de verificación es: ' . $_SESSION['verify_code'];
                $mail->send();
                } catch (PHPMailerException $e) {
                    echo "Error: " . $e->getMessage();
                    header('Location: /../excepciones/error');

                }
                header("Location: restablecer_clave?mensaje=Codigo enviado!");
                exit;
            }
        } catch (PDOException $e) {
             error_log("PHPMailer Error: " . $e->getMessage());
             header('Location: /../excepciones/error');
             exit;
        }
    }

}

?>