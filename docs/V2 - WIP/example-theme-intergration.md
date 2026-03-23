### Theme Integration (UUPD 2.0)

add_action( 'after_setup_theme', function() {
    require_once get_stylesheet_directory() . '/includes/updater.php';

    add_action( 'admin_init', function() {
        \UUPD\V2\UUPD_Updater_V2::register([
            'vendor'    => 'your-company',
            'slug'      => 'example-theme',
            'real_slug' => 'example-theme',
            'name'      => 'Example Theme',
            'version'   => '1.0.0',
            'server'    => 'https://github.com/your/repo',
        ]);
    });
});
