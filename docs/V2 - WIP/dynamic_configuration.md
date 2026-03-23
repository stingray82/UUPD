🧠 Dynamic Configuration (UUPD 2.0)

\UUPD\V2\UUPD_Updater_V2::register([
    'vendor'           => 'my-company',
    'plugin_file'      => plugin_basename( __FILE__ ),
    'slug'             => 'my-plugin',
    'name'             => 'My Plugin',
    'version'          => MY_PLUGIN_VERSION,
    'server'           => 'https://example.com/updates/',
    'allow_prerelease' => get_option('my_plugin_allow_prerelease', false),
]);

Key change:
- vendor is REQUIRED
- config is now vendor-scoped internally
