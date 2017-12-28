<?php

class Test_CoAuthors_Guest_Authors extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create_and_get( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post = $this->factory->post->create_and_get( array(
			'post_author'  => $this->author1->ID,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Checks a simulated WP_User object based on the post ID when key or value is empty.
	 *
	 * @covers CoAuthors_Guest_Authors::get_guest_author_by()
	 */
	public function test_get_guest_author_by_with_empty_key_or_value() {

		global $coauthors_plus;

		// Fetch guest author without forcefully.
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( '', '' ) );
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( 'ID', '' ) );
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( '', $this->author1->ID ) );

		// Fetch guest author forcefully.
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( '', '', true ) );
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( 'ID', '', true ) );
		$this->assertFalse( $coauthors_plus->guest_authors->get_guest_author_by( '', $this->author1->ID, true ) );
	}

	/**
	 * Checks a simulated WP_User object based on the post ID using cache.
	 *
	 * @covers CoAuthors_Guest_Authors::get_guest_author_by()
	 */
	public function test_get_guest_author_by_using_cache() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$guest_author_id = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		$cache_key = $guest_author_obj->get_cache_key( 'ID', $guest_author_id );

		// Checks when guest author does not exist in cache.
		$this->assertFalse( wp_cache_get( $cache_key, $guest_author_obj::$cache_group ) );

		// Checks when guest author exists in cache.
		$guest_author        = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );
		$guest_author_cached = wp_cache_get( $cache_key, $guest_author_obj::$cache_group );

		$this->assertInternalType( 'object', $guest_author );
		$this->assertEquals( $guest_author, $guest_author_cached );
	}

	/**
	 * Checks a simulated WP_User object based on the post ID using different key/value.
	 *
	 * @covers CoAuthors_Guest_Authors::get_guest_author_by()
	 */
	public function test_get_guest_author_by_with_different_keys() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks when user is not a guest author.
		$this->assertFalse( $guest_author_obj->get_guest_author_by( 'ID', $this->author1->ID ) );
		$this->assertFalse( $guest_author_obj->get_guest_author_by( 'ID', $this->author1->ID, true ) );

		// Checks guest author using ID.
		$guest_author_id = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		$guest_author = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );

		$this->assertInternalType( 'object', $guest_author );
		$this->assertEquals( $guest_author_id, $guest_author->ID );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );

		// Checks guest author using user_nicename.
		$guest_author = $guest_author_obj->get_guest_author_by( 'user_nicename', $this->editor1->user_nicename );

		$this->assertInternalType( 'object', $guest_author );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );

		// Checks guest author using linked_account.
		$guest_author = $guest_author_obj->get_guest_author_by( 'linked_account', $this->editor1->user_login );

		$this->assertInternalType( 'object', $guest_author );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );
	}
}
