<?php
/**
 * Usage:
 * php generate_index.php plugin.php changelog.txt output_path github_user static_domain [slug_override] [repo_name_override]
 */

function get_arg_or_env($index, $env_var, $default = '') {
    global $argv;
    return $argv[$index] ?? getenv($env_var) ?? $default;
}

$plugin_file    = get_arg_or_env(1, 'PLUGIN_FILE');
$changelog_file = get_arg_or_env(2, 'CHANGELOG_FILE');
$output_dir     = rtrim(get_arg_or_env(3, 'STATIC_SUBFOLDER'), "/\\");
$github_user    = get_arg_or_env(4, 'GITHUB_USER');
$static_domain  = rtrim(get_arg_or_env(5, 'CDN_PATH'), "/");
$slug           = get_arg_or_env(6, 'REPO_NAME');
$repo_name      = get_arg_or_env(7, 'REPO_NAME');
$static_file    = get_arg_or_env(8, 'STATIC_FILE');

if (!file_exists($plugin_file)) exit("\n\u{274C} Plugin file missing: $plugin_file\n");
if (!file_exists($changelog_file)) exit("\n\u{274C} Changelog file missing: $changelog_file\n");
if (!file_exists($static_file)) exit("\n\u{274C} Static file missing: $static_file\n");
if (!is_dir($output_dir)) {
    if (!mkdir($output_dir, 0775, true)) exit("\n\u{274C} Failed to create output directory: $output_dir\n");
}

$headers = read_plugin_headers($plugin_file);
if (empty($headers['Version']) || empty($headers['Plugin Name'])) {
    exit("\n\u{274C} Missing Version or Plugin Name in headers.\n");
}

$zip_filename = $slug . '.zip';

$sections = parse_readme_sections($static_file);
$sections['changelog'] = parse_changelog($changelog_file);

$json = [
    'slug'            => $slug,
    'name'            => $headers['Plugin Name'],
    'version'         => $headers['Version'],
    'author'          => $headers['Author'] ?? 'Unknown',
    'author_homepage' => $headers['Author URI'] ?? '',
    'requires_php'    => $headers['Requires PHP'] ?? '',
    'requires'        => $headers['Requires at least'] ?? '',
    'tested'          => $headers['Tested up to'] ?? '',
    'sections'        => $sections,
    'last_updated'    => date('Y-m-d H:i:s'),
    'download_url'    => "https://github.com/{$github_user}/{$repo_name}/releases/latest/download/{$zip_filename}",
    'banners' => [
        'low' => "$static_domain/banner-772x250.png"
    ],
    'icons' => [
        '1x' => "$static_domain/icon-128.png",
        '2x' => "$static_domain/icon-256.png",
    ]
];

$output_file = "$output_dir/index.json";
file_put_contents($output_file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$info_txt = <<<EOT
This plugin's update metadata is available at:

{$static_domain}/index.json

Use this URL as the 'server' parameter when configuring the updater.
EOT;

file_put_contents($output_dir . '/info.txt', $info_txt);
echo "\n\u{2705} index.json written to: $output_file\n";

// ─────────── Helpers ───────────

function read_plugin_headers($file) {
    $headers = [
        'Plugin Name'       => '',
        'Version'           => '',
        'Requires at least' => '',
        'Tested up to'      => '',
        'Requires PHP'      => '',
        'Author'            => '',
        'Author URI'        => '',
    ];
    $data = file_exists($file) ? file_get_contents($file) : '';
    foreach ($headers as $key => $val) {
        if (preg_match('/' . preg_quote($key, '/') . ':\s*(.+)/i', $data, $m)) {
            $headers[$key] = trim($m[1]);
        }
    }
    return $headers;
}

function parse_changelog($file) {
    if (!file_exists($file)) return '';
    $lines = explode("\n", file_get_contents($file));
    $html = '';
    $open = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (preg_match('/^=+\s*(.+?)\s*=+$/', $line, $m)) {
            if ($open) $html .= '</ul>';
            $html .= "<h4>" . htmlspecialchars($m[1]) . "</h4><ul>";
            $open = true;
        } else {
            $html .= "<li>" . htmlspecialchars($line) . "</li>";
        }
    }

    if ($open) $html .= '</ul>';
    return $html;
}

function parse_readme_sections($file) {
    if (!file_exists($file)) return [];
    $text = file_get_contents($file);
    $sections = [];
    preg_match_all('/==\s*(.*?)\s*==\s*(.*?)(?=(?:==|$))/s', $text, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $key = strtolower(str_replace(' ', '_', trim($match[1])));
        $sections[$key] = nl2br(trim($match[2]));
    }
    return $sections;
}
