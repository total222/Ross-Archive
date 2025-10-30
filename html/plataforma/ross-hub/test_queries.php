<?php
/**
 * Test file to verify database queries and GCS integration
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Queries</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo "h2{color:#333;border-bottom:2px solid #fed90b;padding-bottom:10px;}";
echo "pre{background:#fff;padding:15px;border-left:4px solid #fed90b;overflow:auto;}";
echo ".success{color:green;} .error{color:red;} .info{color:blue;}";
echo "</style></head><body>";

echo "<h1>🔍 Repository Backend Tests</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    include __DIR__ . '/../../../backend/global_scripts.php';
    $db = db_connect();
    if($db) {
        echo "<p class='success'>✅ Database connection: SUCCESS</p>";
        echo "<pre>Connected to PostgreSQL database</pre>";
    } else {
        echo "<p class='error'>❌ Database connection: FAILED</p>";
        echo "<pre>Could not connect to database</pre>";
        exit;
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Check if items table exists
echo "<h2>2. Items Table Check</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ Items table exists</p>";
    echo "<pre>Total items in database: " . $result['total'] . "</pre>";
} catch(PDOException $e) {
    echo "<p class='error'>❌ Items table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check usuarios table
echo "<h2>3. Usuarios Table Check</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ Usuarios table exists</p>";
    echo "<pre>Total users in database: " . $result['total'] . "</pre>";
} catch(PDOException $e) {
    echo "<p class='error'>❌ Usuarios table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Test JOIN query (from obtain_info_repo.php)
echo "<h2>4. JOIN Query Test (with user status filter)</h2>";
try {
    $sql = "SELECT i.\"ID_item\", i.autor, i.usuario, i.titulo, i.descripcion, i.categoria, i.fecha, i.idioma, i.formato, i.licencia, i.ruta_archivo, i.visibilidad, i.preview
            FROM items i
            LEFT JOIN usuarios u ON i.usuario = u.usuario
            WHERE (u.estado = true OR u.estado IS NULL)
            AND i.visibilidad = true
            ORDER BY i.fecha DESC LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p class='success'>✅ JOIN query executed successfully</p>";
    echo "<pre>Found " . count($items) . " visible items from active users</pre>";

    if(count($items) > 0) {
        echo "<h3> :</h3>";
        foreach($items as $i => $item) {
            if($i >= 3) break; // Show only first 3
            echo "<pre>";
            echo "ID: " . $item['ID_item'] . "\n";
            echo "Título: " . htmlspecialchars($item['titulo']) . "\n";
            echo "Autor: " . htmlspecialchars($item['autor']) . "\n";
            echo "Usuario: " . htmlspecialchars($item['usuario']) . "\n";
            echo "Formato: " . htmlspecialchars($item['formato']) . "\n";
            echo "Fecha: " . htmlspecialchars($item['fecha']) . "\n";
            echo "Ruta: " . htmlspecialchars(substr($item['ruta_archivo'], 0, 50)) . "...\n";
            echo "</pre>";
        }
    } else {
        echo "<p class='info'>ℹ️ No visible items found. This could be normal if:</p>";
        echo "<ul>";
        echo "<li>No items have been uploaded yet</li>";
        echo "<li>All items are set to visibilidad = false</li>";
        echo "<li>All users are blocked (estado = false)</li>";
        echo "</ul>";
    }
} catch(PDOException $e) {
    echo "<p class='error'>❌ JOIN query error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>SQL State: " . $e->getCode() . "</pre>";
}

// Test 5: Check GCS credentials
echo "<h2>5. Google Cloud Storage Credentials Test</h2>";
$envCred = getenv('GOOGLE_APLICATION_CREDENTIAL');
if(!$envCred || trim($envCred) === ''){
    echo "<p class='error'>❌ GOOGLE_APLICATION_CREDENTIAL not set</p>";
    echo "<pre>Environment variable is empty or not configured</pre>";
} else {
    echo "<p class='success'>✅ GOOGLE_APLICATION_CREDENTIAL is set</p>";

    // Check if it's a file path or JSON
    if(file_exists($envCred)){
        echo "<pre>Type: File path\nPath: " . htmlspecialchars($envCred) . "\nFile exists: YES</pre>";
    } else {
        $decoded = json_decode($envCred, true);
        if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
            echo "<pre>Type: JSON string\nProject ID: " . ($decoded['project_id'] ?? 'N/A') . "</pre>";
        } else {
            echo "<p class='error'>❌ Invalid credential format</p>";
        }
    }
}

// Test 6: Test signed URL generation (if we have items)
echo "<h2>6. Signed URL Generation Test</h2>";
try {
    $stmt = $db->query("SELECT ruta_archivo FROM items WHERE visibilidad = true LIMIT 1");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if($item && !empty($item['ruta_archivo'])) {
        echo "<p class='info'>Testing with: " . htmlspecialchars($item['ruta_archivo']) . "</p>";

        include __DIR__ . '/../../../backend/repo_backend/get_signed_url.php';
        try {
            $signedUrl = generateSignedURL($item['ruta_archivo'], 3600);
            echo "<p class='success'>✅ Signed URL generated successfully</p>";
            echo "<pre>URL length: " . strlen($signedUrl) . " characters</pre>";
        } catch(Exception $e) {
            echo "<p class='error'>❌ Signed URL generation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ No items available to test signed URL generation</p>";
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Simulate obtain_info_repo.php response
echo "<h2>7. Simulate API Response (obtain_info_repo.php)</h2>";
try {
    include_once __DIR__ . '/../../../backend/repo_backend/get_signed_url.php';

    $sql = "SELECT i.\"ID_item\", i.autor, i.usuario, i.titulo, i.descripcion, i.categoria, i.fecha, i.idioma, i.formato, i.licencia, i.ruta_archivo, i.visibilidad, i.preview
            FROM items i
            LEFT JOIN usuarios u ON i.usuario = u.usuario
            WHERE (u.estado = true OR u.estado IS NULL)
            AND i.visibilidad = true
            ORDER BY i.fecha DESC LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate signed URLs
    foreach($items as &$item) {
        try {
            $item['download_url'] = generateSignedURL($item['ruta_archivo'], 14400);
        } catch(Exception $e) {
            $item['download_url'] = null;
            $item['error'] = 'No se pudo generar URL de descarga';
        }
    }

    $response = [
        'success' => true,
        'count' => count($items),
        'items' => $items
    ];

    echo "<p class='success'>✅ API response structure created</p>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

} catch(Exception $e) {
    echo "<p class='error'>❌ API simulation error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 8: Check session
echo "<h2>8. Session Check</h2>";

if(!empty($_SESSION['ross_user']) || !empty($_SESSION['user'])){
    echo "<p class='success'>✅ User session exists</p>";
    if(!empty($_SESSION['ross_user'])){
        echo "<pre>User type: ross_user\nName: " . htmlspecialchars($_SESSION['ross_user']['name_ross'] ?? 'N/A') . "</pre>";
    } else {
        echo "<pre>User type: user\nName: " . htmlspecialchars($_SESSION['user']['name'] ?? 'N/A') . "</pre>";
    }
} else {
    echo "<p class='error'>⚠️ No user session found</p>";
    echo "<pre>This is required for obtain_info_repo.php to work</pre>";
}

db_disconnect($db);

echo "<hr><p><strong>Test completed at " . date('Y-m-d H:i:s') . "</strong></p>";
echo "</body></html>";
?>