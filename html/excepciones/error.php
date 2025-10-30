<?php
session_start();
$code = $_SESSION['http_response'] ?? 500;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERROR</title>
    <link rel="stylesheet" href="../global/global.css">
    <link rel="stylesheet" href="../recursos/fonts/Akatab/akatab.css">
</head>
<style>
    body {
        background-color: var(--yellow);
        margin: auto;
        margin-top: 10%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    h1 {
        text-align: center;
        color: black;
        font-family: Akatab-Black;
        font-size: 80px
    }
    a {
        font-family: Akatab-Bold;
        color: white;
        text-decoration: none;
        padding: 20px;
        background-color: black;
        border-radius: 10px;
        font-size: 45px;
        transition: all 0.5s ease;
    }
    a:hover {
        background-color: white;
        color: black;
    }
</style>
<body>
   <?php
    switch ($code) {
    case 400:
        echo "<h1>ERROR 400 - VERIFICACION CSRF FALLIDA :(</h1>";
        break;

    case 404:
        echo "<h1>ERROR 404 - RECURSOS NO ENCONTRADOS :(</h1>";
        break;
    default:
       echo "<h1>ERROR 500 - ERROR INTERNO DEL SERVIDOR :(</h1>";
}
   ?>
   <a href="../index.html">REGRESAR AL INICIO</a>
</body>
</html>