<?php
/**
 * Auto-apply Co-Authors Plus template tags on themes that are properly using the_author()
 * and the_author_posts_link()
 * Auto-apply Co-Authors Plus in oembed endpoint
 */
$wpcom_coauthors_plus_auto_apply_themes = array(
		'premium/portfolio',
		'premium/zuki',
		'pub/editor',
	);
if ( in_array( get_option( 'template' ), $wpcom_coauthors_plus_auto_apply_themes )
	|| ( true === defined( 'WPCOM_VIP_IS_OEMBED' )
		&& true === constant( 'WPCOM_VIP_IS_OEMBED' )
		&& true === apply_filters( 'wpcom_vip_coauthors_replace_oembed', false, 'author_name' )	 
	) ) {
	add_filter( 'coauthors_auto_apply_template_tags', '__return_true' );
}

/**
 * If Co-Authors Plus is enabled on an Enterprise site and hasn't yet been integrated with the theme
 * show an admin notice
 */
if ( function_exists( 'Enterprise' ) ) {
	if ( Enterprise()->is_enabled() && ! in_array( get_option( 'template' ), $wpcom_coauthors_plus_auto_apply_themes ) )
		add_action( 'admin_notices', function() {

			// Allow this to be short-circuted in mu-plugins
			if ( ! apply_filters( 'wpcom_coauthors_show_enterprise_notice', true ) )
				return;

			echo '<div class="error"><p>' . __( "Co-Authors Plus isn't yet integrated with your theme. Please contact support to make it happen." ) . '</p></div>';
		} );
}

/**
 * We want to let Elasticsearch know that it should search the author taxonomy's name as a search field
 * See: https://elasticsearchp2.wordpress.com/2015/01/08/in-36757-z-vanguard-says-they/
 *
 * @param $es_wp_query_args The ElasticSearch Query Parameters
 * @param $query
 *
 * @return mixed
 */
function co_author_plus_es_support( $es_wp_query_args, $query ){
	if ( empty( $es_wp_query_args['query_fields'] ) ) {
		$es_wp_query_args['query_fields'] = array( 'title', 'content', 'author', 'tag', 'category' );
	}

	// Search CAP author names
	$es_wp_query_args['query_fields'][] = 'taxonomy.author.name';

	// Filter based on CAP names
	if ( !empty( $query->query['author'] ) ) {
		$es_wp_query_args['terms']['author'] = 'cap-' . $query->query['author'];
	}

	return $es_wp_query_args;
}
add_filter('wpcom_elasticsearch_wp_query_args', 'co_author_plus_es_support', 10, 2 );


/**
 * Change the post authors in the subscription email.
 *
 * Creates an array of authors, that will be used later.
 *
 * @param $author WP_User the original author
 * @param $post_id
 *
 * @return array of coauthors
 */
add_filter( 'wpcom_subscriber_email_author', function( $author, $post_id ) {

	$authors = get_coauthors( $post_id );
	return $authors;

}, 10, 2 );

/**
 * Change the author avatar url. If there are multiple authors, link the avatar to the post.
 *
 * @param $author_url
 * @param $post_id
 * @param $authors
 *
 * @return string with new author url.
 */
add_filter( 'wpcom_subscriber_email_author_url', function( $author_url, $post_id, $authors ) {
	if( is_array( $authors ) ) {
		if ( count( $authors ) > 1 ) {
			return get_permalink( $post_id );
		}

		return get_author_posts_url( $authors[0]->ID, $authors[0]->user_nicename );
	}

	return get_author_posts_url( $authors->ID, $authors->user_nicename );
}, 10, 3);

/**
 * Change the avatar to be the avatar of the first author
 *
 * @param $author_avatar
 * @param $post_id
 * @param $authors
 *
 * @return string with the html for the avatar
 */
add_filter( 'wpcom_subscriber_email_author_avatar', function( $author_avatar, $post_id, $authors ) {
	if( is_array( $authors ) )
		return coauthors_get_avatar( $authors[0], 50 );

	return coauthors_get_avatar( $authors, 50 );
}, 10, 3);

/**
 * Changes the author byline in the subscription email to include all the authors of the post
 *
 * @param $author_byline
 * @param $post_id
 * @param $authors
 *
 * @return string with the byline html
 */
add_filter( 'wpcom_subscriber_email_author_byline_html', function( $author_byline, $post_id, $authors ) {
	// Check if $authors is a valid array
	if( ! is_array( $authors ) ) {
		$authors = array( $authors );
	}

	$byline = 'by ';
	foreach( $authors as $author ) {
		$byline .= '<a href="' . esc_url( get_author_posts_url( $author->ID, $author->user_nicename ) ) . '" style="color: #888 !important;">' . esc_html( $author->display_name ) . '</a>';
		if ( $author != end( $authors ) ) {
			$byline .= ', ';
		}
	}

	return $byline;
}, 10, 3);

/**
 * Change the meta information to include all the authors
 *
 * @param $meta
 * @param $post_id
 * @param $authors
 *
 * @return array with new meta information
 */
add_filter( 'wpcom_subscriber_email_meta', function( $meta, $post_id, $authors ) {
	// Check if $authors is a valid array
	if( ! is_array( $authors ) ) {
		$authors = array( $authors );
	}

	$author_meta = '';
	foreach( $authors as $author ) {
		$author_meta .= '<strong><a href="' . esc_url( get_author_posts_url( $author->ID, $author->user_nicename ) ) . '">' . esc_html( $author->display_name ) . '</a></strong>';

		if ( $author != end( $authors ) ) {
			$author_meta .= ', ';
		}
	}

	// Only the first entry of meta includes the author listing
	$meta[0] = $author_meta;

	return $meta;
}, 10, 3);

/**
 * Change the author information in the text-only subscription email.
 *
 * @param $author
 * @param $post_id
 *
 * @returns string with the authors
 */
add_filter( 'wpcom_subscriber_text_email_author', function( $author, $post_id ) {
	// Check if $authors is a valid array
	$authors = get_coauthors( $post_id );

	$author_text = '';
	foreach( $authors as $author ) {
		$author_text .= esc_html( $author->display_name );
		if ( $author != end( $authors ) ) {
			$author_text .= ', ';
		}
	}

	return $author_text;
}, 10, 2);

/**
 * Replace author_url in oembed endpoint response
 * Since the oembed specification does not allow multiple urls, we'll go with the first coauthor
 *
 * The function is meant as a filter for `get_author_posts_url` function, which it is using as well
 * Recursion is prevented by a simple check agains original attributes passed to the funciton. That
 * also prevents execution in case the only coauthor is real author.
 *
 * This function is hooked only to oembed endpoint and it should stay that way
 */

function wpcom_vip_cap_replace_author_link( $link, $author_id, $author_nicename ) {
	
	//get coauthors and iterate to the first one
	//in case there are no coauthors, the Iterator returns current author
	$i = new CoAuthorsIterator();
	$i->iterate();

	//check if the current $author_id and $author_nicename is not the same as the first coauthor
	if ( $i->current_author->ID !== $author_id || $i->current_author->user_nicename !== $author_nicename ) {
		
		//alter the author_url
		$link = get_author_posts_url( $i->current_author->ID, $i->current_author->user_nicename );

	}

	return $link;
}
//Hook the above callback only on oembed endpoint reply
if ( true === defined( 'WPCOM_VIP_IS_OEMBED' ) && true === constant( 'WPCOM_VIP_IS_OEMBED' ) && true === apply_filters( 'wpcom_vip_coauthors_replace_oembed', false, 'author_url' ) ) {
	add_filter( 'author_link', 'wpcom_vip_cap_replace_author_link', 99, 3 );
}
