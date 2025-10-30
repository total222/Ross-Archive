<?php
/**
 * Temporary file to clear OPcache
 * Delete this file after use
 */

if(function_exists('opcache_reset')){
    opcache_reset();
    echo "OPcache cleared successfully!";
} else {
    echo "OPcache is not enabled";
}

echo "<br><br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "OPcache Status: " . (function_exists('opcache_get_status') ? 'Enabled' : 'Disabled') . "<br>";

if(function_exists('opcache_get_status')){
    $status = opcache_get_status();
    echo "OPcache Memory Used: " . number_format($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB<br>";
    echo "OPcache Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "<br>";
}
?>