<?php
// backend/foro_backend/foro_back.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../global_scripts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = db_connect();
} catch (PDOException $e) {
    http_response_code(500);
    error_log("DB connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// --- Parameters for pagination and search ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$response_data = [];
$params = [':limit' => $limit, ':offset' => $offset];

// --- Base Query ---
// Fetches threads, author's profile picture, like count, and comment count.
    $username = $_SESSION['user']['name'] ?? $_SESSION['ross_user']['name_ross'] ?? null;

    $sql = "
        SELECT
            h.\"ID_hilo\",
            h.titulo_hilo,
            h.contenido_hilo,
            h.autor_hilo,
            h.fecha_hilo,
            h.likes,
            u.perfil AS autor_perfil,
            (SELECT COUNT(*) FROM comentarios_hilo WHERE \"ID_hilo_comentario\" = h.\"ID_hilo\") AS comment_count
    ";

    if ($username !== null) {
        $sql .= ", CASE WHEN l.usuario IS NOT NULL THEN true ELSE false END AS user_has_liked";
    } else {
        $sql .= ", false AS user_has_liked";
    }

    $sql .= "
        FROM
            hilos h
        LEFT JOIN
            usuarios u ON h.autor_hilo = u.usuario
    ";

    if ($username !== null) {
        $sql .= " LEFT JOIN hilo_likes l ON h.\"ID_hilo\" = l.ID_hilo AND l.usuario = :usuario";
        $params[':usuario'] = $username;
    }

    // --- Search Logic ---
    $where_clauses = [];
    if (!empty($search_query)) {
        $where_clauses[] = "h.titulo_hilo ILIKE :search";
        $params[':search'] = '%' . $search_query . '%';
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

$sql .= " ORDER BY h.fecha_hilo DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    foreach ($params as $key => &$val) {
        // PDO requires different binding for LIMIT/OFFSET
        if ($key == ':limit' || $key == ':offset') {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val);
        }
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        // Extract a snippet from the JSON content of the thread
        $description = '';
        if (!empty($row['contenido_hilo'])) {
            $content = json_decode($row['contenido_hilo'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($content)) {
                foreach ($content as $op) {
                    if (isset($op['insert']) && is_string($op['insert'])) {
                        $description .= $op['insert'] . ' ';
                    }
                }
            } else {
                log_error("Invalid JSON in contenido_hilo for ID_hilo: " . $row['ID_hilo']);
            }
        }

        $snippet = htmlspecialchars(strip_tags($description));
        if (mb_strlen($snippet) > 200) {
            $snippet = mb_substr($snippet, 0, 200) . '...';
        }

        $response_data[] = [
            'id' => $row['ID_hilo'],
            'titulo' => htmlspecialchars($row['titulo_hilo']),
            'descripcion' => $snippet,
            'autor' => htmlspecialchars($row['autor_hilo']),
            'autor_perfil' => $row['autor_perfil'],
            'fecha' => date("d M, Y", strtotime($row['fecha_hilo'])),
            'likes' => $row['likes'] ?? 0,
            'comentarios' => $row['comment_count'] ?? 0,
            'user_has_liked' => (bool)$row['user_has_liked']
        ];
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch forum data.', 'details' => $e->getMessage()]);
    exit;
} finally {
    db_disconnect($db);
}

echo json_encode($response_data);
?>