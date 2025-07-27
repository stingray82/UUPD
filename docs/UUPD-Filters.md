UUPD Filters Reference
======================

This document explains all available filters in the **UUPD (Universal Updater
Drop-in)** system and how to use them for customizing behavior.

 

🔧 `uupd/filter_config`
----------------------

**Purpose:** Allows full override or modification of the entire updater config
array.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/filter_config', function( $config ) {
    if ( $config['slug'] === 'example-plugin' ) {
        $config['allow_prerelease'] = true;
    }
    return $config;
});
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

🔧 `uupd/allow_prerelease`
-------------------------

**Purpose:** Override whether prereleases like beta/alpha should be considered
updateable.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/allow_prerelease', function( $allow, $slug ) {
    return get_option( 'my_plugin_allow_prerelease' ) === 'yes';
}, 10, 2);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd/server_url`
-----------------

**Purpose:** Dynamically override the update server URL (e.g., switch between
stable and dev branches).

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/server_url', function( $url, $slug ) {
    if ( $slug === 'example-plugin' ) {
        return 'https://my-dev-server.com/uupd/';
    }
    return $url;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd/remote_url`
-----------------

**Purpose:** Modify the final metadata URL used for fetching the `index.json`.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/remote_url', function( $url, $slug, $config ) {
    return add_query_arg( 'beta', 'true', $url );
}, 10, 3 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd/metadata_result`
----------------------

**Purpose:** Tweak the decoded JSON metadata object before it's cached or used.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/metadata_result', function( $meta, $slug, $config ) {
    $meta->tested = '6.8';
    return $meta;
}, 10, 3 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd/github_token_override`
----------------------------

**Purpose:** Provide GitHub tokens dynamically to avoid rate limits or access
private repos.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd/github_token_override', function( $token, $slug ) {
    return 'ghp_yourTokenHere';
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd/log`
----------

**Purpose:** Custom logger for debugging purposes when logging is enabled.

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_action( 'uupd/log', function( $msg, $slug ) {
    error_log( "[UUPD LOG][$slug] $msg" );
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

`uupd_success_cache_ttl` & `uupd_fetch_remote_error_ttl`
--------------------------------------------------------

**Purpose:** Control how long metadata is cached (successful or error cases).

**Example:**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd_success_cache_ttl', function( $ttl, $slug ) {
    return 2 * HOUR_IN_SECONDS;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'uupd_fetch_remote_error_ttl', function( $ttl, $slug ) {
    return 10 * MINUTE_IN_SECONDS;
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

Enable Logging
--------------

In `wp-config.php`:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Enable UUPD logging:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
add_filter( 'updater_enable_debug', fn( $e ) => true );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
