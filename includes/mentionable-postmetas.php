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

}
