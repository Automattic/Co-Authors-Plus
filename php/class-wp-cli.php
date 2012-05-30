<?php
/**
 * Co-Authors Plus commands for the WP-CLI framework
 *
 * @package wp-cli
 * @since 2.7
 * @see https://github.com/wp-cli/wp-cli
 */
// WordPress.com is running v0.4 of WP-CLI and we need to maintain backwards compat for now
if ( method_exists( 'WP_CLI', 'addCommand' ) )
	WP_CLI::addCommand( 'co-authors-plus', 'CoAuthorsPlus_Command' );
else
	WP_CLI::addCommand( 'co-authors-plus', 'CoAuthorsPlus_Command' );
class CoAuthorsPlus_Command extends WP_CLI_Command
{

	/**
	 * Help function for this command
	 */
	public static function help() {

		WP_CLI::line( <<<EOB
usage: wp co-authors-plus <parameters>
Possible subcommands:
					  reassign_terms            Reassign posts with an old author to a new author
					  --author_mapping=Where your author mapping file exists
EOB
		);
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
				'author_mapping' => null,
			);
		$this->args = wp_parse_args( $assoc_args, $defaults );

		$author_mapping = $this->args['author_mapping'];

		// Get the reassignment data
		if ( $author_mapping && file_exists( $author_mapping ) ) {
			require_once( $author_mapping );
			$authors_to_migrate = $cli_user_map;
		} else if ( $author_mapping ) {
			WP_CLI::error( "author_mapping doesn't exist: " . $author_mapping );
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
			$old_term = get_term_by( 'slug', $old_user, $coauthors_plus->coauthor_taxonomy );
			if ( !$old_term ) {
				WP_CLI::line( "Error: Term '{$old_user}' doesn't exist, skipping" );
				$results->old_term_missing++;
				continue;
			}

			// If the new user exists as a term already, we want to reassign all posts to that
			// new term and delete the original
			// Otherwise, simply rename the old term
			$new_term = get_term_by( 'slug', $new_user, $coauthors_plus->coauthor_taxonomy );
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
		}

		WP_CLI::line( "Reassignment complete. Here are your results:" );
		WP_CLI::line( "- $results->success authors were successfully reassigned terms" );
		WP_CLI::line( "- $results->new_term_exists authors had their old term merged to their new term" );
		WP_CLI::line( "- $results->old_term_missing authors were missing old terms" );

	}
	
}