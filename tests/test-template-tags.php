<?php

class Test_Template_Tags extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create_and_get( array( 'role' => 'editor', 'user_login' => 'editor1' ) );
	}

	/**
	 * Checks coauthors order.
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_coauthors_order() {

		global $coauthors_plus;

		$post_id = $this->factory->post->create();

		// Checks when no author exist.
		$this->assertEmpty( get_coauthors( $post_id ) );

		// Checks coauthors order.
		$coauthors_plus->add_coauthors( $post_id, array( $this->author1->user_login ), true );
		$coauthors_plus->add_coauthors( $post_id, array( $this->editor1->user_login ), true );

		$expected = array( $this->author1->user_login, $this->editor1->user_login );

		$this->assertEquals( $expected, wp_list_pluck( get_coauthors( $post_id ), 'user_login' ) );

		// Checks coauthors order after modifying.
		$post_id = $this->factory->post->create();

		$coauthors_plus->add_coauthors( $post_id, array( $this->editor1->user_login ), true );
		$coauthors_plus->add_coauthors( $post_id, array( $this->author1->user_login ), true );

		$expected = array( $this->editor1->user_login, $this->author1->user_login );

		$this->assertEquals( $expected, wp_list_pluck( get_coauthors( $post_id ), 'user_login' ) );
	}
}
