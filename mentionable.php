<?php
/**
 * Plugin Name: Mentionable
 * Plugin URI: http://x-team.com
 * Description:
 * Version: 0.1.0
 * Author: X-Team, Jonathan Bardo, Topher
 * Author URI: http://x-team.com/wordpress/
 * License: GPLv2+
 * Text Domain: mentionable
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 X-Team (http://x-team.com/)
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
	 * @var object
	 * @access public
	 */
	public $autocomplete;

	/**
	 * Current admin post_type
	 *
	 * @var string
	 * @access public
	 */
	public static $current_post_type;

	/**
	 * Plugins options
	 *
	 * @var array
	 * @access private
	 */
	private static $options = array(
		'post_types' => array( 'post' ),
		'autocomplete_post_types' => array( 'post' ),
	);

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

		// Let the theme override some options
		self::$options = apply_filters( 'mentionable_options', self::$options );

		// Set constans needed by the plugin.
		add_action( 'plugins_loaded', array( $this, 'define_constants' ), 1 );

		// Internationalize the text strings used.
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 2 );

		// Register tmce plugin -- because pluggable.php is loaded after plugin
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Enqueue admin script
		if ( in_array( self::$current_post_type, self::$options['post_types'] ) ) {
			// Filter tinymce css to add custom
			add_filter( 'mce_css', array( $this, 'filter_mce_css' ) );

			// Enqueue admin script
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		// Filter content on post save
		add_action( 'save_post', array( $this, 'update_mention_meta' ), 10, 3 );

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
		load_plugin_textdomain( 'mentionable', false, 'mentionable/languages' );
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
			in_array( self::$current_post_type, self::$options['post_types'] ) 
		) {
			add_filter( 'mce_external_plugins',  array( $this, 'register_tmce_plugin' ) );
		}

		// Add ajax handler for autocomplete action
		require_once( MENTIONABLE_INCLUDES_DIR . '/' . self::$class_name . '-autocomplete.php' );
		$this->autocomplete = new Mentionable_Autocomplete();
		add_action( 'wp_ajax_get_mentionable', array( $this->autocomplete, 'handle_ajax' ) );
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

		$mce_css .= MENTIONABLE_URL . '/css/' . self::$class_name . '-tmce.style.css';

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

	/**
	 * Updates meta for current post
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @return null
	 */
	public function update_mention_meta( $post_id, $post, $update ) {

		if ( wp_is_post_revision( $post_id ) || ! $update)
			return

		$post = get_post( $post_id );

		// go get the post ids mentioned in this post
		$mentioned_ids = $this->get_mentioned_ids( $post->post_content );

		// stash them in post meta
		update_post_meta( $post_id, 'mentions', $mentioned_ids );

		foreach ( $mentioned_ids as $mention => $mention_data ) {

			$stack = get_post_meta( $mention, 'mentioned_by' );

			if ( $post->ID != $mention)
				$stack[0][$post_id] = $mention_data;

			update_post_meta( $mention, 'mentioned_by', $stack[0] );

		}

	}

	/**
	 * Parses $content looking for the ids of links with data-mentionable in them
	 *
	 * @since 0.1.0
	 * @access private
	 *
	 * @return array
	 */
	private function get_mentioned_ids( $content ) {

		// set up array
		$mentioned_ids = array();

		if ( empty( $content ) )
			return $mentioned_ids;

		// instantiate the DOM browser and get all the 'a' tags
		$dom = new DOMDocument();
		$dom->loadHTML( $content );
		$data_mentionables = $dom->getElementsByTagName( 'a' );


		foreach ( $data_mentionables as $data_mentionable ) {

			// clean up the results a little bit
			$post_id = absint( stripslashes( str_replace( '"' , '' , $data_mentionable->getAttribute( 'data-mentionable' ) ) ) );

			$post_object = get_post( $post_id );

			// make sure we're getting an actual post ID, then pack into output var
			if ( $post_object )
				$mentioned_ids[ $post_id ] = true;

		}

		return $mentioned_ids;

	}

	/**
	 * $options getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public static function get_option( $name ) {
		if ( isset( self::$options[$name] ) ) {
			return self::$options[$name];
		}

		return null;
	}

}

// Register global plugin controller
$GLOBALS['mentionable'] = new Mentionable();
