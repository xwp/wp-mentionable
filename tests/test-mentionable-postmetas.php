<?php
/**
 * Verifies that Mentionable_Postmetas class is working properly
 *
 * @author X-Team <x-team.com>
 */

class Test_Mentionable_Postmetas extends WP_UnitTestCase {

	/**
	 * PHP unit setup function
	 *
	 * @return void
	 */
	function setUp() {
		parent::setUp();
		$this->plugin = $GLOBALS['mentionable'];

		// We need to change user to verify editing option as admin or editor
		$administrator_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		update_user_option( $administrator_id, 'rich_editing', 'true' );
		wp_set_current_user( $administrator_id );
	}

	/**
	 * Make sure the plugin is initialized with it's global variable
	 *
	 * @return void
	 */
	public function test_plugin_initialized() {
		$this->assertFalse( null == $this->plugin->postmetas );
	}

	public function test_constructor() {
		$this->assertEquals( 10, has_action( 'save_post', array( $this->plugin->postmetas, 'update_mention_meta' ) ), 'update_mention_meta action is not defined or has the wrong priority' );
		$this->assertEquals( 10, has_action( 'pre_post_update', array( $this->plugin->postmetas, 'remove_post_meta' ) ), 'remove_post_meta action is not defined or has the wrong priority' );
	}

}
