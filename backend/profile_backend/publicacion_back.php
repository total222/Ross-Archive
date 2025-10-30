<?php
// backend/profile_backend/publicacion_back.php
ini_set('display_errors', 0); // Log errors, don't display them
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../global_scripts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Identify logged-in user
$username = $_SESSION['ross_user']['name_ross'] ?? $_SESSION['user']['name'] ?? null;

if (!$username) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'No user is logged in.']);
    exit;
}

// 2. Connect to the database
try {
    $db = db_connect();
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// 3. Handle request based on method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest($db, $username);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostRequest($db, $username);
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed.']);
}

db_disconnect($db);

// --- HANDLER FUNCTIONS ---

function handleGetRequest($db, $username) {
    $type = $_GET['type'] ?? 'repositorio';
    $response_data = [];

    try {
        switch ($type) {
            case 'repositorio':
                $stmt = $db->prepare("SELECT 'repositorio' as type, titulo, descripcion, fecha, \"ID_item\" as id FROM items WHERE usuario = :username ORDER BY fecha DESC");
                break;
            case 'hilos':
                $stmt = $db->prepare("SELECT 'hilos' as type, titulo_hilo as titulo, contenido_hilo as descripcion, fecha_hilo as fecha, \"ID_hilo\" as id FROM hilos WHERE autor_hilo = :username ORDER BY fecha_hilo DESC");
                break;
            case 'comentarios':
                $stmt = $db->prepare("SELECT 'comentarios' as type, 'Comentario en el hilo #' || \"ID_hilo_comentario\" as titulo, contenido_comentario_hilo as descripcion, fecha_comentario_hilo as fecha, \"ID_comentario_hilo\" as id FROM comentarios_hilo WHERE autor_comentario_hilo = :username ORDER BY fecha_comentario_hilo DESC");
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid content type specified.']);
                return;
        }

        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $description = $row['descripcion'];
            if ($type === 'hilos' && !empty($description)) {
                $content = json_decode($description, true);
                $text_snippet = '';
                if (is_array($content)) {
                    foreach ($content as $op) {
                        if (isset($op['insert']) && is_string($op['insert'])) {
                            $text_snippet .= $op['insert'] . ' ';
                        }
                    }
                }
                $description = trim($text_snippet) ?: 'Contenido no textual.';
            }

            $snippet = htmlspecialchars(strip_tags($description));
            if (mb_strlen($snippet) > 150) {
                $snippet = mb_substr($snippet, 0, 150) . '...';
            }

            $response_data[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'titulo' => htmlspecialchars($row['titulo']),
                'descripcion' => $snippet,
                'fecha' => date("d M, Y", strtotime($row['fecha']))
            ];
        }
        echo json_encode($response_data);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Database query failed: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch data.']);
    }
}

function handlePostRequest($db, $username) {
    $action = $_POST['action'] ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No action specified.']);
        return;
    }

    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? null;

    if (!$id || !$type) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing ID or type.']);
        return;
    }

    // Map type to table details
    $table_map = [
        'repositorio' => ['table' => 'items', 'id_col' => 'ID_item', 'author_col' => 'usuario', 'title_col' => 'titulo', 'desc_col' => 'descripcion'],
        'hilos' => ['table' => 'hilos', 'id_col' => 'ID_hilo', 'author_col' => 'autor_hilo', 'title_col' => 'titulo_hilo', 'desc_col' => 'contenido_hilo']
    ];

    if (!isset($table_map[$type])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid type for action.']);
        return;
    }
    $map = $table_map[$type];

    // --- Security Check: Verify Ownership ---
    try {
        $stmt = $db->prepare("SELECT {$map['author_col']} FROM {$map['table']} WHERE {$map['id_col']} = :id");
        $stmt->execute([':id' => $id]);
        $item_author = $stmt->fetchColumn();

        if ($item_author === false) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Publicación no encontrada.']);
            return;
        }

        if ($item_author !== $username) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'error' => 'No tienes permiso para modificar esta publicación.']);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Ownership check failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error del servidor al verificar permisos.']);
        return;
    }

    // --- Perform Action ---
    try {
        switch ($action) {
            case 'delete':
                $stmt = $db->prepare("DELETE FROM {$map['table']} WHERE {$map['id_col']} = :id");
                $stmt->execute([':id' => $id]);
                echo json_encode(['success' => true]);
                break;

            case 'edit':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($title) || empty($description)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'El título y la descripción no pueden estar vacíos.']);
                    return;
                }
                
                // For hilos, the description is JSON from Quill. We don't modify it here, just save it.
                // For repositorio, it's plain text.
                $desc_to_save = $description;

                $stmt = $db->prepare("UPDATE {$map['table']} SET {$map['title_col']} = :title, {$map['desc_col']} = :description WHERE {$map['id_col']} = :id");
                $stmt->execute([':title' => $title, ':description' => $desc_to_save, ':id' => $id]);

                // Create snippet for the response
                $snippet = htmlspecialchars(strip_tags($description));
                 if (mb_strlen($snippet) > 150) {
                    $snippet = mb_substr($snippet, 0, 150) . '...';
                }

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'type' => $type,
                        'title' => htmlspecialchars($title),
                        'description' => $snippet
                    ]
                ]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
                break;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Action '{$action}' failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => "La acción '{$action}' falló en el servidor."]);
    }
}
?>