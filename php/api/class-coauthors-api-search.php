<?php

class CoAuthors_API_Search extends CoAuthors_API_Controller {

    /**
     * @var string
     */
    protected $route = 'search/';

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

    public function create_routes() {
        register_rest_route( $this->get_namespace(), $this->get_route(), array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'post' ),
            'permission_callback' => array( $this, 'authorization' ),
            'args'                => $this->get_args()
        ) );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function post( WP_REST_Request $request ) {
        global $coauthors_plus;

        $query = strtolower( $request['q'] );

        $exclude = array();
        if ( isset( $request['existing_authors'] ) ) {
            $exclude = array_map( 'sanitize_text_field', $request['existing_authors'] );
        }

        $authors = $coauthors_plus->search_authors( $query, $exclude );

        $data = array( 'authors' => array() );

        foreach ( $authors as $author ) {
            $data['authors'][] = array(
                'id'            => $author->ID,
                'user_login'    => $author->user_login,
                'display_name'  => $author->display_name,
                'user_email'    => $author->user_email,
                'user_nicename' => $author->user_nicename
            );
        }
        return $this->send_response( $data );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return bool|mixed|void
     */
    public function authorization( WP_REST_Request $request ) {
        global $coauthors_plus;

        return $coauthors_plus->current_user_can_set_authors( null, true );
    }
}