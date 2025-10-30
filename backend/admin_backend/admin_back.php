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

    $action = $_GET['action'] ?? 'get_data';
    $type = $_GET['type'] ?? 'usuarios'; // usuarios, items, hilos

    if($action === 'get_counts'){
        // Conteos para las tarjetas estad�sticas
        $counts = [];

        // Count usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
        $counts['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Count items con visibilidad false (pendientes)
        $stmt = $db->query("SELECT COUNT(*) as total FROM items WHERE visibilidad = false");
        $counts['items_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Count total items
        $stmt = $db->query("SELECT COUNT(*) as total FROM items");
        $counts['total_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Count hilos pendientes
        $stmt = $db->query("SELECT COUNT(*) as total FROM hilos WHERE estado_hilo = false");
        $counts['hilos_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode(['success' => true, 'counts' => $counts]);

    } elseif($action === 'get_data'){
        // Obtener datos seg�n el tipo
        $search = $_GET['search'] ?? '';
        $orderBy = $_GET['orderBy'] ?? 'id';
        $filterEstado = $_GET['filterEstado'] ?? 'all'; // all, true, false

        $data = [];

        if($type === 'usuarios'){
            $sql = "SELECT \"ID_user\", usuario, correo, estado FROM usuarios WHERE 1=1";
            $params = [];

            if($search !== ''){
                $sql .= " AND (usuario ILIKE :search OR correo ILIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if($filterEstado !== 'all'){
                $sql .= " AND estado = :estado";
                $params[':estado'] = ($filterEstado === 'true');
            }

            if($orderBy === 'az'){
                $sql .= " ORDER BY usuario ASC";
            } elseif($orderBy === 'id'){
                $sql .= " ORDER BY \"ID_user\" ASC";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif($type === 'items'){
            $sql = "SELECT \"ID_item\", usuario, titulo, ruta_archivo, visibilidad, formato FROM items WHERE 1=1";
            $params = [];

            if($search !== ''){
                $sql .= " AND (titulo ILIKE :search OR usuario ILIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if($filterEstado !== 'all'){
                $sql .= " AND visibilidad = :visibilidad";
                $params[':visibilidad'] = ($filterEstado === 'true');
            }

            if($orderBy === 'az'){
                $sql .= " ORDER BY titulo ASC";
            } elseif($orderBy === 'id'){
                $sql .= " ORDER BY \"ID_item\" ASC";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif($type === 'hilos'){
            $sql = "SELECT \"ID_hilo\", titulo_hilo, contenido_hilo, autor_hilo, fecha_hilo, estado_hilo FROM hilos WHERE 1=1";
            $params = [];

            if($search !== ''){
                $sql .= " AND (titulo_hilo ILIKE :search OR autor_hilo ILIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if($filterEstado !== 'all'){
                $sql .= " AND estado_hilo = :estado";
                $params[':estado'] = ($filterEstado === 'true');
            }

            if($orderBy === 'az'){
                $sql .= " ORDER BY titulo_hilo ASC";
            } elseif($orderBy === 'id'){
                $sql .= " ORDER BY \"ID_hilo\" ASC";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'data' => $data, 'type' => $type]);

    } elseif($action === 'update_estado'){
        // Actualizar estado de usuarios, items o hilos
        $id = $_POST['id'] ?? 0;
        $newEstado = $_POST['estado'] ?? 'true';
        $estadoBool = ($newEstado === 'true');

        if($type === 'usuarios'){
            $stmt = $db->prepare("UPDATE usuarios SET estado = :estado WHERE \"ID_user\" = :id");
            $stmt->execute([':estado' => $estadoBool, ':id' => $id]);

        } elseif($type === 'items'){
            $stmt = $db->prepare("UPDATE items SET visibilidad = :visibilidad WHERE \"ID_item\" = :id");
            $stmt->execute([':visibilidad' => $estadoBool, ':id' => $id]);

        } elseif($type === 'hilos'){
            $stmt = $db->prepare("UPDATE hilos SET estado_hilo = :estado WHERE \"ID_hilo\" = :id");
            $stmt->execute([':estado' => $estadoBool, ':id' => $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);

    } elseif($action === 'delete'){
        // Eliminar items o hilos (y sus archivos de GCS)
        $id = $_POST['id'] ?? 0;

        if($type === 'items'){
            // Obtener ruta del archivo antes de eliminar
            $stmt = $db->prepare("SELECT ruta_archivo FROM items WHERE \"ID_item\" = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if($item && !empty($item['ruta_archivo'])){
                // Delete from GCS
                try {
                    require_once __DIR__ . '/../../vendor/autoload.php';
                    $envCred = getenv('GOOGLE_APLICATION_CREDENTIAL');

                    if($envCred && trim($envCred) !== ''){
                        $opts = [];

                        if(file_exists($envCred)){
                            $opts['keyFilePath'] = $envCred;
                        } else {
                            $decoded = json_decode($envCred, true);
                            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
                                $opts['keyFile'] = $decoded;
                            }
                        }

                        if(!empty($opts)){
                            $storage = new Google\Cloud\Storage\StorageClient($opts);

                            // Parse gs://bucket/path
                            if(preg_match('#^gs://([^/]+)/(.+)$#', $item['ruta_archivo'], $matches)){
                                $bucketName = $matches[1];
                                $objectName = $matches[2];

                                $bucket = $storage->bucket($bucketName);
                                $object = $bucket->object($objectName);
                                $object->delete();
                            }
                        }
                    }
                } catch(Exception $e) {
                    error_log("Error deleting GCS file: " . $e->getMessage());
                }
            }

            // Delete from database
            $stmt = $db->prepare("DELETE FROM items WHERE \"ID_item\" = :id");
            $stmt->execute([':id' => $id]);

        } elseif($type === 'hilos'){
            // Delete hilo and related comments
            $stmt = $db->prepare("DELETE FROM comentarios_hilo WHERE \"ID_hilo_comentario\" = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $db->prepare("DELETE FROM hilos WHERE \"ID_hilo\" = :id");
            $stmt->execute([':id' => $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Eliminado correctamente']);
    }

    db_disconnect($db);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
