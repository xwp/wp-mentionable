<?php
/**
 * Tests bootstrapper
 *
 * @author X-Team <x-team.com>
 * @author Jonathan Bardo <jonathan.bardo@x-team.com>
 */

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _manually_load_plugin() {
	$current_plugin_path = dirname( dirname( __FILE__ ) );

	//We test like if we were on a post page
	$_REQUEST['post_type'] = 'post';
	require $current_plugin_path . '/mentionable.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

function _change_user() {
	// We need to change user to verify editing option as admin or editor
	update_user_option( 1, 'rich_editing', 'true' );
	wp_set_current_user( 1 );
}
tests_add_filter( 'init', '_change_user', 1 );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
