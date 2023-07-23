<?php
use CoAuthors\API\Endpoints;

/**
 * Base unit test class for Co-Authors Plus
 */
class CoAuthorsPlus_TestCase extends \Yoast\WPTestUtils\WPIntegration\TestCase {
	public function set_up() {
		parent::set_up();

		global $coauthors_plus;
		$this->_cap = $coauthors_plus;
		$this->_api = new Endpoints( $coauthors_plus );
	}
}
