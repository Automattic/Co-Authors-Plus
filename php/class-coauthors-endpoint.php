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
	protected const NAMESPACE = 'coauthors/v1';

	/**
	 * Route for authors search endpoint.
	 */
	protected const SEARCH_ROUTE = 'search';
	protected const AUTHOR_ROUTE = 'author';

	/**
	 * Regex to capture the query in a request.
	 */
	// https://regex101.com/r/3HaxlL/1
	protected const ENDPOINT_QUERY_REGEX = '/(?P<q>[\w]+)';
	protected const ENDPOINT_ID_REGEX = '/(?P<id>[\d]+)';

	/**
	 * An instance of the Co_Authors_Plus class.
	 */
	private $coauthors;

	/**
	 * WP_REST_API constructor.
	 */
	public function __construct( $coauthors_instance ) {
		$this->coauthors = $coauthors_instance;

		add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );
	}

	/**
	 * Register endpoints.
	 */
	public function add_endpoints(): void {
		register_rest_route(
			static::NAMESPACE,
			static::SEARCH_ROUTE . static::ENDPOINT_QUERY_REGEX,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_coauthors_search_results' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'q' => [
							'required'          => true,
							'type'              => 'string',
						],
					],
				]
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHOR_ROUTE . static::ENDPOINT_ID_REGEX,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_author' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => [ $this, 'validate_numeric' ],
						],
					],
				]
			]
		);
	}

	/**
	 * Search and return authors.
	 */
	public function get_coauthors_search_results( WP_REST_Request $request ): WP_REST_Response {
		$response = [];

		$search  = sanitize_text_field( strtolower( $request['q'] ) );
		$ignore  = array_map( 'sanitize_text_field', explode( ',', $request['existing_authors'] ) );
		$authors = $this->coauthors->search_authors( $search, $ignore );

		// Return message if no authors found
		if ( empty( $authors ) ) {
			$response = apply_filters( 'coauthors_no_matching_authors_message', 'Sorry, no matching authors found.' );
		}

		foreach ( $authors as $author ) {
			$response[] = $this->_format_author_data( $author );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Return a single author.
	 */
	public function get_author( WP_REST_Request $request ): WP_REST_Response {
		$response = [];

		$author = $this->coauthors->get_coauthor_by( 'id', $request['id'] );

		// Return message if no authors found
		if ( empty( $author ) ) {
			$response = apply_filters( 'coauthors_no_matching_authors_message', 'Sorry, no matching authors found.' );
		}

		$response = $this->_format_author_data( $author );

		return rest_ensure_response( $response );
	}

	/**
	 * Validate input arguments.
	 *
	 * @param mixed $param Value to validate.
	 * @return bool
	 */
	public function validate_numeric( $param ): bool {
		return is_numeric( $param );
	}

	/**
	 * Helper function to consistently format the author data for
	 * the response.
	 *
	 * @param object $author The result from coauthors methods.
	 * @return array
	 */
	public function _format_author_data( object $author ): array {
		return [
			'id' => esc_html( $author->ID ),
			'nicename' => esc_html( rawurldecode( $author->user_nicename ) ),
			'login' => esc_html( $author->user_login ),
			'email' => $author->user_email,
			'display_name' => esc_html( str_replace( '∣', '|', $author->display_name ) ),
			'avatar' => esc_url( get_avatar_url( $author->ID ) ),
		];
	}
}
