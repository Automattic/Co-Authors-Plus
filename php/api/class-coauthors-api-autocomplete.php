<?php

/**
 * Class CoAuthors_API_Autocomplete
 * 
 * Provides search results for authors and coauthors to the Autocomplete field on post.php
 */
class CoAuthors_API_Autocomplete extends CoAuthors_API_Controller {

	/**
	 * @var string
	 */
	protected $route = 'autocomplete/';

	/**
	 * @inheritdoc
	 */
	protected function get_args( $context = null ) {
		$args = array(
			'q' => array(
				'contexts' => array( 'get' ),
				'common'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ), 
			),
			'exclude' => array( 
				'contexts' => array( 'get' ), 
				'common'   => array( 'required' => false, 'sanitize_callback' => array( $this, 'sanitize_exclude_array' ) ), 
			),
		);

		return $this->filter_args( $context, $args );
	}

	/**
	 * Sanitize an array of excluded user_logins
	 *
	 * @param array $exclude Array of dirty user_logins
	 *
	 * @uses sanitize_text_field()
	 * 
	 * @return array Array of sanitized user_logins
	 */
	public function sanitize_exclude_array( $exclude ) {
		return array_map( 'sanitize_text_field', $exclude );
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
		));
	}

	/**
	 * @inheritdoc
	 */
	public function get( WP_REST_Request $request ) {
		global $coauthors_plus;

		$query = strtolower( $request['q'] );
		$exclude = $request['exclude'];

		$data = $this->prepare_data( $coauthors_plus->search_authors( $query, $exclude ) );

		return $this->send_response( array( 'coauthors' => $data ) );
	}

	/**
	 * @inheritdoc
	 */
	public function authorization( WP_REST_Request $request ) {
		global $coauthors_plus;

		return current_user_can( $coauthors_plus->guest_authors->list_guest_authors_cap );
	}

	/**
	 * @param array $coauthors
	 *
	 * @return array
	 */
	protected function prepare_data( array $coauthors ) {
		global $coauthors_plus;

		$data = array();

		foreach ( $coauthors as $coauthor ) {
			$data[] = array(
				'id'          => (int) $coauthor->ID,
				'login'       => $coauthor->user_login, 
				'displayname' => $coauthor->display_name,
				'email'       => $coauthor->user_email,
				'nicename'    => $coauthor->user_nicename, 
				'avatar'      => $coauthors_plus->get_avatar_url( $coauthor->ID, $coauthor->user_email ), 
			);
		}

		return $data;
	}
}