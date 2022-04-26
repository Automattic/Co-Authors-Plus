<?php
use CoAuthors\API\Endpoints;

/**
 * Base unit test class for Co-Authors Plus
 */
class CoAuthorsPlus_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $coauthors_plus;
		$this->_cap = $coauthors_plus;
		$this->_api = new Endpoints( $coauthors_plus );
	}
}
