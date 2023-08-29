<?php
/**
 * Feature tests context class with Co-Authors Plus specific steps.
 *
 * @package Automattic\CoAuthorsPlus
 */

namespace Automattic\CoAuthorsPlus\Tests\Behat;

use WP_CLI\Tests\Context\FeatureContext as WP_CLI_FeatureContext;

/**
 * Feature tests context class with Co-Authors Plus-specific steps.
 *
 * This class extends the one that is provided by the wp-cli/wp-cli-tests package.
 * To see a list of all recognized step definitions, run `vendor/bin/behat -dl`.
 */
final class FeatureContext extends WP_CLI_FeatureContext {

	/**
	 * Set up the plugin to be active.
	 *
	 * @Given a WP install(ation) with the Co-Authors Plus plugin
	 *
	 * Adapted from https://github.com/wearerequired/traduttore/blob/master/tests/phpunit/tests/Behat/FeatureContext.php
	 * with credit and thanks to them.
	 */
	public function given_a_wp_installation_with_the_cap_plugin() {
		$this->install_wp();

		// Symlink the current project folder into the WP folder as a plugin.
		$project_dir = realpath( self::get_vendor_dir() . '/../' );
		$plugin_dir  = $this->variables['RUN_DIR'] . '/wp-content/plugins';
		$this->ensure_dir_exists( $plugin_dir );
		$this->proc( "ln -s {$project_dir} {$plugin_dir}/co-authors-plus" )->run_check();

		// Activate the plugin.
		$this->proc( 'wp plugin activate co-authors-plus' )->run_check();
	}

	/**
	 * Ensure that a requested directory exists and create it recursively as needed.
	 *
	 * Copied as is from the Tradutorre repo as well.
	 *
	 * @param string $directory Directory to ensure the existence of.
	 * @throws \RuntimeException Directory could not be created.
	 */
	private function ensure_dir_exists( $directory ) {
		$parent = dirname( $directory );

		if ( ! empty( $parent ) && ! is_dir( $parent ) ) {
			$this->ensure_dir_exists( $parent );
		}

		if ( ! is_dir( $directory ) && ! mkdir( $directory ) && ! is_dir( $directory ) ) {
			throw new \RuntimeException( "Could not create directory '{$directory}'." );
		}
	}

	/**
	 * Add a published post.
	 *
	 * @Given there is a published post with a slug of :post_name
	 *
	 * @param string $post_name Post name to use.
	 */
	public function there_is_a_published_post( $post_name ) {
		$this->proc( "wp post create --post_title='{$post_name}' --post_name='{$post_name}' --post_status='publish'" )->run_check();
	}
}
