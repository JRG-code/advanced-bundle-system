<?php
/**
 * Syntax Verification Script for Advanced Bundle System
 * Upload this to your server and access it via browser
 * Example: https://yoursite.com/wp-content/plugins/advanced-bundle-system/verify-syntax.php
 */

// Check if running from CLI or web
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    echo '<pre>';
}

echo "=== Advanced Bundle System - Syntax Verification ===\n\n";

// Get the plugin main file path
$plugin_file = __DIR__ . '/advanced-bundle-system.php';

// Check if file exists
if (!file_exists($plugin_file)) {
    echo "ERROR: Plugin file not found at: $plugin_file\n";
    exit;
}

echo "Plugin file found: $plugin_file\n\n";

// Check file size
$filesize = filesize($plugin_file);
echo "File size: " . number_format($filesize) . " bytes\n\n";

// Check file encoding
echo "File encoding check:\n";
$file_command = shell_exec("file " . escapeshellarg($plugin_file));
echo "$file_command\n";

// Check for BOM
$handle = fopen($plugin_file, 'rb');
$bom = fread($handle, 3);
fclose($handle);

if ($bom === "\xEF\xBB\xBF") {
    echo "WARNING: UTF-8 BOM detected! This can cause issues.\n\n";
} else {
    echo "Good: No BOM detected.\n\n";
}

// Read first 30 lines and check for issues
echo "First 30 lines of the file:\n";
echo str_repeat("=", 60) . "\n";

$lines = file($plugin_file);
for ($i = 0; $i < min(30, count($lines)); $i++) {
    $line_num = $i + 1;
    $line = $lines[$i];

    // Check for special characters
    if (preg_match('/[^\x00-\x7F]/', $line)) {
        echo "Line $line_num [HAS SPECIAL CHARS]: " . $line;
    } else {
        echo "Line $line_num: " . $line;
    }
}

echo str_repeat("=", 60) . "\n\n";

// Try to check syntax using php -l
echo "PHP Syntax Check:\n";
$output = shell_exec("php -l " . escapeshellarg($plugin_file) . " 2>&1");
echo "$output\n\n";

// Check specific line 26
echo "Detailed check of line 26:\n";
if (isset($lines[25])) {
    $line26 = $lines[25];
    echo "Content: " . $line26 . "\n";
    echo "Length: " . strlen($line26) . " characters\n";
    echo "Hex dump: " . bin2hex($line26) . "\n\n";

    // Check each character
    echo "Character breakdown:\n";
    for ($i = 0; $i < strlen($line26); $i++) {
        $char = $line26[$i];
        $ord = ord($char);
        $hex = dechex($ord);
        $display = ($ord >= 32 && $ord < 127) ? $char : '·';
        echo "[$i] '$display' (ord:$ord hex:$hex) ";
        if ($i > 0 && ($i + 1) % 10 == 0) echo "\n";
    }
    echo "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Verification complete.\n";

if (!$is_cli) {
    echo '</pre>';
}
