<?php
//Limitaciones SameSite para proteccion extra CSRF
if(session_status() === PHP_SESSION_NONE){
    session_set_cookie_params([
        'samesite' => 'Lax',
        'lifetime' => 0, // Sesión expira al cerrar el navegador
        'path' => '/',
        'domain' => 'ross-archive.org', // Dominio permitido
        'secure' => true, // Solo enviar cookies por HTTPS
        'httponly' => true // Evita acceso desde JavaScript
    ]);
}

//Conexion a la base de datos utilizando PDO
if(!function_exists('db_connect')){
function db_connect(){
//===CREDENCIALES===
$credenciales = 'pgsql:host=localhost;port=5432;dbname=rossdb;sslmode=prefer';
$user = 'jared';
$contraseña = getenv('WORKSPACE_PASS'); 
//====================

    try{
        $db =  new PDO($credenciales, $user, $contraseña);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }catch(PDOException $e){
        echo "Error: " . $e->getMessage();
    }
}

//Vuelve NULL al parametro(variable) usando la referencia para eliminar la conexion
function db_disconnect(&$db){
    if(isset($db)){
        $db = null;
    }
}
}

if(!function_exists('log_error')){
    function log_error($message) {
        $timestamp = date("Y-m-d H:i:s");
        $log_message = "[{$timestamp}] " . $message . "\n";
        error_log($log_message, 3, __DIR__ . '/debug.log');
    }
}
?>