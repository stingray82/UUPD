🧠 Dynamic Configuration (Advanced)
-----------------------------------

You can programmatically build your updater config to support staging, testing, or admin-controlled toggles:

```php
\UUPD\V1\UUPD_Updater_V1::register([
    'plugin_file'      => plugin_basename( __FILE__ ),
    'slug'             => 'my-plugin',
    'name'             => 'My Plugin',
    'version'          => MY_PLUGIN_VERSION,
    'server'           => 'https://example.com/updates/',
    'allow_prerelease' => get_option( 'my_plugin_allow_prerelease', false ),
    'github_token'     => get_option( 'my_plugin_github_token' ),
    'cache_prefix'     => 'upd_',
]);
```

You can create a simple checkbox in your plugin settings to toggle `allow_prerelease` for beta testers.
