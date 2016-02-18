<?php

/**
 * Provides usage for this plugin to be used with WP REST
 */
class CoAuthors_API {

    const API_NAMESPACE = "coauthors/";
    const API_VERSION = "v1";

    const ROUTE_SEARCH  = '/search';
    const ROUTE_POST = '/post/(?P<id>\d+)';

    /**
     * @var coauthors_plus
     */
    private $coauthors_plus;

    public function __construct( coauthors_plus $coauthors_plus ) {
        // Instead of relying of global variables, we inject the parent class here
        $this->coauthors_plus = $coauthors_plus;

        // Action to loads the API class
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

    }

    /**
     * Method to register rest routes
     */
    public function register_routes() {

        // Search
        register_rest_route( $this->build_namespace(), self::ROUTE_SEARCH, array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'get_search' ),
            'permission_callback' => array( $this, 'has_permission' )
        ) );

        // Post
        register_rest_route( $this->build_namespace(), self::ROUTE_POST, array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'post_create' ),
            'permission_callback' => array( $this, 'has_permission' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => array( $this, 'post_validate_callback' )
                )
            )

        ) );
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function get_search( WP_REST_Request $request ) {

        $search = sanitize_text_field( strtolower( $request['q'] ) );

        if ( empty( $search ) ) {
            return new WP_Error( 'rest_invalid_field', __( 'The query parameter cannot be empty.' ), array( 'status' => 400 ) );
        }

        // @todo allow existing_authors to be removed from the search results
        $ignore = array();
        if ( isset( $request['existing_authors'] ) && is_array( $request['existing_authors'] ) ) {
            $ignore = array_map( 'sanitize_text_field', $request['existing_authors'] );
        } elseif ( isset( $request['existing_authors'] ) && ! is_array( $request['existing_authors'] ) ) {
            return new WP_Error( 'rest_invalid_field_type', __( 'The existing_authors parameter must be an array.' ), array( 'status' => 400 ) );
        }

        $authors = $this->coauthors_plus->search_authors( $search, $ignore );

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

    public function post_create( WP_REST_Request $request ) {

        // @todo add this to validate_callback in the register_routes()
        $post_id = (int) sanitize_text_field($request['id']);

        // @todo refactor these next 10 lines into an private method
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'rest_post_not_found', __( 'Post was not found.' ), array( 'status' => 404 ) );
        }

        if ( ! $this->coauthors_plus->is_post_type_enabled( $post->post_type ) ) {
            return new WP_Error( 'rest_post_disabled', __( 'You do not have permissions to access this post.' ),
                array( 'status' => 400 ) );
        }

        $append = (bool) sanitize_text_field($request['append']);

        if ( $this->coauthors_plus->current_user_can_set_authors( $post, true ) ) {
            $coauthors = (array) $request['coauthors'];
            $coauthors = array_map( 'sanitize_text_field', $coauthors );
            $this->coauthors_plus->add_coauthors( $post_id, $coauthors, $append );
        }

        if ( isset( $coauthors ) ) {
            return $this->send_response( array( __( 'Post authors updated.' ) ) );
        }
    }

    /**
     * @param array $param
     * @param WP_REST_Request $request
     * @param string $key
     *
     * @return bool
     */
    public function post_validate_callback($param, WP_REST_Request $request, $key) {
        return is_numeric( sanitize_text_field ($request['id'] ) );
    }

    /**
     * Returns true or false if the user has access permission.
     *
     * @return bool
     */
    public function has_permission() {
        return $this->coauthors_plus->current_user_can_set_authors(null, true);
    }

    /**
     * Wraps an array into the WP_REST_Response.
     * Currently very limited, it should allow header and code to be setted.
     *
     * @param array $data
     *
     * @return WP_REST_Response
     */
    private function send_response( array $data ) {
        $response = new WP_REST_Response( $data );

        return $response;
    }

    /**
     * Builds a namespace string
     *
     * @return string
     */
    private function build_namespace() {
        return self::API_NAMESPACE . self::API_VERSION;
    }
}