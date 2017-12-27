<?php

class Test_CoAuthors_Plus extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create_and_get( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post_id = wp_insert_post( array(
			'post_author'  => $this->author1->ID,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Checks whether the guest authors functionality is enabled or not.
	 *
	 * @covers ::is_guest_authors_enabled()
	 */
	public function test_is_guest_authors_enabled() {

		global $coauthors_plus;

		$this->assertTrue( $coauthors_plus->is_guest_authors_enabled() );

		tests_add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertFalse( $coauthors_plus->is_guest_authors_enabled() );
	}

	/**
	 * Checks coauthor object when he/she is a guest author.
	 *
	 * @covers ::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_guest_author() {

		global $coauthors_plus;

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $guest_author_id );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( stdClass::class, $coauthor );
		$this->assertObjectHasAttribute( 'ID', $coauthor );
		$this->assertEquals( $guest_author_id, $coauthor->ID );
		$this->assertEquals( 'guest-author', $coauthor->type );
	}

	/**
	 * Checks coauthor object when he/she is a wp author.
	 *
	 * @covers ::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_wp_user() {

		global $coauthors_plus;

		$this->assertFalse( $coauthors_plus->get_coauthor_by( '', '' ) );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->author1->ID );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'ID', $coauthor );
		$this->assertEquals( $this->author1->ID, $coauthor->ID );
		$this->assertEquals( 'wpuser', $coauthor->type );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $this->author1->user_login );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_login', $coauthor->data );
		$this->assertEquals( $this->author1->user_login, $coauthor->user_login );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $this->author1->user_nicename );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_nicename', $coauthor->data );
		$this->assertEquals( $this->author1->user_nicename, $coauthor->user_nicename );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_email', $this->author1->user_email );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_email', $coauthor->data );
		$this->assertEquals( $this->author1->user_email, $coauthor->user_email );

		$coauthors_plus->guest_authors->create_guest_author_from_user_id( $this->editor1->ID );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->editor1->ID );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( stdClass::class, $coauthor );
		$this->assertObjectHasAttribute( 'linked_account', $coauthor );
		$this->assertEquals( $this->editor1->user_login, $coauthor->linked_account );
	}
}
