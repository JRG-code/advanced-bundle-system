<?php
/**
 * Complete File Verification Script for Advanced Bundle System
 * Upload this to /wp-content/plugins/advanced-bundle-system/
 * Access via: https://yoursite.com/wp-content/plugins/advanced-bundle-system/check-files.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<pre>';
echo "=== Advanced Bundle System - Complete File Check ===\n\n";

$plugin_dir = __DIR__;
echo "Plugin Directory: $plugin_dir\n\n";

// List of all required files
$required_files = [
    'advanced-bundle-system.php',
    'includes/class-abs-admin.php',
    'includes/class-abs-cart.php',
    'includes/class-abs-frontend.php',
    'includes/class-abs-order.php',
    'includes/class-abs-personalization.php',
    'includes/class-abs-product-type.php',
    'includes/class-abs-settings.php',
    'includes/class-abs-stock.php',
    'includes/class-wc-product-bundle.php',
    'assets/css/admin.css',
    'assets/css/frontend.css',
    'assets/js/admin.js',
    'assets/js/frontend.js',
    'plugin-update-checker/plugin-update-checker.php',
    'plugin-update-checker/Parsedown.php',
];

echo "=== CHECKING REQUIRED FILES ===\n";
echo str_repeat("=", 80) . "\n";

$missing_files = [];
$corrupted_files = [];

foreach ($required_files as $file) {
    $filepath = $plugin_dir . '/' . $file;

    if (!file_exists($filepath)) {
        echo "❌ MISSING: $file\n";
        $missing_files[] = $file;
    } else {
        $size = filesize($filepath);

        // Check if file is suspiciously small (likely corrupted)
        if ($size < 10) {
            echo "⚠️  CORRUPTED (too small): $file (size: $size bytes)\n";
            $corrupted_files[] = $file;
        } else {
            echo "✅ OK: $file (size: " . number_format($size) . " bytes)\n";

            // For PHP files, check syntax
            if (substr($file, -4) === '.php') {
                $syntax_check = shell_exec("php -l " . escapeshellarg($filepath) . " 2>&1");
                if (strpos($syntax_check, 'No syntax errors') === false) {
                    echo "   ⚠️  SYNTAX ERROR: " . trim($syntax_check) . "\n";
                    $corrupted_files[] = $file;
                }
            }
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Check main plugin file in detail
echo "=== DETAILED CHECK: advanced-bundle-system.php ===\n";
echo str_repeat("=", 80) . "\n";

$main_file = $plugin_dir . '/advanced-bundle-system.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    $lines = file($main_file);

    echo "File size: " . number_format(strlen($content)) . " bytes\n";
    echo "Line count: " . count($lines) . "\n\n";

    // Check for BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "⚠️  WARNING: UTF-8 BOM detected!\n\n";
    }

    // Check version
    if (preg_match('/Version:\s*([0-9.]+)/', $content, $matches)) {
        echo "Detected Version: " . $matches[1] . "\n\n";
    }

    // Show lines 20-30 (where the error occurs)
    echo "Lines 20-30:\n";
    echo str_repeat("-", 80) . "\n";
    for ($i = 19; $i < min(30, count($lines)); $i++) {
        $line_num = $i + 1;
        $line = $lines[$i];
        echo sprintf("%3d: %s", $line_num, $line);

        // Check line 26 specifically
        if ($line_num === 26) {
            echo "     ^ This is line 26 (the error line)\n";
            echo "     Hex: " . bin2hex(trim($line)) . "\n";
        }
    }
    echo str_repeat("-", 80) . "\n\n";

    // Syntax check
    echo "PHP Syntax Check:\n";
    $syntax = shell_exec("php -l " . escapeshellarg($main_file) . " 2>&1");
    echo $syntax . "\n";

} else {
    echo "❌ Main plugin file NOT FOUND!\n\n";
}

// Check PHP version and extensions
echo "\n=== SERVER ENVIRONMENT ===\n";
echo str_repeat("=", 80) . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "WooCommerce Active: " . (class_exists('WooCommerce') ? 'YES' : 'NO') . "\n";

// Check OPcache
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    echo "OPcache Status: " . ($opcache['opcache_enabled'] ? 'ENABLED' : 'DISABLED') . "\n";
    if ($opcache['opcache_enabled']) {
        echo "⚠️  OPcache is enabled - this may cache corrupted files!\n";
        echo "   Recommendation: Clear OPcache or disable temporarily\n";
    }
} else {
    echo "OPcache: Not available\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
echo str_repeat("=", 80) . "\n";

if (empty($missing_files) && empty($corrupted_files)) {
    echo "✅ All files present and valid!\n";
    echo "\nIf you're still seeing errors, the issue is likely:\n";
    echo "1. OPcache caching old/corrupted version\n";
    echo "2. PHP version incompatibility\n";
    echo "3. File permissions issue\n";
    echo "4. Server-side caching\n";
} else {
    if (!empty($missing_files)) {
        echo "❌ MISSING FILES (" . count($missing_files) . "):\n";
        foreach ($missing_files as $file) {
            echo "   - $file\n";
        }
        echo "\n";
    }

    if (!empty($corrupted_files)) {
        echo "⚠️  CORRUPTED FILES (" . count($corrupted_files) . "):\n";
        foreach ($corrupted_files as $file) {
            echo "   - $file\n";
        }
        echo "\n";
    }

    echo "SOLUTION: Re-upload these files from a fresh plugin download\n";
}

// Recommendations
echo "\n=== RECOMMENDATIONS ===\n";
echo str_repeat("=", 80) . "\n";
echo "1. Clear OPcache (if enabled)\n";
echo "2. Delete entire plugin folder and re-upload fresh\n";
echo "3. Verify file permissions (should be 644 for files, 755 for folders)\n";
echo "4. Check PHP error logs for more details\n";
echo "5. Disable any caching plugins temporarily\n";

echo "\n" . str_repeat("=", 80) . "\n";
echo "Check complete!\n";
echo '</pre>';
