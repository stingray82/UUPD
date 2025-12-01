### **Theme Integration**

In your theme's `functions.php`:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_action( 'after_setup_theme', function() {
    require_once get_stylesheet_directory() . '/includes/updater.php';

    $updater_config = [
        'slug'            => 'example-theme',                 // Required: theme folder name
        'name'            => 'Example Theme',                 // Required: shown in update UI
        'version'         => '1.0.0',                         // Required: should match style.css Version
        'server'          => 'https://raw.githubusercontent.com/your-user/example-theme/main/uupd/',

        // Optional keys:
        'github_token'    => 'ghp_YourTokenHere',             // GitHub token (for private or rate-limited repos)
        'key'             => 'YourSecretKeyHere',             // Optional secret key for private update servers
        'textdomain'      => 'example-theme',                 // Optional, defaults to slug
        'allow_prerelease'=> false,                           // Optional: enable beta/rc updates (default: false)
        'cache_prefix'    => 'upd_',                        // optional, default 'upd_'
    ];

    add_action( 'admin_init', function() use ( $updater_config ) {
        \UUPD\V1\UUPD_Updater_V1::register( $updater_config );
    });
});
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
