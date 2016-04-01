<?php

/**
 * Class CoAuthors_API_Authors
 */
class CoAuthors_API_Authors extends CoAuthors_API_Controller {

	/**
	 * @var string
	 */
	protected $route = 'authors/';

	/**
	 * @inheritdoc
	 */
	protected function get_args( $context = null ) {

		$contexts = array(
			'get' => array(
				'q' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' )
			)
		);

		return $contexts[ $context ];
	}

	/**
	 * @inheritdoc
	 */
	public function create_routes() {
		register_rest_route( $this->get_namespace(), $this->get_route(), array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get' ),
			'permission_callback' => array( $this, 'authorization' ),
			'args'                => $this->get_args( 'get' )
		) );
	}

	/**
	 * @inheritdoc
	 */
	public function get( WP_REST_Request $request ) {
		global $coauthors_plus;

		$query = strtolower( $request['q'] );

		$data = $this->prepare_data( $coauthors_plus->search_authors( $query ) );

		return $this->send_response( array( 'coauthors' => $data ) );
	}

	/**
	 * @inheritdoc
	 */
	public function authorization( WP_REST_Request $request ) {
		global $coauthors_plus;

		return $coauthors_plus->current_user_can_set_authors( null, true );
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
				'user_email'    => $coauthor->user_email,
				'user_nicename' => $coauthor->user_nicename
			);
		}

		return $data;
	}
}