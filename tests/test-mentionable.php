<?php
/**
 * Verifies main class is working properly.
 *
 * @author X-Team
 * @author Akeda Bagus <akeda@x-team.com>
 * @author Jonathan Bardo <jonathan.bardo@x-team.com>
 */

class Test_Mentionable extends WP_UnitTestCase {

	/**
	 * Holds the plugin base class
	 *
	 * @return void
	 */
	private $plugin;

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
		$this->assertFalse( null == $this->plugin );
	}

	/**
	 * Test constructor
	 *
	 * @return void
	 */

	public function test_contructor() {
		$this->assertEquals( 1, has_action( 'plugins_loaded', array( $this->plugin, 'define_constants' ) ), 'define_constants action is not defined or has the wrong priority' );
		$this->assertEquals( 2, has_action( 'plugins_loaded', array( $this->plugin, 'i18n' ) ), 'i18n action is not defined or has the wrong priority' );

		$this->assertEquals( 3, has_action( 'after_setup_theme', array( $this->plugin, 'setup' ) ), 'The setup function is not called' );
	}


	/**
	 * Check if required constant are defined
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_define_constants() {
		$this->assertTrue( defined( 'MENTIONABLE_DIR' ), 'MENTIONABLE_DIR is not defined' );
		$this->assertTrue( defined( 'MENTIONABLE_INCLUDES_DIR' ), 'MENTIONABLE_INCLUDES_DIR is not defined' );
		$this->assertTrue( defined( 'MENTIONABLE_URL' ), 'MENTIONABLE_MENTION_URL is not defined' );
	}

	/**
	 * Check if the textdomain is loaded
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_i18n() {
		global $l10n;

		// No string in constructor, so we add one here
		$test = __( 'test', 'mentionable' );
		$this->assertArrayHasKey( 'mentionable', $l10n, 'Text Domain is not loaded or has the wrong name' );
	}

	/**
	 * Check if all dependent class are loaded
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_setup() {
		$this->assertGreaterThan( 0, has_action( 'admin_init', array( $this->plugin, 'admin_init' ) ), 'init action is not defined or has the wrong priority' );
		$this->assertGreaterThan( 0, has_filter( 'mce_css', array( $this->plugin, 'filter_mce_css' ) ), 'filter_mce_css action is not defined or has the wrong priority' );
		$this->assertGreaterThan( 0, has_action( 'admin_enqueue_scripts', array( $this->plugin, 'admin_enqueue_scripts' ) ), 'admin_enqueue_scripts action is not defined or has the wrong priority' );

		require_once( MENTIONABLE_INCLUDES_DIR . '/mentionable-settings.php' );
		$this->assertInstanceOf( 'Mentionable_Settings', $this->plugin->settings );

		require_once( MENTIONABLE_INCLUDES_DIR . '/mentionable-postmetas.php' );
		$this->assertInstanceOf( 'Mentionable_Postmetas', $this->plugin->postmetas );
	}

	/**
	 * Test init action
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_admin_init() {
		do_action( 'admin_init' );

		$this->assertEquals( 'true', get_user_option( 'rich_editing' ), 'Current user must have rich_editing option on to use this plugin' );
		$this->assertGreaterThan( 0, has_filter( 'mce_external_plugins', array( $this->plugin, 'register_tmce_plugin' ), 'mce_external_plugins must be defined for the plugin to load' ) );

		require_once( MENTIONABLE_INCLUDES_DIR . '/mentionable-autocomplete.php' );
		$this->assertInstanceOf( 'Mentionable_Autocomplete', $this->plugin->autocomplete );
	}

	/**
	 * Test admin_enqueue_scripts action
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_admin_enqueue_scripts() {
		set_current_screen( 'edit.php' );
		do_action( 'admin_enqueue_scripts' );

		// Check the css is being enqueued
		$this->assertTrue( wp_style_is( 'mentionable_css', 'enqueued' ), 'The required css is not enqueued' );
		// Test that the nonce is registered with wp_localize_script so the js can use it for ajax
		$localize_data = $GLOBALS['wp_scripts']->get_data( 'jquery-core', 'data' );
		$this->assertTrue( strrpos( $localize_data, 'nonce' ) > 0 );
		$this->assertTrue( strrpos( $localize_data, 'mentionable' ) >= 0 );
	}

	/**
	 * Test mce_css filter
	 *
	 * @return void
	 */
	public function test_register_tmce_plugin() {
		$filter_output = apply_filters( 'mce_external_plugins', array() );
		$this->assertArrayHasKey( 'mentionable', $filter_output );
		$this->assertEquals( $filter_output['mentionable'], MENTIONABLE_URL . '/js/mentionable-tmce.js' );
	}

	/**
	 * Test mce_css filter
	 *
	 * @return void
	 */
	public function test_filter_mce_css() {
		$filter_output = apply_filters( 'mce_css', '' );
		$this->assertTrue( strrpos( $filter_output, MENTIONABLE_URL . '/css/mentionable-tmce-style.css' ) >= 0 );
	}

}
