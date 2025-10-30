<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    <title>Ross-Forum - Hilo</title>
    <link rel="stylesheet" href="foro_style.css">
    <link rel="stylesheet" href="hilo_style.css">
    <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
    <link rel="stylesheet" href="../../global/global.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="hilo_ajax.js"></script>
<script>
  window.currentUser = "<?php echo isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : (isset($_SESSION['ross_user']['name_ross']) ? htmlspecialchars($_SESSION['ross_user']['name_ross']) : ''); ?>";

document.addEventListener('DOMContentLoaded', function() {
    console.log("Theme toggling script started.");
    const colorCheckbox = document.getElementById('color_mode');
    const colorModeLabel = document.querySelector('.color_mode_label');

    function applyDark(checked){
        console.log("applyDark called with checked:", checked);
        if(checked) {
            document.body.classList.add('dark');
        } else {
            document.body.classList.remove('dark');
        }
        console.log("document.body.classList after applyDark:", document.body.classList);
    }

    // Initial application of theme based on localStorage
    if (colorCheckbox) {
        try{
            const stored = localStorage.getItem('ross_color_mode');
            if(stored !== null){
                colorCheckbox.checked = stored === '1';
            }
            console.log("Initial colorCheckbox.checked:", colorCheckbox.checked);
        }catch(e){ 
            console.error("Error reading from localStorage:", e);
        }
        applyDark(colorCheckbox.checked);
    } else {
        console.error("colorCheckbox not found on DOMContentLoaded!");
    }

    // Add click listener to the label to toggle the theme
    if(colorModeLabel && colorCheckbox) {
        colorModeLabel.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default label behavior if any
            colorCheckbox.checked = !colorCheckbox.checked; // Toggle checkbox state
            console.log("colorCheckbox.checked after click:", colorCheckbox.checked);
            applyDark(colorCheckbox.checked);
            try{ 
                localStorage.setItem('ross_color_mode', colorCheckbox.checked ? '1' : '0'); 
                console.log("localStorage updated to:", localStorage.getItem('ross_color_mode'));
            }catch(e){
                console.error("Error writing to localStorage:", e);
            }
        });
    } else {
        console.error("colorModeLabel or colorCheckbox not found for click listener!");
    }
});
</script>
</head>
<body>
        <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <header class="home_header">
        <h1 class="title_header">Ross-Forum</h1>
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
                <li class="profile_option"><a href="../../backend/profile_backend/logout.php" class="profile_link">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>
    <main class = "hilo_content">
                    <!-- Post will be dynamically loaded here -->
             <!-- Example Post Structure -->
            <!-- 
             <div> 
                <div class = "post">
                    <div class = "post_header">
                        <a href="" class = "post_profile">
                            <span class="material-symbols-outlined">account_circle</span>
                            <img src="" alt="perfil" class="post_profile_image" lazy="loading">
                        </a>
                        <div class = "post_user_info">
                            <a href="" class = "post_username">Username</a>
                            <span class = "post_timestamp">Fecha y hora</span>
                        </div>
                    </div>
                    <div class = "post_content">
                        <p class = "post_text">Resumen del post...</p>
                        <img src="" alt="post image" class="post_image" lazy="loading">
                    </div>
             </div>
             formulario de respuesta
             <div class="reply_form">
                <textarea placeholder="Escribe tu respuesta aquí..." class="reply_textarea"></textarea>
                <button class="submit_reply_button">Responder</button>
             </div>
             comentarios del post
             <div class="comments_section">
                <div class="comment">
                    <a href="" class="comment_profile">
                        <span class="material-symbols-outlined">account_circle</span>
                        <img src="" alt="perfil" class="comment_profile_image" lazy="loading">
                    </a>
                    <div class="comment_content">
                        <a href="" class="comment_username">Commenter</a>
                        <span class="comment_timestamp">Fecha y hora</span>
                        <p class="comment_text">Este es un comentario de ejemplo.</p>
                    </div>
                    <div class="comment_actions">
                        <button class="reply_button">Responder</button>
                        <button class="delete_button">Eliminar</button> SOLO SI ES TU COMENTARIO
                        <button class="edit_button">Editar</button> SOLO SI ES TU COMENTARIO
                    </div>
                </div>
                respuestas anidadas
                <div class="nested_comment">
                    <a href="" class="comment_profile">
                        <span class="material-symbols-outlined">account_circle</span>
                        <img src="" alt="perfil" class="comment_profile_image" lazy="loading">
                    </a>
                    <div class="comment_content">
                        <a href="" class="comment_username">Nested Commenter</a>
                        <span class="comment_timestamp">Fecha y hora</span>
                        <p class="comment_text">Este es un comentario anidado de ejemplo.</p>
                    </div>
                    <div class="comment_actions">
                        <button class="reply_button">Responder</button>
                        <button class="delete_button">Eliminar</button> SOLO SI ES TU COMENTARIO
                        <button class="edit_button">Editar</button> SOLO SI ES TU COMENTARIO
                    </div>
                </div>
             </div>
             -->
    </main>
</body>
</html>