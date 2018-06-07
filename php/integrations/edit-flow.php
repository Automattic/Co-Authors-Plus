<?php

/**
 * Functions that integrate Co-Authors Plus with Edit Flow.
 */
  
/**
 * Filter Edit Flow's 'ef_calendar_item_information_fields' to add co-authors
 *
 * @see https://github.com/Automattic/Co-Authors-Plus/issues/2
 *
 * @param array $information_fields
 * @param int $post_id
 * @return array
 */
function cap_filter_ef_calendar_item_information_fields( $information_fields, $post_id ) {
	global $coauthors_plus;
	
	// Don't add the author row again if another plugin has removed
	if ( ! array_key_exists( $coauthors_plus->coauthor_taxonomy, $information_fields ) ) {
		return $information_fields;
	}

	$co_authors = get_coauthors( $post_id );
	if ( count( $co_authors ) > 1 ) {
		$information_fields[$coauthors_plus->coauthor_taxonomy]['label'] = __( 'Authors', 'co-authors-plus' );
	}
	$co_authors_names = '';
	foreach ( $co_authors as $co_author ) {
		$co_authors_names .= $co_author->display_name . ', ';
	}
	$information_fields[$coauthors_plus->coauthor_taxonomy]['value'] = rtrim( $co_authors_names, ', ' );
	return $information_fields;
}

/**
 * Filter Edit Flow's 'ef_story_budget_term_column_value' to add co-authors to the story budget
 *
 * @see https://github.com/Automattic/Co-Authors-Plus/issues/2
 *
 * @param string $column_name
 * @param object $post
 * @param object $parent_term
 * @return string
 */
function cap_filter_ef_story_budget_term_column_value( $column_name, $post, $parent_term ) {
	global $coauthors_plus;
	
	// We only want to modify the 'author' column
	if ( $coauthors_plus->coauthor_taxonomy !== $column_name ) {
		return $column_name;
	}

	$co_authors = get_coauthors( $post->ID );
	$co_authors_names = '';
	foreach ( $co_authors as $co_author ) {
		$co_authors_names .= $co_author->display_name . ', ';
	}
	return rtrim( $co_authors_names, ', ' );
}