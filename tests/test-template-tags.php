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

		$GLOBALS['post'] = $this->post;

		// Checks for single post author.
		$single_cpl = coauthors_posts_links( null, null, null, null, false );

		$this->assertContains( 'href="' . get_author_posts_url( $this->author1->ID, $this->author1->user_nicename ) . '"', $single_cpl, 'Author link not found.' );
		$this->assertContains( $this->author1->display_name, $single_cpl, 'Author name not found.' );

		// Checks for multiple post author.
		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$multiple_cpl = coauthors_posts_links( null, null, null, null, false );

		$this->assertContains( 'href="' . get_author_posts_url( $this->author1->ID, $this->author1->user_nicename ) . '"', $multiple_cpl, 'Main author link not found.' );
		$this->assertContains( $this->author1->display_name, $multiple_cpl, 'Main author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$this->author1->display_name}<" because "$this->author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $multiple_cpl, ">{$this->author1->display_name}<" ) );
		$this->assertContains( ' and ', $multiple_cpl, 'Coauthors name separator is not matched.' );
		$this->assertContains( 'href="' . get_author_posts_url( $this->editor1->ID, $this->editor1->user_nicename ) . '"', $multiple_cpl, 'Coauthor link not found.' );
		$this->assertContains( $this->editor1->display_name, $multiple_cpl, 'Coauthor name not found.' );

		// Here we are checking editor name should not be more then one time.
		// Asserting ">{$this->editor1->display_name}<" because "$this->editor1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $multiple_cpl, ">{$this->editor1->display_name}<" ) );

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

		$GLOBALS['post'] = $this->post;

		// Checks for single post author.
		$single_cpl = coauthors_links( null, null, null, null, false );

		$this->assertEquals( $this->author1->display_name, $single_cpl, 'Author name not found.' );

		// Checks for multiple post author.
		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$multiple_cpl = coauthors_links( null, null, null, null, false );

		$this->assertContains( $this->author1->display_name, $multiple_cpl, 'Main author name not found.' );
		$this->assertEquals( 1, substr_count( $multiple_cpl, $this->author1->display_name ) );
		$this->assertContains( ' and ', $multiple_cpl, 'Coauthors name separator is not matched.' );
		$this->assertContains( $this->editor1->display_name, $multiple_cpl, 'Coauthor name not found.' );
		$this->assertEquals( 1, substr_count( $multiple_cpl, $this->editor1->display_name ) );

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
	 * Checks coauthors when post not exist.
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_post_not_exists() {

		$this->assertEmpty( get_coauthors() );
	}

	/**
	 * Checks coauthors when post exist (not global).
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_post_exists() {

		global $coauthors_plus;

		// Compare single author.
		$this->assertEquals( array( $this->author1->ID ), wp_list_pluck( get_coauthors( $this->post->ID ), 'ID' ) );

		// Compare multiple authors.
		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );
		$this->assertEquals( array(
			$this->author1->ID,
			$this->editor1->ID,
		), wp_list_pluck( get_coauthors( $this->post->ID ), 'ID' ) );
	}

	/**
	 * Checks coauthors when terms for post not exist.
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_terms_for_post_not_exists() {

		$post_id = $this->factory->post->create();
		$this->assertEmpty( get_coauthors( $post_id ) );
	}

	/**
	 * Checks coauthors when post not exist.
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_global_post_exists() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->factory->post->create_and_get();

		$this->assertEmpty( get_coauthors() );

		$user_id = $this->factory->user->create();
		$post    = $this->factory->post->create_and_get( array(
			'post_author' => $user_id,
		) );

		$this->assertEquals( array( $user_id ), wp_list_pluck( get_coauthors(), 'ID' ) );

		// Restore global post from backup.
		$post = $post_backup;
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

	/**
	 * Checks whether user is a coauthor of the post when user or post not exists.
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_or_post_not_exists() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$this->assertFalse( is_coauthor_for_post( '' ) );
		$this->assertFalse( is_coauthor_for_post( '', $this->post->ID ) );
		$this->assertFalse( is_coauthor_for_post( $this->author1->ID ) );

		$post = $this->post;

		$this->assertFalse( is_coauthor_for_post( '' ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks whether user is a coauthor of the post when user is not expected as ID,
	 * or user_login is not set in user object.
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_not_numeric_or_user_login_not_set() {

		$this->assertFalse( is_coauthor_for_post( 'test' ) );
	}

	/**
	 * Checks whether user is a coauthor of the post when user is set in either way,
	 * as user_id or user object but he/she is not coauthor of the post.
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_numeric_or_user_login_set_but_not_coauthor() {

		$this->assertFalse( is_coauthor_for_post( $this->editor1->ID, $this->post->ID ) );
		$this->assertFalse( is_coauthor_for_post( $this->editor1, $this->post->ID ) );
	}

	/**
	 * Checks whether user is a coauthor of the post.
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_is_coauthor() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		// Checking with specific post and user_id as well ass user object.
		$this->assertTrue( is_coauthor_for_post( $this->author1->ID, $this->post->ID ) );
		$this->assertTrue( is_coauthor_for_post( $this->author1, $this->post->ID ) );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$this->assertTrue( is_coauthor_for_post( $this->editor1->ID, $this->post->ID ) );
		$this->assertTrue( is_coauthor_for_post( $this->editor1, $this->post->ID ) );

		// Now checking with global post and user_id as well ass user object.
		$post = $this->post;

		$this->assertTrue( is_coauthor_for_post( $this->author1->ID ) );
		$this->assertTrue( is_coauthor_for_post( $this->author1 ) );

		$this->assertTrue( is_coauthor_for_post( $this->editor1->ID ) );
		$this->assertTrue( is_coauthor_for_post( $this->editor1 ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Tests for co-authors display names, without links to their posts.
	 *
	 * @covers ::coauthors()
	 * @covers ::coauthors__echo()
	 **/
	public function test_coauthors() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Checks for single post author.
		$coauthors = coauthors( null, null, null, null, false );

		$this->assertEquals( $this->author1->display_name, $coauthors );

		$coauthors = coauthors( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->display_name . '</span>', $coauthors );

		// Checks for multiple post author.
		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$coauthors = coauthors( null, null, null, null, false );

		$this->assertEquals( $this->author1->display_name . ' and ' . $this->editor1->display_name, $coauthors );

		$coauthors = coauthors( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->display_name . '</span><span>' . $this->editor1->display_name . '</span>', $coauthors );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author linked to their post archive.
	 *
	 * @covers ::coauthors_posts_links_single()
	 */
	public function test_coauthors_posts_links_single() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		$author_link = coauthors_posts_links_single( $this->author1 );

		$this->assertContains( 'href="' . get_author_posts_url( $this->author1->ID, $this->author1->user_nicename ) . '"', $author_link, 'Author link not found.' );
		$this->assertContains( $this->author1->display_name, $author_link, 'Author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$this->author1->display_name}<" because "$this->author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, ">{$this->author1->display_name}<" ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors first names, without links to their posts.
	 *
	 * @covers ::coauthors_firstnames()
	 * @covers ::coauthors__echo()
	 */
	public function test_coauthors_firstnames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Checking when first name is not set for user, so it should match with user_login.
		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login, $first_names );

		$first_names = coauthors_firstnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span>', $first_names );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login . ' and ' . $this->editor1->user_login, $first_names );

		$first_names = coauthors_firstnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span><span>' . $this->editor1->user_login . '</span>', $first_names );

		// Checking when first name is set for user.
		$first_name = 'Test';
		$user_id    = $this->factory->user->create( array(
			'first_name' => $first_name,
		) );
		$post       = $this->factory->post->create_and_get( array(
			'post_author' => $user_id,
		) );

		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $first_name, $first_names );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors last names, without links to their posts.
	 *
	 * @covers ::coauthors_lastnames()
	 * @covers ::coauthors__echo()
	 */
	public function test_coauthors_lastnames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Checking when last name is not set for user, so it should match with user_login.
		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login, $last_names );

		$last_names = coauthors_lastnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span>', $last_names );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login . ' and ' . $this->editor1->user_login, $last_names );

		$last_names = coauthors_lastnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span><span>' . $this->editor1->user_login . '</span>', $last_names );

		// Checking when last name is set for user.
		$last_name = 'Test';
		$user_id   = $this->factory->user->create( array(
			'last_name' => $last_name,
		) );
		$post      = $this->factory->post->create_and_get( array(
			'post_author' => $user_id,
		) );

		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $last_name, $last_names );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors nicknames, without links to their posts.
	 *
	 * @covers ::coauthors_nicknames()
	 * @covers ::coauthors__echo()
	 */
	public function test_coauthors_nicknames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Checking when nickname is not set for user, so it should match with user_login.
		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login, $nick_names );

		$nick_names = coauthors_nicknames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span>', $nick_names );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_login . ' and ' . $this->editor1->user_login, $nick_names );

		$nick_names = coauthors_nicknames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_login . '</span><span>' . $this->editor1->user_login . '</span>', $nick_names );

		// Checking when nickname is set for user.
		$nick_name = 'Test';
		$user_id   = $this->factory->user->create( array(
			'nickname' => $nick_name,
		) );
		$post      = $this->factory->post->create_and_get( array(
			'post_author' => $user_id,
		) );

		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $nick_name, $nick_names );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors email addresses.
	 *
	 * @covers ::coauthors_emails()
	 * @covers ::coauthors__echo()
	 */
	public function test_coauthors_emails() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_email, $emails );

		$emails = coauthors_emails( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_email . '</span>', $emails );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $this->author1->user_email . ' and ' . $this->editor1->user_email, $emails );

		$emails = coauthors_emails( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->user_email . '</span><span>' . $this->editor1->user_email . '</span>', $emails );

		$email   = 'test@example.org';
		$user_id = $this->factory->user->create( array(
			'user_email' => $email,
		) );
		$post    = $this->factory->post->create_and_get( array(
			'post_author' => $user_id,
		) );

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $email, $emails );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author if he/she is a guest author.
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_when_guest_author() {

		global $post, $authordata;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Backing up global author data.
		$authordata_backup = $authordata;
		$authordata = $this->author1;

		// Shows that it's necessary to set $authordata to $this->author1
		$this->assertEquals( $authordata, $this->author1, 'Global $authordata not matching expected $this->author1.' );
		
		$this->author1->type = 'guest-author';

		$this->assertEquals( get_the_author_link(), coauthors_links_single( $this->author1 ), 'Co-Author link generation differs from Core author link one (without user_url)' );
		
		wp_update_user( array( 'ID' => $this->author1->ID, 'user_url' => 'example.org' ) );
		$authordata = get_userdata( $this->author1->ID ); // Because wp_update_user flushes cache, but does not update global var
		
		$this->assertEquals( get_the_author_link(), coauthors_links_single( $this->author1 ), 'Co-Author link generation differs from Core author link one (with user_url)' );

		$author_link = coauthors_links_single( $this->author1 );
		$this->assertContains( get_the_author_meta( 'url' ), $author_link, 'Author url not found in link.' );
		$this->assertContains( get_the_author(), $author_link, 'Author name not found in link.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">get_the_author()<" because "get_the_author()" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, '>' . get_the_author() . '<' ) );

		// Restore global author data from backup.
		$authordata = $authordata_backup;

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author when user's url is set and not a guest author.
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_author_url_is_set() {

		global $post, $authordata;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Backing up global author data.
		$authordata_backup = $authordata;

		$user_id = $this->factory->user->create( array(
			'user_url' => 'example.org',
		) );
		$user    = get_user_by( 'id', $user_id );

		$authordata  = $user;
		$author_link = coauthors_links_single( $user );

		$this->assertContains( get_the_author_meta( 'url' ), $author_link, 'Author link not found.' );
		$this->assertContains( get_the_author(), $author_link, 'Author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">get_the_author()<" because "get_the_author()" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, '>' . get_the_author() . '<' ) );

		// Restore global author data from backup.
		$authordata = $authordata_backup;

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author when user's website/url not exist.
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_when_url_not_exist() {

		global $post, $authordata;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		// Backing up global author data.
		$authordata_backup = $authordata;

		$this->editor1->type = 'guest-author';

		$author_link = coauthors_links_single( $this->editor1 );

		$this->assertEquals( get_the_author(), $author_link );

		$authordata  = $this->author1;
		$author_link = coauthors_links_single( $this->author1 );

		$this->assertEquals( get_the_author(), $author_link );

		// Restore global author data from backup.
		$authordata = $authordata_backup;

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors IDs.
	 *
	 * @covers ::coauthors_ids()
	 * @covers ::coauthors__echo()
	 */
	public function test_coauthors_ids() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		$ids = coauthors_ids( null, null, null, null, false );

		$this->assertEquals( $this->author1->ID, $ids );

		$ids = coauthors_ids( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->ID . '</span>', $ids );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$ids = coauthors_ids( null, null, null, null, false );

		$this->assertEquals( $this->author1->ID . ' and ' . $this->editor1->ID, $ids );

		$ids = coauthors_ids( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $this->author1->ID . '</span><span>' . $this->editor1->ID . '</span>', $ids );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors meta.
	 *
	 * @covers ::get_the_coauthor_meta()
	 */
	public function test_get_the_coauthor_meta() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$this->assertEmpty( get_the_coauthor_meta( '' ) );

		update_user_meta( $this->author1->ID, 'meta_key', 'meta_value' );

		$this->assertEmpty( get_the_coauthor_meta( 'meta_key' ) );

		$post = $this->post;
		$meta = get_the_coauthor_meta( 'meta_key' );

		$this->assertEquals( 'meta_value', $meta[ $this->author1->ID ] );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks all the co-authors of the blog with default args.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_default_args() {

		global $coauthors_plus;

		$args = array(
			'echo' => false,
		);

		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertContains( 'href="' . get_author_posts_url( $this->author1->ID, $this->author1->user_nicename ) . '"', $coauthors, 'Author link not found.' );
		$this->assertContains( $this->author1->display_name, $coauthors, 'Author name not found.' );

		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertNotContains( 'href="' . get_author_posts_url( $this->editor1->ID, $this->editor1->user_nicename ) . '"', $coauthors );
		$this->assertNotContains( $this->editor1->display_name, $coauthors );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertContains( 'href="' . get_author_posts_url( $this->author1->ID, $this->author1->user_nicename ) . '"', $coauthors, 'Main author link not found.' );
		$this->assertContains( $this->author1->display_name, $coauthors, 'Main author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$this->author1->display_name}<" because "$this->author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $coauthors, ">{$this->author1->display_name}<" ) );

		$this->assertContains( '</li><li>', $coauthors, 'Coauthors name separator is not matched.' );
		$this->assertContains( 'href="' . get_author_posts_url( $this->editor1->ID, $this->editor1->user_nicename ) . '"', $coauthors, 'Coauthor link not found.' );
		$this->assertContains( $this->editor1->display_name, $coauthors, 'Coauthor name not found.' );

		// Here we are checking editor name should not be more then one time.
		// Asserting ">{$this->editor1->display_name}<" because "$this->editor1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $coauthors, ">{$this->editor1->display_name}<" ) );
	}

	/**
	 * Checks all the co-authors of the blog with optioncount option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_optioncount() {

		$this->assertContains( '(' . count_user_posts( $this->author1->ID ) . ')', coauthors_wp_list_authors( array(
			'echo'        => false,
			'optioncount' => true,
		) ) );
	}

	/**
	 * Checks all the co-authors of the blog with show_fullname option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_show_fullname() {

		$args = array(
			'echo'          => false,
			'show_fullname' => true,
		);

		$this->assertContains( $this->author1->display_name, coauthors_wp_list_authors( $args ) );

		$user = $this->factory->user->create_and_get( array(
			'first_name' => 'First',
			'last_name'  => 'Last',
		) );

		$this->factory->post->create( array(
			'post_author' => $user->ID,
		) );

		$this->assertContains( "{$user->user_firstname} {$user->user_lastname}", coauthors_wp_list_authors( $args ) );
	}

	/**
	 * Checks all the co-authors of the blog with hide_empty option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_hide_empty() {

		global $coauthors_plus;

		$coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$this->assertContains( 'author2', coauthors_wp_list_authors( array(
			'echo'       => false,
			'hide_empty' => false,
		) ) );
	}

	/**
	 * Checks all the co-authors of the blog with feed option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed() {

		$feed_text = 'link to feed';
		$coauthors = coauthors_wp_list_authors( array(
			'echo' => false,
			'feed' => $feed_text,
		) );

		$this->assertContains( esc_url( get_author_feed_link( $this->author1->ID ) ), $coauthors );
		$this->assertContains( $feed_text, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with feed_image option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed_image() {

		$feed_image = WP_TESTS_DOMAIN . '/path/to/a/graphic.png';
		$coauthors  = coauthors_wp_list_authors( array(
			'echo'       => false,
			'feed_image' => $feed_image,
		) );

		$this->assertContains( esc_url( get_author_feed_link( $this->author1->ID ) ), $coauthors );
		$this->assertContains( $feed_image, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with feed_type option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed_type() {

		$feed_type = 'atom';
		$feed_text = 'link to feed';
		$coauthors = coauthors_wp_list_authors( array(
			'echo'      => false,
			'feed_type' => $feed_type,
			'feed'      => $feed_text,
		) );

		$this->assertContains( esc_url( get_author_feed_link( $this->author1->ID, $feed_type ) ), $coauthors );
		$this->assertContains( $feed_type, $coauthors );
		$this->assertContains( $feed_text, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with style option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_style() {

		$coauthors = coauthors_wp_list_authors( array(
			'echo'  => false,
			'style' => 'none',
		) );

		$this->assertNotContains( '<li>', $coauthors );
		$this->assertNotContains( '</li>', $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with html option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_html() {

		global $coauthors_plus;

		$args = array(
			'echo' => false,
			'html' => false,
		);

		$this->assertEquals( $this->author1->display_name, coauthors_wp_list_authors( $args ) );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->editor1->user_login ), true );

		$this->assertEquals( "{$this->author1->display_name}, {$this->editor1->display_name}", coauthors_wp_list_authors( $args ) );
	}

	/**
	 * Checks all the co-authors of the blog with guest_authors_only option.
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_guest_authors_only() {

		global $coauthors_plus;

		$args = array(
			'echo'               => false,
			'guest_authors_only' => true,
		);

		$this->assertEmpty( coauthors_wp_list_authors( $args ) );

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$this->assertEmpty( coauthors_wp_list_authors( $args ) );

		$guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'id', $guest_author_id );

		$coauthors_plus->add_coauthors( $this->post->ID, array( $guest_author->user_login ), true );

		$this->assertContains( $guest_author->display_name, coauthors_wp_list_authors( $args ) );
	}

	/**
	 * Checks co-author's avatar.
	 *
	 * @covers ::coauthors_get_avatar()
	 */
	public function test_coauthors_get_avatar_default() {

		$this->assertEmpty( coauthors_get_avatar( $this->author1->ID ) );
		$this->assertEquals( preg_match( "|^<img alt='[^']*' src='[^']*' srcset='[^']*' class='[^']*' height='[^']*' width='[^']*' />$|", coauthors_get_avatar( $this->author1 ) ), 1 );
	}

	/**
	 * Checks co-author's avatar when author is a guest author.
	 *
	 * @covers ::coauthors_get_avatar()
	 */
	public function test_coauthors_get_avatar_when_guest_author() {

		global $coauthors_plus;

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'id', $guest_author_id );

		$this->assertEquals( preg_match( "|^<img alt='[^']*' src='[^']*' srcset='[^']*' class='[^']*' height='[^']*' width='[^']*' />$|", coauthors_get_avatar( $guest_author ) ), 1 );

		$filename = rand_str() . '.jpg';
		$contents = rand_str();
		$upload   = wp_upload_bits( $filename, null, $contents );

		$this->assertTrue( empty( $upload['error'] ) );

		$attachment_id = $this->_make_attachment( $upload );

		set_post_thumbnail( $guest_author->ID, $attachment_id );

		$avatar         = coauthors_get_avatar( $guest_author );
		$attachment_url = wp_get_attachment_url( $attachment_id );

		$this->assertContains( $filename, $avatar );
		$this->assertContains( 'src="' . $attachment_url . '"', $avatar );
	}

	/**
	 * Checks co-author's avatar when user's email is not set somehow.
	 *
	 * @covers ::coauthors_get_avatar()
	 */
	public function test_coauthors_get_avatar_when_user_email_not_set() {

		global $coauthors_plus;

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'id', $guest_author_id );

		unset( $guest_author->user_email );

		$this->assertEmpty( coauthors_get_avatar( $guest_author ) );
	}

	/**
	 * Checks co-author's avatar with size.
	 *
	 * @covers ::coauthors_get_avatar()
	 */
	public function test_coauthors_get_avatar_size() {

		$size = '100';
		$this->assertEquals( preg_match( "|^<img .*height='$size'.*width='$size'|", coauthors_get_avatar( $this->author1, $size ) ), 1 );
	}

	/**
	 * Checks co-author's avatar with alt.
	 *
	 * @covers ::coauthors_get_avatar()
	 */
	public function test_coauthors_get_avatar_alt() {

		$alt = 'Test';
		$this->assertEquals( preg_match( "|^<img alt='$alt'|", coauthors_get_avatar( $this->author1, 96, '', $alt ) ), 1 );
	}
}
