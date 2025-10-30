<?php
session_start();

// Check if admin is logged in
if(empty($_SESSION['admin_logged'])){
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ross-Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="icon" type="image/x-icon" href="../recursos/imagenes/yancuno_logo.ico">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="../global/global.css">
    <link rel="stylesheet" href="../recursos/fonts/Akatab/akatab.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        console.log('=== ADMIN PANEL DEBUG ===');
        console.log('Page loaded at:', new Date().toLocaleTimeString());
        console.log('Current URL:', window.location.href);
    </script>
    <script src="admin_ajax.js" defer></script>
</head>
<body>
    <button class="menu_toggle">
        <span class="material-symbols-outlined">menu</span>
    </button>
    <nav class = "lateral_bar">
        <ul class = "nav_links">
            <li class = "nav_elements">
                <button type="button" class="nav_option active" data-type="usuarios">
                    <span class="material-symbols-outlined">home</span>
                    Inicio
                </button>
            </li>
            <li class = "nav_elements">
                <button type="button" class="nav_option" data-type="hilos">
                    <span class="material-symbols-outlined">article</span>
                    Solicitudes - Foro
                </button>
            </li>
            <li class = "nav_elements">
                <button type="button" class="nav_option" data-type="items">
                    <span class="material-symbols-outlined">folder</span>
                    Solicitudes - Repositorio
                </button>
            </li>
            <li class = "nav_elements">
                <button type="button" class="nav_option" data-type="usuarios">
                    <span class="material-symbols-outlined">group</span>
                    Estado - Usuarios
                </button>
            </li>
        </ul>
    </nav>
    <main class ="admin_content">
       <header class="admin_header">
        <h1>Panel de administrador</h1>
        <a href="../../index" class="logout_button">
            <span class="material-symbols-outlined">logout</span>
            Cerrar sesión
        </a>
       </header>
       <div class = "welcome_text">
        <h1 class="welcome_title">¡Bienvenido al panel de administración!</h1>
        <p class="welcome_description">Desde aquí puedes gestionar las solicitudes del blog y el repositorio, así como generar reportes detallados.</p> 
       </div>
       <section class = "stat_cards">
        <div class="stat_card">
            <h2 class="stat_title">Solicitudes de foro</h2>
            <p class="stat_number"></p>
        </div>
        <div class="stat_card">
            <h2 class="stat_title">Solicitudes de Repositorio</h2>
            <p class="stat_number"></p>
        </div>
        <div class="stat_card">
            <h2 class="stat_title">Usuarios</h2>
            <p class="stat_number"></p>
        </div>
        <div class="stat_card">
            <h2 class="stat_title">Archivos en repositorio</h2>
            <p class="stat_number"></p>
        </div>
       </section>
       <section class = "solicitudes">
        <h2 class="section_title">Gestión de Datos</h2>

        <!-- Table Controls -->
        <div class="table_controls">
            <div class="search_box">
                <input type="text" id="searchInput" placeholder="Buscar por usuario, título o correo...">
            </div>
            <div class="filter_buttons">
                <button id="filterAZ" class="filter_btn">A-Z</button>
                <button id="filterID" class="filter_btn">ID</button>
                <select id="filterEstado" class="filter_select">
                    <option value="all">Todos</option>
                    <option value="true">Activos/Aprobados</option>
                    <option value="false">Inactivos/Pendientes</option>
                </select>
            </div>
        </div>

        <table class="solicitudes_table">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
               
            </tbody>
        </table>
       </section>

       <section class = "stat_container">
        <h2 class = "section_title">Estadísticas del Sistema</h2>
        <div class="chart_wrapper">
            <div style="flex: 1;">
                <canvas id="statsChart"></canvas>
            </div>
        </div>
       </section>
    </main>
</body>
</html>