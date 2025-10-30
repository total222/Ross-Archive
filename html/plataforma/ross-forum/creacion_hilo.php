<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $sessName = $_SESSION['user']['name'] ?? $_SESSION['ross_user']['name_ross'] ?? 'Guest';
    $sessImg = $_SESSION['user']['perfil'] ?? $_SESSION['ross_user']['perfil_ross'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Hilo</title>
    <link rel="stylesheet" href="creacion_hilo_style.css">
    <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
    <link rel="stylesheet" href="../../global/global.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
</head>
<body>
    <header class="home_header">
        <a href="foro.php" class="home_link"><h1 class="title_header">Ross-Forum</h1></a>
        <div class="header_actions">
            <input type="checkbox" name="color_mode" id="color_mode" hidden>
            <label for="color_mode" class="color_mode_label">
                <span class="material-symbols-outlined e">light_mode</span>
                <span class="material-symbols-outlined i">dark_mode</span>
            </label>
        </div>
    </header>

    <main class="creacion_hilo_content">
        <h1 class="page_title">Crear Nuevo Hilo</h1>
        <form id="create_hilo_form" class="hilo_form">
            <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form_group">
                <label for="titulo_hilo">Título</label>
                <input type="text" id="titulo_hilo" name="titulo_hilo" required placeholder="El título de tu publicación">
            </div>
            <div class="form_group">
                <label>Contenido</label>
                <div id="editor"></div>
            </div>
            <div class="form_actions">
                <button type="submit" id="submit_hilo" class="submit_button">Publicar Hilo</button>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="creacion_hilo_ajax.js"></script>
    <script>
    (function(){
        const colorCheckbox = document.getElementById('color_mode');
        if(colorCheckbox) {
            function applyDark(checked){ document.body.classList.toggle('dark', checked); }
            const stored = localStorage.getItem('ross_color_mode');
            if(stored !== null) colorCheckbox.checked = stored === '1';
            applyDark(colorCheckbox.checked);
            colorCheckbox.addEventListener('change', () => {
                applyDark(colorCheckbox.checked);
                try { localStorage.setItem('ross_color_mode', colorCheckbox.checked ? '1' : '0'); } catch(e){}
            });
        }
    })();
    </script>
</body>
</html>
