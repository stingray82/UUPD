🔁 Enabling Automatic Updates (UUPD 2.0)

UUPD integrates with WordPress auto-updates exactly as before.

⚠️ Note (v2.0): Slug is still used here — WordPress controls this, not UUPD.

Example (Plugin):

add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( $item->slug === 'your-plugin-slug' ) {
        return true;
    }
    return $update;
}, 10, 2 );
