<?php
/**
 * Plugin Name: Mentionable
 * Plugin URI: https://xwp.co
 * Description: Mention WordPress content with inline autocomplete inside tinyMCE.
 * Version: 0.2.1
 * Author: XWP, Jonathan Bardo, Topher
 * Author URI: https://xwp.co/
 * License: GPLv2+
 * Text Domain: mentionable
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class Mentionable {

	/**
	 * Class name in lowercase
	 *
	 * @var string
	 * @access private
	 */
	private static $class_name;

	/**
	 * Autocomplete component class
	 *
	 * @var Mentionable_Autocomplete
	 * @access public
	 */
	public $autocomplete;

	/**
	 * Content component class
	 *
	 * @var Mentionable_Content
	 * @access public
	 */
	public $content;

	/**
	 * Post metas component class
	 *
	 * @var Mentionable_Postmetas
	 * @access public
	 */
	public $postmetas;

	/**
	 * Settings instance
	 *
	 * @var Mentionable_Settings
	 * @access public
	 */
	public $settings;

	/**
	 * Current admin post_type
	 *
	 * @var string
	 * @access public
	 */
	public static $current_post_type;

	/**
	 * Constructor | Add required hooks
	 *
	 * @access public
	 *
	 * @return \Mentionable
	 */
	public function __construct() {
		// Set class name in lowercase
		self::$class_name = strtolower( __CLASS__ );

		// Get current post type in admin
		self::$current_post_type = $this->get_current_admin_post_type();

		// Set constans needed by the plugin.
		add_action( 'plugins_loaded', array( $this, 'define_constants' ), 1 );

		// Internationalize the text strings used.
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 2 );

		// Setup all dependent class
		add_action( 'after_setup_theme', array( $this, 'setup' ), 3 );
	}

	/**
	 * Define constants used by the plugin.
	 *
	 * @access public
	 * @action plugins_loaded
	 * @return void
	 */
	public function define_constants() {
		// Set constant path to the plugin directory.
		define( 'MENTIONABLE_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		// Set constant path to the includes directory.
		define( 'MENTIONABLE_INCLUDES_DIR', MENTIONABLE_DIR . trailingslashit( 'includes' ) );

		// Plugin url
		$plugin_dirname = basename( dirname( __FILE__ ) );
		define( 'MENTIONABLE_URL', trailingslashit( plugin_dir_url( '' ) ) . $plugin_dirname );
	}

	/**
	 * Loads the translation files.
	 *
	 * @access public
	 * @action plugins_loaded
	 * @return void
	 */
	public function i18n() {
		// Load the translation of the plugin
		load_plugin_textdomain( 'mentionable', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Setup all classes needed for the plugin
	 *
	 * @access public
	 * @action plugins_loaded
	 * @return void
	 */
	public function setup() {
		// Register settings
		require_once( MENTIONABLE_INCLUDES_DIR . '/' . self::$class_name . '-settings.php' );
		$this->settings = new Mentionable_Settings;

		// Register tmce plugin -- because pluggable.php is loaded after plugin
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		if ( in_array( self::$current_post_type, Mentionable_Settings::$options['post_types'] ) ) {
			// Filter tinymce css to add custom
			add_filter( 'mce_css', array( $this, 'filter_mce_css' ) );

			// Enqueue admin script
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		// Intanciate postmetas class
		require_once( MENTIONABLE_INCLUDES_DIR . '/' . self::$class_name . '-postmetas.php' );
		$this->postmetas = new Mentionable_Postmetas;

		// Instanciate content class
		require_once( MENTIONABLE_INCLUDES_DIR . '/' . self::$class_name . '-content.php' );
		$this->content = new Mentionable_Content;
	}

	/**
	 * Add the required action and filter after init hook
	 *
	 * @action admin_init
	 * @access public
	 *
	 * @return void
	 */
	public function admin_init(){
		if (
			'true' === get_user_option( 'rich_editing' ) 
			&& 
			in_array( self::$current_post_type, Mentionable_Settings::$options['post_types'] ) 
		) {
			add_filter( 'mce_external_plugins',  array( $this, 'register_tmce_plugin' ) );
		}

		// Add ajax handler for autocomplete action
		require_once( MENTIONABLE_INCLUDES_DIR . '/' . self::$class_name . '-autocomplete.php' );
		$this->autocomplete = new Mentionable_Autocomplete();
	}

	/**
	 * Register the tinymce plugin
	 *
	 * @param array $plugin_array
	 * @filter mce_external_plugins
	 * @access public
	 *
	 * @return array
	 */
	public function register_tmce_plugin( $plugin_array ) {
		$plugin_array['mentionable'] = MENTIONABLE_URL . '/js/' . self::$class_name . '-tmce.js';

		return $plugin_array;
	}

	/**
	 * Enqueue required scripts and style in admin section
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'mentionable_css', MENTIONABLE_URL . '/css/' . self::$class_name . '-style.css', '0.1.0' );
		wp_localize_script(
			'jquery',
			self::$class_name,
			array(
				'nonce'  => wp_create_nonce( self::$class_name . '_nonce' ),
				'action' => 'get_mentionable',
			)
		);
	}

	/**
	 * Add custom css in tinyMCE iframe
	 *
	 * @param string $mce_css The concatenated css
	 *
	 * @return string
	 */
	public function filter_mce_css( $mce_css ) {
		if ( ! empty( $mce_css ) ){
			$mce_css .= ',';
		}

		if ( apply_filters( 'add_mentionnable_tmce_style', '__return_true' ) ) {
			$mce_css .= MENTIONABLE_URL . '/css/' . self::$class_name . '-tmce-style.css';
		}

		return $mce_css;
	}

	/**
	 * Helper function to get current admin post_type
	 *
	 * @since 0.1.0
	 * @access private
	 * @attribution mjangda <https://gist.github.com/mjangda/476964>
	 *
	 * @return string
	 */
	private function get_current_admin_post_type() {
		global $pagenow;

		$pages = array( 'edit.php', 'post.php', 'post-new.php' );

		if ( in_array( $pagenow, $pages ) && ! isset( $_REQUEST['post_type'] ) ) {
			$current_post_type = 'post';
		} else if ( isset( $_REQUEST['post_type'] ) ) {
			$current_post_type = sanitize_key( $_REQUEST['post_type'] );
		}

		return isset( $current_post_type ) ? $current_post_type : null;
	}

}

// Register global plugin controller
$GLOBALS['mentionable'] = new Mentionable();
