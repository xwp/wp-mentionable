<?php
/**
 * Tests bootstrapper
 *
 * @author XWP <xwp.co>
 * @author Jonathan Bardo (@jonathanbardo)
 */

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _manually_load_plugin() {
	$current_plugin_path = dirname( dirname( __FILE__ ) );

	// We test like if we were on a post page
	$_REQUEST['post_type'] = 'post';
	require $current_plugin_path . '/mentionable.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
