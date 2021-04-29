
<?php

/*
 * @coversDefaultClass \CoAuthors\API\Endpoints
 */
class Test_Endpoints extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		global $coauthors_plus;

		$this->author1 = $this->factory->user->create_and_get(
			[
				'role'       => 'author',
				'user_login' => 'author1',
			]
		);

		$this->author2 = $this->factory->user->create_and_get(
			[
				'role'       => 'author',
				'user_login' => 'author2',
			]
		);

		$this->editor1 = $this->factory->user->create_and_get(
			[
				'role'       => 'editor',
				'user_login' => 'editor1',
			]
		);

		$this->coauthor1 = $coauthors_plus->guest_authors->create(
			[
				'user_login'   => 'coauthor1',
				'display_name' => 'coauthor1',
			]
		);

		$this->coauthor2 = $coauthors_plus->guest_authors->create(
			[
				'user_login'   => 'coauthor2',
				'display_name' => 'coauthor2',
			]
		);

		$this->post = $this->factory->post->create_and_get(
			[
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			]
		);
	}

	/**
	 * @covers ::__construct()
	 */
	public function test_construct() {

		$this->assertEquals(
			10,
			has_action(
				'rest_api_init',
				[
					$this->_api,
					'add_endpoints',
				]
			)
		);
	}

	/**
	 * @covers ::add_endpoints()
	 */
	public function test_add_endpoints(): void {

		$rest_server = rest_get_server();

		$this->assertContains(
			$this->_api::NAMESPACE,
			$rest_server->get_namespaces()
		);

		$routes = $rest_server->get_routes( $this->_api::NAMESPACE );

		$authors_route = sprintf(
			'/%1$s/%2$s%3$s',
			$this->_api::NAMESPACE,
			$this->_api::AUTHORS_ROUTE,
			'/(?P<post_id>[\d]+)'
		);

		$search_route = sprintf(
			'/%1$s/%2$s%3$s',
			$this->_api::NAMESPACE,
			$this->_api::SEARCH_ROUTE,
			'/(?P<q>[\w]+)'
		);

		$this->assertArrayHasKey(
			$authors_route,
			$routes,
			'Failed to assert that authors endpoint is registered'
		);

		$this->assertArrayHasKey(
			$search_route,
			$routes,
			'Failed to assert that search endpoint is registered'
		);

		$this->assertArrayHasKey(
			'GET',
			$routes[ $search_route ][0]['methods'],
			'Failed to assert that search endpoint has GET method.'
		);

		$this->assertArrayHasKey(
			'GET',
			$routes[ $authors_route ][0]['methods'],
			'Failed to assert that authors endpoint has GET method.'
		);

		$this->assertArrayHasKey(
			'POST',
			$routes[ $authors_route ][1]['methods'],
			'Failed to assert that authors endpoint has POST method.'
		);

	}

	/**
	 * @covers ::get_coauthors_search_results()
	 */
	public function test_get_coauthors_search_results(): void {

		$get_request = new WP_REST_Request( 'GET' );
		$get_request->set_url_params(
			[
				'q'                => 'auth',
				'existing_authors' => 'author1,coauthor2'
			]
		);

		$get_response = $this->_api->get_coauthors_search_results( $get_request );

		$this->assertArraySubset(
			[
				[
					'display_name' => 'author2',
				],
				[
					'display_name' => 'coauthor1',
				],
			],
			$get_response->data,
			false,
			'Failed to assert that coauthors search returns results matching the query.'
		);

		$not_found_get_request = new WP_REST_Request( 'GET' );
		$not_found_get_request->set_url_params(
			[
				'q' => 'nonexistent',
			]
		);

		$not_found_get_response = $this->_api->get_coauthors_search_results( $not_found_get_request );

		$this->assertEmpty(
			$not_found_get_response->data,
			'Failed to assert that coauthors search returns an empty array when no coauthors match query.'
		);
	}

	/**
	 * @covers ::update_coauthors()
	 * @covers ::get_coauthors()
	 */
	public function test_authors_route_callbacks(): void {
		$test_post = $this->factory->post->create_and_get(
			[
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			]
		);

		$test_post_id = $test_post->ID;

		$get_request = new WP_REST_Request( 'GET' );
		$get_request->set_url_params(
			[
				'post_id'     => $test_post_id
			]
		);

		$get_response = $this->_api->get_coauthors( $get_request );

		$this->assertEquals( 'author1', $get_response->data[0]['user_nicename'] );

		$post_request = new WP_REST_Request( 'POST' );
		$post_request->set_url_params(
			[
				'post_id'     => $test_post_id,
				'new_authors' => 'author2,coauthor2'
			]
		);

		$this->_api->update_coauthors( $post_request );

		$after_update_get_request = new WP_REST_Request( 'GET' );
		$after_update_get_request->set_url_params(
			[
				'post_id'     => $test_post_id
			]
		);
		$after_update_get_response = $this->_api->get_coauthors( $after_update_get_request );

		$this->assertEquals( 2, count( $after_update_get_response->data ) );
	}

}
