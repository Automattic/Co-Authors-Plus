<?php

require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php';

/**
 * Class Endpoint.
 */
class CoAuthors_Endpoint {

	/**
	 * Namespace for our endpoints.
	 */
	public const NAMESPACE = 'coauthors/v1';

	/**
	 * Route for authors search endpoint.
	 */
	public const ROUTE = 'authors';

	/**
	 * WP_REST_API constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );
	}

	/**
	 * Register endpoints.
	 */
	public function add_endpoints(): void {
		register_rest_route(
			static::NAMESPACE,
			static::ROUTE,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_coauthors' ],
					'permission_callback' => [ $this, 'check_endpoint_read_permissions' ],
				]
			]
		);
	}

	public function get_coauthors( WP_REST_Request $request ): WP_REST_Response {
		$response = 'coauthors response';

		return rest_ensure_response( $response );
	}

	public function check_endpoint_read_permissions(): bool {
		return true;
	}
}
