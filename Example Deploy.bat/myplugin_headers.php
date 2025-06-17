<?php
/**
 * update_plugin_headers.php
 * 
 * Usage: php update_plugin_headers.php path/to/plugin-file.php
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php {$argv[0]} path/to/plugin-file.php\n");
    exit(1);
}

$pluginFile = $argv[1];
if (!file_exists($pluginFile)) {
    fwrite(STDERR, "Plugin file not found: $pluginFile\n");
    exit(1);
}

// 1) Fetch latest WP version
function get_latest_wp_version() {
    $fallback = '6.7.3';
    $ctx = stream_context_create([
        'http'=>['timeout'=>5,'user_agent'=>'update_plugin_headers.php']
    ]);
    $j = @file_get_contents('https://api.wordpress.org/core/version-check/1.7/', false, $ctx);
    if ($j && ($data = json_decode($j, true))) {
        return $data['offers'][0]['current'] ?? $fallback;
    }
    fwrite(STDERR, "[warning] Could not fetch latest WP; using fallback {$fallback}\n");
    return $fallback;
}
$latest = get_latest_wp_version();

// 2) Read file
$content = file_get_contents($pluginFile);

// 3) Define header fields with defaults
$fields = [
    'Plugin Name'       => null,              // required
    'Description'       => null,              // required
    'Tested up to'      => null,              // we'll fill below
    'Requires at least' => '6.5',
    'Requires PHP'      => '8.0',
    'Version'           => '1.0',
    'Author'            => 'reallyusefulplugins.com',
    'Author URI'        => 'https://reallyusefulplugins.com',
    'License'           => 'GPL2',
    'License URI'       => 'https://www.gnu.org/licenses/gpl-2.0.html',
    'Text Domain'       => null,              // required
    'Website'           => 'https://reallyusefulplugins.com',
];

// 4) Extract existing header block
$pattern = '/^(<\?php\s*)(\/\*\*.*?\*\/)?\s*/s';
$headerBlock = '';
if (preg_match($pattern, $content, $m) && !empty($m[2])) {
    $headerBlock = $m[2];
}

// Helper to pull any existing header value
function extract_field($header, $field) {
    $pat = '/^\s*\*\s*'.preg_quote($field, '/').':\s*(.*?)\s*$/mi';
    return preg_match($pat, $header, $m) ? $m[1] : null;
}

// Seed from existing header
if ($headerBlock) {
    foreach ($fields as $key => $def) {
        if ($key === 'Tested up to') continue; // special case below
        $val = extract_field($headerBlock, $key);
        if ($val !== null && $val !== '') {
            $fields[$key] = $val;
        }
    }
    // for Tested up to, capture existing if any
    $fields['Tested up to'] = extract_field($headerBlock, 'Tested up to');
}

// 5) Prompt helper
function promptversion($label, $current, $default, $required = false) {
    if ($current !== null) {
        echo "{$label} (current: {$current}, default: {$default}): ";
    } else {
        echo "{$label} (default: {$default}): ";
    }
    $in = trim(fgets(STDIN));
    if ($in === '') {
        if ($required && $default === null) {
            echo "{$label} is required.\n";
            return prompt($label, $current, $default, $required);
        }
        return $default;
    }
    return $in;
}


function prompt($label, $current, $default, $required = false) {
    echo "{$label} (default: {$default}): ";
    $in = trim(fgets(STDIN));
    if ($in === '') {
        if ($required && $default === null) {
            echo "{$label} is required.\n";
            return prompt($label, $current, $default, $required);
        }
        return $default;
    }
    return $in;
}

// 6) Prompt for required fields
$fields['Version']      = prompt('Version',      extract_field($headerBlock,'Version'),      $fields['Version'],      true);
$fields['Plugin Name']  = prompt('Plugin Name',  extract_field($headerBlock,'Plugin Name'),  $fields['Plugin Name'],  true);
$fields['Description']  = prompt('Description',  extract_field($headerBlock,'Description'),  $fields['Description'],  true);
$fields['Text Domain']  = prompt('Text Domain',  extract_field($headerBlock,'Text Domain'),  $fields['Text Domain'],  true);

// 7) Prompt for Tested up to specially
$fields['Tested up to'] = promptversion(
    'Tested up to',
    extract_field($headerBlock,'Tested up to'),
    $latest,
    false
);

// 8) Prompt for other optionals
foreach (['Requires at least','Requires PHP','Author','Author URI','License','License URI','Website'] as $key) {
    $fields[$key] = prompt(
        $key,
        extract_field($headerBlock,$key),
        $fields[$key],
        false
    );
}

// 9) Sync any define('…_VERSION',…) to our Version
$content = preg_replace_callback(
    '/define\(\s*([\'"])([^\'"]+_VERSION)\1\s*,\s*([\'"])(.*?)\3\s*\)\s*;/',
    function($m) use($fields) {
        return "define({$m[1]}{$m[2]}{$m[1]}, {$m[3]}{$fields['Version']}{$m[3]});";
    },
    $content
);
echo "→ All *_VERSION defines set to '{$fields['Version']}'\n";

// 10) Rebuild header block with nice alignment
$maxLen = max(array_map('strlen', array_keys($fields))) + 1;
$fmt    = " * %-{$maxLen}s %s\n";

$newHeader = "/**\n";
foreach ($fields as $k => $v) {
    $newHeader .= sprintf($fmt, "{$k}:", $v);
}
$newHeader .= " */\n\n";

// 11) Insert or replace it after <?php
$newContent = preg_replace($pattern, "$1{$newHeader}", $content, 1);

// 12) Write back
if (file_put_contents($pluginFile, $newContent) !== false) {
    echo "→ Plugin headers and VERSION defines updated successfully in {$pluginFile}\n";
} else {
    fwrite(STDERR, "Failed to update the plugin file.\n");
    exit(1);
}
