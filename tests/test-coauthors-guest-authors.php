<?php

class Test_CoAuthors_Guest_Authors extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->admin1  = $this->factory->user->create_and_get( array( 'role' => 'administrator', 'user_login' => 'admin1' ) );
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

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Fetch guest author without forcefully.
		$this->assertFalse( $guest_author_obj->get_guest_author_by( '', '' ) );
		$this->assertFalse( $guest_author_obj->get_guest_author_by( 'ID', '' ) );
		$this->assertFalse( $guest_author_obj->get_guest_author_by( '', $this->author1->ID ) );

		// Fetch guest author forcefully.
		$this->assertFalse( $guest_author_obj->get_guest_author_by( '', '', true ) );
		$this->assertFalse( $guest_author_obj->get_guest_author_by( 'ID', '', true ) );
		$this->assertFalse( $guest_author_obj->get_guest_author_by( '', $this->author1->ID, true ) );
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

		$this->assertInstanceOf( stdClass::class, $guest_author );
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

		$guest_author_id = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		// Checks guest author using ID.
		$guest_author = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );

		$this->assertInstanceOf( stdClass::class, $guest_author );
		$this->assertEquals( $guest_author_id, $guest_author->ID );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );

		// Checks guest author using user_nicename.
		$guest_author = $guest_author_obj->get_guest_author_by( 'user_nicename', $this->editor1->user_nicename );

		$this->assertInstanceOf( stdClass::class, $guest_author );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );

		// Checks guest author using linked_account.
		$guest_author = $guest_author_obj->get_guest_author_by( 'linked_account', $this->editor1->user_login );

		$this->assertInstanceOf( stdClass::class, $guest_author );
		$this->assertEquals( $guest_author_obj->post_type, $guest_author->type );
	}

	/**
	 * Checks thumbnail for a guest author object.
	 *
	 * @covers CoAuthors_Guest_Authors::get_guest_author_thumbnail()
	 */
	public function test_get_guest_author_thumbnail() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks when guest author does not have any thumbnail.
		$guest_author_id = $guest_author_obj->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );
		$guest_author    = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );

		$this->assertNull( $guest_author_obj->get_guest_author_thumbnail( $guest_author, 0 ) );

		// Checks when guest author has thumbnail.
		$filename = rand_str() . '.jpg';
		$contents = rand_str();
		$upload   = wp_upload_bits( $filename, null, $contents );

		$this->assertTrue( empty( $upload['error'] ) );

		$attachment_id = $this->_make_attachment( $upload );

		set_post_thumbnail( $guest_author->ID, $attachment_id );

		$thumbnail = $guest_author_obj->get_guest_author_thumbnail( $guest_author, 0 );

		$this->assertContains( 'avatar-0', $thumbnail );
		$this->assertContains( $filename, $thumbnail );
		$this->assertContains( 'src="' . wp_get_attachment_url( $attachment_id ) . '"', $thumbnail );
	}

	/**
	 * Checks all of the meta fields that can be associated with a guest author.
	 *
	 * @covers CoAuthors_Guest_Authors::get_guest_author_fields()
	 */
	public function test_get_guest_author_fields() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks all the meta fields.
		$fields = $guest_author_obj->get_guest_author_fields();

		$this->assertNotEmpty( $fields );
		$this->assertInternalType( 'array', $fields );

		$keys = wp_list_pluck( $fields, 'key' );

		$global_fields = array(
			'display_name',
			'first_name',
			'last_name',
			'user_login',
			'user_email',
			'linked_account',
			'website',
			'aim',
			'yahooim',
			'jabber',
			'description',
		);

		$this->assertEquals( $global_fields, $keys );

		// Checks all the meta fields with group that does not exist.
		$fields = $guest_author_obj->get_guest_author_fields( 'test' );

		$this->assertEmpty( $fields );

		// Checks all the meta fields with group "name".
		$fields = $guest_author_obj->get_guest_author_fields( 'name' );
		$keys   = wp_list_pluck( $fields, 'key' );

		$this->assertEquals( array( 'display_name', 'first_name', 'last_name' ), $keys );

		// Checks all the meta fields with group "slug".
		$fields = $guest_author_obj->get_guest_author_fields( 'slug' );
		$keys   = wp_list_pluck( $fields, 'key' );

		$this->assertEquals( array( 'user_login', 'linked_account' ), $keys );

		// Checks all the meta fields with group "contact-info".
		$fields = $guest_author_obj->get_guest_author_fields( 'contact-info' );
		$keys   = wp_list_pluck( $fields, 'key' );

		$this->assertEquals( array( 'user_email', 'website', 'aim', 'yahooim', 'jabber' ), $keys );

		// Checks all the meta fields with group "about".
		$fields = $guest_author_obj->get_guest_author_fields( 'about' );
		$keys   = wp_list_pluck( $fields, 'key' );

		$this->assertEquals( array( 'description' ), $keys );
	}

	/**
	 * Checks all of the user accounts that have been linked.
	 *
	 * @covers CoAuthors_Guest_Authors::get_all_linked_accounts()
	 */
	public function test_get_all_linked_accounts() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$this->assertEmpty( $guest_author_obj->get_all_linked_accounts() );

		// Checks when guest author ( not linked account ) exists.
		$guest_author_obj->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$this->assertEmpty( $guest_author_obj->get_all_linked_accounts() );

		// Create guest author from existing user and check.
		$guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		$linked_accounts    = $guest_author_obj->get_all_linked_accounts();
		$linked_account_ids = wp_list_pluck( $linked_accounts, 'ID' );

		$this->assertNotEmpty( $linked_accounts );
		$this->assertInternalType( 'array', $linked_accounts );
		$this->assertTrue( in_array( $this->editor1->ID, $linked_account_ids, true ) );
	}

	/**
	 * Checks all of the user accounts that have been linked using cache.
	 *
	 * @covers CoAuthors_Guest_Authors::get_all_linked_accounts()
	 */
	public function test_get_all_linked_accounts_with_cache() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$cache_key = 'all-linked-accounts';

		// Checks when guest author does not exist in cache.
		$this->assertFalse( wp_cache_get( $cache_key, $guest_author_obj::$cache_group ) );

		// Checks when guest author exists in cache.
		$guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		$linked_accounts       = $guest_author_obj->get_all_linked_accounts();
		$linked_accounts_cache = wp_cache_get( $cache_key, $guest_author_obj::$cache_group );

		$this->assertEquals( $linked_accounts, $linked_accounts_cache );
	}

	/**
	 * Checks guest author from an existing WordPress user.
	 *
	 * @covers CoAuthors_Guest_Authors::create_guest_author_from_user_id()
	 */
	public function test_create_guest_author_from_user_id() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks create guest author when user don't exist.
		$response = $guest_author_obj->create_guest_author_from_user_id( 0 );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'invalid-user', $response->get_error_code() );

		// Checks create guest author when user exist.
		$guest_author_id = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );
		$guest_author    = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );

		$this->assertInstanceOf( stdClass::class, $guest_author );
	}

	/**
	 * Checks delete guest author action when $_POST args are not set.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_when_post_args_not_as_expected() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks when nothing is set.
		$this->assertNull( $guest_author_obj->handle_delete_guest_author_action() );

		// Back up $_POST.
		$_post_backup = $_POST;

		// Checks when action is set but not expected.
		$_POST['action'] = 'test';
		$_POST['id']     = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		$this->assertNull( $guest_author_obj->handle_delete_guest_author_action() );

		// Get guest author and check that is should not be removed.
		$guest_author = $guest_author_obj->get_guest_author_by( 'ID', $_POST['id'] );

		$this->assertNotEmpty( $guest_author );

		// Checks when _wpnonce and id not set.
		$_POST['action']   = 'delete-guest-author';
		$_POST['reassign'] = 'test';

		$this->assertNull( $guest_author_obj->handle_delete_guest_author_action() );

		// Get guest author and check that is should not be removed.
		$guest_author = $guest_author_obj->get_guest_author_by( 'ID', $_POST['id'] );

		$this->assertNotEmpty( $guest_author );

		// Checks when all args set for $_POST but action is not as expected.
		$_POST['action']   = 'test';
		$_POST['reassign'] = 'test';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author-1' );

		$this->assertNull( $guest_author_obj->handle_delete_guest_author_action() );

		// Get guest author and check that is should not be removed.
		$guest_author = $guest_author_obj->get_guest_author_by( 'ID', $_POST['id'] );

		$this->assertNotEmpty( $guest_author );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action with nonce.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_nonce() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		$expected = __( "Doin' something fishy, huh?", 'co-authors-plus' );

		$_POST['action']   = 'delete-guest-author';
		$_POST['reassign'] = 'test';
		$_POST['id']       = '0';

		// Checks when nonce is not as expected.
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author-1' );

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertContains( esc_html( $expected ), $exception->getMessage() );

		// Checks when nonce is as expected.
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotContains( esc_html( $expected ), $exception->getMessage() );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action with list_author capability.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_list_users_capability() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		$expected = __( "You don't have permission to perform this action.", 'co-authors-plus' );

		// Back up current user.
		$current_user = get_current_user_id();

		wp_set_current_user( $this->editor1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['reassign'] = 'test';

		// Checks when current user can not have list_users capability.
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->editor1->ID );

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertContains( esc_html( $expected ), $exception->getMessage() );

		// Checks when current user has list_users capability.
		wp_set_current_user( $this->admin1->ID );

		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotContains( esc_html( $expected ), $exception->getMessage() );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action with guest author.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_guest_author_existence() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		$expected = sprintf( __( "%s can't be deleted because it doesn't exist.", 'co-authors-plus' ), $guest_author_obj->labels['singular'] );

		// Back up current user.
		$current_user = get_current_user_id();

		wp_set_current_user( $this->admin1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['reassign'] = 'test';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $this->admin1->ID;

		// Checks when guest author does not exist.
		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertContains( esc_html( $expected ), $exception->getMessage() );

		// Checks when guest author exists.
		$_POST['id'] = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotContains( esc_html( $expected ), $exception->getMessage() );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action with reassign not as expected.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_reassign_not_as_expected() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		$expected = __( 'Please make sure to pick an option.', 'co-authors-plus' );

		// Back up current user.
		$current_user = get_current_user_id();

		wp_set_current_user( $this->admin1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );

		// Checks when reassign is not as expected.
		$_POST['reassign'] = 'test';

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertContains( esc_html( $expected ), $exception->getMessage() );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action when reassign is leave-assigned.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_reassign_is_leave_assigned() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		// Back up current user.
		$current_user = get_current_user_id();

		wp_set_current_user( $this->admin1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );
		$_POST['reassign'] = 'leave-assigned';

		add_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99, 2 );

		try {

			$guest_author_obj->handle_delete_guest_author_action();

		} catch( Exception $e ) {

			$this->assertContains( $guest_author_obj->parent_page, $e->getMessage() );
			$this->assertContains( 'page=view-guest-authors', $e->getMessage() );
			$this->assertContains( 'message=guest-author-deleted', $e->getMessage() );
		}

		remove_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99 );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action when reassign is reassign-another.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_reassign_is_reassign_another() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		// Back up current user.
		$current_user = get_current_user_id();

		$expected = __( 'Co-author does not exists. Try again?', 'co-authors-plus' );

		wp_set_current_user( $this->admin1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );
		$_POST['reassign'] = 'reassign-another';

		// When coauthor does not exist.
		$_POST['leave-assigned-to'] = 'test';

		try {
			$guest_author_obj->handle_delete_guest_author_action();
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertContains( esc_html( $expected ), $exception->getMessage() );

		// When coauthor exists.
		$_POST['leave-assigned-to'] = $this->author1->user_nicename;

		add_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99, 2 );

		try {

			$guest_author_obj->handle_delete_guest_author_action();

		} catch ( Exception $e ) {

			//$this->assertContains( $guest_author_obj->parent_page, $e->getMessage() );
			$this->assertContains( 'page=view-guest-authors', $e->getMessage() );
			$this->assertContains( 'message=guest-author-deleted', $e->getMessage() );
		}

		remove_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99 );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * Checks delete guest author action when reassign is remove-byline.
	 *
	 * @covers CoAuthors_Guest_Authors::handle_delete_guest_author_action()
	 */
	public function test_handle_delete_guest_author_action_with_reassign_is_remove_byline() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Back up $_POST.
		$_post_backup = $_POST;

		// Back up current user.
		$current_user = get_current_user_id();

		wp_set_current_user( $this->admin1->ID );

		$_POST['action']   = 'delete-guest-author';
		$_POST['_wpnonce'] = wp_create_nonce( 'delete-guest-author' );
		$_POST['id']       = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );
		$_POST['reassign'] = 'remove-byline';

		add_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99, 2 );

		try {

			$guest_author_obj->handle_delete_guest_author_action();

		} catch ( Exception $e ) {

			$this->assertContains( $guest_author_obj->parent_page, $e->getMessage() );
			$this->assertContains( 'page=view-guest-authors', $e->getMessage() );
			$this->assertContains( 'message=guest-author-deleted', $e->getMessage() );
		}

		remove_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99 );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore $_POST from back up.
		$_POST = $_post_backup;
	}

	/**
	 * To catch any redirection and throw location and status in Exception.
	 *
	 * Note : Destination location can be get from Exception Message and
	 * status can be get from Exception code.
	 *
	 * @param string $location Redirected location.
	 * @param int    $status   Status.
	 *
	 * @throws \Exception Redirection data.
	 *
	 * @return void
	 **/
	public function catch_redirect_destination( $location, $status ) {

		throw new Exception( $location, $status );
	}

	/**
	 * Checks delete guest author when he/she does not exist.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_when_guest_author_not_exist() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$response = $guest_author_obj->delete( $this->admin1->ID );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'guest-author-missing', $response->get_error_code() );
	}

	/**
	 * Checks delete guest author without reassign author.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_without_reassign() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$author2           = $this->factory->user->create_and_get();
		$guest_author_id   = $guest_author_obj->create_guest_author_from_user_id( $author2->ID );
		$guest_author      = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );
		$guest_author_term = $coauthors_plus->get_author_term( $guest_author );

		$response = $guest_author_obj->delete( $guest_author_id );

		$this->assertTrue( $response );
		$this->assertFalse( get_term_by( 'id', $guest_author_term->term_id, $coauthors_plus->coauthor_taxonomy ) );
		$this->assertNull( get_post( $guest_author_id ) );
	}

	/**
	 * Checks delete guest author with reassign author but he/she does not exist.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_with_reassign_author_not_exist() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		// Checks when reassign author is not exist.
		$author2         = $this->factory->user->create_and_get();
		$guest_author_id = $guest_author_obj->create_guest_author_from_user_id( $author2->ID );

		$response = $guest_author_obj->delete( $guest_author_id, 'test' );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'reassign-to-missing', $response->get_error_code() );
	}

	/**
	 * Checks delete guest author with reassign author when linked account and author are same user.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_with_reassign_when_linked_account_and_author_are_same_user() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$author2            = $this->factory->user->create_and_get();
		$guest_author2_id   = $guest_author_obj->create_guest_author_from_user_id( $author2->ID );
		$guest_author2      = $guest_author_obj->get_guest_author_by( 'ID', $guest_author2_id );
		$guest_author2_term = $coauthors_plus->get_author_term( $guest_author2 );

		$response = $guest_author_obj->delete( $guest_author2_id, $guest_author2->linked_account );

		$this->assertTrue( $response );
		$this->assertNotEmpty( get_term_by( 'id', $guest_author2_term->term_id, $coauthors_plus->coauthor_taxonomy ) );
		$this->assertNull( get_post( $guest_author2_id ) );
	}

	/**
	 * Checks delete guest author with reassign author when linked account and author are different user.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_with_reassign_when_linked_account_and_author_are_different_user() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$guest_admin_id = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );
		$guest_admin    = $guest_author_obj->get_guest_author_by( 'ID', $guest_admin_id );

		$author2            = $this->factory->user->create_and_get();
		$guest_author_id2   = $guest_author_obj->create_guest_author_from_user_id( $author2->ID );
		$guest_author2      = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id2 );
		$guest_author_term2 = $coauthors_plus->get_author_term( $guest_author2 );

		$post = $this->factory->post->create_and_get( array(
			'post_author' => $author2->ID,
		) );

		$response = $guest_author_obj->delete( $guest_author_id2, $guest_admin->linked_account );

		// Checks post author, it should be reassigned to new author.
		$this->assertEquals( array( $guest_admin->linked_account ), wp_list_pluck( get_coauthors( $post->ID ), 'linked_account' ) );
		$this->assertTrue( $response );
		$this->assertFalse( get_term_by( 'id', $guest_author_term2->term_id, $coauthors_plus->coauthor_taxonomy ) );
		$this->assertNull( get_post( $guest_author_id2 ) );
	}

	/**
	 * Checks delete guest author with reassign author and without linked account and author is the same user.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_with_reassign_without_linked_account_and_author_is_same_user() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$guest_author_id   = $guest_author_obj->create( array(
			'user_login'   => 'guest_author',
			'display_name' => 'guest_author',
		) );
		$guest_author      = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );
		$guest_author_term = $coauthors_plus->get_author_term( $guest_author );

		$response = $guest_author_obj->delete( $guest_author_id, $guest_author->user_login );

		$this->assertTrue( $response );
		$this->assertNotEmpty( get_term_by( 'id', $guest_author_term->term_id, $coauthors_plus->coauthor_taxonomy ) );
		$this->assertNull( get_post( $guest_author_id ) );
	}

	/**
	 * Checks delete guest author with reassign author and without linked account and author is other user.
	 *
	 * @covers CoAuthors_Guest_Authors::delete()
	 */
	public function test_delete_with_reassign_without_linked_account_and_author_is_other_user() {

		global $coauthors_plus;

		$guest_author_obj = $coauthors_plus->guest_authors;

		$guest_admin_id = $guest_author_obj->create_guest_author_from_user_id( $this->admin1->ID );
		$guest_admin    = $guest_author_obj->get_guest_author_by( 'ID', $guest_admin_id );

		$guest_author_id   = $guest_author_obj->create( array(
			'user_login'   => 'guest_author',
			'display_name' => 'guest_author',
		) );
		$guest_author      = $guest_author_obj->get_guest_author_by( 'ID', $guest_author_id );
		$guest_author_term = $coauthors_plus->get_author_term( $guest_author );

		$response = $guest_author_obj->delete( $guest_author_id, $guest_admin->user_login );

		$this->assertTrue( $response );
		$this->assertFalse( get_term_by( 'id', $guest_author_term->term_id, $coauthors_plus->coauthor_taxonomy ) );
		$this->assertNull( get_post( $guest_author_id ) );
	}
}
