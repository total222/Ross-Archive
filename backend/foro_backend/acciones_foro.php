<?php
// backend/foro_backend/acciones_foro.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../global_scripts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Get User
$username = $_SESSION['user']['name'] ?? $_SESSION['ross_user']['name_ross'] ?? null;
if (!$username) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

// 2. Check Request Method and CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

// 3. Get Action and Data
$action = $_POST['action'] ?? '';
$data = $_POST['data'] ?? [];

try {
    $db = db_connect();
    $response = ['success' => false];

    switch ($action) {
        case 'like_hilo':
            $hilo_id = (int)($data['id'] ?? 0);
            if ($hilo_id <= 0) throw new Exception('Invalid thread ID for liking.');

            // Check if user has already liked
            $stmt_check = $db->prepare('SELECT COUNT(*) FROM hilo_likes WHERE ID_hilo = :hilo_id AND usuario = :usuario');
            $stmt_check->execute([':hilo_id' => $hilo_id, ':usuario' => $username]);
            $has_liked = (int)$stmt_check->fetchColumn() > 0;

            if ($has_liked) {
                // Unlike: Delete from hilo_likes and decrement count
                $stmt_unlike = $db->prepare('DELETE FROM hilo_likes WHERE ID_hilo = :hilo_id AND usuario = :usuario');
                $stmt_unlike->execute([':hilo_id' => $hilo_id, ':usuario' => $username]);

                $stmt_update = $db->prepare('UPDATE hilos SET likes = likes - 1 WHERE "ID_hilo" = :hilo_id');
                $stmt_update->execute([':hilo_id' => $hilo_id]);
            } else {
                // Like: Insert into hilo_likes and increment count
                $stmt_like = $db->prepare('INSERT INTO hilo_likes (ID_hilo, usuario) VALUES (:hilo_id, :usuario)');
                $stmt_like->execute([':hilo_id' => $hilo_id, ':usuario' => $username]);

                $stmt_update = $db->prepare('UPDATE hilos SET likes = likes + 1 WHERE "ID_hilo" = :hilo_id');
                $stmt_update->execute([':hilo_id' => $hilo_id]);
            }

            // Fetch the new like count
            $stmt_count = $db->prepare('SELECT likes FROM hilos WHERE "ID_hilo" = :id');
            $stmt_count->execute([':id' => $hilo_id]);
            $new_likes = $stmt_count->fetchColumn();

            $response = ['success' => true, 'new_likes' => $new_likes, 'liked' => !$has_liked];
            break;

        case 'like_comentario':
            $comentario_id = (int)($data['id'] ?? 0);
            if ($comentario_id <= 0) throw new Exception('Invalid comment ID for liking.');

            // Check if user has already liked
            $stmt_check = $db->prepare('SELECT COUNT(*) FROM comentario_likes WHERE ID_comentario = :comentario_id AND usuario = :usuario');
            $stmt_check->execute([':comentario_id' => $comentario_id, ':usuario' => $username]);
            $has_liked = (int)$stmt_check->fetchColumn() > 0;

            if ($has_liked) {
                // Unlike: Delete from comentario_likes and decrement count
                $stmt_unlike = $db->prepare('DELETE FROM comentario_likes WHERE ID_comentario = :comentario_id AND usuario = :usuario');
                $stmt_unlike->execute([':comentario_id' => $comentario_id, ':usuario' => $username]);

                $stmt_update = $db->prepare('UPDATE comentarios_hilo SET like_comentario = like_comentario - 1 WHERE "ID_comentario_hilo" = :comentario_id');
                $stmt_update->execute([':comentario_id' => $comentario_id]);
            } else {
                // Like: Insert into comentario_likes and increment count
                $stmt_like = $db->prepare('INSERT INTO comentario_likes (ID_comentario, usuario) VALUES (:comentario_id, :usuario)');
                $stmt_like->execute([':comentario_id' => $comentario_id, ':usuario' => $username]);

                $stmt_update = $db->prepare('UPDATE comentarios_hilo SET like_comentario = like_comentario + 1 WHERE "ID_comentario_hilo" = :comentario_id');
                $stmt_update->execute([':comentario_id' => $comentario_id]);
            }

            // Fetch the new like count
            $stmt_count = $db->prepare('SELECT like_comentario FROM comentarios_hilo WHERE "ID_comentario_hilo" = :id');
            $stmt_count->execute([':id' => $comentario_id]);
            $new_likes = $stmt_count->fetchColumn();

            $response = ['success' => true, 'new_likes' => $new_likes, 'liked' => !$has_liked];
            break;

        case 'add_comentario':
            $hilo_id = (int)($data['hilo_id'] ?? 0);
            $contenido = trim($data['contenido'] ?? '');
            $respuesta_a = ($data['respuesta_a'] ?? null) ? (int)$data['respuesta_a'] : null;
            $date = new DateTime('now', new DateTimeZone('America/Tegucigalpa'));
            $timestamp = $date->getTimestamp();


            if (empty($contenido) || $hilo_id === 0) {
                throw new Exception("Content and thread ID are required.");
            }

            $stmt = $db->prepare(
                "INSERT INTO comentarios_hilo (autor_comentario_hilo, fecha_comentario_hilo, contenido_comentario_hilo, \"ID_hilo_comentario\", respuesta_hacia, like_comentario, hora) 
                 VALUES (:autor, NOW(), :contenido, :hilo_id, :respuesta_a, 0, NOW())"
            );
            $stmt->execute([
                ':autor' => $username,
                ':contenido' => $contenido,
                ':hilo_id' => $hilo_id,
                ':respuesta_a' => $respuesta_a
            ]);
            $response = ['success' => true, 'message' => 'Comment added.'];
            break;

        case 'edit_comentario':
            $comentario_id = (int)($data['id'] ?? 0);
            $contenido = trim($data['contenido'] ?? '');
            $stmt = $db->prepare("UPDATE comentarios_hilo SET contenido_comentario_hilo = :contenido WHERE \"ID_comentario_hilo\" = :id AND autor_comentario_hilo = :autor");
            $stmt->execute([':contenido' => $contenido, ':id' => $comentario_id, ':autor' => $username]);
            $response = ['success' => $stmt->rowCount() > 0];
            break;

        case 'delete_comentario':
            $comentario_id = (int)($data['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM comentarios_hilo WHERE \"ID_comentario_hilo\" = :id AND autor_comentario_hilo = :autor");
            $stmt->execute([':id' => $comentario_id, ':autor' => $username]);
            $response = ['success' => $stmt->rowCount() > 0];
            break;
        
        case 'delete_hilo':
            $hilo_id = (int)($data['id'] ?? 0);
            // First, delete associated comments
            $stmt = $db->prepare("DELETE FROM comentarios_hilo WHERE \"ID_hilo_comentario\" = :id AND EXISTS (SELECT 1 FROM hilos WHERE \"ID_hilo\" = :id AND autor_hilo = :autor)");
            $stmt->execute([':id' => $hilo_id, ':autor' => $username]);
            // Then, delete the thread
            $stmt = $db->prepare("DELETE FROM hilos WHERE \"ID_hilo\" = :id AND autor_hilo = :autor");
            $stmt->execute([':id' => $hilo_id, ':autor' => $username]);
            $response = ['success' => $stmt->rowCount() > 0];
            break;

        default:
            http_response_code(400);
            $response = ['error' => 'Invalid action.'];
            break;
    }

    echo json_encode($response);

} catch (PDOException | Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal error occurred.', 'details' => $e->getMessage()]);
} finally {
    db_disconnect($db);
}
?>