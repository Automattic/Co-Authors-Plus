<?php

class Test_Template_Tags extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		/**
		 * When 'coauthors_auto_apply_template_tags' is set to true,
		 * we need CoAuthors_Template_Filters object to check 'the_author' filter.
		 */
		global $coauthors_plus_template_filters;
		$coauthors_plus_template_filters = new CoAuthors_Template_Filters;

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
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/279
	 *
	 * @covers ::coauthors_posts_links()
	 */
	public function test_coauthors_posts_links() {

		global $coauthors_plus, $coauthors_plus_template_filters;

		// Backing up global post.
		$post_backup = $GLOBALS['post'];

		$GLOBALS['post'] = get_post( $this->post_id );

		// Checks for single post author.
		$author1    = get_user_by( 'id', $this->author1 );
		$single_cpl = coauthors_posts_links( null, null, null, null, false );

		$this->assertContains( 'href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"', $single_cpl, 'Author link not found.' );
		$this->assertContains( $author1->display_name, $single_cpl, 'Author name not found.' );

		// Checks for multiple post author.
		$editor1 = get_user_by( 'id', $this->editor1 );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$multiple_cpl = coauthors_posts_links( null, null, null, null, false );

		$this->assertContains( 'href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"', $multiple_cpl, 'Main author link not found.' );
		$this->assertContains( $author1->display_name, $multiple_cpl, 'Main author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$author1->display_name}<" because "$author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $multiple_cpl, ">{$author1->display_name}<" ) );
		$this->assertContains( ' and ', $multiple_cpl, 'Coauthors name separator is not matched.' );
		$this->assertContains( 'href="' . get_author_posts_url( $editor1->ID, $editor1->user_nicename ) . '"', $multiple_cpl, 'Coauthor link not found.' );
		$this->assertContains( $editor1->display_name, $multiple_cpl, 'Coauthor name not found.' );

		// Here we are checking editor name should not be more then one time.
		// Asserting ">{$editor1->display_name}<" because "$editor1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $multiple_cpl, ">{$editor1->display_name}<" ) );

		$multiple_cpl = coauthors_links( null, ' or ', null, null, false );

		$this->assertContains( ' or ', $multiple_cpl, 'Coauthors name separator is not matched.' );

		$this->assertEquals( 10, has_filter( 'the_author', array(
			$coauthors_plus_template_filters,
			'filter_the_author',
		) ) );

		// Restore backed up post to global.
		$GLOBALS['post'] = $post_backup;
	}

	/**
	 * Tests for co-authors display names.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/279
	 *
	 * @covers ::coauthors_links()
	 */
	public function test_coauthors_links() {

		global $coauthors_plus, $coauthors_plus_template_filters;

		// Backing up global post.
		$post_backup = $GLOBALS['post'];

		$GLOBALS['post'] = get_post( $this->post_id );

		// Checks for single post author.
		$author1    = get_user_by( 'id', $this->author1 );
		$single_cpl = coauthors_links( null, null, null, null, false );

		$this->assertEquals( $author1->display_name, $single_cpl, 'Author name not found.' );

		// Checks for multiple post author.
		$editor1 = get_user_by( 'id', $this->editor1 );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$multiple_cpl = coauthors_links( null, null, null, null, false );

		$this->assertContains( $author1->display_name, $multiple_cpl, 'Main author name not found.' );
		$this->assertEquals( 1, substr_count( $multiple_cpl, $author1->display_name ) );
		$this->assertContains( ' and ', $multiple_cpl, 'Coauthors name separator is not matched.' );
		$this->assertContains( $editor1->display_name, $multiple_cpl, 'Coauthor name not found.' );
		$this->assertEquals( 1, substr_count( $multiple_cpl, $editor1->display_name ) );

		$multiple_cpl = coauthors_links( null, ' or ', null, null, false );

		$this->assertContains( ' or ', $multiple_cpl, 'Coauthors name separator is not matched.' );

		$this->assertEquals( 10, has_filter( 'the_author', array(
			$coauthors_plus_template_filters,
			'filter_the_author',
		) ) );

		// Restore backed up post to global.
		$GLOBALS['post'] = $post_backup;
	}
}
