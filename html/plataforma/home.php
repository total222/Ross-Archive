<!DOCTYPE html>
<html lang="en">
<head>
    <!--CONFIGURACION DEFAULT-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <meta name="title" content="Ross-Archive - Home">
    <meta name="description" content="Página web HOME de Ross-Archive, aqui podras ver los elementos mas importantes de cada sección de Ross-Archive y manejar tu cuenta de usuario. Contamos con un repositorio, blog, wiki, noticias, juegos educativos, Experiencias VR y traductores orientados al garifuna.">
    <meta name="author" content="Jared Javier Ramos Castillo">
    <meta name="keywords" content="garifuna, cultura, preservacion, educacion, interaccion, arte">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/x-icon" href="../recursos/imagenes/yancuno_logo.ico">
    <!--CONFIGURACION DEFAULT-->
    <!--OpenGraph-->
    <meta property="og:title" content="Ross-Archive - Home">
    <meta property="og:description" content="La historia y cultura garifuna en un solo lugar. Unete a Ross-Archive y se parte de la preservacion de la cultura garifuna.">
    <meta property="og:image" content="/recursos/imagenes/yancuno_logo.png">
    <meta property="og:url" content="https://www.ross-archive.org/plataforma/home.php">
     <!--OpenGraph-->

      <!--Dependencias-->
     <link rel="stylesheet" href="../global/global.css">
     <link rel="stylesheet" href="../recursos/fonts/Poppins/poppins.css">
     <link rel="stylesheet" href="../recursos/fonts/Cinzel/cinzel.css">
     <link rel="stylesheet" href="../recursos/fonts/Akatab/akatab.css">
     <link rel="stylesheet" href="home_style.css">
     <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <!--Dependencias-->
</head>
<body>
<!--HEADER SECCION-->
    <header class = "home_header">
        <h1 class = "title_header">Ross-Archive</h1>
        <form action="" class="feed_header" method="POST" id="feed_form">
            <select name="select_header" id="select_header">
                <option value="repositorio" class="option_header option_especial">Repositorio</option>
                <option value="foro" class="option_header">Foro</option>
            </select>
            <div class="feed_options" aria-hidden="true"></div>
        </form>
        <input type="checkbox" name="color_mode" id="color_mode">
        <label for="color_mode" class="color_mode_label">
            <span class="material-symbols-outlined e">light_mode</span>
            <span class="material-symbols-outlined i">dark_mode</span>
        </label>
        <a href="" class = "profile_header">
            <span class="material-symbols-outlined">account_circle</span>
            <img src="" alt="perfil" class="profile_image" lazy="loading">
        </a>
        <ul class = "profile_options">
            <li class = "profile_option"><button type="button" class = "profile_link open_profile_preview">Mi Perfil</button></li>
            <li class = "profile_option"><a href="../profile/edit_profile" class = "profile_link">Editar Perfil</a></li>
            <li class = "profile_option"><a href="../profile/publicaciones" class = "profile_link">Mis publicaciones</a></li>       
            <li class = "profile_option"><a href="../profile/api_logout.php" class = "profile_link">Cerrar Sesión</a></li>
        </ul>
    </header>
<!--HEADER SECCION-->

<!--MAIN SECTION-->
    <main class = "home_content">
        <!--BARRA DE SECCIONES-->
        <navbar class = "right_nav" id="leftSidebar">
            <button id="toggleSidebar" aria-label="Toggle sidebar" title="Ocultar barra" style="margin-bottom:.5rem;">☰</button>
            <ul class = "section_list">
                <li class = "section_item"><a href="home" class = "section_link"><span class="material-symbols-outlined icon">home</span>Inicio</a></li>
                <li class = "section_item"><a href="ross-hub/repositorio" class = "section_link"><span class="material-symbols-outlined icon">folder</span>Repositorio</a></li>
                <li class = "section_item"><a href="ross-forum/foro" class = "section_link"><span class="material-symbols-outlined icon">forum</span>Foro</a></li>
                <li class = "section_item"><a href="ross-vr/juego" class = "section_link"><span class="material-symbols-outlined icon">vr_headset</span>Experiencias VR</a></li>
                <li class = "section_item"><a href="ross-traductor/traductor" class = "section_link"><span class="material-symbols-outlined icon">translate</span>Traductor</a></li>
            </ul>
            <footer>
                <p>&copy; 2025 Ross-Archive. Todos los derechos reservados.</p>
            </footer>
        </navbar>
        <!--BARRA DE SECCIONES-->

        <!--SECCION DE ARTICULOS-->
        <section class = "home_feed" id="homeFeed">
            <!-- Articles will be loaded here dynamically -->

        </section>
        <!--SECCION DE ARTICULOS-->

    </main>
<!--MAIN SECTION-->

<!-- SECCION FOOTER -->

 <script src="home_ajax.js"></script>
 <script>
 // Small hover delay so the profile menu doesn't disappear immediately
 (function(){
     const profileLink = document.querySelector('.profile_header');
     const profileMenu = document.querySelector('.profile_options');
     let hideTimeout = null;

     function showMenu(){
         if(hideTimeout) { clearTimeout(hideTimeout); hideTimeout = null; }
         profileMenu.style.opacity = '1';
         profileMenu.style.visibility = 'visible';
         profileMenu.style.transform = 'translateY(0) scale(1)';
     }
     function hideMenuDelayed(){
         if(hideTimeout) clearTimeout(hideTimeout);
         hideTimeout = setTimeout(()=>{
             profileMenu.style.opacity = '0';
             profileMenu.style.visibility = 'hidden';
             profileMenu.style.transform = 'translateY(-6px) scale(.98)';
         }, 250);
     }

     if(profileLink && profileMenu){
         profileLink.addEventListener('mouseenter', showMenu);
         profileLink.addEventListener('mouseleave', hideMenuDelayed);
         profileMenu.addEventListener('mouseenter', showMenu);
         profileMenu.addEventListener('mouseleave', hideMenuDelayed);
     }
 })();

// Sidebar toggle and dark-mode toggle
(function(){
    const sidebar = document.getElementById('leftSidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const colorCheckbox = document.getElementById('color_mode');

    if(toggleBtn && sidebar){
        toggleBtn.addEventListener('click', ()=>{
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('toggled');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }

    // Keep checkbox and body in sync for dark mode
    if(colorCheckbox){
        function applyDark(checked){
            if(checked) document.body.classList.add('dark');
            else document.body.classList.remove('dark');
        }
        // read persisted value if any
        try{
            const stored = localStorage.getItem('ross_color_mode');
            if(stored !== null){
                const val = stored === '1';
                colorCheckbox.checked = val;
            }
        }catch(e){ /* ignore storage errors */ }
        // initial apply
        applyDark(colorCheckbox.checked);
        colorCheckbox.addEventListener('change', ()=>{
            applyDark(colorCheckbox.checked);
            try{ localStorage.setItem('ross_color_mode', colorCheckbox.checked ? '1' : '0'); }catch(e){}
        });
    }
})();

// Rotate arrow when select is opened (add .open to .feed_header)
(function(){
    const feed = document.querySelector('.feed_header');
    const select = document.getElementById('select_header');
    if(!feed || !select) return;

    function open(){ feed.classList.add('open'); }
    function close(){ feed.classList.remove('open'); }

    select.addEventListener('focus', ()=>{ build(); open(); });
    select.addEventListener('mousedown', (e)=>{ e.preventDefault(); build(); open(); select.focus(); });
    select.addEventListener('blur', ()=>{ setTimeout(close, 150); });
    select.addEventListener('keydown', (e)=>{
        // open custom dropdown on navigation keys and prevent native behavior
        if(['ArrowDown','ArrowUp','Enter',' '].includes(e.key)){
            e.preventDefault();
            build();
            open();
            // focus first option for keyboard users
            const first = container.querySelector('.feed_option');
            if(first) first.focus();
        }
    });

    // --- ensure custom dropdown exists and handle interactions ---
    (function(){
        const containerEl = feed.querySelector('.feed_options');
        function buildOptions(){
            containerEl.innerHTML = '';
            Array.from(select.options).forEach((opt, idx)=>{
                // skip placeholder / empty value options
                if(typeof opt.value === 'string' && opt.value.trim() === '') return;
                const d = document.createElement('div');
                d.className = 'feed_option';
                d.setAttribute('data-value', opt.value);
                d.setAttribute('tabindex', '0');
                d.setAttribute('role', 'option');
                d.textContent = opt.text;
                containerEl.appendChild(d);
            });
        }
        function openOptions(){
            containerEl.classList.add('visible');
            containerEl.setAttribute('aria-hidden','false');
            Array.from(containerEl.children).forEach((c,i)=>{ c.style.transitionDelay = (i*25)+'ms'; c.classList.add('enter'); });
        }
        function closeOptions(){
            Array.from(containerEl.children).forEach((c)=>{ c.classList.remove('enter'); c.style.transitionDelay=''; });
            containerEl.classList.remove('visible');
            containerEl.setAttribute('aria-hidden','true');
        }
        // wire events
        select.addEventListener('focus', ()=>{ buildOptions(); openOptions(); });
        select.addEventListener('mousedown', (e)=>{ e.preventDefault(); buildOptions(); openOptions(); select.focus(); });
        document.addEventListener('click', (e)=>{ if(!feed.contains(e.target)) closeOptions(); });
        containerEl.addEventListener('click', (e)=>{
            const opt = e.target.closest('.feed_option'); if(!opt) return;
            select.value = opt.getAttribute('data-value');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            closeOptions();
            select.focus();
        });
        // keyboard navigation
        containerEl.addEventListener('keydown', (e)=>{
            if(e.key === 'Escape'){ closeOptions(); select.focus(); }
            if(e.key === 'Enter' || e.key === ' '){ const f = document.activeElement; if(f && f.classList.contains('feed_option')){ f.click(); e.preventDefault(); } }
            if(e.key === 'ArrowDown' || e.key === 'ArrowUp'){
                const items = Array.from(containerEl.querySelectorAll('.feed_option'));
                let idx = items.indexOf(document.activeElement);
                if(idx === -1) idx = 0;
                idx = e.key === 'ArrowDown' ? Math.min(items.length-1, idx+1) : Math.max(0, idx-1);
                items[idx].focus(); e.preventDefault();
            }
        });
    })();
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
            <a class="ppm_edit_link" href="../profile/edit_profile">Editar perfil</a>
        </div>
    </div>
</div>
<script src="home_ajax.js"></script>
</body>
</html>