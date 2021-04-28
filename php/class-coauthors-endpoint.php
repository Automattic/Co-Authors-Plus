<?php

namespace CoAuthors\API;

require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php';


/**
 * Class Endpoint.
 */
class Endpoints {

	/**
	 * Namespace for our endpoints.
	 */
	public const NAMESPACE = 'coauthors/v1';

	/**
	 * Route for authors search endpoint.
	 */
	public const SEARCH_ROUTE = 'search';
	public const AUTHORS_ROUTE = 'authors';

	/**
	 * Regex to capture the query in a request.
	 */
	// https://regex101.com/r/3HaxlL/1
	protected const ENDPOINT_QUERY_REGEX = '/(?P<q>[\w]+)';

	// Maybe will use later
	// protected const ENDPOINT_ID_REGEX = '/(?P<id>[\d]+)';

	/**
	 * An instance of the Co_Authors_Plus class.
	 */
	public $coauthors;

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
			// static::AUTHORS_ROUTE . static::ENDPOINT_ID_REGEX,
			static::AUTHORS_ROUTE,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_authors' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id' => [
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => '__return_true',
						],
						'nicenames' => [
							'description' => __( 'Limit result set to specific IDs.' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
							],
							'required'    => false,
						],
					],
				]
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHORS_ROUTE,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_coauthors' ],
					// 'permission_callback' => [ $this, 'can_edit_coauthors', $post ],
					'permission_callback' => '__return_true',
					'args'                => [
						'nicenames' => [
							'description' => __( 'Names of coauthors to save.' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
							],
							'required'    => false,
						],
					],
				]
			]
		);
	}

	/**
	 * Update coauthors.
	 */
	public function update_coauthors( WP_REST_Request $request ) {

		global $post;

		$post_id = $post->ID;

		if ( isset( $request['nicenames'] ) ) {
			$author_names = (array) $request['nicenames'];
			$coauthors    = array_map( 'sanitize_title', $author_names );

			$this->coauthors->add_coauthors( $post_id, $coauthors );
		}
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
		} else {
			foreach ( $authors as $author ) {
				$response[] = $this->_format_author_data( $author );
			}
		}


		return rest_ensure_response( $response );
	}

	/**
	 * Return a single author.
	 */
	public function get_authors( WP_REST_Request $request ): WP_REST_Response {
		$response = [];

		$author_names = $request['nicenames'];

		// Return message if no authors found
		if ( empty( $author_ids ) ) {
			$response = apply_filters( 'coauthors_no_matching_authors_message', 'Sorry, no matching authors found.' );
		} else {
			foreach ( $author_names as $name ) {
				$coauthor = $this->coauthors->get_coauthor_by( 'user_nicename', $name );

				if ( ! empty( $coauthor ) ) {
					$response[] = $this->_format_author_data( $coauthor );
				}

			}
		}

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
	 * Permissions for updating coauthors.
	 */
	public function can_edit_coauthors( WP_Post $post ): bool {
		if ( ! $this->is_post_type_enabled( $post->post_type ) ) {
			return false;
		}

		return $this->current_user_can_set_authors( $post );
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
