
<?php

/*
 * @coversDefaultClass \CoAuthors\API\Endpoints
 */
class Test_Endpoints extends CoAuthorsPlus_TestCase {

	/**
	 * @covers ::__construct()
	 */
	public function test_construct() {

		$this->assertEquals(
			10,
			has_action(
				'rest_api_init',
				array(
					$this->_api,
					'add_endpoints',
				)
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

}
