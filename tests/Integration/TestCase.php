<?php

namespace Automattic\CoAuthorsPlus\Tests\Integration;

/**
 * Base unit test class for Co-Authors Plus
 */
class TestCase extends \Yoast\WPTestUtils\WPIntegration\TestCase {

	/**
	 * @var CoAuthors_Plus
	 */
	protected $_cap;

	public function set_up() {
		parent::set_up();

		global $coauthors_plus;
		$this->_cap = $coauthors_plus;
	}

	protected function create_subscriber( $user_login = 'subscriber' ) {
		return $this->factory()->user->create_and_get(
			array(
				'role'       => 'subscriber',
				'user_login' => $user_login,
			)
		);
	}

	protected function create_contributor( $user_login = 'contributor' ) {
		return $this->factory()->user->create_and_get(
			array(
				'role'       => 'contributor',
				'user_login' => $user_login,
			)
		);
	}

	protected function create_author( $user_login = 'author' ) {
		return $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => $user_login,
			)
		);
	}

	protected function create_editor( $user_login = 'editor' ) {
		return $this->factory()->user->create_and_get(
			array(
				'role'       => 'editor',
				'user_login' => $user_login,
			)
		);
	}

	protected function create_guest_author( $user_login = 'guest_author' ) {
		global $coauthors_plus;
		return $coauthors_plus->guest_authors->create(
			array(
				'display_name' => $user_login,
				'user_login'   => $user_login,
			)
		);
	}

	protected function create_post( ?\WP_User $author = null ) {
		if ( null === $author ) {
			$author = $this->create_author();
		}
		return $this->factory()->post->create_and_get(
			array(
				'post_author'  => $author->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			)
		);
	}
}
