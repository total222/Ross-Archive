<?php
include __DIR__ . '/../global_scripts.php';
$client = include 'GCP_Oauth_script.php';
if (!isset($_GET['code'])) {
    die('No se recibió el código de Google');
}

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $oauth = new Google_Service_Oauth2($client);
    $profile = $oauth->userinfo->get();


        $_SESSION['user'] = [
            'name' => $profile->name,
            'email' => $profile->email
        ];
    
    
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])){
        $db = db_connect();
        try {
            $comprobation = $db->prepare("SELECT correo FROM usuarios WHERE correo = :correo");
            $comprobation->bindParam(':correo', $_SESSION['user']['email']);
            $comprobation->execute();
            $result = $comprobation->fetchAll(PDO::FETCH_ASSOC);

            // Check if this is a registration attempt (from registro.php)
            $isRegistration = isset($_SESSION['oauth_intent']) && $_SESSION['oauth_intent'] === 'register';

            if(count($result) > 0){
                // User already exists
                if($isRegistration){
                    // Trying to register with existing account - show error and redirect to sesion.php
                    unset($_SESSION['user']);
                    unset($_SESSION['oauth_intent']);
                    header('Location: /../formularios/sesion?error=' . urlencode('Esta cuenta de Google ya está registrada. Por favor, inicia sesión.'));
                    exit;
                } else {
                    // Login attempt - redirect to home
                    unset($_SESSION['oauth_intent']);
                    header('Location: /../plataforma/home');
                    exit;
                }
            }
            else {
                // New user - create account
                $account = $db->prepare("INSERT INTO public.usuarios(usuario, correo) VALUES(:user, :gmail)");
                $account->execute(
                    [
                        ':user' => htmlspecialchars($_SESSION['user']['name']),
                        ':gmail'=> htmlspecialchars($_SESSION['user']['email'])
                    ]
                );
                unset($_SESSION['oauth_intent']);
                header('Location: /../plataforma/home');
                exit;
            }
        } catch (\PDOException $e) {
            error_log("Error: " . $e->getMessage());
            header('Location: /../excepciones/error');
            exit;
        }
    exit;
    }
}
?>
