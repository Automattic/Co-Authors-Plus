<?php
/**
 * Co-Authors Plus commands for the WP-CLI framework
 *
 * @package wp-cli
 * @since 2.7
 * @see https://github.com/wp-cli/wp-cli
 */
WP_CLI::add_command( 'co-authors-plus', 'CoAuthorsPlus_Command' );

class CoAuthorsPlus_Command extends WP_CLI_Command
{

	/**
	 * Help function for this command
	 */
	public static function help() {

		WP_CLI::line( <<<EOB
usage: wp co-authors-plus <parameters>
Possible subcommands:
					create_guest_authors        Create guest author profiles for each author
					assign_coauthors            Assign authors to a post based on a postmeta value
					--meta_key=Post meta key to base the assignment on
					--post_type=Which post type to modify assignments on
					reassign_terms            Reassign posts with an old author to a new author
					--author_mapping=           Where your author mapping file exists
					--old_term=                 Term to be reassigned (instead of using author mapping file)
					--new_term=                 Term to reassign to. Create a term if one doesn't exist
					list_posts_without_terms    List all posts without Co-Authors Plus terms
					migrate_author_terms        Migrate author terms without prefixes to ones with prefixes
EOB
		);
	}

	/**
	 * Subcommand to create guest authors based on users
	 *
	 * @todo Don't create a guest author if the user is already mapped to a guest author
	 */
	public function create_guest_authors( $args, $assoc_args ) {
		global $coauthors_plus;

		$defaults = array(
				// There are no arguments at this time
			);
		$this->args = wp_parse_args( $assoc_args, $defaults );

		$users = get_users();
		$created = 0;
		$skipped = 0;
		foreach( $users as $user ) {

			$result = $coauthors_plus->guest_authors->create_guest_author_from_user_id( $user->ID );
			if ( is_wp_error( $result ) ) {
				$skipped++;
			} else {
				$created++;
			}
		}

		WP_CLI::line( "All done! Here are your results:" );
		WP_CLI::line( "- {$created} guest author profiles were created" );
		WP_CLI::line( "- {$skipped} users already had guest author profiles" );

	}

	/**
	 * Subcommand to assign coauthors to a post based on a given meta key
	 *
	 * @todo support assigning multiple meta keys
	 */
	public function assign_coauthors( $args, $assoc_args ) {
		global $coauthors_plus;

		$defaults = array(
				'meta_key'         => '_original_import_author',
				'post_type'        => 'post',
				'order'            => 'ASC',
				'orderby'          => 'ID',
				'posts_per_page'   => 100,
				'paged'            => 1,
				'append_coauthors' => false,
			);
		$this->args = wp_parse_args( $assoc_args, $defaults );

		// For global use and not a part of WP_Query
		$append_coauthors = $this->args['append_coauthors'];
		unset( $this->args['append_coauthors'] );

		$posts_total = 0;
		$posts_already_associated = 0;
		$posts_missing_coauthor = 0;
		$posts_associated = 0;
		$missing_coauthors = array();

		$posts = new WP_Query( $this->args );
		while( $posts->post_count ) {

			foreach( $posts->posts as $single_post ) {
				$posts_total++;

				// See if the value in the post meta field is the same as any of the existing coauthors
				$original_author = get_post_meta( $single_post->ID, $this->args['meta_key'], true );
				$existing_coauthors = get_coauthors( $single_post->ID );
				$already_associated = false;
				foreach( $existing_coauthors as $existing_coauthor ) {
					if ( $original_author == $existing_coauthor->user_login )
						$already_associated = true;
				}
				if ( $already_associated ) {
					$posts_already_associated++;
					WP_CLI::line( $posts_total . ': Post #' . $single_post->ID . ' already has "' . $original_author . '" associated as a coauthor' );
					continue;
				}

				// Make sure this original author exists as a co-author
				if ( !$coauthors_plus->get_coauthor_by( 'user_login', $original_author ) ) {
					$posts_missing_coauthor++;
					$missing_coauthors[] = $original_author;
					WP_CLI::line( $posts_total . ': Post #' . $single_post->ID . ' does not have "' . $original_author . '" associated as a coauthor but there is not a coauthor profile' );
					continue;
				}

				// Assign the coauthor to the post
				$coauthors_plus->add_coauthors( $single_post->ID, array( $original_author ), $append_coauthors );
				WP_CLI::line( $posts_total . ': Post #' . $single_post->ID . ' has been assigned "' . $original_author . '" as the author' );
				$posts_associated++;
				clean_post_cache( $single_post->ID );
			}
			
			$this->args['paged']++;
			$this->stop_the_insanity();
			$posts = new WP_Query( $this->args );
		}

		WP_CLI::line( "All done! Here are your results:" );
		if ( $posts_already_associated )
			WP_CLI::line( "- {$posts_already_associated} posts already had the coauthor assigned" );
		if ( $posts_missing_coauthor ) {
			WP_CLI::line( "- {$posts_missing_coauthor} posts reference coauthors that don't exist. These are:" );
			WP_CLI::line( "  " . implode( ', ', array_unique( $missing_coauthors ) ) );
		}
		if ( $posts_associated )
			WP_CLI::line( "- {$posts_associated} posts now have the proper coauthor" );

	}

	/**
	 * Subcommand to reassign co-authors based on some given format
	 * This will look for terms with slug 'x' and rename to term with slug and name 'y'
	 * This subcommand can be helpful for cleaning up after an import if the usernames
	 * for authors have changed. During the import process, 'author' terms will be
	 * created with the old user_login value. We can use this to migrate to the new user_login
	 *
	 * @todo support reassigning by CSV
	 */
	public function reassign_terms( $args, $assoc_args ) {
		global $coauthors_plus;

		$defaults = array(
				'author_mapping'    => null,
				'old_term'          => null,
				'new_term'          => null,
			);
		$this->args = wp_parse_args( $assoc_args, $defaults );

		$author_mapping = $this->args['author_mapping'];
		$old_term = $this->args['old_term'];
		$new_term = $this->args['new_term'];

		// Get the reassignment data
		if ( $author_mapping && file_exists( $author_mapping ) ) {
			require_once( $author_mapping );
			$authors_to_migrate = $cli_user_map;
		} else if ( $author_mapping ) {
			WP_CLI::error( "author_mapping doesn't exist: " . $author_mapping );
			exit;
		}

		// Alternate reassigment approach
		if ( $old_term && $new_term ) {
			$authors_to_migrate = array(
					$old_term => $new_term,
				);
		}

		// For each author to migrate, check whether the term exists,
		// whether the target term exists, and only do the migration if both are met
		$results = (object)array(
				'old_term_missing' => 0,
				'new_term_exists' => 0,
				'success' => 0,
			);
		foreach( $authors_to_migrate as $old_user => $new_user ) {

			if ( is_numeric( $new_user ) )
				$new_user = get_user_by( 'id', $new_user )->user_login;

			// The old user should exist as a term
			$old_term = $coauthors_plus->get_author_term( get_user_by( 'login', $old_user ) );
			if ( !$old_term ) {
				WP_CLI::line( "Error: Term '{$old_user}' doesn't exist, skipping" );
				$results->old_term_missing++;
				continue;
			}

			// If the new user exists as a term already, we want to reassign all posts to that
			// new term and delete the original
			// Otherwise, simply rename the old term
			$new_term = $coauthors_plus->get_author_term( get_user_by( 'login', $new_user ) );
			if ( is_object( $new_term ) ) {
				$args = array(
						'default' => $new_term->term_id,
						'force_default' => true,
					);
				wp_delete_term( $old_term->term_id, $coauthors_plus->coauthor_taxonomy, $args );
				WP_CLI::line( "Success: There's already a '{$new_user}' term for '{$old_user}'. Reassigning posts and then deleting the term" );
				$results->new_term_exists++;
			} else {
				$args = array(
						'slug' => $new_user,
						'name' => $new_user,
					);
				wp_update_term( $old_term->term_id, $coauthors_plus->coauthor_taxonomy, $args );
				WP_CLI::line( "Success: Converted '{$old_user}' term to '{$new_user}'" );
				$results->success++;
			}
			clean_term_cache( $old_term->term_id, $coauthors_plus->coauthor_taxonomy );
		}

		WP_CLI::line( "Reassignment complete. Here are your results:" );
		WP_CLI::line( "- $results->success authors were successfully reassigned terms" );
		WP_CLI::line( "- $results->new_term_exists authors had their old term merged to their new term" );
		WP_CLI::line( "- $results->old_term_missing authors were missing old terms" );

	}

	/**
	 * List all of the posts without assigned co-authors terms
	 */
	public function list_posts_without_terms( $args, $assoc_args ) {
		global $coauthors_plus;

		$defaults = array(
				'post_type'         => 'post',
				'order'             => 'ASC',
				'orderby'           => 'ID',
				'year'              => '',
				'posts_per_page'    => 300,
				'paged'             => 1,
				'no_found_rows'     => true,
				'update_meta_cache' => false,
			);
		$this->args = wp_parse_args( $assoc_args, $defaults );

		$posts = new WP_Query( $this->args );
		while( $posts->post_count ) {

			foreach( $posts->posts as $single_post ) {
				
				$terms = wp_get_post_terms( $single_post->ID, $coauthors_plus->coauthor_taxonomy );
				if ( empty( $terms ) ) {
					$saved = array(
							$single_post->ID,
							addslashes( $single_post->post_title ),
							get_permalink( $single_post->ID ),
							$single_post->post_date,
						);
					WP_CLI::line( '"' . implode( '","', $saved ) . '"' );
				}
			}

			$this->stop_the_insanity();
			
			$this->args['paged']++;
			$posts = new WP_Query( $this->args );
		}

	}

	/**
	 * Migrate author terms without prefixes to ones with prefixes
	 * Pre-3.0, all author terms didn't have a 'cap-' prefix, which means
	 * they can easily collide with terms in other taxonomies
	 */
	public function migrate_author_terms( $args, $assoc_args ) {
		global $coauthors_plus;
		
		$author_terms = get_terms( $coauthors_plus->coauthor_taxonomy );
		WP_CLI::line( "Now migrating up to " . count( $author_terms ) . " terms" );
		foreach( $author_terms as $author_term ) {
			// Term is already prefixed. We're good.
			if ( preg_match( '#^cap\-#', $author_term->slug, $matches ) ) {
				WP_CLI::line( "Term {$author_term->slug} ({$author_term->term_id}) is already prefixed, skipping" );
				continue;
			}
			// A prefixed term was accidentally created, and the old term needs to be merged into the new (WordPress.com VIP)
			if ( $prefixed_term = get_term_by( 'slug', 'cap-' . $author_term->slug, $coauthors_plus->coauthor_taxonomy ) ) {
				WP_CLI::line( "Term {$author_term->slug} ({$author_term->term_id}) has a new term too: $prefixed_term->slug ($prefixed_term->term_id). Merging" );
				$args = array(
					'default' => $prefixed_term->term_id,
					'force_default' => true,
				);
				wp_delete_term( $author_term->term_id, $coauthors_plus->coauthor_taxonomy, $args );
				continue;
			}

			// Term isn't prefixed, doesn't have a sibling, and should be updated
			WP_CLI::line( "Term {$author_term->slug} ({$author_term->term_id}) isn't prefixed, adding one" );
			$args = array(
					'slug' => 'cap-' . $author_term->slug,
				);
			wp_update_term( $author_term->term_id, $coauthors_plus->coauthor_taxonomy, $args );
		}
		WP_CLI::success( "All done! Grab a cold one (Affogatto)" );
	}

	/**
	 * Clear all of the caches for memory management
	 */
	private function stop_the_insanity() {
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( !is_object( $wp_object_cache ) )
			return;

		$wp_object_cache->group_ops = array();
		$wp_object_cache->stats = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache = array();

		if( is_callable( $wp_object_cache, '__remoteset' ) )
			$wp_object_cache->__remoteset(); // important
	}
	
}