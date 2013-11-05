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
		if ( 'on' === Mentionable_Settings::$options['load_template'] && apply_filters( 'mentionable_load_template', '__return_true' ) )
			$content = preg_replace_callback( '#<a\b[^>]*?\sdata-mentionable="([^"]*?)"[^>]*?>([^<]*?)</a>#', array( $this, 'handle_replacement' ), $content );

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
		global $post;

		// Get the id of the post
		$id           = absint( $matches[1] );
		$host_post    = $post;
		$mention_post = get_post( $id );

		if ( is_null( $mention_post ) )
			return $matches[2];

		// Assign the content of the tag so template can use it
		$post = $mention_post;
		// Reference to mentionable tag content if needed by template
		$post->mentionable_tag_content = $matches[2];
		//Reference to the host post if needed by template
		$post->mentionable_host_post = $host_post;

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
			$template_location = MENTIONABLE_DIR . 'templates/' . self::TEMPLATE_NAME . '.php';

		// Start output buffering so we could load a template
		ob_start();

		// Setup post_data for template use
		setup_postdata( $post );

		// Load located templates
		load_template( apply_filters( 'mentionable_template_location', $template_location, $post ), false );

		// Reset WordPress to default post data
		$post = $post->mentionable_host_post;
		setup_postdata( $post );

		// Return the result to the_content()
		return ob_get_clean();
	}
}
