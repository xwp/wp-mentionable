<?php

/**
 * Settings class
 *
 * @author  Shady Sharaf <shady@x-team.com>
 */
class Mentionable_Settings {

	/**
	 * Settings key/identifier
	 */
	const KEY = 'mentionable';

	/**
	 * Plugin settings
	 * @var array
	 */
	public static $options = array();

	public function __construct() {
		// Register settings page
		add_action( 'admin_menu', array( $this, 'register_menu') );

		// Register settings, and fields
		add_action( 'admin_init', array($this, 'register_settings') );

		$defaults = array(
			'post_types'              => array( 'post' ),
			'autocomplete_post_types' => array( 'post' ),
			);

		self::$options = apply_filters(
			'mentionable_options',
			wp_parse_args(
				(array) get_option( self::KEY, array() ),
				$defaults
				)
			);
	}

	/**
	 * Register menu page
	 *
	 * @action admin_menu
	 * @return void
	 */
	public function register_menu() {
		if ( current_user_can( 'manage_options' ) ) {
			add_options_page(
				__( 'Mentionable', 'mentionable' ),
				__( 'Mentionable', 'mentionable' ),
				'manage_options',
				'mentionable',
				array( $this, 'settings_page' )
				);
		}
	}

	/**
	 * Render settings page
	 * @return void
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'tools' ); ?>
			<h2><?php _e( 'Mentionable Options', 'mentionable' ) ?></h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::KEY );
				do_settings_sections( self::KEY );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers settings fields and sections
	 * @return void
	 */
	public function register_settings() {
		register_setting( self::KEY, self::KEY );

		add_settings_section(
			'post_types', 
			__( 'Post Types', 'mentionable' ),
			'__return_false',
			self::KEY
			);

		add_settings_field(
			'post_types',
			__( 'Activate for', 'mentionable' ),
			array( $this, 'field_post_types' ),
			self::KEY,
			'post_types'
			);

		add_settings_field(
			'autocomplete_post_types',
			__( 'Autocomplete from', 'mentionable' ),
			array( $this, 'field_autocomplete_post_types' ),
			self::KEY,
			'post_types'
			);
	}

	/**
	 * Render Callback for post_types field
	 * @return void
	 */
	public function field_post_types() {
		global $wp_post_types;
		$slugs = array_keys( $wp_post_types );
		$names = wp_list_pluck( $wp_post_types, 'label' );
		$types = array_combine( $slugs, $names );
		$types = array_diff_key( $types, array_flip( array( 'nav_menu_item', 'revision' ) ) );
		$value = self::$options['post_types'];

		$output = '<select name="mentionable[post_types][]" multiple >';
		foreach ( $types as $slug => $name ) {
			$output .= sprintf( '<option value="%1$s" %3$s>%2$s</option>', $slug, $name, selected( in_array( $slug, $value ), true, false ) );
		}
		$output .= '</select>';

		$output .= sprintf(
			'<p class="description">%s</p>',
			__( 'Post types which this plugin will be activated for.', 'mentionable' )
			);

		echo balanceTags( $output );
	}

	/**
	 * Render Callback for autocomplete_post_types
	 * @return void
	 */
	public function field_autocomplete_post_types() {
		global $wp_post_types;
		$slugs = array_keys( $wp_post_types );
		$names = wp_list_pluck( $wp_post_types, 'label' );
		$types = array_combine( $slugs, $names );
		$types = array_diff_key( $types, array_flip( array( 'nav_menu_item', 'revision' ) ) );
		$value = self::$options['autocomplete_post_types'];

		$output = '<select name="mentionable[autocomplete_post_types][]" multiple >';
		foreach ( $types as $slug => $name ) {
			$output .= sprintf( '<option value="%1$s" %3$s>%2$s</option>', $slug, $name, selected( in_array( $slug, $value ), true, false ) );
		}
		$output .= '</select>';

		$output .= sprintf(
			'<p class="description">%s</p>',
			__( 'Post types that auto-completion will match against.', 'mentionable' )
			);

		echo balanceTags( $output );
	}

}

new Mentionable_Settings;
