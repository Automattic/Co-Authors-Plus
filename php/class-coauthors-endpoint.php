<?php

namespace CoAuthors\API;

// Load required classes from core.
require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php';

use WP_REST_Request;
use WP_REST_Response;

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
	 * Regexes to capture the query in a request.
	 * @see https://regex101.com/r/3HaxlL/1
	 */
	protected const ENDPOINT_QUERY_REGEX = '/(?P<q>[\w]+)';
	protected const ENDPOINT_POST_ID_REGEX = '/(?P<post_id>[\d]+)';

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
							'description' => __( 'Text to search.' ),
							'required'    => true,
							'type'        => 'string',
						],
						'existing_authors' => [
							'description' => __( 'Names of existing coauthors to exclude from search results.' ),
							'type'        => 'string',
							'required'    => false,
							// TODO: valudation of user_nicename?
						],
					],
				]
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHORS_ROUTE . static::ENDPOINT_POST_ID_REGEX,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_coauthors' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'post_id' => [
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => [ $this, 'validate_numeric' ],
						]
					],
				]
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHORS_ROUTE . static::ENDPOINT_POST_ID_REGEX,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_coauthors' ],
					// 'permission_callback' => [ $this, 'can_edit_coauthors', $post ], // TODO
					'permission_callback' => '__return_true',
					'args'                => [
						'post_id' => [
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => [ $this, 'validate_numeric' ],
						],
						'new_authors' => [
							'description' => __( 'Names of coauthors to save.' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
							],
							'required'    => false,
							// TODO: valudation of user_nicename?
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

		if ( ! empty( $authors ) ) {
			foreach ( $authors as $author ) {
				$response[] = $this->_format_author_data( $author );
			}
		}


		return rest_ensure_response( $response );
	}

	/**
	 * Return a single author.
	 */
	public function get_coauthors( WP_REST_Request $request ): WP_REST_Response {
		$response = [];

		// Calling the global function of the same name.
		$authors = get_coauthors( $request['post_id']);

		// Return message if no authors found
		if ( ! empty( $authors ) ) {
			foreach ( $authors as $author ) {
				$response[] = $this->_format_author_data( $author );
			}
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Update coauthors.
	 */
	public function update_coauthors( WP_REST_Request $request ) {

		if ( isset( $request['new_authors'] ) ) {
			$author_names = explode( ',', $request['new_authors'] );
			$coauthors    = array_map( 'sanitize_title', (array) $author_names );

			$this->coauthors->add_coauthors( $request['post_id'], $coauthors );
		}
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
			'id'            => esc_html( $author->ID ),
			'user_nicename' => esc_html( rawurldecode( $author->user_nicename ) ),
			'login'         => esc_html( $author->user_login ),
			'email'         => $author->user_email,
			'display_name'  => esc_html( str_replace( '∣', '|', $author->display_name ) ),
			'avatar'        => esc_url( get_avatar_url( $author->ID ) ),
		];
	}
}
