<?php
require_once  __DIR__ . '/../../vendor/autoload.php';
session_start();
//Credenciales
$clientId = getenv('GOOGLE_AUTH_CLIENT');
$clientSecret = getenv('GOOGLE_AUTH_SECRET');
$redirectUri = getenv('REDIRECT_GOOGLE_URL');

//Instancia del cliente de Google Oauth2.0
$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);
$client->setAccessType('offline');
$client->setPrompt('select_account');

return $client;
?>