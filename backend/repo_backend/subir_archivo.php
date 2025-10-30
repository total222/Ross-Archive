<?php
include __DIR__ . '/../global_scripts.php';
session_start();

// Verify request method
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $_SESSION['http_response'] = 405;
    header('Location: /excepciones/error.php');
    exit;
}

// CSRF validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['http_response'] = 401;
    header('Location: /excepciones/error.php');
    exit;
}

// Validate basic required fields (always required)
$basic_required_fields = ['autor', 'titulo', 'descripcion', 'fecha', 'formato', 'declaracion_legal'];
foreach($basic_required_fields as $field){
    if(empty($_POST[$field])){
        header('Location: /plataforma/ross-hub/repositorio.php?error=Faltan campos requeridos');
        exit;
    }
}

// Get formato first to determine conditional validation
$formato = htmlspecialchars(trim($_POST['formato']));

// Conditional validation based on formato
// document: all fields required
// video: all fields required
// image: categoria and idioma NOT required
// audio: categoria NOT required, idioma required

if($formato === 'document' || $formato === 'video'){
    // All fields required
    if(empty($_POST['categoria'])){
        header('Location: /plataforma/ross-hub/repositorio.php?error=Categoría es requerida para documentos y videos');
        exit;
    }
    if(empty($_POST['idioma'])){
        header('Location: /plataforma/ross-hub/repositorio.php?error=Idioma es requerido para documentos y videos');
        exit;
    }
} elseif($formato === 'audio'){
    // idioma required, categoria optional
    if(empty($_POST['idioma'])){
        header('Location: /plataforma/ross-hub/repositorio.php?error=Idioma es requerido para archivos de audio');
        exit;
    }
}
// For image: both categoria and idioma are optional (no validation needed)

// Sanitize inputs
$autor = htmlspecialchars(trim($_POST['autor']));
$titulo = htmlspecialchars(trim($_POST['titulo']));
$descripcion = htmlspecialchars(trim($_POST['descripcion']));
$categoria = htmlspecialchars(trim($_POST['categoria'] ?? 'N/A'));
$fecha = htmlspecialchars(trim($_POST['fecha']));
$idioma = htmlspecialchars(trim($_POST['idioma'] ?? 'N/A'));
$licencia = htmlspecialchars(trim($_POST['licencia'] ?? ''));

// Get user from session
$usuario = '';
if(!empty($_SESSION['ross_user'])){
    $usuario = $_SESSION['ross_user']['name_ross'] ?? '';
} elseif(!empty($_SESSION['user'])){
    $usuario = $_SESSION['user']['name'] ?? '';
}

if(empty($usuario)){
    header('Location: /formularios/sesion.php?error=Debes iniciar sesión');
    exit;
}

// Validate main file upload
if(!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])){
    header('Location: /plataforma/ross-hub/repositorio.php?error=No se subió ningún archivo');
    exit;
}

$archivo = $_FILES['archivo'];
$evidencia_permiso = 'N/A';

// Handle permission evidence file if license is not selected
if(empty($licencia) && isset($_FILES['evidencia_permiso']) && is_uploaded_file($_FILES['evidencia_permiso']['tmp_name'])){
    $permiso_file = $_FILES['evidencia_permiso'];
    $allowed_permiso = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    if(!in_array($permiso_file['type'], $allowed_permiso)){
        header('Location: /plataforma/ross-hub/repositorio.php?error=Formato de evidencia de permiso no válido');
        exit;
    }

    // Generate unique name for permission file
    $permiso_ext = pathinfo($permiso_file['name'], PATHINFO_EXTENSION);
    $permiso_unique_name = 'permiso_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $permiso_ext;

    // Upload permission file to GCS
    try {
        $evidencia_permiso = uploadToGCS($permiso_file['tmp_name'], 'documents', $permiso_unique_name);
    } catch(Exception $e) {
        error_log("Error uploading permission file: " . $e->getMessage());
        header('Location: /plataforma/ross-hub/repositorio.php?error=Error al subir evidencia de permiso');
        exit;
    }
}

// Determine bucket folder based on format
$bucket_folder = '';
switch($formato){
    case 'document':
        $bucket_folder = 'documents';
        break;
    case 'image':
        $bucket_folder = 'images';
        break;
    case 'video':
        $bucket_folder = 'videos';
        break;
    case 'audio':
        $bucket_folder = 'music';
        break;
    default:
        $bucket_folder = 'documents';
}

// Generate unique filename
$file_ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
$unique_filename = 'repo_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;

// Upload main file to GCS
try {
    $ruta_archivo = uploadToGCS($archivo['tmp_name'], $bucket_folder, $unique_filename);
} catch(Exception $e) {
    error_log("Error uploading main file: " . $e->getMessage());
    header('Location: /plataforma/ross-hub/repositorio.php?error=Error al subir el archivo');
    exit;
}

// Insert into database
try {
    $db = db_connect();

    // Log para debugging
    $logFile = __DIR__ . '/../../storage_upload.log';
    $log = function($m) use ($logFile){
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    };

    $log("Attempting database insert for file: $titulo");

    $stmt = $db->prepare("INSERT INTO items (autor, usuario, titulo, descripcion, categoria, fecha, idioma, formato, licencia, evidencia_permiso, ruta_archivo, visibilidad, declaracion_legal, preview)
                          VALUES (:autor, :usuario, :titulo, :descripcion, :categoria, :fecha, :idioma, :formato, :licencia, :evidencia_permiso, :ruta_archivo, :visibilidad::boolean, :declaracion_legal, :preview)");

    $visibilidad = 'false'; // PostgreSQL boolean as string
    $declaracion = 'Aceptado'; // User accepted legal declaration
    $preview = 'N/A'; // Can be enhanced later

    $params = [
        ':autor' => $autor,
        ':usuario' => $usuario,
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':categoria' => $categoria,
        ':fecha' => $fecha,
        ':idioma' => $idioma,
        ':formato' => $formato,
        ':licencia' => $licencia ?: 'Sin licencia',
        ':evidencia_permiso' => $evidencia_permiso,
        ':ruta_archivo' => $ruta_archivo,
        ':visibilidad' => $visibilidad,
        ':declaracion_legal' => $declaracion,
        ':preview' => $preview
    ];

    $log("Parameters: " . json_encode($params));

    $stmt->execute($params);

    $log("Database insert successful");

    db_disconnect($db);

    header('Location: /plataforma/ross-hub/repositorio.php?success=Archivo subido exitosamente');
    exit;

} catch(PDOException $e) {
    $log("Database error: " . $e->getMessage());
    $log("SQL State: " . $e->getCode());
    error_log("Database error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    header('Location: /plataforma/ross-hub/repositorio.php?error=Error al guardar en la base de datos: ' . urlencode($e->getMessage()));
    exit;
}

/**
 * Upload file to Google Cloud Storage (private bucket)
 * @param string $tmpPath - Temporary file path
 * @param string $folder - Bucket folder (documents, images, videos, music)
 * @param string $filename - Unique filename
 * @return string - GCS object path (gs://bucket/folder/file)
 */
function uploadToGCS($tmpPath, $folder, $filename){
    $logFile = __DIR__ . '/../../storage_upload.log';
    $log = function($m) use ($logFile){
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    };

    $gcsBucket = 'ross_archive-bucket';
    $objectName = $folder . '/' . $filename;

    $envCred = getenv('GOOGLE_APLICATION_CREDENTIAL');

    if(!$envCred || trim($envCred) === ''){
        $log('GOOGLE_APLICATION_CREDENTIAL env var not set');
        throw new Exception('Credenciales de Google Cloud no configuradas');
    }

    try {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $opts = [];

        // Check if env variable is a file path
        if(file_exists($envCred)){
            $opts['keyFilePath'] = $envCred;
            $log("Using credential file: $envCred");
        } else {
            // Try to decode as JSON
            $decoded = json_decode($envCred, true);
            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
                $opts['keyFile'] = $decoded;
                $log('Using credential JSON from env');
            } else {
                throw new Exception('Invalid GOOGLE_APLICATION_CREDENTIAL format');
            }
        }

        // Extract project ID if available
        if(!empty($opts['keyFile']['project_id'])){
            $opts['projectId'] = $opts['keyFile']['project_id'];
        }

        // Initialize Google Cloud Storage client
        $storage = new Google\Cloud\Storage\StorageClient($opts);
        $bucket = $storage->bucket($gcsBucket);

        $log("Uploading to GCS private bucket: $objectName");

        // Upload file - DO NOT USE ACL (Uniform bucket-level access enabled)
        $object = $bucket->upload(
            fopen($tmpPath, 'r'),
            [
                'name' => $objectName,
                'metadata' => [
                    'contentType' => mime_content_type($tmpPath)
                ]
            ]
        );

        // Return GCS path (not public URL since bucket is private)
        // We'll generate signed URLs when accessing files
        $gcsPath = 'gs://' . $gcsBucket . '/' . $objectName;
        $log("Upload successful to private bucket: $gcsPath");

        return $gcsPath;

    } catch(Exception $e) {
        $log('GCS upload failed: ' . $e->getMessage());
        $log('Trace: ' . $e->getTraceAsString());
        throw $e;
    }
}
?>
