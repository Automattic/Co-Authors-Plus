<?php

class Test_Manage_CoAuthors extends CoAuthorsPlus_TestCase {

	public function setUp() {
		parent::setUp();

		$this->admin1 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'admin1' ) );
		$this->author1 = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor2' ) );

		$post = array(
			'post_author'     => $this->author1,
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_type'       => 'post',
		);

		$this->author1_post1 = wp_insert_post( $post );

		$post = array(
			'post_author'     => $this->author1,
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_type'       => 'post',
		);

		$this->author1_post2 = wp_insert_post( $post );

		$page = array(
			'post_author'     => $this->author1,
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_type'       => 'page',
		);

		$this->author1_page1 = wp_insert_post( $page );

		$page = array(
			'post_author'     => $this->author1,
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_type'       => 'page',
		);

		$this->author1_page2 = wp_insert_post( $page );
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test assigning a Co-Author to a post
	 */
	public function test_add_coauthor_to_post() {
		global $coauthors_plus;

		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( 1, count( $coauthors ) );

		// append = true, should preserve order
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), true );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );

		// append = false, overrides existing authors
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), false );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );

	}

	/**
	 * When a co-author is assigned to a post, the post author value
	 * should be set appropriately
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/140
	 */
	public function test_add_coauthor_updates_post_author() {
		global $coauthors_plus;

		// append = true, preserves existing post_author
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), true );
		$this->assertEquals( $this->author1, get_post( $this->author1_post1 )->post_author );

		// append = false, overrides existing post_author
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), false );
		$this->assertEquals( $this->editor1, get_post( $this->author1_post1 )->post_author );

	}

	/**
	 * Post published count should default to 'post', but be filterable
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/170
	 */
	public function test_post_publish_count_for_coauthor() {
		global $coauthors_plus;

		$editor1 = get_user_by( 'id', $this->editor1 );

		/**
		 * Two published posts
		 */
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ) );
		$coauthors_plus->add_coauthors( $this->author1_post2, array( $editor1->user_login ) );
		$this->assertEquals( 2, count_user_posts( $editor1->ID ) );

		/**
		 * One published page too, but no filter
		 */
		$coauthors_plus->add_coauthors( $this->author1_page1, array( $editor1->user_login ) );
		$this->assertEquals( 2, count_user_posts( $editor1->ID ) );

		// Publish count to include posts and pages
		$filter = function() {
			return array( 'post', 'page' );
		};
		add_filter( 'coauthors_count_published_post_types', $filter );

		/**
		 * Two published posts and pages
		 */
		$coauthors_plus->add_coauthors( $this->author1_page2, array( $editor1->user_login ) );
		$this->assertEquals( 4, count_user_posts( $editor1->ID ) );

		// Publish count is just pages
		remove_filter( 'coauthors_count_published_post_types', $filter );
		$filter = function() {
			return array( 'page' );
		};
		add_filter( 'coauthors_count_published_post_types', $filter );

		/**
		 * Just one published page now for the editor
		 */
		$author1 = get_user_by( 'id', $this->author1 );
		$coauthors_plus->add_coauthors( $this->author1_page2, array( $author1->user_login ) );
		$this->assertEquals( 1, count_user_posts( $editor1->ID ) );

	}

	/**
	 * When filtering post data before saving to db, post_author should be set appropriately
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/198
	 */
	public function test_wp_insert_post_data_set_post_author() {

		global $coauthors_plus;

		$this->assertEquals( 10, has_filter( 'wp_insert_post_data', array(
			$coauthors_plus,
			'coauthors_set_post_author_field',
		) ) );

		wp_set_current_user( $this->author1 );

		$author1       = get_user_by( 'id', $this->author1 );
		$author1_post1 = get_post( $this->author1_post1 );
		$data          = array(
			'post_type'   => $author1_post1->post_type,
			'post_author' => $author1_post1->post_author,
		);

		// Check post type is enabled or not.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( $data['post_type'] ) );

		// Encoding user nicename if it has any special characters in it.
		$author = urlencode( sanitize_text_field( $author1->user_nicename ) );
		$this->assertNotEmpty( $author );

		// Create guest author with linked account with user.
		$coauthors_plus->guest_authors = new CoAuthors_Guest_Authors;
		$coauthors_plus->guest_authors->create_guest_author_from_user_id( $this->editor1 );

		$editor1      = get_user_by( 'id', $this->editor1 );
		$editor1_data = $coauthors_plus->get_coauthor_by( 'user_nicename', $editor1->user_nicename );

		$this->assertEquals( 'guest-author', $editor1_data->type );
		$this->assertNotEmpty( $editor1_data->linked_account );

		// Main WP user.
		$author_data = $coauthors_plus->get_coauthor_by( 'user_nicename', $author );
		$this->assertEquals( 'wpuser', $author_data->type );

		// Checks if post_author is unset somehow and it is available from wp_get_current_user().
		unset( $data['post_author'] );

		$user = wp_get_current_user();

		$this->assertNotEmpty( $user->ID );
		$this->assertGreaterThan( 0, $user->ID );
	}

	/**
	 * Adding coauthors when saving post.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/198
	 */
	public function test_save_post_to_update_post_coauthor() {

		global $coauthors_plus;

		$this->assertEquals( 10, has_filter( 'wp_insert_post_data', array(
			$coauthors_plus,
			'coauthors_set_post_author_field'
		) ) );

		$author1_post1 = get_post( $this->author1_post1 );

		// Check post type is enabled or not.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( $author1_post1->post_type ) );

		// If post does not have author terms.
		$post_id = $this->factory->post->create();
		$post    = get_post( $post_id );
		$user    = get_userdata( $post->post_author );

		$this->assertFalse( $coauthors_plus->has_author_terms( $post_id ) );
		$this->assertFalse( $user );

		// If current user is not allowed to set authors.
		wp_set_current_user( $this->author1 );

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors( $author1_post1 ) );
		$this->assertTrue( $coauthors_plus->has_author_terms( $this->author1_post1 ) );

		// If current user is allowed to set authors.
		wp_set_current_user( $this->admin1 );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors( $author1_post1 ) );

		$editor1 = get_user_by( 'id', $this->editor1 );

		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), true );

		$_REQUEST['coauthors-nonce'] = wp_create_nonce( 'coauthors-edit' );
		$_POST['coauthors']          = get_coauthors( $this->author1_post1 );

		$this->assertNotEmpty( $_REQUEST['coauthors-nonce'] );
		$this->assertNotEmpty( $_POST['coauthors'] );
		$this->assertNotEmpty( check_admin_referer( 'coauthors-edit', 'coauthors-nonce' ) );
	}
}
