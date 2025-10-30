<?php
// backend/foro_backend/subir_hilo.php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../global_scripts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verify user is logged in
$username = null;
if (!empty($_SESSION['ross_user']['name_ross'])) {
    $username = $_SESSION['ross_user']['name_ross'];
} elseif (!empty($_SESSION['user']['name'])) {
    $username = $_SESSION['user']['name'];
}

if (!$username) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para crear un hilo.']);
    exit;
}

// 2. Check for POST request and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Error de validación. Por favor, recarga la página.']);
    exit;
}

// 3. Get and validate data
$title = trim($_POST['titulo'] ?? '');
$content_json = trim($_POST['contenido'] ?? '');

if (empty($title) || empty($content_json)) {
    http_response_code(400);
    echo json_encode(['error' => 'El título y el contenido no pueden estar vacíos.']);
    exit;
}

// Validate that content is valid JSON
$content_data = json_decode($content_json);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato del contenido es inválido.']);
    exit;
}

// 4. Insert into database
try {
    $db = db_connect();
    $stmt = $db->prepare(
        "INSERT INTO hilos (titulo_hilo, contenido_hilo, autor_hilo, fecha_hilo, estado_hilo, likes)
         VALUES (:titulo, :contenido, :autor, CURRENT_DATE, 'false', 0)"
    );

    $stmt->execute([
        ':titulo' => $title,
        ':contenido' => $content_json,
        ':autor' => $username
    ]);

    $new_hilo_id = $db->lastInsertId('hilos_ID_hilo_seq');

    echo json_encode(['success' => true, 'message' => 'Hilo creado exitosamente.', 'hilo_id' => $new_hilo_id]);

} catch (PDOException $e) {
    log_error("Error al crear el hilo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor al crear el hilo.']);
    exit;
} finally {
    db_disconnect($db);
}
?>