<?php
/**
 * Plugin Debug Tool
 * Upload to: /wp-content/plugins/advanced-bundle-system/debug-plugin.php
 * Access via: https://yoursite.com/wp-content/plugins/advanced-bundle-system/debug-plugin.php
 */

// Load WordPress
$wp_load_paths = [
    '../../../../wp-load.php',
    '../../../wp-load.php',
    '../../wp-load.php',
    '../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
        $wp_loaded = true;
        break;
    }
}

echo '<html><head><meta charset="UTF-8"><title>Plugin Debug</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#00ff00;}';
echo '.ok{color:#00ff00;} .error{color:#ff0000;} .warning{color:#ffcc00;}';
echo 'pre{background:#000;padding:15px;border:1px solid #00ff00;overflow-x:auto;}</style></head><body>';
echo '<h1>🔍 Advanced Bundle System - Debug Tool</h1>';

if (!$wp_loaded) {
    echo '<div class="error">❌ Could not load WordPress!</div>';
    exit;
}

echo '<div class="ok">✅ WordPress loaded successfully</div><br>';

// Check WooCommerce
echo '<h2>1️⃣ WooCommerce Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';
if (class_exists('WooCommerce')) {
    echo '<span class="ok">✅ WooCommerce is active</span><br>';
    if (defined('WC_VERSION')) {
        echo 'WooCommerce Version: ' . WC_VERSION . '<br>';
    }
} else {
    echo '<span class="error">❌ WooCommerce is NOT active!</span><br>';
    echo '<strong>This is likely the problem!</strong><br>';
}
echo '</div>';

// Check if plugin is active
echo '<h2>2️⃣ Plugin Activation Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';
$active_plugins = get_option('active_plugins');
$plugin_file = 'advanced-bundle-system/advanced-bundle-system.php';

if (in_array($plugin_file, $active_plugins)) {
    echo '<span class="ok">✅ Plugin is marked as ACTIVE in WordPress</span><br>';
} else {
    echo '<span class="error">❌ Plugin is NOT active in WordPress!</span><br>';
    echo '<strong>Solution: Activate the plugin in WordPress > Plugins</strong><br>';
}
echo '</div>';

// Check if plugin files exist
echo '<h2>3️⃣ Required Files Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

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
];

$missing = [];
foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo '<span class="ok">✅ ' . $file . '</span> (' . filesize($path) . ' bytes)<br>';
    } else {
        echo '<span class="error">❌ MISSING: ' . $file . '</span><br>';
        $missing[] = $file;
    }
}

if (!empty($missing)) {
    echo '<br><strong class="error">Missing ' . count($missing) . ' files! Re-upload the plugin.</strong><br>';
}
echo '</div>';

// Check if plugin constants are defined
echo '<h2>4️⃣ Plugin Constants Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

$constants = ['ABS_VERSION', 'ABS_PLUGIN_DIR', 'ABS_PLUGIN_URL', 'ABS_PLUGIN_BASENAME'];
foreach ($constants as $constant) {
    if (defined($constant)) {
        echo '<span class="ok">✅ ' . $constant . '</span> = ' . constant($constant) . '<br>';
    } else {
        echo '<span class="error">❌ ' . $constant . ' not defined</span><br>';
    }
}
echo '</div>';

// Check if classes exist
echo '<h2>5️⃣ Plugin Classes Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

$classes = [
    'Advanced_Bundle_System',
    'WC_Product_Bundle',
    'ABS_Product_Type',
    'ABS_Admin',
    'ABS_Frontend',
    'ABS_Cart',
    'ABS_Stock',
    'ABS_Settings',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo '<span class="ok">✅ Class exists: ' . $class . '</span><br>';
    } else {
        echo '<span class="error">❌ Class NOT found: ' . $class . '</span><br>';
    }
}
echo '</div>';

// Check product types
echo '<h2>6️⃣ Product Type Registration Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

if (function_exists('wc_get_product_types')) {
    $product_types = wc_get_product_types();
    if (isset($product_types['bundle'])) {
        echo '<span class="ok">✅ "bundle" product type is registered!</span><br>';
        echo 'Label: ' . $product_types['bundle'] . '<br>';
    } else {
        echo '<span class="error">❌ "bundle" product type is NOT registered!</span><br>';
        echo '<strong>This is the main problem!</strong><br><br>';
        echo 'Available product types:<br>';
        echo '<pre>' . print_r($product_types, true) . '</pre>';
    }
} else {
    echo '<span class="error">❌ WooCommerce function wc_get_product_types() not available</span><br>';
}
echo '</div>';

// Check hooks
echo '<h2>7️⃣ WordPress Hooks Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

global $wp_filter;

$important_hooks = [
    'plugins_loaded',
    'init',
    'woocommerce_loaded',
    'product_type_selector',
];

foreach ($important_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        echo '<span class="ok">✅ Hook "' . $hook . '" has ' . count($wp_filter[$hook]) . ' callbacks</span><br>';
    } else {
        echo '<span class="warning">⚠️  Hook "' . $hook . '" has no callbacks</span><br>';
    }
}
echo '</div>';

// Check WordPress debug log
echo '<h2>8️⃣ Error Log Check</h2>';
echo '<div style="background:#000;padding:15px;border:1px solid #00ff00;margin:10px 0;">';

if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<span class="ok">✅ WP_DEBUG is enabled</span><br>';
} else {
    echo '<span class="warning">⚠️  WP_DEBUG is disabled</span><br>';
    echo 'Enable it in wp-config.php to see errors<br>';
}

$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    echo '<br>Latest errors from debug.log:<br>';
    echo '<pre>';
    $log_content = file_get_contents($debug_log);
    $lines = explode("\n", $log_content);
    $relevant_lines = array_filter($lines, function($line) {
        return stripos($line, 'bundle') !== false || stripos($line, 'abs') !== false;
    });
    echo htmlspecialchars(implode("\n", array_slice($relevant_lines, -20)));
    echo '</pre>';
} else {
    echo 'No debug.log found<br>';
}
echo '</div>';

// Recommendations
echo '<h2>📋 Recommendations</h2>';
echo '<div style="background:#333;padding:15px;border:2px solid #ffcc00;margin:10px 0;">';

if (!class_exists('WooCommerce')) {
    echo '<strong class="error">❌ CRITICAL: Install and activate WooCommerce first!</strong><br>';
}

if (!in_array($plugin_file, $active_plugins)) {
    echo '<strong class="error">❌ Activate the Advanced Bundle System plugin!</strong><br>';
}

if (!empty($missing)) {
    echo '<strong class="error">❌ Re-upload missing files!</strong><br>';
}

if (!class_exists('ABS_Product_Type')) {
    echo '<strong class="error">❌ Plugin classes not loaded - check for PHP errors</strong><br>';
}

if (class_exists('WooCommerce') && in_array($plugin_file, $active_plugins) && empty($missing) && !isset($product_types['bundle'])) {
    echo '<strong class="warning">⚠️  Plugin files are OK but bundle type not registered</strong><br>';
    echo 'This could be a hook priority issue. Try deactivating and reactivating the plugin.<br>';
}

echo '</div>';

echo '<div style="margin-top:20px;padding:15px;background:#000;border:1px solid #00ff00;">';
echo '<strong>💡 Next Steps:</strong><br>';
echo '1. Fix any errors shown above<br>';
echo '2. Deactivate and reactivate the plugin<br>';
echo '3. Clear all caches (OPcache, WordPress, browser)<br>';
echo '4. If still not working, enable WP_DEBUG and check debug.log<br>';
echo '</div>';

echo '</body></html>';
