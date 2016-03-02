<?php

/**
 * Class CoAuthors_API_Posts
 */
class CoAuthors_API_Posts extends CoAuthors_API_Controller {

	/**
	 * @var string
	 */
	protected $route = '/posts/(?P<id>\d+)';

	/**
	 * @inheritdoc
	 */
	protected function get_args( $method = null ) {
		return array(
			'id'        => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'post_validate_id' )
			),
			'coauthors' => array(
				'required'          => true,
				'sanitize_callback' => array( $this, 'sanitize_array' ),
				'validate_callback' => function ( $param, $request, $key ) {
					return ! empty( $param ) && is_array( $param ) && count( $param ) > 0;
				}
			),
			'append'    => array(
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $param, $request, $key ) {
					return ! empty( $param );
				}
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function create_routes() {

		$args = $this->get_args();

		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_item' ),
			'permission_callback' => array( $this, 'post_authorization'),
			'args'                => array( 'id' => $args['id'] )
		) );

		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'put_item' ),
			'permission_callback' => array( $this, 'authorization' ),
			'args'                => $args
		) );

		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'delete_item' ),
			'permission_callback' => array( $this, 'authorization' ),
			'args'                => $args
		) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_item( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		$coauthors = $this->filter_authors_array( get_coauthors( $post_id ) );

		$data = $this->prepare_data( $coauthors );

		return $this->send_response( array( 'coauthors' => $data ) );
	}

	/**
	 * @inheritdoc
	 */
	public function put_item( WP_REST_Request $request ) {
		global $coauthors_plus;

		$post = get_post( (int) $request['id'] );

		$append = (bool) sanitize_text_field( $request['append'] );

		if ( $coauthors_plus->current_user_can_set_authors( $post, true ) ) {
			$coauthors = (array) $request['coauthors'];

			$result = $this->add_coauthors( $post->ID, $coauthors, $append );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $this->send_response( array( __( 'Post authors updated.', 'co-authors-plus' ) ) );
	}

	/**
	 * @inheritdoc
	 */
	public function delete_item( WP_REST_Request $request ) {
		global $coauthors_plus;

		$post = get_post( (int) $request['id'] );

		if ( $coauthors_plus->current_user_can_set_authors( $post, true ) ) {
			$coauthors_to_remove = (array) $request['coauthors'];
			$current_coauthors   = wp_list_pluck( get_coauthors( $post->ID ), 'user_nicename' );
			$coauthors           = array_values( array_diff( $current_coauthors, $coauthors_to_remove ) );

			$result = $this->add_coauthors( $post->ID, $coauthors );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $this->send_response( array( __( 'Post authors deleted.', 'co-authors-plus' ) ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function post_authorization(  WP_REST_Request $request )
	{
		if ( ! $this->is_post_accessible( (int) $request['id']) ) {
			return new WP_Error( 'rest_post_not_accessible', __( 'Post does not exist or not accessible.',
				'co-authors-plus' ),
				array( 'status' => self::NOT_FOUND ) );
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function authorization( WP_REST_Request $request ) {
		global $coauthors_plus;

		$post_authorization = $this->post_authorization( $request );
		if ( ! $post_authorization ) {
			return $this->post_authorization;
		}

		return $coauthors_plus->current_user_can_set_authors( null, true );
	}

	/**
	 * @param array $param
	 * @param WP_REST_Request $request
	 * @param string $key
	 *
	 * @return bool
	 */
	public function post_validate_id( $param, WP_REST_Request $request, $key ) {
		return is_numeric( sanitize_text_field( $request['id'] ) );
	}

	/**
	 * Adds coauthors directly to the $coauthors_plus instance.
	 * Returnes true if successfully updated, throws exception otherwise.
	 *
	 * @param int $post_id
	 * @param array $coauthors
	 * @param bool $append
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function add_coauthors( $post_id, $coauthors, $append = false ) {
		global $coauthors_plus;

		if ( ! $coauthors_plus->add_coauthors( $post_id, $coauthors, $append ) ) {
			return new WP_Error( __( 'No WP_Users assigned to the post', 'co-authors-plus' ),
				array( 'status' => self::BAD_REQUEST ) );
		}

		return true;
	}

	/**
	 * Checks if a Post exist and it's enabled.
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	private function is_post_accessible( $post_id ) {
		global $coauthors_plus;

		$post = get_post( $post_id );

		return ( ! $post || ! $coauthors_plus->is_post_type_enabled( $post->post_type ) ) ? false : true;
	}

	/**
	 * @param array $coauthors
	 *
	 * @return array
	 */
	protected function prepare_data( array $coauthors ) {

		$data = array();

		foreach  ($coauthors as $coauthor ) {
			$data[] = array(
				'id' => (int) $coauthor['id'],
				'display_name' => $coauthor['display_name'],
				'user_email' => $coauthor['user_email'],
				'user_nicename' => $coauthor['user_nicename']
			);
		}

		return $data;
	}
}