<?php
/**
 * Universal Updater Drop-In (UUPD) for Plugins & Themes
 * ===================================================
 *
 * A lightweight, self-contained WordPress updater supporting both
 * private JSON endpoints and GitHub Releases (public or private).
 *
 * Designed to be copied directly into plugins or themes with no
 * external dependencies.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * Supported Features
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ✔ Private update servers (JSON metadata)
 * ✔ GitHub Releases-based updates (public or private)
 * ✔ Manual “Check for updates” trigger
 * ✔ WordPress-native update UI integration
 * ✔ Private GitHub release assets (via API + token)
 * ✔ Caching via WordPress transients
 * ✔ Pre-release (alpha/beta/RC/dev) handling
 * ✔ Optional branding (icons, banners, screenshots)
 *
 * Safe to include multiple times. Class is namespaced and encapsulated.
 *
 * ───────────────────────── Compatibility / Upgrade Notes ──────────────────────
 *
 * Version 1.4.0 is intended to be **backwards compatible** for all
 * standard usage patterns from previous releases.
 *
 * Existing configurations continue to work when:
 *
 *   • JSON mode is used (`server` points to a JSON metadata endpoint)
 *   • GitHub Releases mode is used (`server` is a GitHub repo root URL)
 *
 * ⚠️ Notes for edge cases:
 *
 *   • GitHub auto-detection is stricter in v1.4.0:
 *     GitHub Releases mode is triggered ONLY when `server` is a repo-root URL:
 *
 *         ✅ https://github.com/owner/repo
 *         ❌ https://github.com/owner/repo/releases
 *         ❌ https://raw.githubusercontent.com/...
 *
 *     If you previously used a non-root GitHub URL, explicitly set:
 *
 *         'mode' => 'github_release'
 *
 *   • When a GitHub token is configured, UUPD automatically injects
 *     Authorization headers for outgoing requests to:
 *
 *       - api.github.com
 *       - github.com release asset downloads
 *
 *     This is required for private repositories, private assets,
 *     and to avoid GitHub API rate limits.
 *
 * ───────────────────────────── Update Modes ─────────────────────────────
 *
 * UUPD supports two update modes:
 *
 * 1) JSON Mode (Private Update Server)
 * -----------------------------------
 *    Set `server` to a JSON metadata URL (recommended: ends with `.json`)
 *
 *    Example:
 *      https://example.com/uupd/index.json
 *
 *    JSON metadata may include:
 *      - version
 *      - download_url
 *      - homepage
 *      - author / author_homepage
 *      - sections (changelog, description, installation, etc)
 *      - icons, banners, screenshots
 *
 * 2) GitHub Releases Mode
 * ----------------------
 *    Set `server` to the GitHub repository root:
 *
 *      https://github.com/<owner>/<repo>
 *
 *    UUPD will call:
 *      https://api.github.com/repos/<owner>/<repo>/releases/latest
 *
 *    • Public repos work without a token
 *    • Private repos and/or private assets REQUIRE a GitHub token
 *
 * ───────────────────────── Mode Auto-Detection ─────────────────────────
 *
 * Default mode: 'auto'
 *
 *   • If `server` is a GitHub repo root → GitHub Releases mode
 *   • Otherwise → JSON mode
 *
 * You may force a mode explicitly:
 *
 *   'mode' => 'auto'            // default
 *   'mode' => 'json'            // always use JSON metadata
 *   'mode' => 'github_release'  // always use GitHub Releases
 *
 * ───────────────────────── GitHub Token Filters ─────────────────────────
 *
 * Override GitHub tokens globally or per slug:
 *
 *   // A) Global fallback token
 *   add_filter( 'uupd/github_token_override', function( $token, $slug ) {
 *       return 'ghp_globalFallbackToken';
 *   }, 10, 2 );
 *
 *   // B) Per-slug tokens
 *   add_filter( 'uupd/github_token_override', function( $token, $slug ) {
 *       $tokens = [
 *           'plugin-slug-1' => 'ghp_pluginToken',
 *           'theme-slug-2'  => 'ghp_themeToken',
 *       ];
 *       return $tokens[ $slug ] ?? $token;
 *   }, 10, 2 );
 *
 * Token scopes:
 *   • Private repos generally require appropriate `repo` access
 *
 * ───────────────────────── Visual Assets & Branding ─────────────────────────
 *
 * In JSON mode, icons/banners are read directly from metadata.
 *
 * In GitHub Releases mode, UUPD does not fetch remote JSON metadata.
 * To provide branding in this case, assets may be supplied via config or filters.
 *
 * Via config:
 *
 *   'icons' => [
 *       '1x' => 'https://cdn.example.com/icon-128.png',
 *       '2x' => 'https://cdn.example.com/icon-256.png',
 *   ],
 *
 *   'banners' => [
 *       'low'  => 'https://cdn.example.com/banner-772x250.png',
 *       'high' => 'https://cdn.example.com/banner-1544x500.png',
 *   ],
 *
 * Via filters (per slug):
 *
 *   add_filter( 'uupd/icons/my-plugin-slug', function() {
 *       return [
 *           '1x' => 'https://cdn.example.com/icon-128.png',
 *           '2x' => 'https://cdn.example.com/icon-256.png',
 *       ];
 *   } );
 *
 *   add_filter( 'uupd/banners/my-plugin-slug', function() {
 *       return [
 *           'low'  => 'https://cdn.example.com/banner-772x250.png',
 *           'high' => 'https://cdn.example.com/banner-1544x500.png',
 *       ];
 *   } );
 *
 * ─────────────────────────── Plugin Integration ───────────────────────────
 *
 *   add_action( 'plugins_loaded', function() {
 *       require_once __DIR__ . '/includes/updater.php';
 *
 *       \UUPD\V1\UUPD_Updater_V1::register( [
 *           'plugin_file'  => plugin_basename( __FILE__ ),
 *           'slug'         => 'my-plugin-slug',
 *           'name'         => 'My Plugin Name',
 *           'version'      => MY_PLUGIN_VERSION,
 *           'server'       => 'https://github.com/user/repo',
 *           'github_token' => 'ghp_YourTokenHere',
 *       ] );
 *   }, 1 );
 *
 * ─────────────────────────── Theme Integration ───────────────────────────
 *
 *   add_action( 'after_setup_theme', function() {
 *       require_once get_stylesheet_directory() . '/includes/updater.php';
 *
 *       add_action( 'admin_init', function() {
 *           \UUPD\V1\UUPD_Updater_V1::register( [
 *               'slug'         => 'my-theme-folder',
 *               'name'         => 'My Theme Name',
 *               'version'      => '1.0.0',
 *               'server'       => 'https://github.com/user/repo',
 *               'github_token' => 'ghp_YourTokenHere',
 *           ] );
 *       } );
 *   } );
 *
 * ───────────────────────── Cache Duration Filters ─────────────────────────
 *
 *   add_filter( 'uupd_success_cache_ttl', function( $ttl, $slug ) {
 *       return 1 * HOUR_IN_SECONDS;
 *   }, 10, 2 );
 *
 *   add_filter( 'uupd_fetch_remote_error_ttl', function( $ttl, $slug ) {
 *       return 15 * MINUTE_IN_SECONDS;
 *   }, 10, 2 );
 *
 * ───────────────────────── Scoped Filters ─────────────────────────
 *
 * All core filters support per-slug overrides.
 *
 * Example:
 *
 *   add_filter( 'uupd/server_url/my-plugin-slug', function( $url ) {
 *       return 'https://example.com/custom-endpoint.json';
 *   } );
 *
 * ───────────────────────── Optional Debugging ─────────────────────────
 *
 *   add_filter( 'updater_enable_debug', '__return_true' );
 *
 *   In wp-config.php:
 *     define( 'WP_DEBUG', true );
 *     define( 'WP_DEBUG_LOG', true );
 *
 * ───────────────────────── Summary ─────────────────────────
 *
 * • Fetches update metadata from JSON or GitHub Releases
 * • Injects updates into native WordPress transients
 * • Supports private repos, private assets, and branding
 * • Backwards compatible with previous UUPD usage
 * • Zero dependencies, safe to bundle anywhere
 *
 */

namespace UUPD\V1;

if ( ! class_exists( __NAMESPACE__ . '\UUPD_Updater_V1' ) ) {

    class UUPD_Updater_V1 {

        const VERSION = '1.4.0'; // Change as needed

        /** @var array Configuration settings */
        private $config;

        private static function apply_filters_per_slug( $filter_base, $default, $slug ) {
            $slug   = sanitize_key( $slug );
            $scoped = apply_filters( "{$filter_base}/{$slug}", $default, $slug );
            return apply_filters( $filter_base, $scoped, $slug );
        }

        /**
         * Constructor.
         *
         * @param array $config {
         *   @type string 'slug'           Plugin or theme slug.
         *   @type string 'name'           Human-readable name.
         *   @type string 'version'        Current version.
         *   @type string 'key'            Your secret key.
         *   @type string 'server'         Base URL of your updater endpoint.
         *   @type string 'plugin_file'    (optional) plugin_basename(__FILE__) for plugins.
         *   @type bool   'allow_prerelease' (optional) Whether to allow updates to prerelease versions.
         *   @type string 'cache_prefix'   (optional) Transient prefix, default 'upd_'.
         * }
         */
        public function __construct( array $config ) {
            // Allow plugins to override full config dynamically.
            $config = self::apply_filters_per_slug( 'uupd/filter_config', $config, $config['slug'] ?? '' );

            // Allow override of prerelease flag (per-slug logic).
            $config['allow_prerelease'] = self::apply_filters_per_slug(
                'uupd/allow_prerelease',
                $config['allow_prerelease'] ?? false,
                $config['slug'] ?? ''
            );

            // Allow overriding the server URL.
            $config['server'] = self::apply_filters_per_slug(
                'uupd/server_url',
                $config['server'] ?? '',
                $config['slug'] ?? ''
            );

            // NEW: Allow overriding cache prefix, default 'upd_'.
            $config['cache_prefix'] = self::apply_filters_per_slug(
                'uupd/cache_prefix',
                $config['cache_prefix'] ?? 'upd_',
                $config['slug'] ?? ''
            );

            $this->config = $config;
            $this->log( "✓ Using UUPD_Updater_V1 version " . self::VERSION );
            $this->register_hooks();
        }

       /**
		 * Filter outgoing HTTP requests so GitHub downloads include auth headers when needed.
		 *
		 * @param array  $args HTTP request arguments.
		 * @param string $url  Request URL.
		 * @return array
		 */
		public function filter_http_request_args( $args, $url ) {

		    $url = (string) $url;

		    // Only touch GitHub URLs (public + API).
		    if ( strpos( $url, 'github.com/' ) === false && strpos( $url, 'api.github.com/' ) === false ) {
		        return $args;
		    }

		    return $this->add_github_auth_headers_for_download( $args, $url );
		}

		/** Attach update and info filters for plugin or theme. */
		private function register_hooks() {

		    // 1) Normal WP update hooks
		    if ( ! empty( $this->config['plugin_file'] ) ) {
		        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'plugin_update' ] );
		        add_filter( 'site_transient_update_plugins',         [ $this, 'plugin_update' ] ); // WP 6.8
		        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 10, 3 );
		    } else {
		        add_filter( 'pre_set_site_transient_update_themes', [ $this, 'theme_update' ] );
		        add_filter( 'site_transient_update_themes',         [ $this, 'theme_update' ] ); // WP 6.8
		        add_filter( 'themes_api',                           [ $this, 'theme_info' ], 10, 3 );
		    }

		    // 2) Add GitHub auth headers when WP downloads metadata or zip packages.
		    //    This is essential for private repos and private release assets.
		    add_filter( 'http_request_args', [ $this, 'filter_http_request_args' ], 10, 2 );
		}


        /** Fetch metadata JSON from remote server and cache it. */
        private function fetch_remote() {
            $c          = $this->config;
            $slug_plain = $c['slug'] ?? '';
            $prefix     = $c['cache_prefix'] ?? 'upd_';

            if ( empty( $c['server'] ) ) {
                $this->log( 'No server URL configured — skipping fetch and caching an error state.' );
                $ttl = self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $slug_plain );
                set_transient( $prefix . $slug_plain . '_error', time(), $ttl );
                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $slug_plain, 'server' => '', 'message' => 'No server configured' ] );
                do_action( "uupd_metadata_fetch_failed/{$slug_plain}", [ 'slug' => $slug_plain, 'server' => '', 'message' => 'No server configured' ] );
                return;
            }

            $slug_qs = rawurlencode( $slug_plain ); // only for the URL query
            $key_qs  = rawurlencode( isset( $c['key'] ) ? $c['key'] : '' );
            $host_qs = rawurlencode( wp_parse_url( untrailingslashit( home_url() ), PHP_URL_HOST ) );

            $separator = strpos( $c['server'], '?' ) === false ? '?' : '&';
            $is_json = self::ends_with( $c['server'], '.json' );

			if ( $is_json ) {
			    $url = $c['server'];
			} else {
			    $separator = strpos( $c['server'], '?' ) === false ? '?' : '&';
			    $url = untrailingslashit( $c['server'] ) . $separator . "action=get_metadata&slug={$slug_qs}&key={$key_qs}&domain={$host_qs}";
			}


            // Allow full override of constructed URL.
            $url = self::apply_filters_per_slug( 'uupd/remote_url', $url, $slug_plain );

            $failure_cache_key = $prefix . $slug_plain . '_error';

            $this->log( " Fetching metadata: {$url}" );
            do_action( 'uupd/before_fetch_remote', $slug_plain, $c );
            $this->log( "→ Triggered action: uupd/before_fetch_remote for '{$slug_plain}'" );

            $resp = wp_remote_get( $url, [
                'timeout' => 15,
                'headers' => [ 'Accept' => 'application/json' ],
            ] );

            if ( is_wp_error( $resp ) ) {
                $msg = $resp->get_error_message();
                $this->log( " WP_Error: $msg — caching failure for 6 hours" );
                $ttl = self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $slug_plain );
                set_transient( $failure_cache_key, time(), $ttl );
                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $slug_plain, 'server' => $c['server'], 'message' => $msg ] );
                do_action( "uupd_metadata_fetch_failed/{$slug_plain}", [ 'slug' => $slug_plain, 'server' => $c['server'], 'message' => $msg ] );
                return;
            }

            $code = wp_remote_retrieve_response_code( $resp );
            $body = wp_remote_retrieve_body( $resp );

            $this->log( "← HTTP {$code}: " . trim( $body ) );

            if ( 200 !== (int) $code ) {
                $this->log( "Unexpected HTTP {$code} — update fetch will pause until next cycle" );
                $ttl = self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $slug_plain );
                set_transient( $failure_cache_key, time(), $ttl );
                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $slug_plain, 'server' => $c['server'], 'code' => $code ] );
                do_action( "uupd_metadata_fetch_failed/{$slug_plain}", [ 'slug' => $slug_plain, 'server' => $c['server'], 'code' => $code ] );
                return;
            }

            $meta = json_decode( $body );
            if ( ! $meta ) {
                $this->log( ' JSON decode failed — caching error state' );
                $ttl = self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $slug_plain );
                set_transient( $failure_cache_key, time(), $ttl );
                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $slug_plain, 'server' => $c['server'], 'code' => 200, 'message' => 'Invalid JSON' ] );
                do_action( "uupd_metadata_fetch_failed/{$slug_plain}", [ 'slug' => $slug_plain, 'server' => $c['server'], 'code' => 200, 'message' => 'Invalid JSON' ] );
                return;
            }

            // Allow developers to manipulate raw metadata before use.
            $meta = self::apply_filters_per_slug( 'uupd/metadata_result', $meta, $slug_plain );

            $ttl = self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $slug_plain );
            set_transient( $prefix . $slug_plain, $meta, $ttl );
            delete_transient( $failure_cache_key );
            $this->log( " Cached metadata '{$slug_plain}' → v" . ( $meta->version ?? 'unknown' ) );
        }

        private function normalize_version( $v ) {
            $v = trim( (string) $v );

            // Strip build metadata (SemVer: everything after '+')
            $v = preg_replace( '/\+.*$/', '', $v );

            // Drop a leading 'v' (e.g. v1.3.0)
            $v = ltrim( $v, "vV" );

            // Normalize separators
            $v = str_replace( '_', '-', $v );

            // Ensure we have three numeric components (x.y.z)
            if ( preg_match( '/^\d+\.\d+$/', $v ) ) {
                $v .= '.0';
            } elseif ( preg_match( '/^\d+$/', $v ) ) {
                $v .= '.0.0';
            }

            // Insert a hyphen before pre-release if someone wrote 1.3.0alpha2 / 1.3.0rc
             // Also capture shorthands and synonyms: a,b,pre,preview
            // Capture optional numeric like alpha2 / alpha-2 / alpha.2
            if ( preg_match( '/^(\d+\.\d+\.\d+)[\.\-]?((?:alpha|a|beta|b|rc|dev|pre|preview))(?:(?:[\.\-]?)(\d+))?$/i', $v, $m ) ) {
                $core = $m[1];
                $tag  = strtolower( $m[2] );
                $num  = isset( $m[3] ) && $m[3] !== '' ? $m[3] : '0';

                switch ( $tag ) {
                    case 'a':       $tag = 'alpha'; break;
                    case 'b':       $tag = 'beta';  break;
                    case 'pre': // treat "pre/preview" as earlier than RC, closer to beta
                    case 'preview': $tag = 'beta';  break;
                    case 'rc':      $tag = 'rc';    break; // PHP is case-insensitive
                    case 'dev':     $tag = 'dev';   break;
                }

                $v = "{$core}-{$tag}.{$num}";
            }

            // If someone wrote "1.3.0-alpha" (no number), pad with .0
            $v = preg_replace( '/^(\d+\.\d+\.\d+)-(alpha|beta|rc|dev)(?=$)/i', '$1-$2.0', $v );

            return $v;
        }

        /**
		 * Resolve icons/banners/screenshots from config and allow per-slug filters.
		 *
		 * Supports both:
		 *  - config values: 'icons', 'banners', 'screenshots', 'screenshot'
		 *  - filters: uupd/icons, uupd/banners, uupd/screenshots, uupd/screenshot (and per-slug variants)
		 *
		 * @return array{icons:array,banners:array,screenshots:array,screenshot:string}
		 */
		private function resolve_visual_assets() {
		    $slug = $this->config['slug'] ?? '';

		    $icons = $this->config['icons'] ?? [];
		    $banners = $this->config['banners'] ?? [];
		    $screenshots = $this->config['screenshots'] ?? [];
		    $screenshot = $this->config['screenshot'] ?? '';

		    $icons = (array) self::apply_filters_per_slug( 'uupd/icons', $icons, $slug );
		    $banners = (array) self::apply_filters_per_slug( 'uupd/banners', $banners, $slug );
		    $screenshots = (array) self::apply_filters_per_slug( 'uupd/screenshots', $screenshots, $slug );
		    $screenshot = (string) self::apply_filters_per_slug( 'uupd/screenshot', $screenshot, $slug );

		    return [
		        'icons'       => $icons,
		        'banners'     => $banners,
		        'screenshots' => $screenshots,
		        'screenshot'  => $screenshot,
		    ];
		}

		/**
		 * Apply visual assets (icons/banners/screenshots) from config/filters to meta.
		 * By default, this only fills missing fields (metadata wins).
		 *
		 * @param object $meta
		 * @return object
		 */
		private function apply_visual_assets_to_meta( $meta ) {
		    if ( ! is_object( $meta ) ) {
		        return $meta;
		    }

		    $va = $this->resolve_visual_assets();

		    // Fill gaps only (metadata wins).
		    if ( empty( $meta->icons ) && ! empty( $va['icons'] ) ) {
		        $meta->icons = $va['icons'];
		    }
		    if ( empty( $meta->banners ) && ! empty( $va['banners'] ) ) {
		        $meta->banners = $va['banners'];
		    }
		    if ( empty( $meta->screenshots ) && ! empty( $va['screenshots'] ) ) {
		        $meta->screenshots = $va['screenshots'];
		    }
		    if ( empty( $meta->screenshot ) && ! empty( $va['screenshot'] ) ) {
		        $meta->screenshot = $va['screenshot'];
		    }

		    return $meta;
		}



        /** Handle plugin update injection. */
        public function plugin_update( $trans ) {
            if ( ! is_object( $trans ) || ! isset( $trans->checked ) || ! is_array( $trans->checked ) ) {
                return $trans;
            }

            $c        = $this->config;
            $file     = $c['plugin_file'];
            $slug     = $c['slug'];
            $prefix   = $c['cache_prefix'] ?? 'upd_';
            $cache_id = $prefix . $slug;
            $error_key = $cache_id . '_error';

            $this->log( "Plugin-update hook for '{$slug}'" );

            $current = $trans->checked[ $file ] ?? $c['version'];
            $meta    = get_transient( $cache_id );

            // Skip if last fetch failed
            if ( false === $meta && get_transient( $error_key ) ) {
                $this->log( " Skipping plugin update check for '{$slug}' — previous error cached" );
                return $trans;
            }

            // Fetch metadata if missing
            if ( false === $meta ) {
			    if ( $this->should_use_github_release_mode() ) {

			        $repo_url  = rtrim( $c['server'], '/' );
			        $cache_key = 'uupd_github_release_' . $slug . '_' . md5( $repo_url );

			        $release   = get_transient( $cache_key );

			        if ( false === $release ) {

			            $api_url = $this->github_latest_release_api_url( $repo_url );

			            $token = self::apply_filters_per_slug(
			                'uupd/github_token_override',
			                $c['github_token'] ?? '',
			                $c['slug'] ?? ''
			            );

			            $headers = [
			                'Accept'     => 'application/vnd.github.v3+json',
			                'User-Agent' => 'WordPress-UUPD',
			            ];

			            if ( $token ) {
			                $headers['Authorization'] = 'token ' . $token;
			            }

			            $this->log( " GitHub fetch: $api_url" );
			            $response = wp_remote_get( $api_url, [ 'headers' => $headers, 'timeout' => 15 ] );

			            if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
			                $release = json_decode( wp_remote_retrieve_body( $response ) );
			                $ttl     = self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $slug );
			                set_transient( $cache_key, $release, $ttl );
			            } else {
			                $msg = is_wp_error( $response ) ? $response->get_error_message() : ( 'HTTP ' . wp_remote_retrieve_response_code( $response ) );
			                $this->log( "✗ GitHub API failed — {$msg} — caching error state" );

			                set_transient(
			                    $error_key,
			                    time(),
			                    self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $slug )
			                );

			                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $slug, 'server' => $repo_url, 'message' => $msg ] );
			                do_action( "uupd_metadata_fetch_failed/{$slug}", [ 'slug' => $slug, 'server' => $repo_url, 'message' => $msg ] );
			                return $trans;
			            }
			        }

			        // GitHub Releases mode: build $meta from the release payload
					if ( isset( $release->tag_name ) ) {

					    // Private-safe: API asset endpoint (/releases/assets/{id})
					    $zip_url = $this->github_release_download_url( $repo_url, $release );

					    $meta = (object) [
					        'version'      => ltrim( (string) $release->tag_name, 'v' ),
					        'download_url' => $zip_url,
					        'homepage'     => $release->html_url ?? $repo_url,
					        'sections'     => [ 'changelog' => $release->body ?? '' ],
					    ];

					} else {

					    $meta = (object) [
					        'version'      => $c['version'],
					        'download_url' => '',
					        'homepage'     => $repo_url,
					        'sections'     => [ 'changelog' => '' ],
					    ];
					}

					// Apply Visual Assets
					$meta = $this->apply_visual_assets_to_meta( $meta );

					// Now Cache it
					set_transient( $cache_id, $meta, self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $slug ) );



					// Success: clear the error flag for this slug (if any)
					delete_transient( $error_key );


			    } else {

			        $this->fetch_remote();
					$meta = get_transient( $cache_id );

					if ( $meta ) {
					    $meta = $this->apply_visual_assets_to_meta( $meta );
					    set_transient(
					        $cache_id,
					        $meta,
					        self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $c['slug'] )
					    );
					}


			    }
			}


            // If still no metadata, bail
            if ( ! $meta ) {
                $this->log( "No metadata found, skipping update logic." );
                return $trans;
            }

            // Compare versions
            $remote_version   = $meta->version ?? '0.0.0';
            $allow_prerelease = $this->config['allow_prerelease'] ?? false;

            $current_normalized = $this->normalize_version( $current );
            $remote_normalized  = $this->normalize_version( $remote_version );

            $this->log( "Original versions: installed={$current}, remote={$remote_version}" );
            $this->log( "Normalized versions: installed={$current_normalized}, remote={$remote_normalized}" );
            $this->log( "Comparing (normalized): installed={$current_normalized} vs remote={$remote_normalized}" );

            if (
                ( ! $allow_prerelease && preg_match( '/^\d+\.\d+\.\d+-(alpha|beta|rc|dev|preview)(?:[.\-]\d+)?$/i', $remote_normalized ) )
                || version_compare( $current_normalized, $remote_normalized, '>=' )
            ) {
                $this->log( "Plugin '{$slug}' is up to date (v{$current})" );
                $trans->no_update[ $file ] = (object) [
                    'id'           => $file,
                    'slug'         => $slug,
                    'plugin'       => $file,
                    'new_version'  => $current,
                    'url'          => $meta->homepage ?? '',
                    'package'      => '',
                    'icons'        => (array) ( $meta->icons ?? [] ),
                    'banners'      => (array) ( $meta->banners ?? [] ),
                    'tested'       => $meta->tested ?? '',
                    'requires'     => $meta->requires ?? $meta->min_wp_version ?? '',
                    'requires_php' => $meta->requires_php ?? '',
                    'compatibility'=> new \stdClass(),
                ];
                return $trans;
            }

            // Inject update
            $this->log( "Injecting plugin update '{$slug}' → v{$meta->version}" );
            $trans->response[ $file ] = (object) [
                'id'           => $file,
                'name'         => $c['name'],
                'slug'         => $slug,
                'plugin'       => $file,
                'new_version'  => $meta->version ?? $c['version'],
                'package'      => $meta->download_url ?? '',
                'url'          => $meta->homepage ?? '',
                'tested'       => $meta->tested ?? '',
                'requires'     => $meta->requires ?? $meta->min_wp_version ?? '',
                'requires_php' => $meta->requires_php ?? '',
                'sections'     => (array) ( $meta->sections ?? [] ),
                'icons'        => (array) ( $meta->icons ?? [] ),
                'banners'      => (array) ( $meta->banners ?? [] ),
                'compatibility'=> new \stdClass(),
            ];

            unset( $trans->no_update[ $file ] );
            return $trans;
        }

        /** Handle theme update injection. */
        public function theme_update( $trans ) {
            if ( ! is_object( $trans ) || ! isset( $trans->checked ) || ! is_array( $trans->checked ) ) {
                return $trans;
            }

            $c         = $this->config;
            $slug      = $c['real_slug'] ?? $c['slug'];      // WP expects real theme folder slug
            $prefix    = $c['cache_prefix'] ?? 'upd_';
            $cache_id  = $prefix . $c['slug'];               // Transient key for metadata
            $error_key = $cache_id . '_error';               // Transient key for error flag

            $this->log( "Theme-update hook for '{$c['slug']}'" );

            $current = $trans->checked[ $slug ] ?? wp_get_theme( $slug )->get( 'Version' );
            $meta    = get_transient( $cache_id );

            // Skip if last fetch failed
            if ( false === $meta && get_transient( $error_key ) ) {
                $this->log( "Skipping theme update check for '{$c['slug']}' — previous error cached" );
                return $trans;
            }

            // If metadata is missing, try to fetch it (GitHub or private server)
            if ( false === $meta ) {

			    if ( $this->should_use_github_release_mode() ) {

			        $repo_url  = rtrim( $c['server'], '/' );
			        $cache_key = 'uupd_github_release_' . $c['slug'] . '_' . md5( $repo_url );
			        $release   = get_transient( $cache_key );

			        if ( false === $release ) {

			            $api_url = $this->github_latest_release_api_url( $repo_url );

			            // IMPORTANT: token filter should be keyed on config slug, not real theme slug
			            $token = self::apply_filters_per_slug(
			                'uupd/github_token_override',
			                $c['github_token'] ?? '',
			                $c['slug'] ?? ''
			            );

			            $headers = [
			                'Accept'     => 'application/vnd.github.v3+json',
			                'User-Agent' => 'WordPress-UUPD',
			            ];

			            if ( $token ) {
			                $headers['Authorization'] = 'token ' . $token;
			            }

			            $this->log( " GitHub fetch: $api_url" );
			            $response = wp_remote_get( $api_url, [ 'headers' => $headers, 'timeout' => 15 ] );

			            if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
			                $release = json_decode( wp_remote_retrieve_body( $response ) );
			                $ttl = self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $c['slug'] );
							set_transient( $cache_key, $release, $ttl );
			            } else {
			                $msg = is_wp_error( $response ) ? $response->get_error_message() : ( 'HTTP ' . wp_remote_retrieve_response_code( $response ) );
			                $this->log( "✗ GitHub API failed — {$msg} — caching error state" );

			                set_transient(
			                    $error_key,
			                    time(),
			                    self::apply_filters_per_slug( 'uupd_fetch_remote_error_ttl', 6 * HOUR_IN_SECONDS, $c['slug'] )
			                );

			                do_action( 'uupd_metadata_fetch_failed', [ 'slug' => $c['slug'], 'server' => $repo_url, 'message' => $msg ] );
			                do_action( "uupd_metadata_fetch_failed/{$c['slug']}", [ 'slug' => $c['slug'], 'server' => $repo_url, 'message' => $msg ] );
			                return $trans;
			            }
			        }

			        // GitHub Releases mode: build $meta from the release payload
					if ( isset( $release->tag_name ) ) {

					    // Private-safe: API asset endpoint (/releases/assets/{id})
					    $zip_url = $this->github_release_download_url( $repo_url, $release );

					    $meta = (object) [
					        'version'      => ltrim( (string) $release->tag_name, 'v' ),
					        'download_url' => $zip_url,
					        'homepage'     => $release->html_url ?? $repo_url,
					        'sections'     => [ 'changelog' => $release->body ?? '' ],
					    ];

					} else {

					    $meta = (object) [
					        'version'      => $c['version'],
					        'download_url' => '',
					        'homepage'     => $repo_url,
					        'sections'     => [ 'changelog' => '' ],
					    ];
					}

					// Apply Visual Assets
					$meta = $this->apply_visual_assets_to_meta( $meta );

					// Now Cache it
					set_transient($cache_id, $meta, self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $c['slug'] ));

					// Success: clear the error flag for this slug (if any)
					delete_transient( $error_key );


			    } else {

			        $this->fetch_remote(); // will handle error caching internally
			        $meta = get_transient( $cache_id );
			        if ( $meta ) {
					    $meta = $this->apply_visual_assets_to_meta( $meta );
					    set_transient( $cache_id, $meta, self::apply_filters_per_slug( 'uupd_success_cache_ttl', 6 * HOUR_IN_SECONDS, $c['slug'] ?? $slug ) );
					}

			    }
			}


            // If still no metadata, bail before touching $meta->...
            if ( ! $meta ) {
                $this->log( "No metadata found, skipping update logic." );
                return $trans;
            }

            // Build base info used for both "no update" and "update available"
            $base_info = [
                'theme'        => $slug,
                'url'          => $meta->homepage ?? '',
                'requires'     => $meta->requires ?? $meta->min_wp_version ?? '',
                'requires_php' => $meta->requires_php ?? '',
                'screenshot'   => $meta->screenshot ?? '',
                'tested'       => $meta->tested ?? '',
            ];

            // Compare versions
            $remote_version   = $meta->version ?? '0.0.0';
            $allow_prerelease = $this->config['allow_prerelease'] ?? false;

            $current_normalized = $this->normalize_version( $current );
            $remote_normalized  = $this->normalize_version( $remote_version );

            $this->log( "Original versions: installed={$current}, remote={$remote_version}" );
            $this->log( "Normalized versions: installed={$current_normalized}, remote={$remote_normalized}" );
            $this->log( "Comparing (normalized): installed={$current_normalized} vs remote={$remote_normalized}" );

            if (
                ( ! $allow_prerelease && preg_match( '/^\d+\.\d+\.\d+-(alpha|beta|rc|dev|preview)(?:[.\-]\d+)?$/i', $remote_normalized ) )
                || version_compare( $current_normalized, $remote_normalized, '>=' )
            ) {
                $this->log( " Theme '{$c['slug']}' is up to date (v{$current})" );
                $trans->no_update[ $slug ] = (object) array_merge( $base_info, [
                    'new_version' => $current,
                    'package'     => '',
                ] );
                return $trans;
            }

            $this->log( " Injecting theme update '{$c['slug']}' → v{$meta->version}" );
            $trans->response[ $slug ] = array_merge( $base_info, [
                'new_version' => $meta->version ?? $current,
                'package'     => $meta->download_url ?? '',
            ] );

            unset( $trans->no_update[ $slug ] );
            return $trans;
        }

        /** Provide plugin information for the details popup. */
        public function plugin_info( $res, $action, $args ) {
            $c = $this->config;
            if ( 'plugin_information' !== $action || $args->slug !== $c['slug'] ) {
                return $res;
            }

            $prefix = $c['cache_prefix'] ?? 'upd_';
            $meta   = get_transient( $prefix . $c['slug'] );
            if ( ! $meta ) {
                return $res;
            }

            // Build sections array (description, installation, faq, screenshots, changelog…)
            $sections = [];
            if ( isset( $meta->sections ) ) {
                foreach ( (array) $meta->sections as $key => $content ) {
                    $sections[ $key ] = $content;
                }
            }

            return (object) [
                'name'            => $c['name'],
                'title'           => $c['name'],               // Popup title
                'slug'            => $c['slug'],
                'version'         => $meta->version        ?? '',
                'author'          => $meta->author         ?? '',
                'author_homepage' => $meta->author_homepage ?? '',
                'requires'        => $meta->requires       ?? $meta->min_wp_version ?? '',
                'tested'          => $meta->tested         ?? '',
                'requires_php'    => $meta->requires_php   ?? '', // “Requires PHP: x.x or higher”
                'last_updated'    => $meta->last_updated   ?? '',
                'download_link'   => $meta->download_url   ?? '',
                'homepage'        => $meta->homepage       ?? '',
                'sections'        => $sections,
                'icons'           => isset( $meta->icons )   ? (array) $meta->icons   : [],
                'banners'         => isset( $meta->banners ) ? (array) $meta->banners : [],
                'screenshots'     => isset( $meta->screenshots )
                    ? (array) $meta->screenshots
                    : [],
            ];
        }

        /** Provide theme information for the details popup. */
        public function theme_info( $res, $action, $args ) {
            $c    = $this->config;
            $slug = $c['real_slug'] ?? $c['slug'];

            if ( 'theme_information' !== $action || $args->slug !== $slug ) {
                return $res;
            }

            $prefix = $c['cache_prefix'] ?? 'upd_';
            $meta   = get_transient( $prefix . $c['slug'] );
            if ( ! $meta ) {
                return $res;
            }

            // Safely extract changelog HTML
            if ( isset( $meta->changelog_html ) ) {
                $changelog = $meta->changelog_html;
            } elseif ( isset( $meta->sections ) ) {
                if ( is_array( $meta->sections ) ) {
                    $changelog = $meta->sections['changelog'] ?? '';
                } elseif ( is_object( $meta->sections ) ) {
                    $changelog = $meta->sections->changelog ?? '';
                } else {
                    $changelog = '';
                }
            } else {
                $changelog = '';
            }

            return (object) [
                'name'          => $c['name'],
                'slug'          => $c['real_slug'] ?? $c['slug'],
                'version'       => $meta->version ?? '',
                'tested'        => $meta->tested ?? '',
                'requires'      => $meta->min_wp_version ?? '',
                'sections'      => [ 'changelog' => $changelog ],
                'download_link' => $meta->download_url ?? '',
                'icons'         => isset( $meta->icons )   ? (array) $meta->icons   : [],
                'banners'       => isset( $meta->banners ) ? (array) $meta->banners : [],
            ];
        }

        /** Optional debug logger. */
        private function log( $msg ) {
            if ( apply_filters( 'updater_enable_debug', false ) ) {
                error_log( "[Updater] {$msg}" );
                do_action( 'uupd/log', $msg, $this->config['slug'] ?? '' );
            }
        }

        private function is_github_repo_root_url( $url ) {
		    $url = trim( (string) $url );
		    if ( $url === '' ) return false;

		    $parts = wp_parse_url( $url );
		    if ( empty( $parts['host'] ) ) return false;

		    // Only github.com repo root should trigger GitHub release mode
		    if ( strtolower( $parts['host'] ) !== 'github.com' ) return false;

		    $path = trim( $parts['path'] ?? '', '/' );
		    if ( $path === '' ) return false;

		    $segments = explode( '/', $path );
		    // Repo root is exactly: /owner/repo
		    return count( $segments ) === 2;
		}

		private function get_mode() {
		    $mode = $this->config['mode'] ?? 'auto';
		    $mode = strtolower( trim( (string) $mode ) );
		    return in_array( $mode, [ 'auto', 'json', 'github_release' ], true ) ? $mode : 'auto';
		}

		private function should_use_github_release_mode() {
		    $mode   = $this->get_mode();
		    $server = $this->config['server'] ?? '';

		    if ( $mode === 'json' ) return false;
		    if ( $mode === 'github_release' ) return true;

		    // auto:
		    return $this->is_github_repo_root_url( $server );
		}

		private function add_github_auth_headers_for_download( $args, $url ) {
		    $slug  = $this->config['slug'] ?? '';
		    $token = self::apply_filters_per_slug(
		        'uupd/github_token_override',
		        $this->config['github_token'] ?? '',
		        $slug
		    );

		    if ( ! $token ) {
		        return $args;
		    }

		    $args['headers'] = $args['headers'] ?? [];
		    $args['headers']['Authorization'] = 'token ' . $token;
		    $args['headers']['User-Agent']    = $args['headers']['User-Agent'] ?? 'WordPress-UUPD';

		    // GitHub asset API requires this to stream the binary
		    if ( strpos( $url, 'api.github.com/repos/' ) !== false && strpos( $url, '/releases/assets/' ) !== false ) {
		        $args['headers']['Accept'] = 'application/octet-stream';
		    }

		    return $args;
		}


		/**
		 * Determine which asset name to pick from a GitHub release.
		 * Priority:
		 *  1) config['github_asset_name']
		 *  2) config['slug'] . '.zip'
		 *  3) config['real_slug'] . '.zip'
		 *  4) null (means: first .zip)
		 */
		private function get_github_asset_name() {
		    $c = $this->config;

		    if ( ! empty( $c['github_asset_name'] ) ) {
		        return (string) $c['github_asset_name'];
		    }

		    if ( ! empty( $c['slug'] ) ) {
		        return (string) $c['slug'] . '.zip';
		    }

		    if ( ! empty( $c['real_slug'] ) ) {
		        return (string) $c['real_slug'] . '.zip';
		    }

		    return null;
		}

		/**
		 * Build the GitHub API URL for /releases/latest from a repo root URL.
		 * Example input: https://github.com/owner/repo
		 * Output: https://api.github.com/repos/owner/repo/releases/latest
		 */
		private function github_latest_release_api_url( $repo_url ) {
		    $repo_url = rtrim( (string) $repo_url, '/' );
		    $path     = trim( (string) wp_parse_url( $repo_url, PHP_URL_PATH ), '/' ); // owner/repo
		    return "https://api.github.com/repos/{$path}/releases/latest";
		}

		/**
		 * Resolve the download URL for a release:
		 * - Prefer a matching .zip asset (private-safe) via API asset endpoint
		 * - Otherwise fall back to zipball_url
		 */
		private function github_release_download_url( $repo_url, $release ) {
		    $repo_url = rtrim( (string) $repo_url, '/' );
		    $path     = trim( (string) wp_parse_url( $repo_url, PHP_URL_PATH ), '/' ); // owner/repo

		    // If we have a token, we can safely use the API asset endpoint
		    $slug  = $this->config['slug'] ?? '';
		    $token = self::apply_filters_per_slug(
		        'uupd/github_token_override',
		        $this->config['github_token'] ?? '',
		        $slug
		    );
		    $use_api_assets = ! empty( $token );

		    $wanted = $this->get_github_asset_name();
		    $wanted_lc = $wanted ? strtolower( $wanted ) : null;

		    if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {

		        if ( $wanted_lc ) {
		            foreach ( $release->assets as $asset ) {
		                if ( ! empty( $asset->name ) ) {
		                    if ( strtolower( (string) $asset->name ) === $wanted_lc ) {
		                        if ( $use_api_assets && ! empty( $asset->id ) ) {
		                            return "https://api.github.com/repos/{$path}/releases/assets/{$asset->id}";
		                        }
		                        // old behaviour for public repos:
		                        return $asset->browser_download_url ?? '';
		                    }
		                }
		            }
		        }

		        foreach ( $release->assets as $asset ) {
		            if ( ! empty( $asset->name ) && self::ends_with( strtolower( (string) $asset->name ), '.zip' ) ) {
		                if ( $use_api_assets && ! empty( $asset->id ) ) {
		                    return "https://api.github.com/repos/{$path}/releases/assets/{$asset->id}";
		                }
		                return $asset->browser_download_url ?? '';
		            }
		        }
		    }

		    return $release->zipball_url ?? '';
		}




        private static function ends_with( $haystack, $needle ) {
            if ( function_exists( 'str_ends_with' ) ) {
                return \str_ends_with( (string) $haystack, (string) $needle );
            }
            $haystack = (string) $haystack;
            $needle   = (string) $needle;
            if ( $needle === '' ) {
                return true;
            }
            if ( strlen( $needle ) > strlen( $haystack ) ) {
                return false;
            }
            return substr( $haystack, -strlen( $needle ) ) === $needle;
        }

        /**
         * NEW STATIC HELPER: register everything (was the global function before).
         *
         * @param array $config  Same structure you passed to the old uupd_register_updater_and_manual_check().
         */
        public static function register( array $config ) {
            // 1) Instantiate the updater class:
            new self( $config );

            // 2) Add the “Check for updates” link under the plugin row:
            $our_file   = $config['plugin_file'] ?? null;
            $slug       = $config['slug'];
            $textdomain = ! empty( $config['textdomain'] ) ? $config['textdomain'] : $slug;

            // Only register plugin row meta for plugins, not themes.
            if ( $our_file ) {
                add_filter(
                    'plugin_row_meta',
                    function( array $links, string $file, array $plugin_data ) use ( $our_file, $slug, $textdomain ) {
                        if ( $file === $our_file ) {
                            $nonce     = wp_create_nonce( 'uupd_manual_check_' . $slug );
                            $check_url = admin_url( sprintf(
                                'admin.php?action=uupd_manual_check&slug=%s&_wpnonce=%s',
                                rawurlencode( $slug ),
                                $nonce
                            ) );

                            $links[] = sprintf(
                                '<a href="%s">%s</a>',
                                esc_url( $check_url ),
                                esc_html__( 'Check for updates', $textdomain )
                            );
                        }
                        return $links;
                    },
                    10,
                    3
                );
            }

            // 3) Hook up the manual‐check listener:
            add_action( 'admin_action_uupd_manual_check', function() use ( $slug, $config ) {
                // 1) Grab the requested slug and normalize it.
                $request_slug = isset( $_REQUEST['slug'] ) ? sanitize_key( wp_unslash( $_REQUEST['slug'] ) ) : '';

                // 2) If the incoming 'slug' doesn’t match this plugin’s slug, bail out early:
                if ( $request_slug !== $slug ) {
                    return;
                }

                // 3) Only users who can update plugins/themes should proceed.
                if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'update_themes' ) ) {
                    wp_die( __( 'Cheatin’ uh?' ) );
                }

                // 4) Verify the nonce for this slug.
                $nonce     = isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : '';
                $checkname = 'uupd_manual_check_' . $slug;
                if ( ! wp_verify_nonce( $nonce, $checkname ) ) {
                    wp_die( __( 'Security check failed.' ) );
                }

                // 5) It’s our plugin’s “manual check,” so clear the transient and force WP to fetch again.
                $prefix = $config['cache_prefix'] ?? 'upd_';
                delete_transient( $prefix . $slug );
                delete_transient( $prefix . $slug . '_error' );

                // ALSO clear GitHub release cache if using GitHub.
                if ( isset( $config['server'] ) && strpos( $config['server'], 'github.com' ) !== false ) {
				    $repo_url = rtrim( $config['server'], '/' );

				    // Match the key used by the updater.
				    $gh_key = 'uupd_github_release_' . ( $config['slug'] ?? '' ) . '_' . md5( $repo_url );
				    delete_transient( $gh_key );

				    // (Optional backwards-compat cleanup if you had old deployments)
				    delete_transient( 'uupd_github_release_' . md5( $repo_url ) );
				}


                if ( ! empty( $config['plugin_file'] ) ) {
                    wp_update_plugins();
                    $redirect = wp_get_referer() ?: admin_url( 'plugins.php' );
                } else {
                    wp_update_themes();
                    $redirect = wp_get_referer() ?: admin_url( 'themes.php' );
                }

                $redirect = UUPD_Updater_V1::apply_filters_per_slug( 'uupd/manual_check_redirect', $redirect, $slug );
                wp_safe_redirect( $redirect );
                exit;
            } );
        }
    }
}
