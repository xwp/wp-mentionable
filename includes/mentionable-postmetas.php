<?php

/**
 * Provides a mechanism logging mentions both in the local post and in posts mentioned
 *
 * @package Mentionable
 * @since Mentionable 0.1.0
 * @author Topher
 */

/**
 * Mentionable meta data management class
 *
 * Contains functions for reading and writing mentions in the local post and in posts mentioned
 *
 * @class Mentionable_Postmetas
 * @version 1.0.0
 * @since 0.1.0
 * @package Mentionable
 * @author Topher
 */

class Mentionable_Postmetas {

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

			if ( ! $data_mentionable->hasAttribute( 'data-mentionable' ) )
				continue;

			// clean up the results a little bit
			$post_id = absint( stripslashes( str_replace( '"' , '' , $data_mentionable->getAttribute( 'data-mentionable' ) ) ) );

			$post_object = get_post( $post_id );

			// make sure we're getting an actual post ID, then pack into output var
			if ( $post_object )
				$mentioned_ids[ $post_id ] = true;

		}

		return $mentioned_ids;

	}

}
