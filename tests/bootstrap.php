<?php

/**
 * Set up environment for my plugin's tests suite.
 */

/**
 * The path to the WordPress tests checkout.
 */
define( 'WP_TESTS_DIR', '/Applications/wordpress-3.8.3-0/apps/wordpress/htdocs/wp-content/plugins/hf-accountability/tests/wordpress-dev/trunk/tests/phpunit/' );

/**
 * The path to the main file of the plugin to test.
 */
define( 'TEST_PLUGIN_FILE', '/Applications/wordpress-3.8.3-0/apps/wordpress/htdocs/wp-content/plugins/hf-accountability/hf-accountability.php' );

/**
 * The WordPress tests functions.
 *
 * We are loading this so that we can add our tests filter
 * to load the plugin, using tests_add_filter().
 */
require_once WP_TESTS_DIR . 'includes/functions.php';

/**
 * Manually load the plugin main file.
 *
 * The plugin won't be activated within the test WP environment,
 * that's why we need to load it manually.
 *
 * You will also need to perform any installation necessary after
 * loading your plugin, since it won't be installed.
 */
function _manually_load_plugin() {

    require TEST_PLUGIN_FILE;

    // Make sure plugin is installed here ...

    add_action( 'init', 'hfCreateTestPages' );

    $_SERVER['SERVER_NAME'] = 'habitfree.org';
    $_SERVER["SERVER_PORT"] = 80;

    hfActivate();
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

function hfCreateTestPages() {
    $settingsPage = array(
        'post_title'  => 'Settings',
        'post_status' => 'publish',
        'post_type'   => 'page',
        'post_author' => 1
    );

    wp_insert_post( $settingsPage );
}

/**
 * Sets up the WordPress test environment.
 *
 * We've got our action set up, so we can load this now,
 * and viola, the tests begin.
 */
require WP_TESTS_DIR . 'includes/bootstrap.php';