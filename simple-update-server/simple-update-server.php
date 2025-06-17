<?php
/**
 * Plugin Name:     UUPD Server
 * Description:     Provides a custom post type and secure file download or remote redirect for Universal Updater (UUPD_Updater), with placeholder support and referer-based domain detection.
 * Version:         1.0.9
 * Author:          Stingray82
 * Text Domain:     uupd-server
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UUPD_Server {
    const TOKEN_EXPIRY = 3600;

    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_update', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media' ] );
        add_action( 'template_redirect', [ $this, 'handle_requests' ] );
    }

    public function register_cpt() {
        register_post_type( 'update', [
            'label'    => __( 'Updates', 'uupd-server' ),
            'public'   => false,
            'show_ui'  => true,
            'supports' => [ 'title' ],
        ] );
    }

    public function add_meta_boxes() {
        add_meta_box( 'uupd_meta', __( 'Update Details', 'uupd-server' ), [ $this, 'render_meta_box' ], 'update', 'normal', 'default' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'uupd_save', 'uupd_nonce' );
        $m = get_post_meta( $post->ID );
        foreach ( [ 'slug','version','tested','homepage','remote_url' ] as $f ) {
            $$f = $m[ $f ][0] ?? '';
        }
        $min_wp  = $m['min_wp_version'][0] ?? '';
        $min_php = $m['min_php_version'][0] ?? '';

        $sizes = [ 'icon_128','icon_256','banner_773','banner_1544' ];
        foreach ( $sizes as $field ) {
            ${$field . '_id'}  = intval( $m[ $field . '_id' ][0] ?? 0 );
            ${$field . '_url'} = esc_url( $m[ $field . '_url' ][0] ?? '' );
        }

        $changelog     = $m['changelog_html'][0] ?? '';
        $attachment_id = intval( $m['file_id'][0] ?? 0 );
        ?>
        <table class="form-table">
            <?php foreach ( [ 'slug','version','tested','homepage','remote_url' ] as $f ): ?>
                <tr>
                    <th><label for="uupd_<?php echo esc_attr( $f ); ?>"><?php echo ucfirst( str_replace( '_', ' ', $f ) ); ?></label></th>
                    <td><input type="text" id="uupd_<?php echo esc_attr( $f ); ?>" name="uupd_<?php echo esc_attr( $f ); ?>" value="<?php echo esc_attr( $$f ); ?>" class="regular-text"></td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th><label for="uupd_min_wp_version"><?php _e( 'Min WP Version', 'uupd-server' ); ?></label></th>
                <td><input type="text" id="uupd_min_wp_version" name="uupd_min_wp_version" value="<?php echo esc_attr( $min_wp ); ?>" class="regular-text" placeholder="e.g. 5.0"></td>
            </tr>
            <tr>
                <th><label for="uupd_min_php_version"><?php _e( 'Min PHP Version', 'uupd-server' ); ?></label></th>
                <td><input type="text" id="uupd_min_php_version" name="uupd_min_php_version" value="<?php echo esc_attr( $min_php ); ?>" class="regular-text" placeholder="e.g. 7.2"></td>
            </tr>

            <tr><th colspan="2"><strong><?php _e( 'Icons', 'uupd-server' ); ?></strong> (128×128 &amp; 256×256)</th></tr>
            <?php foreach ( [ ['128','icon_128'], ['256','icon_256'] ] as list( $size, $field ) ): ?>
                <tr>
                    <th><label for="uupd_<?php echo esc_attr( $field ); ?>_url"><?php echo esc_html( "$size × $size" ); ?></label></th>
                    <td>
                        <input type="url" id="uupd_<?php echo esc_attr( $field ); ?>_url" name="uupd_<?php echo esc_attr( $field ); ?>_url" value="<?php echo esc_attr( ${$field . '_url'} ); ?>" class="regular-text" placeholder="https://...">
                        <input type="hidden" id="uupd_<?php echo esc_attr( $field ); ?>_id" name="uupd_<?php echo esc_attr( $field ); ?>_id" value="<?php echo intval( ${$field . '_id'} ); ?>">
                        <button type="button" class="button" id="uupd_<?php echo esc_attr( $field ); ?>_button"><?php _e( 'Select/Upload', 'uupd-server' ); ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr><th colspan="2"><strong><?php _e( 'Banners', 'uupd-server' ); ?></strong> (773×250 &amp; 1544×500)</th></tr>
            <?php foreach ( [ ['773×250','banner_773'], ['1544×500','banner_1544'] ] as list( $size, $field ) ): ?>
                <tr>
                    <th><label for="uupd_<?php echo esc_attr( $field ); ?>_url"><?php echo esc_html( $size ); ?></label></th>
                    <td>
                        <input type="url" id="uupd_<?php echo esc_attr( $field ); ?>_url" name="uupd_<?php echo esc_attr( $field ); ?>_url" value="<?php echo esc_attr( ${$field . '_url'} ); ?>" class="regular-text" placeholder="https://...">
                        <input type="hidden" id="uupd_<?php echo esc_attr( $field ); ?>_id" name="uupd_<?php echo esc_attr( $field ); ?>_id" value="<?php echo intval( ${$field . '_id'} ); ?>">
                        <button type="button" class="button" id="uupd_<?php echo esc_attr( $field ); ?>_button"><?php _e( 'Select/Upload', 'uupd-server' ); ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th><label for="uupd_changelog_html"><?php _e( 'Changelog', 'uupd-server' ); ?></label></th>
                <td><?php wp_editor( $changelog, 'uupd_changelog_html', [ 'textarea_name' => 'uupd_changelog_html', 'textarea_rows' => 10 ] ); ?></td>
            </tr>

            <tr>
                <th><?php _e( 'File', 'uupd-server' ); ?></th>
                <td>
                    <input type="hidden" id="uupd_file_id" name="uupd_file_id" value="<?php echo $attachment_id; ?>">
                    <button type="button" class="button" id="uupd_file_button"><?php _e( 'Select/Upload File', 'uupd-server' ); ?></button>
                    <p class="description" id="uupd_file_name"><?php echo $attachment_id ? esc_html( get_the_title( $attachment_id ) ) : ''; ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function enqueue_media() {
        global $post;
        if ( $post && $post->post_type === 'update' ) {
            wp_enqueue_media();
            $fields = [ 'icon_128', 'icon_256', 'banner_773', 'banner_1544', 'file' ];
            $script = "jQuery(function($){";
            foreach ( $fields as $f ) {
                $script .= "(function(){var frame;$('#uupd_{$f}_button').on('click',function(e){e.preventDefault();if(frame)frame.open();else{frame=wp.media({title:'Select Media',button:{text:'Use'},multiple:false});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();$('#uupd_{$f}_id').val(a.id);$('#uupd_{$f}_url').val(a.url);if($('#uupd_{$f}_name').length)$('#uupd_{$f}_name').text(a.filename||a.url);});}frame.open();});})();";
            }
            $script .= "});";
            wp_add_inline_script( 'jquery', $script );
        }
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['uupd_nonce'] ) || ! wp_verify_nonce( $_POST['uupd_nonce'], 'uupd_save' ) ) {
            return;
        }
        foreach ( [ 'slug', 'version', 'tested', 'homepage', 'remote_url', 'min_wp_version', 'min_php_version' ] as $f ) {
            if ( isset( $_POST['uupd_' . $f] ) ) {
                update_post_meta( $post_id, $f, sanitize_text_field( $_POST['uupd_' . $f] ) );
            }
        }
        foreach ( [ 'icon_128', 'icon_256', 'banner_773', 'banner_1544', 'file' ] as $f ) {
            if ( isset( $_POST['uupd_' . $f . '_url'] ) ) {
                update_post_meta( $post_id, $f . '_url', esc_url_raw( $_POST['uupd_' . $f . '_url'] ) );
            }
            if ( isset( $_POST['uupd_' . $f . '_id'] ) ) {
                update_post_meta( $post_id, $f . '_id', intval( $_POST['uupd_' . $f . '_id'] ) );
            }
        }
        if ( isset( $_POST['uupd_changelog_html'] ) ) {
            update_post_meta( $post_id, 'changelog_html', wp_kses_post( $_POST['uupd_changelog_html'] ) );
        }
        update_post_meta( $post_id, 'last_updated', current_time( 'mysql' ) );
    }

    public function handle_requests() {
        $action = $_GET['action'] ?? '';
        if ( $action === 'get_metadata' ) {
            $this->output_metadata();
        } elseif ( $action === 'download' ) {
            $this->serve_download();
        }
    }

    private function output_metadata() {
    $slug   = sanitize_text_field( $_GET['slug'] ?? '' );
    $key    = sanitize_text_field( $_GET['key'] ?? '' );
    $domain = ! empty( $_GET['domain'] )
        ? sanitize_text_field( $_GET['domain'] )
        : (
            ! empty( $_SERVER['HTTP_REFERER'] )
                ? wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST )
                : sanitize_text_field( $_SERVER['HTTP_HOST'] ?? '' )
        );

    $posts = get_posts([
        'post_type'  => 'update',
        'meta_key'   => 'slug',
        'meta_value' => $slug,
    ]);
    if ( ! $posts ) {
        wp_send_json_error( 'Invalid slug', 404 );
    }
    $post = $posts[0];

    // Base metadata
    $meta = [];
    foreach ( [ 'slug', 'version', 'tested', 'homepage', 'changelog_html', 'last_updated', 'min_wp_version', 'min_php_version' ] as $f ) {
        $meta[ $f ] = get_post_meta( $post->ID, $f, true ) ?: '';
    }

    // Icons & banners
    $meta['icons'] = [
        '1x' => get_post_meta( $post->ID, 'icon_128_url', true ) ?: get_post_meta( $post->ID, 'icon_256_url', true ),
        '2x' => get_post_meta( $post->ID, 'icon_256_url', true ),
    ];
    $meta['banners'] = [
        'low'  => get_post_meta( $post->ID, 'banner_773_url', true ) ?: get_post_meta( $post->ID, 'banner_1544_url', true ),
        'high' => get_post_meta( $post->ID, 'banner_1544_url', true ),
    ];

    // Determine download_url
    $remote = get_post_meta( $post->ID, 'remote_url', true );
    if ( $remote ) {
        // Remote URL takes priority
        $meta['download_url'] = esc_url_raw( $remote );
    } else {
        // Fall back to signed local download
        $file_id = get_post_meta( $post->ID, 'file_id', true );
        if ( $file_id ) {
            $expires = time() + self::TOKEN_EXPIRY;
            $data    = "{$slug}|{$file_id}|{$expires}";
            $token   = hash_hmac( 'sha256', $data, AUTH_KEY );
            $meta['download_url'] = esc_url_raw( add_query_arg([
                'action'  => 'download',
                'slug'    => $slug,
                'expires' => $expires,
                'token'   => $token,
                'key'     => $key,
                'domain'  => $domain,
            ], site_url( '/' ) ) );
        } else {
            // No download source available
            $meta['download_url'] = '';
        }
    }

    wp_send_json( $meta );
}


    private function serve_download() {
    $slug    = sanitize_text_field( $_GET['slug'] ?? '' );
    $expires = intval( $_GET['expires'] ?? 0 );
    $token   = sanitize_text_field( $_GET['token'] ?? '' );

    // 1) Expiry check
    if ( time() > $expires ) {
        wp_die( 'Download link expired', 403 );
    }

    // 2) Lookup the update post
    $posts = get_posts( [
        'post_type'  => 'update',
        'meta_key'   => 'slug',
        'meta_value' => $slug,
    ] );
    if ( ! $posts ) {
        wp_die( 'Invalid slug', 404 );
    }
    $post    = $posts[0];
    $file_id = get_post_meta( $post->ID, 'file_id', true );

    // 3) Ensure we have either a file or remote URL
    $remote = get_post_meta( $post->ID, 'remote_url', true );
    if ( $remote ) {
        // Redirect to remote URL
        wp_redirect( esc_url_raw( $remote ) );
        exit;
    }

    // 4) No remote URL: must have a local file
    if ( ! $file_id ) {
        wp_die( 'No file available', 404 );
    }

    // 5) Token validation
    $data = "{$slug}|{$file_id}|{$expires}";
    if ( ! hash_equals( hash_hmac( 'sha256', $data, AUTH_KEY ), $token ) ) {
        wp_die( 'Invalid token', 403 );
    }

    // 6) Serve local attachment
    $file = get_attached_file( $file_id );
    if ( ! $file || ! file_exists( $file ) ) {
        wp_die( 'File not found', 404 );
    }

    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
    header( 'Expires: 0' );
    header( 'Cache-Control: must-revalidate' );
    header( 'Pragma: public' );
    header( 'Content-Length: ' . filesize( $file ) );
    readfile( $file );
    exit;
}


}   


new UUPD_Server();

add_filter( 'wp_handle_upload_prefilter', 'uupd_randomize_media_filename' );
/**
 * Append a 15-char random suffix only when uploading from an 'update' CPT edit screen.
 */
function uupd_randomize_media_filename( $file ) {
    // try the AJAX post_id first
    $post_id = ! empty( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;

    // fallback: parse the post ID out of the referer URL (media modal iframe)
    if ( ! $post_id ) {
        $ref = wp_get_referer();
        if ( $ref && preg_match( '/post\.php\?post=(\d+)/', $ref, $matches ) ) {
            $post_id = (int) $matches[1];
        }
    }

    // only randomize when editing an 'update' post
    if ( ! $post_id || get_post_type( $post_id ) !== 'update' ) {
        return $file;
    }

    // generate and append the 15-char suffix
    $ext  = pathinfo( $file['name'], PATHINFO_EXTENSION );
    $name = pathinfo( $file['name'], PATHINFO_FILENAME );
    $rand = substr( wp_generate_password( 15, false, false ), 0, 15 );
    $file['name'] = sanitize_file_name( "{$name}-{$rand}.{$ext}" );

    return $file;
}