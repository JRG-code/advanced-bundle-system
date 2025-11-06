<?php
/**
 * OPcache Cleaner
 * Upload to site root and access via browser
 * DELETE THIS FILE after fixing the issue (security risk)
 */

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>OPcache Clear</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#00ff00;}</style></head><body>';
echo '<h1>🧹 Advanced Bundle System - OPcache Cleaner</h1>';
echo '<div style="background:#000;padding:15px;border:2px solid #00ff00;margin:20px 0;">';

// Clear OPcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    if ($result) {
        echo '✅ <strong>OPcache CLEARED successfully!</strong><br>';
    } else {
        echo '❌ Failed to clear OPcache<br>';
    }
} else {
    echo '⚠️  OPcache not available on this server<br>';
}

// Clear file stat cache
clearstatcache(true);
echo '✅ File stat cache cleared!<br>';

echo '</div>';

// Show OPcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo '<h2>📊 OPcache Status:</h2>';
    echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;">';
    echo 'Enabled: ' . ($status['opcache_enabled'] ? '✅ YES' : '❌ NO') . '<br>';
    echo 'Cache Full: ' . ($status['cache_full'] ? '⚠️  YES' : '✅ NO') . '<br>';
    echo 'Cached Scripts: ' . number_format($status['opcache_statistics']['num_cached_scripts']) . '<br>';
    echo 'Hits: ' . number_format($status['opcache_statistics']['hits']) . '<br>';
    echo 'Misses: ' . number_format($status['opcache_statistics']['misses']) . '<br>';
    echo 'Memory Used: ' . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB<br>';
    echo 'Memory Free: ' . round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . ' MB<br>';
    echo '</div>';
} else {
    echo '<p>⚠️  Cannot get OPcache status</p>';
}

echo '<div style="margin-top:30px;padding:15px;background:#ffcc00;color:#000;border:2px solid #ff0000;">';
echo '<strong>⚠️  SECURITY WARNING:</strong><br>';
echo 'DELETE this file immediately after fixing your issue!<br>';
echo 'File location: ' . __FILE__;
echo '</div>';

// Instructions
echo '<div style="margin-top:20px;padding:15px;border:1px solid #00ff00;">';
echo '<h2>📋 Next Steps:</h2>';
echo '<ol>';
echo '<li>Go to WordPress and <strong>deactivate</strong> the Advanced Bundle System plugin</li>';
echo '<li><strong>Delete</strong> the entire plugin folder: /wp-content/plugins/advanced-bundle-system/</li>';
echo '<li><strong>Refresh this page</strong> to clear cache again</li>';
echo '<li><strong>Upload</strong> a fresh copy of the plugin</li>';
echo '<li><strong>Activate</strong> the plugin</li>';
echo '<li><strong>DELETE THIS FILE</strong> (clear-opcache.php)</li>';
echo '</ol>';
echo '</div>';

echo '</body></html>';
