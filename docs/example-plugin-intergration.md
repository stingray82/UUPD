### **Plugin Integration**

In your main plugin file (e.g. `my-plugin.php`):

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/includes/updater.php';

    \UUPD\V1\UUPD_Updater_V1::register([
        'plugin_file'     => plugin_basename( __FILE__ ),      // Required: "my-plugin/my-plugin.php"
        'slug'            => 'example-plugin',                 // Required: must match plugin slug or folder
        'name'            => 'Example Plugin',                 // Required: shown in update UI
        'version'         => '1.0.0',                          // Required: current plugin version
        'server'          => 'https://raw.githubusercontent.com/your-user/example-plugin/main/uupd/',

        // Optional keys:
        'github_token'    => 'ghp_YourTokenHere',              // GitHub token (for private repos or rate limits)
        'key'             => 'YourSecretKeyHere',              // Optional secret for private servers
        'textdomain'      => 'example-plugin',                 // Optional, defaults to slug
        'allow_prerelease'=> false,                            // Optional: allow beta/rc versions (default: false)
        'cache_prefix' 	  => 'upd_',                        // optional, default 'upd_'
    ]);
}, 1);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
