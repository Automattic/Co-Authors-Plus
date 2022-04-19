<?php

use CoAuthors\API\Endpoints;

/*
 * @coversDefaultClass \CoAuthors\API\Endpoints
 */
class Test_Endpoints extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		global $coauthors_plus;

		$this->author1 = $this->factory->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author1',
			)
		);

		$this->author2 = $this->factory->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author2',
			)
		);

		$this->editor1 = $this->factory->user->create_and_get(
			array(
				'role'       => 'editor',
				'user_login' => 'editor1',
			)
		);

		$this->coauthor1 = $coauthors_plus->guest_authors->create(
			array(
				'user_login'   => 'coauthor1',
				'display_name' => 'coauthor1',
			)
		);

		$this->coauthor2 = $coauthors_plus->guest_authors->create(
			array(
				'user_login'   => 'coauthor2',
				'display_name' => 'coauthor2',
			)
		);

		$this->post = $this->factory->post->create_and_get(
			array(
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			)
		);
	}

	/**
	 * @covers ::__construct()
	 * @covers ::modify_endpoints()
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

		$this->assertEquals(
			10,
			has_action(
				'wp_loaded',
				array(
					$this->_api,
					'modify_responses',
				)
			)
		);

	}

	/**
	 * @covers ::add_endpoints()
	 */
	public function test_add_endpoints() {

		$rest_server = rest_get_server();

		$this->assertContains(
			Endpoints::NS,
			$rest_server->get_namespaces()
		);

		$routes = $rest_server->get_routes( Endpoints::NS );

		$authors_route = sprintf(
			'/%1$s/%2$s%3$s',
			Endpoints::NS,
			Endpoints::AUTHORS_ROUTE,
			'/(?P<post_id>[\d]+)'
		);

		$search_route = sprintf(
			'/%1$s/%2$s',
			Endpoints::NS,
			Endpoints::SEARCH_ROUTE
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
	public function test_get_coauthors_search_results() {

		$get_request = new WP_REST_Request( 'GET' );
		$get_request->set_url_params(
			array(
				'q'                => 'auth',
				'existing_authors' => 'author1,coauthor2',
			)
		);

		$get_response = $this->_api->get_coauthors_search_results( $get_request );

		$this->assertArraySubset(
			array(
				array(
					'displayName' => 'author2',
				),
				array(
					'displayName' => 'coauthor1',
				),
			),
			$get_response->data,
			false,
			'Failed to assert that coauthors search returns results matching the query.'
		);

		$not_found_get_request = new WP_REST_Request( 'GET' );
		$not_found_get_request->set_url_params(
			array(
				'q' => 'nonexistent',
			)
		);

		$not_found_get_response = $this->_api->get_coauthors_search_results( $not_found_get_request );

		$this->assertEmpty(
			$not_found_get_response->data,
			'Failed to assert that coauthors search returns an empty array when no coauthors match query.'
		);
	}

	/**
	 * @covers ::get_coauthors()
	 */
	public function test_authors_get_coauthors() {
		$test_post = $this->factory->post->create_and_get(
			array(
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			)
		);

		$test_post_id = $test_post->ID;

		$get_request = new WP_REST_Request( 'GET' );
		$get_request->set_url_params(
			array(
				'post_id' => $test_post_id,
			)
		);

		$get_response = $this->_api->get_coauthors( $get_request );
		$this->assertEquals( 'author1', $get_response->data[0]['userNicename'] );

	}

	/**
	 * @covers ::update_coauthors()
	 */
	public function test_update_coauthors() {

		wp_set_current_user( $this->editor1->ID );

		$test_post = $this->factory->post->create_and_get(
			array(
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			)
		);

		$test_post_id = $test_post->ID;

		$post_request = new WP_REST_Request( 'POST' );
		$post_request->set_url_params(
			array(
				'post_id'     => $test_post_id,
				'new_authors' => $this->author1->user_nicename . ',coauthor2',
			)
		);

		$update_response = $this->_api->update_coauthors( $post_request );

		$this->assertEquals( 2, count( $update_response->data ) );
	}

	public function test_can_edit_coauthors() {
		$post_id = $this->factory->post->create(
			array(
				'post_author' => $this->editor1->ID,
			)
		);

		$request = new WP_REST_Request(
			'GET',
			''
		);
		$request->set_default_params(
			array(
				'post_id' => $post_id,
			)
		);

		wp_set_current_user( $this->editor1->ID );

		$this->assertTrue( $this->_api->can_edit_coauthors( $request ) );

		wp_set_current_user( $this->author1->ID );

		$this->assertFalse( $this->_api->can_edit_coauthors( $request ) );
	}

	/**
	 * @covers ::remove_author_link()
	 */
	public function test_remove_author_link() {

		$test_post = $this->factory->post->create_and_get(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $test_post->ID );

		wp_set_current_user( $this->editor1->ID );

		$request->set_param( 'context', 'edit' );

		$response = rest_do_request( $request );

		$this->_api->remove_author_link( $response, $test_post, $request );

		$this->assertArrayNotHasKey(
			Endpoints::SUPPORT_LINK,
			$response->get_links(),
			'Failed to assert that link is removed when the block editor is loaded.'
		);

		add_filter( 'use_block_editor_for_post', '__return_false' );

		$response = rest_do_request( $request );

		$this->_api->remove_author_link( $response, $test_post, $request );

		$this->assertArrayHasKey(
			Endpoints::SUPPORT_LINK,
			$response->get_links(),
			'Failed to assert that links are unchanged when block editor is disabled.'
		);

	}

	/**
	 * @covers ::modify_response()
	 */
	public function test_modify_response() {
		$this->_api->modify_responses();

		foreach ( $this->_cap->supported_post_types as $post_type ) {
			$this->assertEquals(
				10,
				has_filter(
					'rest_prepare_' . $post_type,
					array(
						$this->_api,
						'remove_author_link',
					)
				)
			);
		}

	}
}
