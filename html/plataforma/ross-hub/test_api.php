<?php
/**
 * Simple test to see what obtain_info_repo.php returns
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../../backend/global_scripts.php';
require_once __DIR__ . '/../../../backend/repo_backend/get_signed_url.php';

header('Content-Type: application/json; charset=utf-8');

// Check session
if(empty($_SESSION['ross_user']) && empty($_SESSION['user'])){
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No user session found',
        'session_keys' => array_keys($_SESSION)
    ]);
    exit;
}

try {
    $db = db_connect();

    if(!$db){
        throw new Exception('Database connection failed');
    }

    $sql = "SELECT i.\"ID_item\", i.autor, i.usuario, i.titulo, i.descripcion, i.categoria, i.fecha, i.idioma, i.formato, i.licencia, i.ruta_archivo, i.visibilidad, i.preview
            FROM items i
            LEFT JOIN usuarios u ON i.usuario = u.usuario
            WHERE (u.estado = true OR u.estado IS NULL)
            AND i.visibilidad = true
            ORDER BY i.fecha DESC LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $errors = [];

    // Generate signed URLs for each item
    foreach($items as &$item) {
        try {
            $item['download_url'] = generateSignedURL($item['ruta_archivo'], 14400);
        } catch(Exception $e) {
            $item['download_url'] = null;
            $item['url_error'] = $e->getMessage();
            $errors[] = "Item {$item['ID_item']}: " . $e->getMessage();
        }
    }

    db_disconnect($db);

    echo json_encode([
        'success' => true,
        'count' => count($items),
        'items' => $items,
        'url_generation_errors' => $errors,
        'session_user' => !empty($_SESSION['ross_user']) ? 'ross_user' : 'user'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>