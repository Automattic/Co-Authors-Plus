<?php

/**
 * Base unit test class for Co-Authors Plus
 */
class CoAuthorsPlus_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $coauthors_plus;
		$this->_cap = $coauthors_plus;

		// Create guest author for user `admin`
		$coauthors_plus->guest_authors->create_guest_author_from_user_id( 1 );
	}
}
