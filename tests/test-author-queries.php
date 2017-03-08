<?php

class Test_Author_Queries extends CoAuthorsPlus_TestCase {

	public function test__author_arg__user_is_post_author() {
		$author_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author = get_userdata( $author_id );
		$post_id = $this->factory->post->create( array(
			'post_author'     => $author_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author->user_login ) );

		$query = new WP_Query( array(
			'author' => $author_id,
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}

	public function test__author_name_arg__user_is_post_author() {
		$author_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author = get_userdata( $author_id );
		$post_id = $this->factory->post->create( array(
			'post_author'     => $author_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author->user_login ) );

		$query = new WP_Query( array(
			'author_name' => $author->user_login,
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}

	public function test__author_name_arg__user_is_coauthor() {
		$author1_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author1 = get_userdata( $author1_id );
		$author2_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'superman' ) );
		$author2 = get_userdata( $author2_id );

		$post_id = $this->factory->post->create( array(
			'post_author'     => $author1_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author1->user_login, $author2->user_login ) );

		$query = new WP_Query( array(
			'author_name' => $author2->user_login,
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}

	public function test__author_arg__user_is_coauthor__author_arg() {
		return; // TODO: re-enable; fails currently because WordPress generates query as `post_author IN (id)` which doesn't match our regex in the posts_where filter.

		$author1_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author1 = get_userdata( $author1_id );
		$author2_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'superman' ) );
		$author2 = get_userdata( $author2_id );

		$post_id = $this->factory->post->create( array(
			'post_author'     => $author1_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author1->user_login, $author2->user_login ) );

		$query = new WP_Query( array(
			'author' => $author2_id,
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}

	public function test__author_name_arg_plus_tax_query__user_is_post_author() {
		$author_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author = get_userdata( $author_id );
		$post_id = $this->factory->post->create( array(
			'post_author'     => $author_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author->user_login ) );
		wp_set_post_terms( $post_id, 'test', 'post_tag' );

		$query = new WP_Query( array(
			'author_name' => $author->user_login,
			'tag' => 'test',
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}

	public function tests__author_name_arg_plus_tax_query__is_coauthor() {
		return; // TODO: re-enable; fails currently because our posts_join_filter doesn't add an exclusive JOIN on relationships + taxonomy to match the query mods we make. We'd need aliased JOINs on relationships + taxonomy on top of the JOIN that the tax query already adds.

		$author1_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'batman' ) );
		$author1 = get_userdata( $author1_id );
		$author2_id = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'superman' ) );
		$author2 = get_userdata( $author2_id );

		$post_id = $this->factory->post->create( array(
			'post_author'     => $author1_id,
			'post_status'     => 'publish',
			'post_type'       => 'post',
		) );
		$this->_cap->add_coauthors( $post_id, array( $author1->user_login, $author2->user_login ) );
		wp_set_post_terms( $post_id, 'test', 'post_tag' );

		$query = new WP_Query( array(
			'author_name' => $author2->user_login,
			'tag' => 'test',
		) );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post_id, $query->posts[ 0 ]->ID );
	}
}
