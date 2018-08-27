<?php

/**
 * Install functions.
 *
 * @package Co-Authors Plus
 */ 

/**
 * Runs on plugin activation.
 */ 
function cap_install_setup() {
	wp_schedule_single_event( time(), 'cap_import_existing_users' );
}


/**
 * Creates guest author profiles for existing users.
 *
 * Since CAP 2.7, we're searching against the term description for the fields instead of the user details.
 * When CAP is first installed, guest authors are missing for all existing users.
 * This function take care of creating them for all users who have permission to be added as coauthors.
 * Uses a WP-Cron event that gets rescheduled until all users have been processed.
 *
 * @param int $imported_count number of users already processed (used as `offset` in get_users)
 * @param int $number_to_update number of users to process per each cron event
 */ 
function cap_create_guest_authors( $imported_count = 0, $number_to_update = 100 ) {
	global $coauthors_plus;

	$args = array(
		'orderby' => 'user_nicename',
		'order' => 'ASC',
		'offset' => $imported_count,
		'number' => $number_to_update,
		'fields' => 'all_with_meta',
	);
	$found_users = get_users( $args );

	if ( empty( $found_users ) ) {
		return;
	}

	foreach ( $found_users as $found_user ) {
		//Check that user has permission to be added as coauthor
		if( ! $found_user->has_cap( apply_filters( 'coauthors_edit_author_cap', 'edit_posts' ) ) ) {
			continue;
		}
		
		$coauthors_plus->guest_authors->create_guest_author_from_user_id( $found_user->ID );
	}

	$imported_count += $number_to_update;

	wp_schedule_single_event( time() + 15, 'cap_import_existing_users', array( $imported_count ) );
}
add_action( 'cap_import_existing_users', 'cap_create_guest_authors' );