<?php

class Test_Template_Tags extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post_id = wp_insert_post( array(
			'post_author'  => $this->author1,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Tests for co-authors display names, with links to their posts.
	 *
	 * @see : https://github.com/Automattic/Co-Authors-Plus/issues/279
	 *
	 * @covers :: coauthors_posts_links()
	 */
	public function test_coauthors_posts_links() {

		global $coauthors_plus;

		$GLOBALS['post'] = get_post( $this->post_id );

		// Checks for single post author.
		$author1       = get_user_by( 'id', $this->author1 );
		$single_cpl    = coauthors_posts_links( null, null, null, null, false );
		$expected_href = '<a href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"';
		$expected_name = '>' . $author1->display_name . '</a>';

		$this->assertContains( $expected_href, $single_cpl );
		$this->assertContains( $expected_name, $single_cpl );

		// Checks for multiple post author.
		$editor1 = get_user_by( 'id', $this->editor1 );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$multiple_cpl         = coauthors_posts_links( null, null, null, null, false );
		$expected_first_href  = '<a href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"';
		$expected_first_name  = '>' . $author1->display_name . '</a> and ';
		$expected_second_href = '<a href="' . get_author_posts_url( $editor1->ID, $editor1->user_nicename ) . '"';
		$expected_second_name = '>' . $editor1->display_name . '</a>';

		$this->assertContains( $expected_first_href, $multiple_cpl );
		$this->assertContains( $expected_first_name, $multiple_cpl );
		$this->assertContains( $expected_second_href, $multiple_cpl );
		$this->assertContains( $expected_second_name, $multiple_cpl );

		$multiple_cpl        = coauthors_links( null, ' or ', null, null, false );
		$expected_first_name = '>' . $author1->display_name . '</a> or ';

		$this->assertContains( $expected_first_name, $multiple_cpl );

		$this->assertEquals( 10, has_filter( 'the_author') );
	}

	/**
	 * Tests for co-authors display names.
	 *
	 * @see : https://github.com/Automattic/Co-Authors-Plus/issues/279
	 *
	 * @covers :: coauthors_links()
	 */
	public function test_coauthors_links() {

		global $coauthors_plus;

		$GLOBALS['post'] = get_post( $this->post_id );

		// Checks for single post author.
		$author1       = get_user_by( 'id', $this->author1 );
		$single_cpl    = coauthors_links( null, null, null, null, false );
		$expected_name = $author1->display_name;

		$this->assertEquals( $expected_name, $single_cpl );

		// Checks for multiple post author.
		$editor1 = get_user_by( 'id', $this->editor1 );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$multiple_cpl         = coauthors_links( null, null, null, null, false );
		$expected_first_name  = $author1->display_name;
		$expected_second_name = $editor1->display_name;

		$this->assertContains( $expected_first_name, $multiple_cpl );
		$this->assertContains( ' and ', $multiple_cpl );
		$this->assertContains( $expected_second_name, $multiple_cpl );

		$multiple_cpl = coauthors_links( null, ' or ', null, null, false );

		$this->assertContains( ' or ', $multiple_cpl );

		$this->assertEquals( 10, has_filter( 'the_author' ) );
	}
}
