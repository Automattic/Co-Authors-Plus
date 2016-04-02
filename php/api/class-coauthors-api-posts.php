<?php

/**
 * Class CoAuthors_API_Posts
 */
class CoAuthors_API_Posts extends CoAuthors_API_Controller {

	/**
	 * @var string
	 */
	protected $route = '/posts/(?P<post_id>\d+)/authors';

	/**
	 * @inheritdoc
	 */
	protected function get_args( $context = null ) {

		$args = array(
			'post_id' => array(
				'contexts' => array( 'get', 'put', 'delete_item' ),
				'common' => array( 'required' => true,
				                   'sanitize_callback' => 'sanitize_key',
				                   'validate_callback' => array( $this, 'post_validate_id' )
				)
			),
			'coauthors' => array(
				'contexts' => array( 'put' ),
				'common' => array(
					'required'          => true,
					'sanitize_callback' => array( $this, 'sanitize_array' ),
					'validate_callback' => array( $this, 'validate_is_array_and_has_content' ),
				)
			),
			'coauthor_id' => array(
				'contexts' => array( 'delete_item' ),
				'common' => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' )
			)
		);

		return $this->filter_args( $context, $args );
	}

	/**
	 * @inheritdoc
	 */
	public function create_routes() {

		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get' ),
			'args'     => $this->get_args( 'get' )
		) );

		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'put' ),
			'permission_callback' => array( $this, 'authorization' ),
			'args'                => $this->get_args( 'put' )
		) );

		register_rest_route( $this->get_namespace(), $this->get_route() . '/(?P<coauthor_id>\d+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'delete_item' ),
			'permission_callback' => array( $this, 'authorization' ),
			'args'                => $this->get_args( 'delete_item' )
		) );
	}

	/**
	 * @inheritdoc
	 */
	public function get( WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];

		$data = $this->prepare_data( get_coauthors( $post_id ) );

		return $this->send_response( array( 'coauthors' => $data ) );
	}

	/**
	 * @inheritdoc
	 */
	public function put( WP_REST_Request $request ) {
		global $coauthors_plus;

		$post_id = (int) $request['post_id'];
		$post    = get_post( $post_id );

		if ( $coauthors_plus->current_user_can_set_authors( $post ) ) {
			$coauthors = (array) $request['coauthors'];

			$result = $this->add_coauthors( $post->ID, $coauthors, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$coauthors = get_coauthors( $post_id );
		$data      = $this->prepare_data( $coauthors );

		return $this->send_response( array( 'coauthors' => $data ) );
	}

	/**
	 * @inheritdoc
	 */
	public function delete_item( WP_REST_Request $request ) {
		global $coauthors_plus;

		$post     = get_post( (int) $request['post_id'] );
		$coauthor = $coauthors_plus->get_coauthor_by( 'id', (int) $request['coauthor_id'] );

		if ( ! $coauthor ) {
			return new WP_Error( 'rest_author_not_found', __( 'Author not found.',
				'co-authors-plus' ),
				array( 'status' => self::NOT_FOUND ) );
		}

		if ( $coauthors_plus->current_user_can_set_authors( $post, true ) ) {
			$current_coauthors = wp_list_pluck( get_coauthors( $post->ID ), 'user_nicename' );
			$coauthors         = array_values( array_diff( $current_coauthors, array( $coauthor->user_nicename ) ) );
			$result            = $this->add_coauthors( $post->ID, $coauthors );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$data = $this->prepare_data( array( $coauthor ) );

		return $this->send_response( $data );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function post_authorization( WP_REST_Request $request ) {
		if ( ! $this->is_post_accessible( (int) $request['post_id'] ) ) {
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
		if ( $post_authorization instanceof WP_Error ) {
			return $post_authorization;
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
		return is_numeric( sanitize_text_field( $param ) );
	}

	/**
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	public function validate_is_array_and_has_content( $param, $request, $key ) {
		return ! empty( $param ) && is_array( $param ) && count( $param ) > 0;
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
		try {
			if ( ! $coauthors_plus->add_coauthors( $post_id, $coauthors, $append ) ) {
				return new WP_Error( __( 'At least one WP user must be included.', 'co-authors-plus' ),
					array( 'status' => self::BAD_REQUEST ) );
			}
		} catch ( Exception $e ) {
			return false;
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

		foreach ( $coauthors as $coauthor ) {
			$data[] = array(
				'id'            => (int) $coauthor->ID,
				'display_name'  => $coauthor->display_name,
				'user_nicename' => $coauthor->user_nicename
			);
		}

		return $data;
	}
}