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
// if no user session, redirect to login
if(empty($sessName) || empty($sessEmail)){
    header('Location: ../../formularios/registro');
    exit;
}
// ensure a CSRF token exists for forms
if(empty($_SESSION['csrf_token'])){
    try{ $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); }catch(Exception $e){ $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(24)); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!--CONFIGURACION DEFAULT-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ross-Hub</title>
    <meta name="title" content="Ross-Archive">
    <meta name="description" content="Pagina repositorio de Ross-Archive, aqui puedes almacenar informacion y consultarla. Contamos con un repositorio, blog, wiki, noticias, juegos educativos, Experiencias VR y traductores orientados al garifuna.">
    <meta name="author" content="Jared Javier Ramos Castillo">
    <meta name="keywords" content="garifuna, cultura, preservacion, educacion, interaccion, arte">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/x-icon" href="../../recursos/imagenes/yancuno_logo.ico">
    <!--CONFIGURACION DEFAULT-->
        <!--OpenGraph-->
    <meta property="og:title" content="Ross-Archive">
    <meta property="og:description" content="La historia y cultura garifuna en un solo lugar. Unete a Ross-Archive y se parte de la preservacion de la cultura garifuna.">
    <meta property="og:image" content="/recursos/imagenes/yancuno_logo.png">
    <meta property="og:url" content="https://www.ross-archive.org">
     <!--OpenGraph-->
      <link rel="stylesheet" href="../../global/global.css">
     <link rel="stylesheet" href="../../recursos/fonts/Poppins/poppins.css">
     <link rel="stylesheet" href="../../recursos/fonts/Cinzel/cinzel.css">
     <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
     <link rel="stylesheet" href="repositorio_style.css">
     <script src="repositorio_ajax.js"></script>
     <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <!--Dependencias-->
     <!-- Plyr.js for audio player -->
     <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
     <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
</head>
<body>
    <header class = "home_header header_repo">
        <a href="" class="logo">
            <object data="../../recursos/SVG/yancuno.svg" type="image/svg+xml" width="100px" height="100px" class="logo_repo" aria-label="Warunagu">
            <title>Warunagu</title>
            </object>
            <h2 class="logo_text">Ross-Hub</h2>
        </a>
    <!-- right-side navigation (sidebar) — reuse home sidebar structure for functionality -->
        <nav class="right_nav" id="leftSidebar">
            <ul class = "header_list">
                <li class = "header_item"><a href="../home" class = "section_link">Inicio</a></li>
                <li class = "header_item"><a href="../ross-forum/foro" class = "section_link">Foro</a></li>
                <li class = "header_item"><a href="../ross-vr/juegos.html" class = "section_link">Experiencias VR</a></li>
                <li class = "header_item"><a href="../ross-hub/traductor" class = "section_link">Traductor</a></li>
            </ul>
        </nav>

        <button class="hamburger_menu" aria-label="Toggle navigation">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- theme toggle + profile control -->
        <input type="checkbox" name="color_mode" id="color_mode" style="display:none;">
        <label for="color_mode" class="color_mode_label" title="Cambiar tema">
            <span class="material-symbols-outlined e">light_mode</span>
            <span class="material-symbols-outlined i">dark_mode</span>
        </label>

        <!-- profile control (kept inside header for hover dropdown behavior) -->
        <div class="profile_group" id="profileGroup" data-name="<?php echo htmlspecialchars($sessName); ?>" data-email="<?php echo htmlspecialchars($sessEmail); ?>" data-img="<?php echo htmlspecialchars($sessImg); ?>">
            <a href="#" class = "profile_header">
                <span class="material-symbols-outlined a">account_circle</span>
            </a>
            <ul class = "profile_options">
                <li class = "profile_option"><button type="button" class = "profile_link open_profile_preview">Mi Perfil</button></li>
                <li class = "profile_option"><a href="../../profile/edit_profile" class = "profile_link">Editar Perfil</a></li>
                <li class = "profile_option"><a href="../../profile/publicaciones" class = "profile_link">Mis publicaciones</a></li>
                <li class = "profile_option"><button type="button" class = "profile_link open_upload_modal">Subir archivo</button></li>
                <li class = "profile_option"><a href="../../index" class = "profile_link">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>
    <nav class = "nav_search">
        <h1 class="nav_title">Repositorio</h1>
        <p class="nav_description">En esta sección podras subir y descargar archivos, ademas de gestionar los archivos que hayas subido.</p>
        <form action="api_repositorio" method="post" class="upload_form" enctype="multipart/form-data">
           <input type="search" name="search" id="search" class="search_input" placeholder="Buscar archivo...">
              <button type="submit" class="search_button"><span class="material-symbols-outlined icon">search</span></button>
        </form>
    </nav>
    <main class = "main_content">
        <nav class = "filters">
            <h2 class="filter_title">Filtros</h2>
            <form action="api_repositorio" method="post" class="filter_form">
                <label for="file_name" class="filter_label">Nombre de autor:</label>
                <input type="text" id="file_name" name="file_name" class="filter_input">
                <label for="materia" class="filter_label">Materia:</label>
                <div class="materia_options">
                    <div class="materia_option">
                        <input type="checkbox" id="materia_historia" name="materia" value="Historia" class="filter_checkbox">
                        <label for="materia_historia" class="materia_label">Historia</label>
                    </div>
                    <div class="materia_option">
                        <input type="checkbox" id="materia_filosofia" name="materia" value="Filosofia" class="filter_checkbox">
                        <label for="materia_filosofia" class="materia_label">Filosofia</label>
                    </div>
                    <div class="materia_option">
                        <input type="checkbox" id="materia_ciencia" name="materia" value="Ciencia" class="filter_checkbox">
                        <label for="materia_ciencia" class="materia_label">Ciencia</label>
                    </div>
                    <div class="materia_option">
                        <input type="checkbox" id="materia_politica" name="materia" value="Politica" class="filter_checkbox">
                        <label for="materia_politica" class="materia_label">Política</label>
                    </div>
                    <div class="materia_option">
                        <input type="checkbox" id="materia_literatura" name="materia" value="Literatura" class="filter_checkbox">
                        <label for="materia_literatura" class="materia_label">Literatura</label>
                    </div>
                    <div class="materia_option">
                        <input type="checkbox" id="materia_otros" name="materia" value="Otros" class="filter_checkbox">
                        <label for="materia_otros" class="materia_label">Otros</label>
                    </div>
                </div>
                <label for="file_type" class="filter_label">Tipo de archivo:</label>
                <select name="file_type" id="file_type" class="filter_select">
                    <option value="">Todos</option>
                    <option value="document">Documento</option>
                    <option value="image">Imagen</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                </select>
                <label for="items_per_page" class="filter_label">Items por página:</label>
                <select name="items_per_page" id="items_per_page" class="filter_select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button type="submit" class="apply_filters_button">Aplicar filtros</button>
            </form>
        </nav>
        <section class = "result_preview">
            <h1 class="result_title">Recursos</h1>
            <p class="result_description">Estos son los recursos disponibles:</p>
                <!-- Aquí se llenarán los archivos dinámicamente -->
                 <div class = "item_container">
                    
                 </div>
        </section>
    </main>
    
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

    <!-- Upload file modal -->
    <div class="upload_modal" id="uploadModal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="upload_dialog">
            <button class="upload_close" aria-label="Cerrar">×</button>
            <header class="upload_header">
                <h2>Subir Archivo al Repositorio</h2>
            </header>
            <form action="api_subir_archivo.php" method="POST" enctype="multipart/form-data" class="upload_form_modal" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="form_group">
                    <label for="upload_autor" class="form_label">Autor:</label>
                    <input type="text" id="upload_autor" name="autor" class="form_input" required>
                </div>

                <div class="form_group">
                    <label for="upload_titulo" class="form_label">Título:</label>
                    <input type="text" id="upload_titulo" name="titulo" class="form_input" required>
                </div>

                <div class="form_group">
                    <label for="upload_descripcion" class="form_label">Descripción:</label>
                    <textarea id="upload_descripcion" name="descripcion" class="form_textarea" rows="3" required></textarea>
                </div>

                <div class="form_group">
                    <label for="upload_formato" class="form_label">Formato:</label>
                    <select id="upload_formato" name="formato" class="form_select" required>
                        <option value="">Selecciona un formato</option>
                        <option value="document">Documento</option>
                        <option value="image">Imagen</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                    </select>
                </div>


                <div class="form_group" id="categoria_container">
                    <label for="upload_categoria" class="form_label">Categoría:</label>
                    <select id="upload_categoria" name="categoria" class="form_select" required>
                        <option value="">Selecciona una categoría</option>
                        <option value="Historia">Historia</option>
                        <option value="Filosofia">Filosofía</option>
                        <option value="Ciencia">Ciencia</option>
                        <option value="Politica">Política</option>
                        <option value="Literatura">Literatura</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>

                <div class="form_group">
                    <label for="upload_fecha" class="form_label">Fecha de creación:</label>
                    <input type="date" id="upload_fecha" name="fecha" class="form_input" required>
                </div>

                <div class="form_group" id="idioma_container">
                    <label for="upload_idioma" class="form_label">Idioma:</label>
                    <select id="upload_idioma" name="idioma" class="form_select" required>
                        <option value="">Selecciona un idioma</option>
                        <option value="Español">Español</option>
                        <option value="Inglés">Inglés</option>
                        <option value="Francés">Francés</option>
                        <option value="Alemán">Alemán</option>
                        <option value="Italiano">Italiano</option>
                        <option value="Portugués">Portugués</option>
                        <option value="Chino">Chino</option>
                        <option value="Japonés">Japonés</option>
                        <option value="Árabe">Árabe</option>
                        <option value="Ruso">Ruso</option>
                        <option value="Coreano">Coreano</option>
                        <option value="Hindi">Hindi</option>
                        <option value="Garifuna">Garifuna</option>
                        <option value="Náhuatl">Náhuatl</option>
                        <option value="Quechua">Quechua</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="form_group">
                    <label for="upload_licencia" class="form_label">Licencia:</label>
                    <select id="upload_licencia" name="licencia" class="form_select">
                        <option value="">Sin licencia Creative Commons (requiere permiso)</option>
                        <option value="CC BY">CC BY - Uso libre con reconocimiento</option>
                        <option value="CC BY-SA">CC BY-SA - Uso libre con reconocimiento (modificaciones bajo misma licencia)</option>
                        <option value="CC BY-ND">CC BY-ND - Uso libre con reconocimiento (sin modificaciones)</option>
                        <option value="CC BY-NC">CC BY-NC - Uso libre con reconocimiento (sin uso comercial)</option>
                        <option value="CC BY-NC-ND">CC BY-NC-ND - Uso libre con reconocimiento (sin uso comercial ni modificaciones)</option>
                        <option value="CC0">CC0 - Dominio público (uso totalmente libre)</option>
                    </select>
                </div>

                <div class="form_group" id="permiso_container" style="display:none;">
                    <label for="upload_permiso" class="form_label">Evidencia de permiso (archivo):</label>
                    <div class="file_input_wrapper">
                        <input type="file" id="upload_permiso" name="evidencia_permiso" class="form_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <label for="upload_permiso" class="file_input_label">
                            <span class="material-symbols-outlined file_icon">upload_file</span>
                            <span class="file_text">Seleccionar archivo</span>
                        </label>
                        <span class="file_name"></span>
                    </div>
                    <p class="form_hint">Sube un documento que acredite el permiso para publicar este contenido.</p>
                </div>

                <div class="form_group">
                    <label for="upload_archivo" class="form_label">Archivo a subir:</label>
                    <div class="file_input_wrapper">
                        <input type="file" id="upload_archivo" name="archivo" class="form_file" required>
                        <label for="upload_archivo" class="file_input_label">
                            <span class="material-symbols-outlined file_icon">upload_file</span>
                            <span class="file_text">Seleccionar archivo</span>
                        </label>
                        <span class="file_name"></span>
                    </div>
                </div>

                <div class="form_group checkbox_group">
                    <input type="checkbox" id="upload_declaracion" name="declaracion_legal" class="form_checkbox" required>
                    <label for="upload_declaracion" class="checkbox_label">
                        Acepto que Ross-Archive no se hace responsable por problemas de copyright. Confirmo que tengo los derechos necesarios para subir este contenido.
                    </label>
                </div>

                <div class="form_actions">
                    <button type="button" class="btn_cancel" id="cancelUpload">Cancelar</button>
                    <button type="submit" class="btn_submit">Subir archivo</button>
                </div>
            </form>
        </div>
    </div>

    <script src="repositorio_ajax.js"></script>
    <script>
    // Populate profile data from server-side session (data attributes)
    (function(){
        const pg = document.getElementById('profileGroup');
        if(!pg) return;
        const name = pg.dataset.name || '';
        const email = pg.dataset.email || '';
        const img = pg.dataset.img || '';
        // if there's an image URL, replace icon with img (safely to prevent XSS)
        const ph = pg.querySelector('.profile_header');
        if(img && ph){
            const imgElement = document.createElement('img');
            imgElement.src = img;
            imgElement.alt = 'perfil';
            imgElement.className = 'profile_image';
            ph.innerHTML = '';
            ph.appendChild(imgElement);
        }
        // Remove name/email display from profile menu (only show buttons)

        // hover delay for profile menu (250ms) - reuse pattern from home
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

    // underline animation for header links and title
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

    // theme toggle: persist in localStorage like home.php
    (function(){
        const checkbox = document.getElementById('color_mode');
        function applyDark(checked){ if(checked) document.body.classList.add('dark'); else document.body.classList.remove('dark'); }
        if(checkbox){
            // read persisted value if any
            try{
                const stored = localStorage.getItem('ross_color_mode');
                if(stored !== null){
                    const val = stored === '1';
                    checkbox.checked = val;
                }
            }catch(e){ /* ignore storage errors */ }
            // initial apply
            applyDark(checkbox.checked);
            checkbox.addEventListener('change', ()=>{
                applyDark(checkbox.checked);
                try{ localStorage.setItem('ross_color_mode', checkbox.checked ? '1' : '0'); }catch(e){}
            });
        }
    })();

    // hamburger menu
    (function(){
        const hamburger = document.querySelector('.hamburger_menu');
        const sidebar = document.getElementById('leftSidebar');
        if(hamburger && sidebar){
            hamburger.addEventListener('click', ()=>{
                sidebar.classList.toggle('active');
            });
        }
    })();

    </script>
</body>
</html>