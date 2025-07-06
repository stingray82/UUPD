<?php
add_action('after_setup_theme', function() {
    require_once get_stylesheet_directory() . '/uupd/updater.php';

    $config = [
        'slug'         => 'uupd-test-theme',
        'name'         => 'UUPD Test Theme',
        'version'      => '1.0.0',
        'server'       => 'https://ServerGoeshere.com',
    ];

    \UUPD\V1\UUPD_Updater_V1::register($config);
});
