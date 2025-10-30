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
// ==========================
// Lógica PHP para Moses
// ==========================
$translationResult = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Texto enviado desde el formulario
    $textToTranslate = isset($_POST['text']) ? $_POST['text'] : '';

    if ($textToTranslate) {
        $mosesServer = "http://localhost:8090/RPC2";

        // Construir XML-RPC
        $xml = '<?xml version="1.0"?>
        <methodCall>
            <methodName>translate</methodName>
            <params>
                <param>
                    <value>
                        <struct>
                            <member>
                                <name>text</name>
                                <value><string>' . htmlspecialchars($textToTranslate) . '</string></value>
                            </member>
                        </struct>
                    </value>
                </param>
            </params>
        </methodCall>';

        // Llamada a Moses via cURL
        $ch = curl_init($mosesServer);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: text/xml"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        // Extraer traducción del XML
        if (preg_match('/<string>(.*?)<\/string>/', $response, $matches)) {
            $translationResult = $matches[1];
        } else {
            $translationResult = "Error: no se pudo obtener traducción";
        }
    } else {
        $translationResult = "Por favor ingresa un texto para traducir.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ross-Traductor</title>
    <link rel="stylesheet" href="../../global/global.css">
    <link rel="icon" type="image/x-icon" href="../../recursos/imagenes/yancuno_logo.ico">
    <link rel="stylesheet" href="../../recursos/fonts/Akatab/akatab.css">
    <link rel="stylesheet" href="traductor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

    <div class="translator-container">
        <a href="../home" class="home-btn">Volver a Home</a>
        <h1>Ross-Traductor</h1>
        <form method="POST" id="translateForm">
            <div class="input-area">
                <input type="text" name="text" id="text" placeholder="Escribe algo o usa el micrófono..." required>
                <button type="button" id="mic-btn" title="Usar micrófono">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1.2-9.1c0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2l-.01 6.2c0 .66-.53 1.2-1.19 1.2s-1.2-.54-1.2-1.2V4.9zm6.5 4.1h-1c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-2.08c3.39-.49 6-3.39 6-6.92z"/></svg>
                </button>
                <button type="submit">Traducir</button>
            </div>
        </form>

        <div id="result-container">
            <div class="result-header">
                <p id="result-label">Traducción:</p>
                <button id="speak-btn" title="Escuchar traducción">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
                </button>
            </div>
            <div id="result">
                <?php if ($translationResult) echo htmlspecialchars($translationResult); ?>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('translateForm');
        const textInput = document.getElementById('text');
        const resultDiv = document.getElementById('result');
        const speakBtn = document.getElementById('speak-btn');
        const micBtn = document.getElementById('mic-btn');

        // Lógica para el envío del formulario con AJAX
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const text = textInput.value;
            const formData = new FormData();
            formData.append('text', text);

            resultDiv.textContent = 'Traduciendo...';

            const response = await fetch('', { method: 'POST', body: formData });
            const html = await response.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newResult = doc.getElementById('result').innerHTML;
            resultDiv.innerHTML = newResult;
        });

        // Lógica para el Text-to-Speech
        speakBtn.addEventListener('click', () => {
            const textToSpeak = resultDiv.textContent.trim();
            if (textToSpeak && 'speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(textToSpeak);
                utterance.lang = 'cab';
                window.speechSynthesis.speak(utterance);
            } else if (!textToSpeak) {
                alert('No hay nada que leer.');
            } else {
                alert('Tu navegador no soporta la funcionalidad de texto a voz.');
            }
        });

        // Lógica para el Speech-to-Text (Dictado)
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            const recognition = new SpeechRecognition();
            recognition.lang = 'es-ES'; // Configurado para español
            recognition.interimResults = false;

            micBtn.addEventListener('click', () => {
                try {
                    recognition.start();
                } catch(e) {
                    alert("Error al iniciar el reconocimiento. Puede que ya esté activo.");
                }
            });

            recognition.onstart = () => {
                micBtn.classList.add('listening');
                micBtn.title = "Escuchando...";
            };

            recognition.onend = () => {
                micBtn.classList.remove('listening');
                micBtn.title = "Usar micrófono";
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                textInput.value = transcript;
            };

            recognition.onerror = (event) => {
                alert(`Error en el reconocimiento: ${event.error}`);
            };

        } else {
            micBtn.style.display = 'none'; // Ocultar el botón si la API no está soportada
            alert('Tu navegador no soporta la funcionalidad de dictado por voz.');
        }
    </script>
</body>
</html>
