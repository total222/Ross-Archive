<?php
// backend/foro_backend/hilo_back.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../global_scripts.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hilo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($hilo_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No thread ID provided.']);
    exit;
}

try {
    $db = db_connect();
} catch (PDOException $e) {
    http_response_code(500);
    error_log("DB connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

    $username = $_SESSION['user']['name'] ?? $_SESSION['ross_user']['name_ross'] ?? null;

    // Fetch main thread content
    $sql_hilo = "
        SELECT
            h.\"ID_hilo\",
            h.titulo_hilo,
            h.contenido_hilo,
            h.autor_hilo,
            h.fecha_hilo,
            h.likes,
            u.perfil AS autor_perfil
    ";

    if ($username !== null) {
        $sql_hilo .= ", CASE WHEN l.usuario IS NOT NULL THEN true ELSE false END AS user_has_liked";
    } else {
        $sql_hilo .= ", false AS user_has_liked";
    }

    $sql_hilo .= "
        FROM
            hilos h
        LEFT JOIN
            usuarios u ON h.autor_hilo = u.usuario
    ";

    if ($username !== null) {
        $sql_hilo .= " LEFT JOIN hilo_likes l ON h.\"ID_hilo\" = l.ID_hilo AND l.usuario = :usuario";
    }

    $sql_hilo .= " WHERE h.\"ID_hilo\" = :hilo_id;
    ";
// Fetch comments
$sql_comentarios = "
    SELECT 
        c.\"ID_comentario_hilo\" AS \"ID_comentario\",
        c.contenido_comentario_hilo AS contenido_comentario,
        c.autor_comentario_hilo AS autor_comentario,
        c.fecha_comentario_hilo AS fecha_comentario,
        c.hora AS hora_comentario,
        c.like_comentario AS likes_comentario,
        c.respuesta_hacia,
        u.perfil AS autor_perfil
    FROM 
        comentarios_hilo c
    LEFT JOIN
        usuarios u ON c.autor_comentario_hilo = u.usuario
    WHERE c.\"ID_hilo_comentario\" = :hilo_id
    ORDER BY c.fecha_comentario_hilo ASC;
";

try {
    // Fetch thread
    $stmt_hilo = $db->prepare($sql_hilo);
    $stmt_hilo->bindParam(':hilo_id', $hilo_id, PDO::PARAM_INT);
    if ($username !== null) {
        $stmt_hilo->bindParam(':usuario', $username, PDO::PARAM_STR);
    }
    $stmt_hilo->execute();
    $hilo = $stmt_hilo->fetch(PDO::FETCH_ASSOC);

    if (!$hilo) {
        http_response_code(404);
        echo json_encode(['error' => 'Thread not found.']);
        exit;
    }

    // Fetch comments
    $stmt_comentarios = $db->prepare($sql_comentarios);
    $stmt_comentarios->bindParam(':hilo_id', $hilo_id, PDO::PARAM_INT);
    $stmt_comentarios->execute();
    $comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize and structure data
    $hilo_data = [
        'id' => $hilo['ID_hilo'],
        'titulo' => htmlspecialchars($hilo['titulo_hilo']),
        'contenido' => json_decode($hilo['contenido_hilo']), // Keep as object for Quill
        'autor' => htmlspecialchars($hilo['autor_hilo']),
        'autor_perfil' => $hilo['autor_perfil'],
        'fecha' => date("d M, Y", strtotime($hilo['fecha_hilo'])),
        'likes' => $hilo['likes'] ?? 0,
        'user_has_liked' => (bool)$hilo['user_has_liked']
    ];

    $comentarios_data = [];
    foreach ($comentarios as $comentario) {
        error_log("Raw fecha_comentario: " . $comentario['fecha_comentario']);
        $timestamp = strtotime($comentario['fecha_comentario']);
        error_log("strtotime result: " . ($timestamp ? date('Y-m-d H:i:s', $timestamp) : 'Failed to parse'));
        $comentarios_data[] = [
            'id' => $comentario['ID_comentario'],
            'contenido' => htmlspecialchars($comentario['contenido_comentario']),
            'autor' => htmlspecialchars($comentario['autor_comentario']),
            'autor_perfil' => $comentario['autor_perfil'],
            'fecha' => date("d M, Y", strtotime($comentario['fecha_comentario'])),
            'hora' => date("H:i", strtotime($comentario['hora_comentario'])),
            'likes' => $comentario['likes_comentario'] ?? 0,
            'respuesta_a' => $comentario['respuesta_hacia']
        ];
    }

    $response_data = [
        'hilo' => $hilo_data,
        'comentarios' => $comentarios_data
    ];

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch thread data.', 'details' => $e->getMessage()]);
    exit;
} finally {
    db_disconnect($db);
}

echo json_encode($response_data);
?>
