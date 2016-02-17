<?php

/**
 * Test Co-Authors Plus' REST API
 */
global $wp_version;
if (version_compare($wp_version, '4.4', '>=')) {

    class Test_API extends CoAuthorsPlus_TestCase {

        protected $server;

        public function setUp() {
            parent::setUp();

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

        public function testSearchWithoutAuthentication() {
            $request  = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 403, $response->get_status() );
            $this->assertErrorResponse( 'rest_forbidden', $response );
        }

        public function testSearchAuthenticatedWithoutPermission() {
            wp_set_current_user( $this->subscriber );
            $request  = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 403, $response->get_status() );
            $this->assertErrorResponse( 'rest_forbidden', $response );
        }

        public function testSearchAuthenticatedWithPermission() {
            wp_set_current_user( 1 );
            $request = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $request->set_body_params( [ 'q' => 'foo' ] );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );
        }

        public function testSearchResults() {
            wp_set_current_user( 1 );
            $request = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $request->set_body_params( [ 'q' => 'tor' ] );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );
            $this->assertEquals( 2, count( $response->get_data()['authors'] ) );
        }

        public function testExistingAuthorsInvalid() {

            $request = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $request->set_body_params( [ 'q' => 'tor', 'existing_authors' => "foo" ] );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 400, $response->get_status() );
            $this->assertErrorResponse( 'rest_invalid_field_type', $response );

        }

        public function testExistingAuthorsValid() {
            $request = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $request->set_body_params( [ 'q' => 'tor', 'existing_authors' => [ 'contributor1' ] ] );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );
            $this->assertEquals( 1, count( $response->get_data()['authors'] ) );

            $request = new WP_REST_Request( 'POST', '/coauthors/v1/search' );
            $request->set_body_params( [ 'q' => 'tor', 'existing_authors' => [ 'contributor1', 'editor2' ] ] );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );
            $this->assertEquals( 0, count( $response->get_data()['authors'] ) );
        }

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

}