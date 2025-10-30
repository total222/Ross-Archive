<?php
// Simple helper to peek the upload log (for debugging). Use with care.
$logFile = __DIR__ . '/../../storage_upload.log';
header('Content-Type: text/plain; charset=utf-8');
if(!file_exists($logFile)){
    echo "No log file found\n"; exit;
}
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
 $count = isset($_GET['lines']) ? (int)$_GET['lines'] : 200;
 if($count <= 0) $count = 200;
 $start = max(0, count($lines) - $count);
 for($i=$start;$i<count($lines);$i++) echo $lines[$i] . "\n";
?>
