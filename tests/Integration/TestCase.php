<?php

namespace Automattic\CoAuthorsPlus\Tests\Integration;

use CoAuthors\API\Endpoints;

/**
 * Base unit test class for Co-Authors Plus
 */
class TestCase extends \Yoast\WPTestUtils\WPIntegration\TestCase {

	/**
	 * @var CoAuthors_Plus
	 */
	protected $_cap;
	/**
	 * @var Endpoints
	 */
	protected $_api;

	public function set_up() {
		parent::set_up();

		global $coauthors_plus;
		$this->_cap = $coauthors_plus;
		$this->_api = new Endpoints( $coauthors_plus );
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
}
