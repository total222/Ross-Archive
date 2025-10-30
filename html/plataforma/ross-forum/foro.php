<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessName = '';
$sessEmail = '';
$sessImg = '';
if(!empty($_SESSION['ross_user'])){
    $s = $_SESSION['ross_user'];
    $sessName = $s['name_ross'] ?? '';
    $sessEmail = $s['email_ross'] ?? '';
    $sessImg = $s['img'] ?? '';
} elseif(!empty($_SESSION['user'])){
    $s = $_SESSION['user'];
    $sessName = $s['name'] ?? '';
    $sessEmail = $s['email'] ?? '';
    $sessImg = $s['img'] ?? '';
}
}
// Redirect to login if no user session
// if no user session, redirect to login
if(empty($sessName) || empty($sessEmail)){
    header('Location: ../../formularios/registro');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ross-Forum</title>
    <link rel="stylesheet" href="foro_style.css">
    <link rel="stylesheet" href="../../home_style.css">
    <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
    <link rel="stylesheet" href="../../global/global.css">
    <link rel="icon" type="image/x-icon" href="../../recursos/imagenes/yancuno_logo.ico">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body>
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <?php
    // Assuming $sessName, $sessEmail, $sessImg are set in a global script or session
    $sessName = $_SESSION['user']['name'] ?? $_SESSION['ross_user']['name_ross'] ?? 'Guest';
    $sessEmail = $_SESSION['user']['email'] ?? $_SESSION['ross_user']['email_ross'] ?? '';
    $sessImg = $_SESSION['user']['perfil'] ?? $_SESSION['ross_user']['perfil_ross'] ?? '';
?>
    <header class="home_header">
        <h1 class="title_header">Ross-Forum</h1>
        <form action="#" method="get" class="search_form">
            <input type="search" name="search" id="search" class="search_input" placeholder="Buscar en el foro...">
            <button type="submit" class="search_button"><span class="material-symbols-outlined icon">search</span></button>
        </form>
        <div class="header_actions">
            <input type="checkbox" name="color_mode" id="color_mode" hidden>
            <label for="color_mode" class="color_mode_label">
                <span class="material-symbols-outlined e">light_mode</span>
                <span class="material-symbols-outlined i">dark_mode</span>
            </label>
            <a href="creacion_hilo.php" class="new_thread_button" title="Crear nuevo hilo">
                <span class="material-symbols-outlined">add</span>
            </a>
            <a href="#" class="profile_header">
                <span class="material-symbols-outlined">account_circle</span>
                <img src="<?php echo htmlspecialchars($sessImg); ?>" alt="perfil" class="profile_image" style="<?php echo empty($sessImg) ? 'display:none;' : ''; ?>">
            </a>
            <ul class="profile_options">
                <li class="profile_option"><button type="button" class="profile_link open_profile_preview">Mi Perfil</button></li>
                <li class="profile_option"><a href="../../profile/edit_profile.php" class="profile_link">Editar Perfil</a></li>
                <li class="profile_option"><a href="../../profile/publicaciones.php" class="profile_link">Mis publicaciones</a></li>
                <li class="profile_option"><a href="../../profile/api_logout.php" class="profile_link">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>
    <div class="main_container">
        <navbar class="right_nav" id="leftSidebar">
            <button id="toggleSidebar" aria-label="Toggle sidebar" title="Ocultar barra" style="margin-bottom:.5rem;">☰</button>
            <ul class="section_list">
                <li class="section_item"><a href="../home.php" class="section_link"><span class="material-symbols-outlined icon">home</span><span class="link_text">Inicio</span></a></li>
                <li class="section_item"><a href="../ross-hub/repositorio.php" class="section_link"><span class="material-symbols-outlined icon">folder</span><span class="link_text">Repositorio</span></a></li>
                <li class="section_item"><a href="foro.php" class="section_link"><span class="material-symbols-outlined icon">forum</span><span class="link_text">Foro</span></a></li>
                <li class="section_item"><a href="../ross-news/noticias.php" class="section_link"><span class="material-symbols-outlined icon">newspaper</span><span class="link_text">Noticias</span></a></li>
                <li class="section_item"><a href="../ross-vr/juego.html" class="section_link"><span class="material-symbols-outlined icon">vr_headset</span><span class="link_text">VR</span></a></li>
                <li class="section_item"><a href="../ross-traductor/traductor" class="section_link"><span class="material-symbols-outlined icon">translate</span><span class="link_text">Traductor</span></a></li>
            </ul>
            <footer>
                <p>&copy; 2025 Ross-Archive. Todos los derechos reservados.</p>
            </footer>
        </navbar>
        <main class="main_forum_content">
        <section class = "post_list">
            <!-- Posts will be dynamically loaded here -->
        </section>
    </main>
    </div>

    <script>
    // Sidebar toggle, theme management, and profile menu logic
    (function(){
        const sidebar = document.getElementById('leftSidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const colorCheckbox = document.getElementById('color_mode');
        const profileLink = document.querySelector('.profile_header');
        const profileMenu = document.querySelector('.profile_options');
        let hideTimeout = null;

        // Sidebar functionality
        if(toggleBtn && sidebar){
            toggleBtn.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    sidebar.classList.toggle('toggled');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
        }

        // Theme toggling
        if(colorCheckbox) {
            function applyDark(checked){
                if(checked) document.body.classList.add('dark');
                else document.body.classList.remove('dark');
            }
            try{
                const stored = localStorage.getItem('ross_color_mode');
                if(stored !== null){
                    colorCheckbox.checked = stored === '1';
                }
            }catch(e){ /* ignore */ }
            applyDark(colorCheckbox.checked);
            colorCheckbox.addEventListener('change', () => {
                applyDark(colorCheckbox.checked);
                try{ localStorage.setItem('ross_color_mode', colorCheckbox.checked ? '1' : '0'); }catch(e){}
            });
        }

        // Profile menu hover logic
        function showMenu(){
            if(hideTimeout) { clearTimeout(hideTimeout); hideTimeout = null; }
            if(profileMenu) {
                profileMenu.style.opacity = '1';
                profileMenu.style.visibility = 'visible';
                profileMenu.style.transform = 'translateY(0) scale(1)';
            }
        }
        function hideMenuDelayed(){
            if(hideTimeout) clearTimeout(hideTimeout);
            hideTimeout = setTimeout(()=>{
                if(profileMenu) {
                    profileMenu.style.opacity = '0';
                    profileMenu.style.visibility = 'hidden';
                    profileMenu.style.transform = 'translateY(-6px) scale(.98)';
                }
            }, 250);
        }

        if(profileLink && profileMenu){
            profileLink.addEventListener('mouseenter', showMenu);
            profileLink.addEventListener('mouseleave', hideMenuDelayed);
            profileMenu.addEventListener('mouseenter', showMenu);
            profileMenu.addEventListener('mouseleave', hideMenuDelayed);
        }
    })();
    </script>
    <!-- Profile preview modal -->
    <div class="profile_preview_modal" id="profilePreview" aria-hidden="true">
        <div class="ppm_dialog">
            <button class="ppm_close" aria-label="Cerrar">×</button>
            <div class="ppm_body">
                <div class="ppm_avatar">
                    <img class="ppm_img" src="" alt="Foto de perfil">
                    <div class="ppm_avatar_placeholder"><span class="material-symbols-outlined">account_circle</span></div>
                </div>
                <h3 class="ppm_name"></h3>
                <p class="ppm_bio"></p>
                <div class="ppm_contact">
                    <span class="ppm_email"></span>
                    <span class="ppm_phone"></span>
                </div>
                <a class="ppm_edit_link" href="../../profile/edit_profile.php">Editar perfil</a>
            </div>
        </div>
    </div>
    <script src="foro_ajax.js"></script>
</body>
</html>
