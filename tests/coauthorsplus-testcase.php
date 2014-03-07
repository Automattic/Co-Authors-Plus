<?php

/**
 * Base unit test class for Co-Authors Plus
 */

class CoAuthorsPlus_TestCase extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->author1 = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'author2' ) );

		$post = array(
			'post_author'     => $this->author1,
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
		);

		$this->author1_post1 = wp_insert_post( $post );
	}

}