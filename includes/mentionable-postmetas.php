<?php
/**
 * Mentionable meta data management class
 * Provides a mechanism logging mentions both in the local post and in posts mentioned
 * Contains functions for reading and writing mentions in the local post and in posts mentioned
 *
 * @class Mentionable_Postmetas
 * @version 1.0.0
 * @since 0.1.0
 * @package Mentionable
 * @author X-Team <x-team.com>
 * @author Topher <topher.derosia@x-team.com>
 */

class Mentionable_Postmetas {

	/**
	 * Class constructor
	 *
	 * @return \Mentionable_Postmetas
	 */
	public function __construct(){
		// Filter content on post save
		add_action( 'save_post', array( $this, 'update_mention_meta' ), 10, 3 );
		add_action( 'pre_post_update', array( $this, 'remove_post_meta' ), 10, 2 );
	}

	/**
	 * Updates meta for current post
	 *
	 * @since  0.1.0
	 * @access private
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @return null
	 */
	public function update_mention_meta( $post_id, $post, $update ) {

		if ( wp_is_post_revision( $post_id ) || ! $update )
			return

		$post = get_post( $post_id );

		// Go get the post ids mentioned in this post
		$mentioned_ids = $this->get_mentioned_ids( $post->post_content );

		// Stash them in post meta
		update_post_meta( $post_id, 'mentions', $mentioned_ids );

		foreach ( $mentioned_ids as $mention => $mention_data ) {

			$stack = get_post_meta( $mention, 'mentioned_by' );

			if ( $post->ID != $mention)
				$stack[0][$post_id] = $mention_data;

			update_post_meta( $mention, 'mentioned_by', $stack[0] );

		}

	}

	/**
	 * Removes ids from post meta on posts UNmentioned
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param $post_id
	 * @param $data
	 *
	 * @return null
	 */
	public function remove_post_meta( $post_id, $data ) {

		// get the version of the post from before this update
		$old_version = get_post( $post_id );

		// get the menttioned IDs from before the latest update
		$old_mentioned_ids = $this->get_mentioned_ids( $old_version->post_content );

		// get the mentioned IDs from the latest update
		$new_mentioned_ids = $this->get_mentioned_ids( $data['post_content'] );

		// figure out which IDs have been deleted
		$difference_ids = array_diff_key( $old_mentioned_ids, $new_mentioned_ids );

		// loop through the deleted IDs and unset them
		foreach ( $difference_ids as $post_id => $value ) {

			$stack = get_post_meta( $post_id, 'mentioned_by' );

			unset($stack[0][$old_version->ID]);

		}

		// update the post meta to remove IDs
		update_post_meta( $post_id, 'mentioned_by', $stack[0] );

	}


	/**
	 * Parses $content looking for the ids of links with data-mentionable in them
	 *
	 * @since  0.1.0
	 * @access private
	 *
	 * @param $content
	 *
	 * @return array
	 */
	private function get_mentioned_ids( $content ) {

		// Set up array
		$mentioned_ids = array();

		if ( empty( $content ) )
			return $mentioned_ids;

		// Instantiate the DOM browser and get all the 'a' tags
		$dom = new DOMDocument();
		$dom->loadHTML( $content );
		$data_mentionables = $dom->getElementsByTagName( 'a' );

		foreach ( $data_mentionables as $data_mentionable ) {

			if ( ! $data_mentionable->hasAttribute( 'data-mentionable' ) )
				continue;

			// Clean up the results a little bit
			$post_id = absint( stripslashes( str_replace( '"' , '' , $data_mentionable->getAttribute( 'data-mentionable' ) ) ) );

			$post_object = get_post( $post_id );

			// Make sure we're getting an actual post ID, then pack into output var
			if ( $post_object )
				$mentioned_ids[ $post_id ] = true;

		}

		return $mentioned_ids;

	}

}
