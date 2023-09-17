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
}
