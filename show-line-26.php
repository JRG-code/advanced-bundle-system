<?php
/**
 * Debug Line 26 - Shows exactly what's on the server
 * Upload to site root and access via browser
 */

echo '<html><head><meta charset="UTF-8"><title>Line 26 Debug</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#00ff00;}';
echo 'pre{background:#000;padding:15px;border:1px solid #00ff00;overflow-x:auto;}</style></head><body>';
echo '<h1>🔍 Line 26 Debug Tool</h1>';

// Try different possible paths
$possible_paths = [
    __DIR__ . '/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php',
    dirname(__DIR__) . '/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php',
    '/home/u125521932/domains/thecouplesbrand.com/public_html/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php',
];

$file = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $file = $path;
        break;
    }
}

if (!$file) {
    echo '<div style="color:#ff0000;padding:20px;border:2px solid #ff0000;">';
    echo '❌ Plugin file NOT FOUND!<br><br>';
    echo 'Tried these paths:<br>';
    foreach ($possible_paths as $path) {
        echo '- ' . htmlspecialchars($path) . '<br>';
    }
    echo '</div>';
} else {
    echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:20px 0;">';
    echo '✅ File found at: ' . htmlspecialchars($file);
    echo '</div>';

    $lines = file($file);
    $total_lines = count($lines);

    echo '<h2>📄 Lines 20-35:</h2>';
    echo '<pre>';
    for ($i = 19; $i < min(35, $total_lines); $i++) {
        $line_num = $i + 1;
        $line = $lines[$i];

        if ($line_num == 26) {
            echo '<span style="background:#ff0000;color:#fff;padding:2px 5px;">';
            echo sprintf("%3d: %s", $line_num, htmlspecialchars($line));
            echo '</span>';
        } else {
            echo sprintf("%3d: %s", $line_num, htmlspecialchars($line));
        }
    }
    echo '</pre>';

    // Detailed analysis of line 26
    if (isset($lines[25])) {
        echo '<h2>🔬 Line 26 Detailed Analysis:</h2>';
        echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;">';

        $line26 = $lines[25];
        echo '<strong>Content:</strong><br>';
        echo '<pre>' . htmlspecialchars($line26) . '</pre>';

        echo '<strong>Length:</strong> ' . strlen($line26) . ' bytes<br><br>';

        echo '<strong>Hex Dump:</strong><br>';
        echo '<pre>' . chunk_split(bin2hex($line26), 2, ' ') . '</pre>';

        echo '<strong>Character Analysis:</strong><br>';
        echo '<pre>';
        for ($i = 0; $i < min(strlen($line26), 100); $i++) {
            $char = $line26[$i];
            $ord = ord($char);
            $hex = str_pad(dechex($ord), 2, '0', STR_PAD_LEFT);

            if ($char == "\n") {
                $display = '\\n';
            } elseif ($char == "\r") {
                $display = '\\r';
            } elseif ($char == "\t") {
                $display = '\\t';
            } elseif ($ord >= 32 && $ord < 127) {
                $display = $char;
            } else {
                $display = '�';
            }

            printf("[%2d] '%s' (0x%s ord:%3d)  ", $i, $display, $hex, $ord);

            if (($i + 1) % 3 == 0) echo "\n";
        }
        echo '</pre>';

        echo '</div>';
    }

    // File info
    echo '<h2>📊 File Information:</h2>';
    echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;">';
    echo 'File size: ' . number_format(filesize($file)) . ' bytes<br>';
    echo 'Total lines: ' . number_format($total_lines) . '<br>';
    echo 'Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '<br>';
    echo 'Permissions: ' . substr(sprintf('%o', fileperms($file)), -4) . '<br>';

    // Check for BOM
    $content = file_get_contents($file);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo '<br><span style="color:#ff0000;">⚠️  UTF-8 BOM DETECTED!</span><br>';
    }

    echo '</div>';

    // PHP Syntax check
    echo '<h2>🔧 PHP Syntax Check:</h2>';
    echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;">';
    echo '<pre>';
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    echo htmlspecialchars($output);
    echo '</pre>';
    echo '</div>';
}

echo '<div style="margin-top:20px;padding:15px;border:1px solid #ffcc00;background:#333;">';
echo '<strong>💡 What to do with this info:</strong><br>';
echo '1. Check if line 26 matches what you expect<br>';
echo '2. Look for unusual characters in the hex dump<br>';
echo '3. If syntax check shows errors, the file is corrupted<br>';
echo '4. Share this output with support if needed<br>';
echo '</div>';

echo '</body></html>';
