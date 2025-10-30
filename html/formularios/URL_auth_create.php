<?php
$client = include __DIR__ . '/../../backend/sesiones_sistema/GCP_Oauth_script.php';

// Check if this is a registration or login attempt
if(isset($_GET['intent']) && $_GET['intent'] === 'register'){
    $_SESSION['oauth_intent'] = 'register';
}

$authUrl = $client->createAuthUrl();

header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;

?>