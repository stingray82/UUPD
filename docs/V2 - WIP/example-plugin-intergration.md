### Plugin Integration (UUPD 2.0)

add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/includes/updater.php';

    \UUPD\V2\UUPD_Updater_V2::register([
        'vendor'      => 'your-company',
        'plugin_file' => plugin_basename( __FILE__ ),
        'slug'        => 'example-plugin',
        'name'        => 'Example Plugin',
        'version'     => '1.0.0',
        'server'      => 'https://github.com/your/repo',
    ]);
}, 1);
