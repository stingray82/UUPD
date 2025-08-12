<?php
// 1) Register the updater (unchanged except we read the option for allow_prerelease)
add_action('after_setup_theme', function () {
    require_once get_stylesheet_directory() . '/uupd/updater.php';

    $config = [
        'slug'    => 'uupd-test-theme',
        'name'    => 'UUPD Test Theme',
        'version' => '1.0.0',
        'server'  => 'https://ServerGoeshere.com',

        // feed the saved option into the config at bootstrap time
        'allow_prerelease' => (bool) get_option('uupd_test_theme_allow_prerelease', false),
    ];

    \UUPD\V1\UUPD_Updater_V1::register($config);
});

// 2) Also provide the per-slug filter so changes apply without a reload race
add_filter('uupd/allow_prerelease/uupd-test-theme', function ($allow, $slug) {
    return (bool) get_option('uupd_test_theme_allow_prerelease', false);
}, 10, 2);

// 3) Settings storage (an option) + field in a Theme page
add_action('admin_init', function () {
    register_setting(
        'uupd_test_theme_settings',
        'uupd_test_theme_allow_prerelease',
        [
            'type'              => 'boolean',
            'sanitize_callback' => static function ($v) { return (int) ! empty($v); },
            'default'           => 0,
        ]
    );

    add_settings_section(
        'uupd_test_theme_updates',
        __('Update Settings', 'uupd-test-theme'),
        '__return_false',
        'uupd-test-theme-settings' // page slug below
    );

    add_settings_field(
        'uupd_test_theme_allow_prerelease_field',
        __('Allow prereleases', 'uupd-test-theme'),
        function () {
            $val = (bool) get_option('uupd_test_theme_allow_prerelease', false);
            ?>
            <label>
                <input type="checkbox" name="uupd_test_theme_allow_prerelease" value="1" <?php checked($val, true); ?> />
                <?php esc_html_e('Enable beta/RC/dev updates', 'uupd-test-theme'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the updater will offer pre-release versions (alpha, beta, RC, dev).', 'uupd-test-theme'); ?>
            </p>
            <?php
        },
        'uupd-test-theme-settings',
        'uupd_test_theme_updates'
    );
});

// 4) Add Appearance → UUPD Test Theme page
add_action('admin_menu', function () {
    add_theme_page(
        __('UUPD Test Theme', 'uupd-test-theme'),
        __('UUPD Test Theme', 'uupd-test-theme'),
        'edit_theme_options',
        'uupd-test-theme-settings',
        'uupd_test_theme_render_settings_page'
    );
});

// 5) Render the page (includes “Check for updates now” button)
function uupd_test_theme_render_settings_page() {
    if ( ! current_user_can('edit_theme_options') ) {
        return;
    }

    $slug      = 'uupd-test-theme';
    $nonce     = wp_create_nonce('uupd_manual_check_' . $slug);
    $check_url = admin_url(sprintf(
        'admin.php?action=uupd_manual_check&slug=%s&_wpnonce=%s',
        rawurlencode($slug),
        $nonce
    ));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('UUPD Test Theme', 'uupd-test-theme'); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('uupd_test_theme_settings');
            do_settings_sections('uupd-test-theme-settings');
            submit_button(__('Save Changes', 'uupd-test-theme'));
            ?>
        </form>

        <hr />
        <p><?php esc_html_e('Need to refresh update data now?', 'uupd-test-theme'); ?></p>
        <p>
            <a class="button button-secondary" href="<?php echo esc_url($check_url); ?>">
                <?php esc_html_e('Check for updates now', 'uupd-test-theme'); ?>
            </a>
        </p>
    </div>
    <?php
}


/*
// Point the theme updater somewhere else
add_filter('uupd/server_url/uupd-test-theme', function ($url, $slug) {
    return 'https://example.com/update/'; // or a static JSON: https://raw.githubusercontent.com/user/repo/branch/uupd/index.json
}, 10, 2);

/*