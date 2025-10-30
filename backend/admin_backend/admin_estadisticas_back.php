<?php
include __DIR__ . '/../global_scripts.php';
session_start();

// Check if admin is logged in
if(empty($_SESSION['admin_logged'])){
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = db_connect();

    if(!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo conectar a la base de datos. Variable WORKSPACE_PASS no configurada.']);
        exit;
    }

    // Get all counts for chart
    $stats = [];

    // Count usuarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['usuarios'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count items pendientes
    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE visibilidad = false");
    $stats['items_pendientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count total items
    $stmt = $db->query("SELECT COUNT(*) as total FROM items");
    $stats['items_total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count hilos pendientes
    $stmt = $db->query("SELECT COUNT(*) as total FROM hilos WHERE estado_hilo = false");
    $stats['hilos_pendientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count total hilos
    $stmt = $db->query("SELECT COUNT(*) as total FROM hilos");
    $stats['hilos_total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Additional statistics
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = true");
    $stats['usuarios_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE visibilidad = true");
    $stats['items_publicos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    db_disconnect($db);

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
