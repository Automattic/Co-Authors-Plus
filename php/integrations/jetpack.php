<?php
/**
 * Functions that integrate Co-Authors Plus with Edit Flow.
 */

/**
 * Filter non-native users added by Co-Author-Plus in Jetpack
 *
 * @since 3.1
 *
 * @param array $og_tags Required. Array of Open Graph Tags.
 * @param array $image_dimensions Required. Dimensions for images used.
 * @return array Open Graph Tags either as they were passed or updated.
 */
function cap_filter_jetpack_open_graph_tags( $og_tags, $image_dimensions ) {
	global $coauthors_plus;
	
	if ( is_author() ) {
		$author = get_queried_object();
		$og_tags['og:title']           = $author->display_name;
		$og_tags['og:url']             = get_author_posts_url( $author->ID, $author->user_nicename );
		$og_tags['og:description']     = $author->description;
		$og_tags['profile:first_name'] = $author->first_name;
		$og_tags['profile:last_name']  = $author->last_name;
		if ( isset( $og_tags['article:author'] ) ) {
			$og_tags['article:author'] = get_author_posts_url( $author->ID, $author->user_nicename );
		}
	} else if ( is_singular() && $coauthors_plus->is_post_type_enabled() ) {
		$authors = get_coauthors();
		if ( ! empty( $authors ) ) {
			$author = array_shift( $authors );
			if ( isset( $og_tags['article:author'] ) ) {
				$og_tags['article:author'] = get_author_posts_url( $author->ID, $author->user_nicename );
			}
		}
	}

	// Send back the updated Open Graph Tags
	return apply_filters( 'coauthors_open_graph_tags', $og_tags );
}
