🔁 Enabling Automatic Updates for Your Plugin or Theme
-----------------------------------------------------

UUPD works seamlessly with WordPress' built-in auto-update system.

If you'd like your plugin or theme to receive updates automatically (e.g. via
WP-Cron), you can add a filter to your code to enable this behavior manually.

### For Plugins

Add the following code inside your plugin:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( $item->slug === 'your-plugin-slug' ) {
        return true; // Always auto-update this plugin
    }
    return $update;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Replace `'your-plugin-slug'` with your plugin's actual slug (typically its
folder name).

###  For Themes

Add this in your theme's `functions.php`:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'auto_update_theme', function( $update, $item ) {
    if ( $item->slug === 'your-theme-folder' ) {
        return true; // Always auto-update this theme
    }
    return $update;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Replace `'your-theme-folder'` with your theme's directory name.

###  Conditional Example (Optional)

Want to auto-update only for beta testers or staging sites?

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( $item->slug === 'your-plugin-slug' && get_option( 'your_plugin_allow_prerelease' ) ) {
        return true;
    }
    return $update;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This lets you control auto-updates based on user settings or environment
conditions.

📝 WordPress will still perform version and compatibility checks as normal. UUPD
simply provides the update metadata, while WordPress decides whether and when to
install it.
