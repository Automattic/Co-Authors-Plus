
<?php

/*
 * @coversDefaultClass \CoAuthors\API\Endpoints
 */
class Test_Endpoints extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get(
			[
				'role'       => 'author',
				'user_login' => 'author1',
			]
		);
		$this->editor1 = $this->factory->user->create_and_get(
			[
				'role'       => 'editor',
				'user_login' => 'editor1',
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
			'/%1$s/%2$s',
			$this->_api::NAMESPACE,
			$this->_api::AUTHORS_ROUTE,
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
		// assert returns coauthors when string is searched
	}

	/**
	 * @covers ::update_coauthors()
	 */
	public function test_update_coauthors(): void {
		// assert updates coauthors for a post with given IDs
	}

	/**
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors(): void {
		// assert returns first user as author, for post with no coauthors
		// assert returns multiple coauthors
	}
}
