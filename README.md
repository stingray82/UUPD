UUPD: Universal Updater Drop-In for WordPress Plugins & Themes
==============================================================

 

**UUPD** is a lightweight and flexible update manager for WordPress plugins and
themes. It supports both GitHub-hosted projects and private update servers, with
optional metadata caching and update debugging. The system is designed to work
seamlessly for plugin developers who want to manage updates outside of
WordPress.org.

 

✨ Key Features
--------------

-   Works with both **GitHub Releases** and **custom/private update servers**

-   Uses native WordPress update hooks for seamless integration

-   Caches metadata with transients to reduce API usage

-   Manual "Check for updates" link added under plugin row

-   Lightweight and dependency-free

-   Optional GitHub token override for authenticated API calls

 

⚙️ Setup
-------

 

### 1. **Add the Updater File**

Copy `includes/updater.php` into your plugin or theme directory.

 

### 2. **Plugin Integration**

In your main plugin file (e.g. `my-plugin.php`):

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/includes/updater.php';

    \UUPD\V1\UUPD_Updater_V1::register([
        'plugin_file'   => plugin_basename(__FILE__),
        'slug'          => 'example-plugin',
        'name'          => 'Example Plugin',
        'version'       => '1.0.0',
        'server'        => 'https://raw.githubusercontent.com/your-user/example-plugin/main/uupd/',
    ]);
}, 1);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

### 3. **Theme Integration**

In your theme's `functions.php`:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_action( 'after_setup_theme', function() {
    require_once get_stylesheet_directory() . '/includes/updater.php';

    \UUPD\V1\UUPD_Updater_V1::register([
        'slug'      => 'example-theme',
        'name'      => 'Example Theme',
        'version'   => '1.0.0',
        'server'    => 'https://raw.githubusercontent.com/your-user/example-theme/main/uupd/',
    ]);
});
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

📁 Hosting Your Update Metadata
------------------------------

 

You can generate the required `index.json` using the `generate_index.php`
script:

### Inputs Required:

-   `plugin.php` file

-   `changelog.txt`

-   GitHub username

-   Repo name

### Output:

-   `index.json` with plugin metadata

-   Optional: `info.txt` for direct integration URL

This can be hosted anywhere:

-   GitHub (via `raw.githubusercontent.com`)

-   Static sites (e.g. Cloudflare Pages)

-   PHP endpoint (compatible with WP Update Server or similar)

-   Wordpress Site using the simple-update-server

 

🚀 Example JSON URL
------------------

 

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://raw.githubusercontent.com/your-user/your-plugin/main/uupd/index.json
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Include this as the `server` in your UUPD config to allow automatic updates.

 

📅 GitHub Release Integration
----------------------------

-   Automatically fetches version and changelog from the latest release

-   Uploads ZIP via API (included in batch deployment script)

-   Works with or without GitHub authentication

If you exceed GitHub API limits or use private repos:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_filter( 'uupd/github_token_override', function( $token, $slug ) {
    return 'your_github_pat';
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

🔧 Command Line Build & Deployment
---------------------------------

Use the included `deploy.bat` script to:

-   Extract plugin headers

-   Build `readme.txt`

-   Generate ZIP

-   Push to GitHub

-   Generate `index.json`

 

You can customize the script with:

-   `DEPLOY_TARGET=github` or `private`

-   Plugin source and changelog locations

-   Optional GitHub token loading

 

📈 Debugging
-----------

Enable debug logging:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_filter( 'updater_enable_debug', fn( $e ) => true );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Also in `wp-config.php`:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

🔗 Compatibility
---------------

-   WordPress 5.8+

-   PHP 7.4+

-   Compatible with WP-Cron, `wp_update_plugins()` and most deployment workflows

 

✨ Credits
---------

Created by [Really Useful Plugins](https://reallyusefulplugins.com). Inspired by
simplicity and the desire to empower developers with GitHub or private
updates—without lock-in.

**You can view a dummy plugin using this exact updater and its used for testing
new versions here:**

Plugin: <https://github.com/stingray82/example-plugin/>  
Updates:
<https://raw.githubusercontent.com/stingray82/example-plugin/main/uupd/index.json>

 

Please if your editing the main update scope it read this [article
here](https://techarticles.co.uk/why-rescoping-is-important-uupd/) for
information and details on why you should scope if your not using standard

 

🎉 Happy Updating!
-----------------
