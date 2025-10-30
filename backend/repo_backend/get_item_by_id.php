<?php
/**
 * Get a single repository item by ID
 * Returns item details with signed URL for file access
 */

session_start();
require_once __DIR__ . '/../global_scripts.php';
require_once __DIR__ . '/get_signed_url.php';

header('Content-Type: application/json; charset=utf-8');

// Basic security: check if user is logged in
if(empty($_SESSION['ross_user']) && empty($_SESSION['user'])){
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Get item ID from request
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($itemId <= 0){
    http_response_code(400);
    echo json_encode(['error' => 'ID de item inválido']);
    exit;
}

try {
    $db = db_connect();

    // Get item with JOIN to check if user is blocked
    $sql = "SELECT i.\"ID_item\", i.autor, i.usuario, i.titulo, i.descripcion, i.categoria,
                   i.fecha, i.idioma, i.formato, i.licencia, i.ruta_archivo, i.visibilidad, i.preview
            FROM items i
            LEFT JOIN usuarios u ON i.usuario = u.usuario
            WHERE  i.\"ID_item\" = :id
            AND (u.estado = true OR u.estado IS NULL)";

    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$item){
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Archivo no encontrado o no disponible'
        ]);
        exit;
    }

    // Check if item is public or user has access
    if(!$item['visibilidad']){
        // Item is not public, check if user is owner or admin
        $currentUser = '';
        if(!empty($_SESSION['ross_user'])){
            $currentUser = $_SESSION['ross_user']['name_ross'] ?? '';
        } elseif(!empty($_SESSION['user'])){
            $currentUser = $_SESSION['user']['name'] ?? '';
        }

        $isOwner = ($currentUser === $item['usuario']);
        $isAdmin = !empty($_SESSION['admin_logged']);

        if(!$isOwner && !$isAdmin){
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No tienes permiso para ver este archivo'
            ]);
            exit;
        }
    }

    // Generate signed URL valid for 4 hours
    try {
        $item['download_url'] = generateSignedURL($item['ruta_archivo'], 14400);
    } catch(Exception $e) {
        $item['download_url'] = null;
        $item['url_error'] = 'No se pudo generar URL de descarga';
    }

    db_disconnect($db);

    echo json_encode([
        'success' => true,
        'item' => $item
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
