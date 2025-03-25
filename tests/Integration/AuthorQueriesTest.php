<?php

namespace Automattic\CoAuthorsPlus\Tests\Integration;

class AuthorQueriesTest extends TestCase {

	/**
	 * Test a simple query that a post is returned after setting a co-author on it.
	 * The focus is the query by user login instead of ID.
	 */
	public function test_get_post_by_user_login_when_single_author_is_set_as_post_author(): void {
		$author = $this->create_author();
		$post   = $this->create_post( $author );
		$this->_cap->add_coauthors( $post->ID, array( $author->user_login ) );

		$query = new \WP_Query(
			array(
				'author_name' => $author->user_login, // This is the change.
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	/**
	 * Test a simple query that a post is returned after setting a co-author on it.
	 * This test is run as the default administrator user.
	 */
	public function test_get_post_by_user_ID_when_single_author_is_set_as_post_author_but_current_user_is_admin(): void {
		$author = $this->create_author();
		$post   = $this->create_post( $author );
		$this->_cap->add_coauthors( $post->ID, array( $author->user_login ) );

		$query = new \WP_Query(
			array(
				'author' => $author->ID,
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	/**
	 * Test a simple query that a post is returned after setting a co-author on it.
	 * This test is run as the author user. This is to ensure that the logic works for non-administrator roles. See #508.
	 */
	public function test_get_post_by_user_ID_when_single_author_is_set_as_post_author_but_current_user_is_author(): void {
		$author = $this->create_author();
		$post   = $this->create_post( $author );
		$this->_cap->add_coauthors( $post->ID, array( $author->user_login ) );

		wp_set_current_user( $author->ID ); // This is the only difference to the last test.

		$query = new \WP_Query(
			array(
				'author' => $author->ID,
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	/**
	 * Test a simple user_login query that a post is returned after setting multiple co-authors for it.
	 * The post_author is set as the first co-author.
	 */
	public function test_get_post_by_user_ID_when_queried_author_is_set_as_post_author(): void {
		$author1 = $this->create_author( 'author1' );
		$author2 = $this->create_author( 'author2' );
		$post   = $this->create_post( $author1 );
		$this->_cap->add_coauthors( $post->ID, array( $author1->user_login, $author2->user_login ) );

		$query = new \WP_Query(
			array(
				'author_name' => $author2->user_login,
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	/**
	 * Test a simple user_login query that a post is returned after setting multiple co-authors for it.
	 * The post_author is set as the first co-author, but we're querying by the second co-author, so
	 * this is magic working now.
	 */
	public function test_get_post_by_user_ID_when_queried_author_is_not_set_as_post_author(): void {
		$author1 = $this->create_author( 'author1' );
		$author2 = $this->create_author( 'author2' );
		$post   = $this->create_post( $author1 );
		$this->_cap->add_coauthors( $post->ID, array( $author1->user_login, $author2->user_login ) );

		$query = new \WP_Query(
			array(
				'author' => $author2->ID,
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	public function test_get_post_by_user_ID_when_queried_author_is_set_as_post_author_but_there_is_a_tag(): void {
		$author = $this->create_author();
		$post   = $this->create_post( $author );
		$this->_cap->add_coauthors( $post->ID, array( $author->user_login ) );
		wp_set_post_terms( $post->ID, 'test' );

		$query = new \WP_Query(
			array(
				'author_name' => $author->user_login,
				'tag'         => 'test',
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}

	public function test_get_post_by_user_ID_when_queried_author_is_not_set_as_post_author_and_there_is_a_tag(): void {
		$author1 = $this->create_author( 'author1' );
		$author2 = $this->create_author( 'author2' );
		$post   = $this->create_post( $author1 );
		$this->_cap->add_coauthors( $post->ID, array( $author1->user_login, $author2->user_login ) );
		wp_set_post_terms( $post->ID, 'test' );

		$query = new \WP_Query(
			array(
				'author_name' => $author2->user_login,
				'tag'         => 'test',
			)
		);

		$this->assertCount( 1, $query->posts );
		$this->assertEquals( $post->ID, $query->posts[0]->ID );
	}
}
