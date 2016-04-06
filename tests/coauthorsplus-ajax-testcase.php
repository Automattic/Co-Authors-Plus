<?php

/**
 * AJAX test case base class for Co-Authors Plus
 */
class CoAuthorsPlus_Ajax_TestCase extends WP_Ajax_UnitTestCase {

	protected $suppress = false;

	public function setUp() {
		global $wpdb;

		parent::setUp();
		
		$this->suppress = $wpdb->suppress_errors();

		$_SERVER['REMOTE_ADDR'] = '';

		$this->author1 = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor2' ) );
	}

	public function tearDown() {
		global $wpdb;
		
		parent::tearDown();
		
		$wpdb->suppress_errors( $this->suppress );
	}
}
