<?php

class Test_CoAuthors_Plus extends CoAuthorsPlus_TestCase {

	public function setUp() {
		parent::setUp();
	}

	/**
	 * Checks whether the guest authors functionality is enabled or not.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/388
	 *
	 * @covers ::is_guest_authors_enabled()
	 */
	public function test_is_guest_authors_enabled() {

		global $coauthors_plus;

		$this->assertTrue( $coauthors_plus->is_guest_authors_enabled() );

		tests_add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertFalse( $coauthors_plus->is_guest_authors_enabled() );
	}
}
