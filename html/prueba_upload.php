<?php
require_once __DIR__ . '/../vendor/autoload.php';
function logit($m){ file_put_contents(__DIR__ . '/../storage_upload.log', '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }

$env = getenv('GOOGLE_APLICATION_CREDENTIAL');
if(!$env){ echo "ENV var GOOGLE_APLICATION_CREDENTIAL not set\n"; logit('ENV var not set'); exit; }

if(file_exists($env)){
    echo "Using credential file path: $env\n";
    $opts = ['keyFilePath' => $env];
} else {
    // try parse JSON
    $dec = json_decode($env, true);
    if(json_last_error() === JSON_ERROR_NONE) {
        echo "Using credential JSON from env variable\n";
        $opts = ['keyFile' => $dec];
    } else {
        echo "Env var is neither path nor valid JSON\n"; logit('Env invalid: ' . substr($env,0,200)); exit;
    }
}

try{
    $client = new Google\Cloud\Storage\StorageClient($opts);
    echo "StorageClient instantiated\n";
    $bucket = $client->bucket('ross-archive_public-bucket');
    if($bucket){
        echo "Bucket exists or is accessible\n";
        // list objects (first 5)
        $objects = $bucket->objects(['maxResults' => 5]);
        foreach($objects as $o){ echo $o->name() . "\n"; }
    } else {
        echo "Bucket not found or inaccessible\n";
    }
}catch(Exception $e){
    echo 'Error: ' . $e->getMessage() . "\n";
    logit('prueba_upload error: ' . $e->getMessage());
}

?>
