<?php
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
// Ensure a CSRF token exists for forms
if(empty($_SESSION['csrf_token'])){
    try{ $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); }catch(Exception $e){ $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(24)); }
}

// Get item ID from URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!--CONFIGURACION DEFAULT-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - Ross-Hub</title>
    <meta name="title" content="Ross-Archive">
    <meta name="description" content="Vista previa de archivo del repositorio Ross-Hub">
    <meta name="author" content="Jared Javier Ramos Castillo">
    <meta name="keywords" content="garifuna, cultura, preservacion, educacion, interaccion, arte">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/x-icon" href="../../recursos/imagenes/yancuno_logo.ico">
    <!--CONFIGURACION DEFAULT-->
    <!--OpenGraph-->
    <meta property="og:title" content="Ross-Archive">
    <meta property="og:description" content="La historia y cultura garifuna en un solo lugar">
    <meta property="og:image" content="/recursos/imagenes/yancuno_logo.png">
    <meta property="og:url" content="https://www.ross-archive.org">
    <!--OpenGraph-->
    <link rel="stylesheet" href="../../global/global.css">
    <link rel="stylesheet" href="../../recursos/fonts/Poppins/poppins.css">
    <link rel="stylesheet" href="../../recursos/fonts/Cinzel/cinzel.css">
    <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
    <link rel="stylesheet" href="repositorio_style.css">
    <link rel="stylesheet" href="preview_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <!-- Plyr.js for media player -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
</head>
<body>
    <header class="home_header header_repo">
        <a href="../home.php" class="logo">
            <object data="../../recursos/SVG/yancuno.svg" type="image/svg+xml" width="100px" height="100px" class="logo_repo" aria-label="Warunagu">
            <title>Warunagu</title>
            </object>
            <h2 class="logo_text">Ross-Hub</h2>
        </a>
        <!-- right-side navigation (sidebar) -->
        <nav class="right_nav" id="leftSidebar">
            <ul class="header_list">
                <li class="header_item"><a href="../home" class="section_link">Inicio</a></li>
                <li class="header_item"><a href="../ross-forum/foro" class="section_link">Foro</a></li>
                <li class="header_item"><a href="../ross-vr/vr" class="section_link">Experiencias VR</a></li>
                <li class="header_item"><a href="../traductores/traductores" class="section_link">Traductor</a></li>
                <li class="header_item"><a href="repositorio" class="section_link">Repositorio</a></li>
            </ul>
        </nav>

        <!-- theme toggle + profile control -->
        <input type="checkbox" name="color_mode" id="color_mode" style="display:none;">
        <label for="color_mode" class="color_mode_label" title="Cambiar tema">
            <span class="material-symbols-outlined e">light_mode</span>
            <span class="material-symbols-outlined i">dark_mode</span>
        </label>

        <!-- profile control -->
        <div class="profile_group" id="profileGroup" data-name="<?php echo htmlspecialchars($sessName); ?>" data-email="<?php echo htmlspecialchars($sessEmail); ?>" data-img="<?php echo htmlspecialchars($sessImg); ?>">
            <a href="#" class="profile_header">
                <span class="material-symbols-outlined">account_circle</span>
            </a>
            <ul class="profile_options">
                <li class="profile_option"><a href="../../profile/edit_profile" class="profile_link">Editar Perfil</a></li>
                <li class="profile_option"><a href="../../profile/publicaciones" class="profile_link">Mis publicaciones</a></li>
                <li class="profile_option"><a href="../index.html" class="profile_link">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>

    <main class="preview_content">
        <div class="loading_indicator" id="loadingIndicator">
            <div class="spinner"></div>
            <p>Cargando...</p>
        </div>

        <div class="preview_container" id="previewContainer" style="display:none;">
            <!-- Element preview section -->
            <section class="element_preview" id="elementPreview">
                <!-- Preview will be dynamically loaded here -->
            </section>

            <!-- Element metadata section -->
            <section class="element_data" id="elementData">
                <h1 class="preview_title" id="itemTitle"></h1>
                <div class="metadata_grid">
                    <div class="metadata_item">
                        <span class="metadata_label">Autor:</span>
                        <span class="metadata_value" id="itemAuthor"></span>
                    </div>
                    <div class="metadata_item">
                        <span class="metadata_label">Fecha:</span>
                        <span class="metadata_value" id="itemDate"></span>
                    </div>
                    <div class="metadata_item">
                        <span class="metadata_label">Categoría:</span>
                        <span class="metadata_value" id="itemCategory"></span>
                    </div>
                    <div class="metadata_item">
                        <span class="metadata_label">Idioma:</span>
                        <span class="metadata_value" id="itemLanguage"></span>
                    </div>
                    <div class="metadata_item">
                        <span class="metadata_label">Formato:</span>
                        <span class="metadata_value" id="itemFormat"></span>
                    </div>
                    <div class="metadata_item">
                        <span class="metadata_label">Licencia:</span>
                        <span class="metadata_value" id="itemLicense"></span>
                    </div>
                </div>
            </section>

            <!-- Description and download section -->
            <section class="access_data" id="accessData">
                <div class="description_section">
                    <h2 class="section_title">Descripción</h2>
                    <p class="item_description" id="itemDescription"></p>
                </div>
                <div class="download_section">
                    <a href="#" class="download_button" id="downloadButton" download>
                        <span class="material-symbols-outlined">download</span>
                        Descargar archivo
                    </a>
                </div>
            </section>
        </div>

    </main>

    <script>
    // Theme toggle
    (function(){
        const checkbox = document.getElementById('color_mode');
        function applyDark(checked){
            if(checked) document.body.classList.add('dark');
            else document.body.classList.remove('dark');
        }
        if(checkbox){
            try{
                const stored = localStorage.getItem('ross_color_mode');
                if(stored !== null){
                    const val = stored === '1';
                    checkbox.checked = val;
                }
            }catch(e){}
            applyDark(checkbox.checked);
            checkbox.addEventListener('change', ()=>{
                applyDark(checkbox.checked);
                try{ localStorage.setItem('ross_color_mode', checkbox.checked ? '1' : '0'); }catch(e){}
            });
        }
    })();

    // Profile hover menu
    (function(){
        const pg = document.getElementById('profileGroup');
        if(!pg) return;
        const name = pg.dataset.name || '';
        const email = pg.dataset.email || '';
        const img = pg.dataset.img || '';
        const ph = pg.querySelector('.profile_header');
        if(img && ph){
            const imgElement = document.createElement('img');
            imgElement.src = img;
            imgElement.alt = 'perfil';
            imgElement.className = 'profile_image';
            ph.innerHTML = '';
            ph.appendChild(imgElement);
        }

        const profileHeader = pg.querySelector('.profile_header');
        const menu = pg.querySelector('.profile_options');
        let hideTimeout = null;
        function showMenu(){
            if(hideTimeout){ clearTimeout(hideTimeout); hideTimeout=null; }
            if(menu){
                menu.style.opacity='1';
                menu.style.visibility='visible';
                menu.style.transform='translateY(0) scale(1)';
            }
        }
        function hideMenuDelayed(){
            if(hideTimeout) clearTimeout(hideTimeout);
            hideTimeout = setTimeout(()=>{
                if(menu){
                    menu.style.opacity='0';
                    menu.style.visibility='hidden';
                    menu.style.transform='translateY(-6px) scale(.98)';
                }
            }, 250);
        }
        if(profileHeader && menu){
            profileHeader.addEventListener('mouseenter', showMenu);
            profileHeader.addEventListener('mouseleave', hideMenuDelayed);
            menu.addEventListener('mouseenter', showMenu);
            menu.addEventListener('mouseleave', hideMenuDelayed);
        }
    })();

    // Underline animation for header links
    (function(){
        const links = document.querySelectorAll('.home_header .section_link, .home_header .logo_text');
        links.forEach(el =>{
            el.style.position='relative';
            const bar = document.createElement('span');
            bar.style.position='absolute';
            bar.style.left='0';
            bar.style.bottom='-4px';
            bar.style.height='2px';
            bar.style.width='0%';
            bar.style.background='rgba(0,0,0,0.9)';
            bar.style.transition='width 220ms cubic-bezier(.2,.9,.2,1)';
            el.appendChild(bar);
            el.addEventListener('mouseenter', ()=>{ bar.style.width='100%'; });
            el.addEventListener('mouseleave', ()=>{ bar.style.width='0%'; });
        });
    })();
    </script>

    <script src="preview_ajax.js"></script>
</body>
</html>
