<?php
/**
 * For themes where it's easily doable, add support for Co-Authors Plus on the frontend
 * by filtering the common template tags
 */

class CoAuthors_Template_Filters {

	function __construct() {
		add_filter( 'the_author', array( $this, 'filter_the_author' ) );
		add_filter( 'the_author_posts_link', array( $this, 'filter_the_author_posts_link' ) );
	}

	function filter_the_author() {
		return coauthors( null, null, null, null, false );
	}

	function filter_the_author_posts_link() {
		return coauthors_posts_links( null, null, null, null, false );
	}
}