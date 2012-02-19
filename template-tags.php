<?php

function get_coauthors( $post_id = 0, $args = array() ) {
	global $post, $post_ID, $coauthors_plus, $wpdb;
	
	$coauthors = array();
	$post_id = (int)$post_id;
	if(!$post_id && $post_ID) $post_id = $post_ID;
	if(!$post_id && $post) $post_id = $post->ID;

	$defaults = array('orderby'=>'term_order', 'order'=>'ASC');
	$args = wp_parse_args( $args, $defaults );
	
	if($post_id) {
		$coauthor_terms = wp_get_post_terms( $post_id, $coauthors_plus->coauthor_taxonomy, $args );
		
		if(is_array($coauthor_terms) && !empty($coauthor_terms)) {
			foreach($coauthor_terms as $coauthor) {
				$post_author =  get_user_by( 'login', $coauthor->name );
				// In case the user has been deleted while plugin was deactivated
				if(!empty($post_author)) $coauthors[] = $post_author;
			}
		} else {
			if($post) {
				$post_author = get_userdata($post->post_author);
			} else {
				$post_author = get_userdata($wpdb->get_var($wpdb->prepare("SELECT post_author FROM $wpdb->posts WHERE ID = %d", $post_id)));
			}
			if(!empty($post_author)) $coauthors[] = $post_author;
		}
	}
	return $coauthors;
}

/**
 * Checks to see if the the specified user is author of the current global post or post (if specified)
 * @param object|int $user 
 * @param int $post_id
 */
function is_coauthor_for_post( $user, $post_id = 0 ) {
	global $post;
	
	if( ! $post_id && $post ) $post_id = $post->ID;
	if( ! $post_id ) return false;
	
	$coauthors = get_coauthors( $post_id );
	if( is_numeric( $user ) ) {
		$user = get_userdata( $user );
		$user = $user->user_login;
	}
	
	foreach( $coauthors as $coauthor ) {
		if( $user == $coauthor->user_login ) return true;
	}
	return false;
}

class CoAuthorsIterator {
	var $position = -1;
	var $original_authordata;
	var $current_author;
	var $authordata_array;
	var $count;
	
	function CoAuthorsIterator($postID = 0){
		global $post, $authordata, $wpdb;
		$postID = (int)$postID;
		if(!$postID && $post)
			$postID = (int)$post->ID;
		if(!$postID)
			trigger_error(__('No post ID provided for CoAuthorsIterator constructor. Are you not in a loop or is $post not set?', 'co-authors-plus')); //return null;

		$this->original_authordata = $this->current_author = $authordata;
		$this->authordata_array = get_coauthors($postID);
		
		$this->count = count($this->authordata_array);
	}
	
	function iterate(){
		global $authordata;
		$this->position++;
		
		//At the end of the loop
		if($this->position > $this->count-1){
			$authordata = $this->current_author = $this->original_authordata;
			$this->position = -1;
			return false;
		}
		
		//At the beginning of the loop
		if($this->position == 0 && !empty($authordata))
			$this->original_authordata = $authordata;
		
		$authordata = $this->current_author = $this->authordata_array[$this->position];
		
		return true;
	}
	
	function get_position(){
		if($this->position === -1)
			return false;
		return $this->position;
	}
	function is_last(){
		return  $this->position === $this->count-1;
	}
	function is_first(){
		return $this->position === 0;
	}
	function count(){
		return $this->count;
	}
	function get_all(){
		return $this->authordata_array;
	}
}

//Helper function for the following new template tags
function coauthors__echo( $tag, $type = 'tag', $separators = array(), $tag_args = null, $echo = true ) {

	// Define the standard output separator. Constant support is for backwards compat.
	// @see https://github.com/danielbachhuber/Co-Authors-Plus/issues/12
	$default_before = ( defined( 'COAUTHORS_DEFAULT_BEFORE' ) ) ? COAUTHORS_DEFAULT_BEFORE : '';
	$default_between = ( defined( 'COAUTHORS_DEFAULT_BETWEEN' ) ) ? COAUTHORS_DEFAULT_BETWEEN : ', ';
	$default_between_last = ( defined( 'COAUTHORS_DEFAULT_BETWEEN_LAST' ) ) ? COAUTHORS_DEFAULT_BETWEEN_LAST :  __( ' and ', 'co-authors-plus' );
	$default_after = ( defined( 'COAUTHORS_DEFAULT_AFTER' ) ) ? COAUTHORS_DEFAULT_AFTER : '';

	if( ! isset( $separators['before'] ) || $separators['before'] === NULL )
		$separators['before'] = apply_filters( 'coauthors_default_before', $default_before );
	if( ! isset( $separators['between'] ) || $separators['between'] === NULL )
		$separators['between'] = apply_filters( 'coauthors_default_between', $default_between );
	if( ! isset( $separators['betweenLast'] ) || $separators['betweenLast'] === NULL )
		$separators['betweenLast'] = apply_filters( 'coauthors_default_between_last', $default_between_last );
	if( ! isset( $separators['after'] ) || $separators['after'] === NULL )
		$separators['after'] = apply_filters( 'coauthors_default_after', $default_after );

	$output = '';
	
	$i = new CoAuthorsIterator();
	$output .= $separators['before'];
	$i->iterate();
	do {
		$author_text = '';
		
		if( $type == 'tag' )
			$author_text = $tag( $tag_args );
		elseif( $type == 'field' && isset( $i->current_author->$tag ) )
			$author_text = $i->current_author->$tag;
		elseif( $type == 'callback' && is_callable( $tag ) )
			$author_text = call_user_func( $tag, $i->current_author );
		
		// Fallback to user_login if we get something empty
		if( empty( $author_text ) )
			$author_text = $i->current_author->user_login;
		
		// Append separators
		if ( ! $i->is_first() && $i->count() > 2 )
			$output .= $separators['between'];
		
		if ( $i->is_last() && $i->count() > 1 ) {
			$output = rtrim( $output, ' ' );
			$output .= ' ' . $separators['betweenLast'];
		}
		
		$output .= $author_text;
	} while( $i->iterate() );
	
	$output .= $separators['after'];
	
	if( $echo )
		echo $output;
	
	return $output;
}

//Provide co-author equivalents to the existing author template tags
function coauthors( $between = null, $betweenLast = null, $before = null, $after = null, $echo = true ){
	return coauthors__echo('display_name', 'field', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), null, $echo );
}
function coauthors_posts_links( $between = null, $betweenLast = null, $before = null, $after = null, $echo = true ){
	return coauthors__echo('coauthors_posts_links_single', 'callback', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), null, $echo );
}
function coauthors_posts_links_single( $author ) {
	return sprintf(
		'<a href="%1$s" title="%2$s">%3$s</a>',
		get_author_posts_url( $author->ID, $author->user_nicename ),
		esc_attr( sprintf( __( 'Posts by %s', 'co-authors-plus' ), get_the_author() ) ),
		get_the_author()
	);
}

function coauthors_firstnames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true ){
	return coauthors__echo('get_the_author_meta', 'tag', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), 'first_name', $echo );
}
function coauthors_lastnames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true ) {
	return coauthors__echo('get_the_author_meta', 'tag', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), 'last_name', $echo );
}
function coauthors_nicknames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true ) {
	return coauthors__echo('get_the_author_meta', 'tag', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), 'nickname', $echo );
}
function coauthors_links($between = null, $betweenLast = null, $before = null, $after = null, $echo = true ) {
	return coauthors__echo('coauthors_links_single', 'callback', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), null, $echo );
}
function coauthors_links_single( $author ) {
	if ( get_the_author_meta('url') ) {
		return sprintf( '<a href="%s" title="%s" rel="external">%s</a>',
			get_the_author_meta('url'),
			esc_attr( sprintf(__("Visit %s&#8217;s website"), get_the_author()) ),
			get_the_author()
		);
	} else {
		return get_the_author();
	}
}
function coauthors_IDs($between = null, $betweenLast = null, $before = null, $after = null, $echo = true ) {
	return coauthors__echo('ID', 'field', array(
		'between' => $between,
		'betweenLast' => $betweenLast,
		'before' => $before,
		'after' => $after
	), null, $echo );
}

function get_the_coauthor_meta( $field ) {
	global $wp_query, $post;
	
	$coauthors = get_coauthors();
	$meta = array();
	
	foreach( $coauthors as $coauthor ) {
		$user_id = $coauthor->ID;
		$meta[$user_id] = get_the_author_meta( $field, $user_id );
	}
	return $meta;
}

function the_coauthor_meta( $field, $user_id = 0 ) {
	// TODO: need before after options
	echo get_the_coauthor_meta($field, $user_id);
}

/**
 * List all the *co-authors* of the blog, with several options available.
 * optioncount (boolean) (false): Show the count in parenthesis next to the author's name.
 * exclude_admin (boolean) (true): Exclude the 'admin' user that is installed by default.
 * show_fullname (boolean) (false): Show their full names.
 * hide_empty (boolean) (true): Don't show authors without any posts.
 * feed (string) (''): If isn't empty, show links to author's feeds.
 * feed_image (string) (''): If isn't empty, use this image to link to feeds.
 * echo (boolean) (true): Set to false to return the output, instead of echoing.
 * @param array $args The argument array.
 * @return null|string The output, if echo is set to false.
 * 
 * NOTE: This is not perfect and probably won't work that well. 
 *
 */

function coauthors_wp_list_authors($args = '') {
	global $wpdb, $coauthors_plus;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'feed_type' => '', 'echo' => true,
		'style' => 'list', 'html' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);
	$return = '';

	$authors = $coauthors_plus->search_authors();
	$author_terms = get_terms( 'author' );
	
	foreach ( (array) $author_terms as $author_term ) {
		$author_count[$author_term->slug] = $author_term->count;
	}

	foreach ( (array) $authors as $author ) {

		$link = '';

		$author = get_userdata( $author->ID );
		$posts = (isset($author_count[$author->user_login])) ? $author_count[$author->user_login] : 0;
		$name = $author->display_name;

		if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
			$name = "$author->first_name $author->last_name";

		if( !$html ) {
			if ( $posts == 0 ) {
				if ( ! $hide_empty )
					$return .= $name . ', ';
			} else
				$return .= $name . ', ';

			// No need to go further to process HTML.
			continue;
		}

		if ( !($posts == 0 && $hide_empty) && 'list' == $style )
			$return .= '<li>';
		if ( $posts == 0 ) {
			if ( ! $hide_empty )
				$link = $name;
		} else {
			$link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . esc_attr( sprintf(__("Posts by %s", 'co-authors-plus'), $author->display_name) ) . '">' . $name . '</a>';

			if ( (! empty($feed_image)) || (! empty($feed)) ) {
				$link .= ' ';
				if (empty($feed_image))
					$link .= '(';
				$link .= '<a href="' . get_author_feed_link($author->ID) . '"';

				if ( !empty($feed) ) {
					$title = ' title="' . esc_attr($feed) . '"';
					$alt = ' alt="' . esc_attr($feed) . '"';
					$name = $feed;
					$link .= $title;
				}

				$link .= '>';

				if ( !empty($feed_image) )
					$link .= "<img src=\"" . esc_url($feed_image) . "\" style=\"border: none;\"$alt$title" . ' />';
				else
					$link .= $name;

				$link .= '</a>';

				if ( empty($feed_image) )
					$link .= ')';
			}

			if ( $optioncount )
				$link .= ' ('. $posts . ')';

		}

		if ( !($posts == 0 && $hide_empty) && 'list' == $style )
			$return .= $link . '</li>';
		else if ( ! $hide_empty )
			$return .= $link . ', ';
	}

	$return = trim($return, ', ');

	if ( ! $echo )
		return $return;
	echo $return;
}