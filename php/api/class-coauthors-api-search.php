<?php

/**
 * Class CoAuthors_API_Search
 */
class CoAuthors_API_Search extends CoAuthors_API_Controller {

    /**
     * @var string
     */
    protected $route = 'search/';

    /**
     * @inheritdoc
     */
    protected function get_args() {
        return array(
            'q' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return !empty( $param );
                },
                'sanitize_callback' => 'sanitize_key'
            ),
            'existing_authors' => array(
                'validate_callback' => function($param, $request, $key) {
                    return !empty( $param ) && is_array( $param ) && count( $param ) > 0;
                },
                'sanitize_callback' => array( $this, 'sanitize_array' )
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function create_routes() {
        register_rest_route( $this->get_namespace(), $this->get_route(), array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'post' ),
            'permission_callback' => array( $this, 'authorization' ),
            'args'                => $this->get_args()
        ) );
    }

    /**
     * @inheritdoc
     */
    public function post( WP_REST_Request $request ) {
        global $coauthors_plus;

        $query = strtolower( $request['q'] );

        $exclude = array();
        if ( isset( $request['existing_authors'] ) ) {
            $exclude = array_map( 'sanitize_text_field', $request['existing_authors'] );
        }

        $coauthors = $this->filter_authors_array( $coauthors_plus->search_authors( $query, $exclude ) );
        return $this->send_response( array( 'coauthors' => $coauthors ) );
    }

    /**
     * @inheritdoc
     */
    public function authorization( WP_REST_Request $request ) {
        global $coauthors_plus;

        return $coauthors_plus->current_user_can_set_authors( null, true );
    }
}