<?php
/**
 * Handle the mention text replacement if needed
 *
 * @author X-Team <x-team.com>
 * @author Jonathan Bardo <jonathan.bardo@x-team.com>
 */

class Mentionable_Content {

	/**
	 * Hold the template base name
	 *
	 * @access public
	 * @var string
	 */
	const TEMPLATE_NAME = 'mentionable';

	/**
	 * Class constructor
	 *
	 * @return \Mentionable_Content
	 */
	public function __construct(){
		add_filter( 'the_content', array( $this, 'the_content' ) );
	}

	/**
	 * Filter the_content to replace with mentionable template if needed.
	 *
	 * @filter the_content();
	 * @param $content
	 *
	 * @return string $content
	 */
	public function the_content( $content ){

		if ( 'on' === Mentionable_Settings::$options['load_template'] )
			$content = preg_replace_callback( '#<a .+? data-mentionable="([^"].*?)">([^<].*?)</a>#', array( $this, 'handle_replacement' ), $content );

		return $content;
	}

	/**
	 * Handle mentionable tag replacement with custom template
	 *
	 * @param array $matches
	 *
	 * @return string
	 */
	public function handle_replacement( $matches ) {
		// Get the id of the post
		$id = absint( $matches[1] );
		$post = get_post( $id );

		if ( is_null( $post ) )
			return $matches[2];

		// Assign the content of the tag so template can use it
		/** @noinspection PhpUndefinedFieldInspection */
		$post->mentionable_tag_content = $matches[2];

		// Create array of template
		$templates = array();

		// Search post-type specific template first
		if ( ! empty( $post->post_type ) )
			$templates[] = self::TEMPLATE_NAME . "-{$post->post_type}.php";

		// Load default template if in last resort
		$templates[] = self::TEMPLATE_NAME . '.php';

		// Try to locate the template
		$template_location = locate_template( $templates );

		// If we didn't find any template, fall back to the one from this plugin
		if ( '' === $template_location )
			$template_location = MENTIONABLE_DIR . 'templates/' .self::TEMPLATE_NAME . '.php';

		// Start output buffering so we could load a template
		ob_start();

		// Setup post_data for template use
		setup_postdata( $GLOBALS['post'] =& $post );

		// Load located templates
		load_template( apply_filters( 'mentionable_template_location', $template_location, $post ), false );

		// Reset WordPress to default post data
		wp_reset_postdata();

		// Return the result to the_content()
		return ob_get_clean();
	}
}
