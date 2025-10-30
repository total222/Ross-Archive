<?php
/**
 * Obtain repository items information
 * Returns list of items with signed URLs for file access
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

try {
    $db = db_connect();

    // Get filters from request (optional)
    $categoria = $_GET['categoria'] ?? null;
    $formato = $_GET['formato'] ?? null;
    $search = $_GET['search'] ?? null;
    $autor = $_GET['autor'] ?? null;
    $visibilidad = $_GET['visibilidad'] ?? null; // Admin might want to see all

    // Build query with JOIN to filter out items from blocked users
    $sql = "SELECT i.\"ID_item\", i.autor, i.usuario, i.titulo, i.descripcion, i.categoria, i.fecha, i.idioma, i.formato, i.licencia, i.ruta_archivo, i.visibilidad, i.preview
            FROM items i
            LEFT JOIN usuarios u ON i.usuario = u.usuario
            WHERE (u.estado = true OR u.estado IS NULL)";

    $params = [];

    // Default: only show public items unless user is admin
    if($visibilidad !== 'all') {
        $sql .= " AND i.visibilidad = true";
    }

    if($categoria) {
        $sql .= " AND i.categoria = :categoria";
        $params[':categoria'] = $categoria;
    }

    if($formato) {
        $sql .= " AND i.formato = :formato";
        $params[':formato'] = $formato;
    }

    if($search) {
        $sql .= " AND (i.titulo ILIKE :search OR i.descripcion ILIKE :search OR i.autor ILIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if($autor) {
        $sql .= " AND i.autor ILIKE :autor";
        $params[':autor'] = '%' . $autor . '%';
    }

    $sql .= " ORDER BY i.fecha DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate signed URLs for each item
    foreach($items as &$item) {
        try {
            // Generate signed URL valid for 4 hours
            $item['download_url'] = generateSignedURL($item['ruta_archivo'], 14400);
        } catch(Exception $e) {
            $item['download_url'] = null;
            $item['error'] = 'No se pudo generar URL de descarga';
        }
    }

    db_disconnect($db);

    echo json_encode([
        'success' => true,
        'count' => count($items),
        'items' => $items
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    error_log("obtain_info_repo PDO Error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    error_log("obtain_info_repo Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
