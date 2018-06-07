<?php

/**
 * Functions that change the behavior of the Users screen.
 */

/**
 * Unset the post count column because it's going to be inaccurate and provide our own.
 *
 * @param array $columns
 * @return array
 */
function cap_filter_manage_users_columns( $columns ) {
	$new_columns = array();
	
	// Unset and add our column while retaining the order of the columns
	foreach ( $columns as $column_name => $column_title ) {
		if ( 'posts' === $column_name ) {
			$new_columns['coauthors_post_count'] = __( 'Posts', 'co-authors-plus' );
		} else {
			$new_columns[ $column_name ] = $column_title;
		}
	}
	return $new_columns;
}

/**
 * Provide an accurate count when looking up the number of published posts for a user.
 *
 * @param string $value
 * @param string $column_name
 * @param int $user_id
 * @return string
 */
function cap_filter_manage_users_custom_column( $value, $column_name, $user_id ) {
	if ( 'coauthors_post_count' !== $column_name ) {
		return $value;
	}
	
	// We filter count_user_posts() so it provides an accurate number
	$numposts = count_user_posts( $user_id );
	$user = get_user_by( 'id', $user_id );
	if ( $numposts > 0 ) {
		$value .= "<a href='edit.php?author_name=$user->user_nicename' title='" . esc_attr__( 'View posts by this author', 'co-authors-plus' ) . "' class='edit'>";
		$value .= $numposts;
		$value .= '</a>';
	} else {
		$value .= 0;
	}
	return $value;
}