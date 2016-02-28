<?php

/**
 * Test Co-Authors Plus' REST API
 */

class Test_API extends CoAuthorsPlus_TestCase {

	protected $server;

	public function setUp() {
		global $wp_version;

		if ( ! version_compare( $wp_version, '4.4', '>=' ) ) {
			$this->markTestSkipped( 'Current version of WP does not support REST API classes.' );
		}

		parent::setUp();

		$this->logout();

		$this->guest1 = array(
			"display_name"   => "John Doe",
			"user_login"     => "johndoe",
			"user_email"     => "jdoe@email.com",
			"first_name"     => "John",
			"last_name"      => "Doe",
			"website"        => "http://www.site.com/",
			"aim"            => "jdaim",
			"yahooim"        => "jdyahoo",
			"jabber"         => "jdjabber",
			"description"    => "Some very simple description john doe.",
			"linked_account" => null
		);

		$this->guest2 = array(
			"display_name"   => "Foo Bar",
			"user_login"     => "foobar",
			"user_email"     => "fb@email.com",
			"first_name"     => "Foo",
			"last_name"      => "Bar",
			"website"        => "http://www.foobar.com/",
			"aim"            => "fbaim",
			"yahooim"        => "fbyahoo",
			"jabber"         => "fbjabber",
			"description"    => "Some very simple description foo bar.",
			"linked_account" => null
		);

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

	public function tearDown() {
		parent::tearDown();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Search and Authentication
	 */
	public function testSearchWithoutAuthentication() {
		$response = $this->get_request_response( 'POST', 'search', array( 'q' => 'foo' ) );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertErrorResponse( 'rest_forbidden', $response );
	}

	public function testSearchAuthenticatedWithoutPermission() {
		wp_set_current_user( $this->subscriber );
		$response = $this->get_request_response( 'POST', 'search', array( 'q' => 'foo' ) );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertErrorResponse( 'rest_forbidden', $response );
	}

	public function testSearchAuthenticatedWithPermission() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'search', array( 'q' => 'foo' ) );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function testSearchResults() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'search', array( 'q' => 'tor' ) );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data['coauthors'] ) );
	}

	public function testExistingAuthorsValid() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'search', array(
			'q'                => 'tor',
			'existing_authors' => array( 'contributor1' )
		) );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 1, count( $data['coauthors'] ) );

		$response = $this->get_request_response( 'POST', 'search', array(
			'q'                => 'tor',
			'existing_authors' => array(
				'contributor1',
				'editor2'
			)
		) );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 0, count( $data['coauthors'] ) );
	}

	/**
	 * POST route
	 */
	public function testPostAuthorsAdmin() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'author1', 'editor2' ) ) );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'Post authors updated.', $data[0] );

		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );
	}

	public function testPostAuthorsAuthor() {
		wp_set_current_user( $this->author1 );
		$response = $this->get_request_response( 'POST', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'author1', 'editor2' ) ) );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function testPostAuthorsAppend() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'author1' ) ) );
		$this->assertEquals( 200, $response->get_status() );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1 ), wp_list_pluck( $coauthors, 'ID' ) );

		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'editor2' ), 'append' => true ) );
		$this->assertEquals( 200, $response->get_status() );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );
	}

	public function testPostAuthorsUnauthorized() {
		wp_set_current_user( $this->editor1 );
		$response = $this->get_request_response( 'POST', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'author1', 'editor2' ) ) );
		$this->assertEquals( 200, $response->get_status() );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );
	}

	public function testPostAuthorsDelete() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( 'author1', 'subscriber1' ) );

		$response = $this->get_request_response( 'DELETE', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'subscriber1' ) ) );

		$this->assertEquals( 200, $response->get_status() );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1 ), wp_list_pluck( $coauthors, 'ID' ) );
	}

	public function testPostAuthorsEmptyDeleteAll() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( 'author1', 'subscriber1' ) );

		$response = $this->get_request_response( 'DELETE', 'post/' . $this->author1_post1,
			array( 'coauthors' => array( 'subscriber1', 'author1' ) ) );

		$this->assertEquals( 200, $response->get_status() );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( 1 ), wp_list_pluck( $coauthors, 'ID' ) );
	}

	public function testPostGet() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( 'author1', 'subscriber1' ) );
		$response = $this->get_request_response( 'GET', 'post/' . $this->author1_post1 );
		$data     = $response->get_data();
		$this->assertEquals( 2, count( $data['coauthors'] ) );

		$coauthors_plus->add_coauthors( $this->author1_post1, array( 'author1', 'subscriber1', 'contributor1' ) );
		$response = $this->get_request_response( 'GET', 'post/' . $this->author1_post1 );
		$data     = $response->get_data();
		$this->assertEquals( 3, count( $data['coauthors'] ) );
	}

	public function testPostGetNoPost() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'GET', 'post/' . 9999 );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Guest route
	 */

	public function testGuestAddNoSession() {
		$response = $this->get_request_response( 'POST', 'guest', $this->guest1 );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function testGuestAdd() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'guest', $this->guest1 );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data[0]->display_name, $this->guest1['display_name'] );
		$this->assertEquals( $data[0]->user_login, $this->guest1['user_login'] );
		$this->assertEquals( $data[0]->first_name, $this->guest1['first_name'] );
		$this->assertEquals( $data[0]->last_name, $this->guest1['last_name'] );
		$this->assertEquals( $data[0]->user_email, $this->guest1['user_email'] );
		$this->assertEquals( $data[0]->linked_account, $this->guest1['linked_account'] );
		$this->assertEquals( $data[0]->website, $this->guest1['website'] );
		$this->assertEquals( $data[0]->aim, $this->guest1['aim'] );
		$this->assertEquals( $data[0]->yahooim, $this->guest1['yahooim'] );
		$this->assertEquals( $data[0]->jabber, $this->guest1['jabber'] );
		$this->assertEquals( $data[0]->description, $this->guest1['description'] );
	}

	public function testGuestAddInvalid() {
		wp_set_current_user( 1 );
		$this->guest1['user_login'] = 'admin';
		$response                   = $this->get_request_response( 'POST', 'guest', $this->guest1 );
		$this->assertErrorResponse( 'rest_guest_invalid_username', $response, 400 );

		$this->guest1['user_login'] = '%?!&';
		$response                   = $this->get_request_response( 'POST', 'guest', $this->guest1 );
		$data                       = $response->get_data();
		$this->assertEquals( 'field-required', $data['code'] );
	}

	public function testGuestUpdate() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'POST', 'guest', $this->guest1 );
		$guest    = $response->get_data();

		unset( $this->guest1['user_login'] );
		$this->guest1['display_name'] = 'New display name';
		$response                     = $this->get_request_response( 'PUT', 'guest/' . $guest[0]->ID, $this->guest1 );
		$data                         = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data[0]->display_name, 'New display name' );
	}

	public function testGuestUpdateSameUserName() {
		wp_set_current_user( 1 );
		$this->get_request_response( 'POST', 'guest', $this->guest1 );
		$response = $this->get_request_response( 'POST', 'guest', $this->guest2 );
		$guest2   = $response->get_data();

		// Tests user login
		$this->guest2['user_login'] = $this->guest1['user_login'];
		$response                   = $this->get_request_response( 'PUT', 'guest/' . $guest2[0]->ID, $this->guest2 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_guest_invalid_username', $response, 400 );

		// Tests existing email
		$this->guest2['user_login'] = 'foobar';
		$this->guest2['user_email'] = $this->guest1['user_email'];
		$response                   = $this->get_request_response( 'PUT', 'guest/' . $guest2[0]->ID, $this->guest2 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_guest_invalid_username', $response, 400 );
	}

	public function testGuestGet() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'GET', 'guest/' . $coauthor );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data[0]->display_name, $this->guest1['display_name'] );
	}

	public function testGuestNotFoundGet() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'GET', 'guest/' . 1000 );
		$this->assertEquals( 404, $response->get_status() );

		$response = $this->get_request_response( 'GET', 'guest/' . 0 );
		$this->assertEquals( 404, $response->get_status() );

		$response = $this->get_request_response( 'GET', 'guest/' . $this->guest1['user_login'] );
		$this->assertEquals( 404, $response->get_status() );
	}

	public function testGuestDeleteEmptyParams() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . 9000 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_missing_callback_param', $response, 400 );
	}

	public function testGuestDeleteNonExistingUser() {
		wp_set_current_user( 1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . 9000,
			array( 'reassign' => 'leave-assigned' ) );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function testGuestDeleteExistingUser() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'leave-assigned' ) );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function testGuestDeleteExistingUserReassignAnother() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->guest_authors->create( $this->guest2 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'reassign-another', 'leave-assigned-to' => $this->guest2['user_login'] ) );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function testGuestDeleteNonExistingUserReassignAnother() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->guest_authors->create( $this->guest2 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'reassign-another', 'leave-assigned-to' => 9000 ) );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_reassigned_user_not_found', $response, 400 );
	}

	public function testGuestDeleteInvalidReassignAnotherOption() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->guest_authors->create( $this->guest2 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'reassign-another', 'Something' => 9000 ) );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function testGuestDeleteRemoveByLine() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->guest_authors->create( $this->guest2 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'remove-byline' ) );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function testGuestDeleteInvalidOption() {
		global $coauthors_plus;

		wp_set_current_user( 1 );
		$coauthors_plus->guest_authors->create( $this->guest2 );
		$coauthor = $coauthors_plus->guest_authors->create( $this->guest1 );
		$response = $this->get_request_response( 'DELETE', 'guest/' . $coauthor,
			array( 'reassign' => 'something' ) );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @param String $code
	 * @param WP_REST_Response $response
	 * @param null $status
	 */
	protected function assertErrorResponse( $code, $response, $status = null ) {
		if ( is_a( $response, 'WP_REST_Response' ) ) {
			$response = $response->as_error();
		}
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( $code, $response->get_error_code() );
		if ( null !== $status ) {
			$data = $response->get_error_data();
			$this->assertArrayHasKey( 'status', $data );
			$this->assertEquals( $status, $data['status'] );
		}
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function get_request_response( $method, $path, array $params = array() ) {
		$request = new WP_REST_Request( $method, '/coauthors/v1/' . $path );
		$request->set_header( 'Content-Type', 'application/json' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Clears any persisted authentication
	 */
	protected function logout() {
		wp_set_current_user( - 1 );
	}
}


/**
 * "Stolen" From https://github.com/WP-API/WP-API/blob/develop/tests/class-wp-test-spy-rest-server.php
 */
class WP_Test_Spy_REST_Server extends WP_REST_Server {
	/**
	 * Get the raw $endpoints data from the server
	 *
	 * @return array
	 */
	public function get_raw_endpoint_data() {
		return $this->endpoints;
	}

	/**
	 * Allow calling protected methods from tests
	 *
	 * @param string $method Method to call
	 * @param array $args Arguments to pass to the method
	 *
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		return call_user_func_array( array( $this, $method ), $args );
	}

	/**
	 * Call dispatch() with the rest_post_dispatch filter
	 */
	public function dispatch( $request ) {
		$result = parent::dispatch( $request );
		$result = rest_ensure_response( $result );
		if ( is_wp_error( $result ) ) {
			$result = $this->error_to_response( $result );
		}

		return apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $this, $request );
	}
}