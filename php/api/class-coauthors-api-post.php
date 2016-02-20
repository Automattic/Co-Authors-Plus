<?php

/**
 * Class CoAuthors_API_Post
 */
class CoAuthors_API_Post extends CoAuthors_API_Controller {

    /**
     * @var string
     */
    protected $route = '/post/(?P<id>\d+)';

    /**
     * @inheritdoc
     */
    protected function get_args() {
        return array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'post_validate_id' )
            ),
            'coauthors' => array(
                'required'          => true,
                'sanitize_callback' => array( $this, 'sanitize_array' ),
                'validate_callback' => function($param, $request, $key) {
                    return !empty( $param ) && is_array( $param ) && count( $param ) > 0;
                }
            ),
            'append' => array(
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function($param, $request, $key) {
                    return !empty( $param );
                }
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

        register_rest_route( $this->get_namespace(), $this->get_route(), array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete' ),
            'permission_callback' => array( $this, 'authorization' ),
            'args'                => $this->get_args()
        ) );
    }

    /**
     * @inheritdoc
     */
    public function post( WP_REST_Request $request ) {
        global $coauthors_plus;

        $post = get_post( (int) $request['id'] );

        $append = (bool) sanitize_text_field( $request['append'] );

        if ( $coauthors_plus->current_user_can_set_authors( $post, true ) ) {
            $coauthors = (array) $request['coauthors'];
            $coauthors_plus->add_coauthors( $post->ID, $coauthors, $append );
        }

        return $this->send_response( array( __( 'Post authors updated.' ) ) );
    }

    /**
     * @inheritdoc
     */
    public function delete( WP_REST_Request $request ) {
        global $coauthors_plus;

        $post = get_post( (int) $request['id'] );

        if ( $coauthors_plus->current_user_can_set_authors( $post, true ) ) {
            $coauthors_to_remove = (array) $request['coauthors'];
            $current_coauthors = wp_list_pluck( get_coauthors( $post->ID ), 'user_nicename' );
            $coauthors = array_values( array_diff( $current_coauthors, $coauthors_to_remove ) );

            if ( ! $coauthors_plus->add_coauthors( $post->ID, $coauthors ) ) {
                return new WP_Error( 'rest_unsigned_author', __( 'No WP_Users assigned to the post.' ),
                    array( 'status' => 400 ) );
            }
        }

        return $this->send_response( array( __( 'Post authors deleted.' ) ) );
    }

    /**
     * @inheritdoc
     */
    public function authorization( WP_REST_Request $request ) {
        global $coauthors_plus;

        if ( ! $this->is_post_accessible( $request['id'] ) ) {
            return new WP_Error( 'rest_post_not_accessible', __( 'Post does not exist or not accessible.' ),
                array( 'status' => 404 ) );
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
}