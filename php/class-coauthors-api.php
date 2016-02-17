<?php

/**
 * Provides usage for this plugin to be used with WP REST
 */
class CoAuthors_API {

    const api_namespace = "coauthors/";
    const api_version = "v1";

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

        register_rest_route( $this->build_namespace(), '/search', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'get_search' ],
            'permission_callback' => [ $this, 'get_permission' ]
        ] );

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
        //$ignore = array_map( 'sanitize_text_field', explode( ',', $_REQUEST['existing_authors'] ) );

        $authors = $this->coauthors_plus->search_authors( $search, [ ] );

        $data = [ 'authors' => [ ] ];

        foreach ( $authors as $author ) {
            $data['authors'][] = [
                'id'            => $author->ID,
                'user_login'    => $author->user_login,
                'display_name'  => $author->display_name,
                'user_email'    => $author->user_email,
                'user_nicename' => $author->user_nicename
            ];
        }

        return $this->send_response( $data );
    }

    /**
     * Returns true or false if the user has access permission.
     *
     * @return bool
     */
    public function get_permission() {
        return current_user_can( 'edit_others_posts' );
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
        return self::api_namespace . self::api_version;
    }
}