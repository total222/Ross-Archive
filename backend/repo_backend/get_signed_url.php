<?php
/**
 * Generate signed URLs for private GCS objects
 * This allows temporary access to files stored in private buckets
 */

include __DIR__ . '/../global_scripts.php';


// This can be called via AJAX or included in other scripts
// Returns a signed URL valid for a specified duration

/**
 * Generate a signed URL for a GCS object
 * @param string $gcsPath - Full GCS path (gs://bucket/folder/file)
 * @param int $duration - URL validity duration in seconds (default: 1 hour)
 * @return string - Signed URL
 */
function generateSignedURL($gcsPath, $duration = 3600) {
    $logFile = __DIR__ . '/../../storage_upload.log';
    $log = function($m) use ($logFile){
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    };

    // Parse GCS path (gs://bucket/object)
    $gcsPath = trim($gcsPath);

    // Log the path being parsed
    $log("Parsing GCS path: $gcsPath");

    if (!preg_match('#^gs://([^/]+)/(.+)$#', $gcsPath, $matches)) {
        $log("Invalid GCS path format: $gcsPath");
        throw new Exception("Invalid GCS path format: $gcsPath");
    }

    $bucketName = $matches[1];
    $objectName = $matches[2];

    $log("Bucket: $bucketName, Object: $objectName");

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
            $log("Using credential file for signed URL: $envCred");
        } else {
            // Try to decode as JSON
            $decoded = json_decode($envCred, true);
            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
                $opts['keyFile'] = $decoded;
                $log('Using credential JSON from env for signed URL');
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
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        // Generate signed URL valid for specified duration
        $signedUrl = $object->signedUrl(
            new DateTime('+' . $duration . ' seconds'),
            [
                'version' => 'v4'
            ]
        );

        $log("Generated signed URL for: $objectName (valid for {$duration}s)");

        return $signedUrl;

    } catch(Exception $e) {
        $log('Signed URL generation failed: ' . $e->getMessage());
        throw $e;
    }
}

// If called directly via AJAX
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gcs_path'])){
    header('Content-Type: application/json');

    // Basic security: check if user is logged in
    if(empty($_SESSION['ross_user']) && empty($_SESSION['user'])){
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    try {
        $gcsPath = $_POST['gcs_path'];
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 3600; // Default 1 hour

        $signedUrl = generateSignedURL($gcsPath, $duration);

        echo json_encode([
            'success' => true,
            'url' => $signedUrl,
            'expires_in' => $duration
        ]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error generando URL: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>
