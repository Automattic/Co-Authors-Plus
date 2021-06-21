<?php

namespace CoAuthors\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Post;

/**
 * Class Endpoint.
 */
class Endpoints {

	/**
	 * Namespace for our endpoints.
	 */
	public const NAMESPACE = 'coauthors/v1';

	/**
	 * Routes for various endpoints.
	 */
	public const SEARCH_ROUTE  = 'search';
	public const AUTHORS_ROUTE = 'authors';

	/**
	 * Link to remove from REST response to manage core author visibility in
	 * admin.
	 */
	public const SUPPORT_LINK = 'https://api.w.org/action-assign-author';

	/**
	 * Regex to capture the query in a request.
	 */
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
		add_action( 'wp_loaded', [ $this, 'modify_responses' ] );
	}

	/**
	 * Register endpoints.
	 */
	public function add_endpoints(): void {
		register_rest_route(
			static::NAMESPACE,
			static::SEARCH_ROUTE,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_coauthors_search_results' ),
					'permission_callback' => array( $this, 'can_edit_posts' ),
					'args'                => array(
						'q'                => array(
							'description' => __( 'Text to search.' ),
							'required'    => false,
							'type'        => 'string',
						),
						'existing_authors' => array(
							'description' => __( 'Names of existing coauthors to exclude from search results.' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHORS_ROUTE . static::ENDPOINT_POST_ID_REGEX,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_coauthors' ],
					'permission_callback' => array( $this, 'can_edit_posts' ),
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => [ $this, 'validate_numeric' ],
						),
					),
				),
			)
		);

		register_rest_route(
			static::NAMESPACE,
			static::AUTHORS_ROUTE . static::ENDPOINT_POST_ID_REGEX,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_coauthors' ],
					'permission_callback' => [ $this, 'can_edit_coauthors' ],
					'args'                => array(
						'post_id'     => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => array( $this, 'validate_numeric' ),
						),
						'new_authors' => array(
							'description' => __( 'Names of coauthors to save.' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Search and return authors based on a text query.
	 *
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_coauthors_search_results( WP_REST_Request $request ): WP_REST_Response {
		$response = array();

		$search  = strtolower( $request->get_param( 'q' ) );
		$ignore  = explode( ',', $request->get_param( 'existing_authors' ) );
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
	 *
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_coauthors( WP_REST_Request $request ): WP_REST_Response {
		$response = array();

		$this->_build_authors_response( $response, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Update coauthors.
	 *
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_coauthors( WP_REST_Request $request ): WP_REST_Response {

		$response = array();

		if ( ! empty( $request->get_param( 'new_authors' ) ) ) {
			$coauthors = explode( ',', $request->get_param( 'new_authors' ) );

			// Replace all existing authors
			$this->coauthors->add_coauthors( $request->get_param( 'post_id' ), $coauthors );

			$this->_build_authors_response( $response, $request );
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
	 * Limit read endpoints to users that can edit posts.
	 *
	 * @return bool
	 */
	public function can_edit_posts(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permissions for updating coauthors.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function can_edit_coauthors( WP_REST_Request $request ): bool {
		$post = get_post( $request->get_param( 'post_id' ) );

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return $this->coauthors->current_user_can_set_authors( $post );
	}

	/**
	 * Helper function to consistently format the author data for
	 * the response.
	 *
	 * @param object  $author The result from coauthors methods.
	 * @return array
	 */
	public function _format_author_data( object $author ): array {

		return array(
			'id'            => esc_html( $author->ID ),
			'user_nicename' => esc_html( rawurldecode( $author->user_nicename ) ),
			'login'         => esc_html( $author->user_login ),
			'email'         => $author->user_email,
			'display_name'  => esc_html( str_replace( '∣', '|', $author->display_name ) ),
			'avatar'        => esc_url( get_avatar_url( $author->ID ) ),
		);
	}

	/**
	 * Get authors' data and add it to the response.
	 *
	 * @param array The response array.
	 * @param int   Thet post ID from the request.
	 */
	public function _build_authors_response( &$response, $request ): void {
		$authors = get_coauthors( $request->get_param( 'post_id' ) );

		if ( ! empty( $authors ) ) {
			foreach ( $authors as $author ) {
				$response[] = $this->_format_author_data( $author );
			}
		}
	}

	/**
	 * Add filters to REST endpoints for each post that
	 * supports coauthors.
	 */
	public function modify_responses(): void {

		$post_types = $this->coauthors->supported_post_types;

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			add_filter(
				'rest_prepare_' . $post_type,
				[ $this, 'remove_author_link' ],
				10,
				3
			);
		}
	}

	/**
	 * Remove the link for wp:action-assign-author to remove the author
	 * select from the document sidebar.
	 *
	 * @see https://github.com/WordPress/gutenberg/pull/6630
	 *
	 * @param WP_REST_Response  $response Response object.
	 * @param WP_Post           $post     The current post object.
	 * @param WP_REST_Request   $request  Request object.
	 * @return WP_REST_Response
	 */
	public function remove_author_link(
		WP_REST_Response $response,
		WP_Post $post,
		WP_REST_Request $request
	): WP_REST_Response {
		if (
			! isset( $request['context'] )
			|| 'edit' !== $request['context']
		) {
			return $response;
		}

		if ( ! use_block_editor_for_post( $post ) ) {
			return $response;
		}

		if ( ! $this->coauthors->using_block_editor_integration() ) {
			return $response;
		}

		$link = $response->get_links()[ static::SUPPORT_LINK ] ?? null;

		if ( ! isset( $link ) ) {
			return $response;
		}

		$response->remove_link( static::SUPPORT_LINK );

		return $response;
	}
}
