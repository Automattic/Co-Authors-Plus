<?php

/**
 * Class CoAuthors_API_Guest
 */
class CoAuthors_API_Guest extends CoAuthors_API_Controller {

    /**
     * @var string
     */
    protected $route = 'guest/';

    /**
     * @inheritdoc
     */
    protected function get_args( $method = null ) {
        $args = array(
            'display_name'   => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'user_login'   => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_user',
            ),
            'user_email'     => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_email'
            ),
            'first_name'     => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name'      => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'linked_account' => array(
                'sanitize_callback' => 'sanitize_key'
            ),
            'website'        => array(
                'sanitize_callback' => 'esc_url_raw'
            ),
            'aim'            => array(
                'sanitize_callback' => 'sanitize_key'
            ),
            'yahooim'        => array(
                'sanitize_callback' => 'sanitize_key'
            ),
            'jabber'         => array(
                'sanitize_callback' => 'sanitize_key'
            ),
            'description'    => array(
                'sanitize_callback' => 'wp_filter_post_kses'
            ),
        );

        // We don't need to make these required on PUT, since
        // we already have the ID.
        if ( 'put' === $method  ) {
            // we don't to update user_login
            unset($args['user_login']['required']);
            $args['display_name']['required'] = false;
            $args['user_email']['required'] = false;
        }

        return $args;
    }

    /**
     * @inheritdoc
     */
    public function create_routes() {
        register_rest_route( $this->get_namespace(), $this->get_route() . '(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get' ),
            'permission_callback' => array( $this, 'authorization' )
        ) );

        register_rest_route( $this->get_namespace(), $this->get_route(), array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'post' ),
            'permission_callback' => array( $this, 'authorization' ),
            'args'                => $this->get_args( 'post' )
        ) );

        register_rest_route( $this->get_namespace(), $this->get_route() . '(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'put' ),
            'permission_callback' => array( $this, 'authorization' ),
            'args'                => $this->get_args( 'put' )
        ) );
    }

    /**
     * @inheritdoc
     */
    public function get( WP_REST_Request $request ) {
        global $coauthors_plus;

        $coauthor_id = (int) sanitize_text_field( $request['id'] );

        $guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'ID', $coauthor_id );

        if ( !$guest_author ) {
            return new WP_Error( 'rest_guest_not_found', __( 'Guest not found.', 'co-authors-plus' ),
                array( 'status' => 404 ) );
        }

        return $this->send_response( array( $guest_author ) );
    }

    /**
     * @inheritdoc
     */
    public function post( WP_REST_Request $request ) {
        global $coauthors_plus;

        if ( $this->does_coauthor_exists( $request['user_email'], $request['user_login'] ) ) {
            return new WP_Error( 'rest_guest_invalid_username', __( 'Invalid username or already exists.', 'co-authors-plus' ),
                array( 'status' => 400 ) );
        }

        $params = $this->prepare_params_for_database( $request->get_params(), false );

        $guest_author_id = $coauthors_plus->guest_authors->create( $params );

        if ( is_wp_error( $guest_author_id ) ) {
            return $guest_author_id;
        }

        if ( isset( $request['author_id'] ) ) {
            update_post_meta( $guest_author_id, '_original_author_id', $request['ID'] );
        }
        update_post_meta( $guest_author_id, '_original_author_login', $request['user_login'] );

        return $this->send_response( array( $coauthors_plus->get_coauthor_by( 'ID', $guest_author_id ) ) );
    }

    /**
     * @inheritdoc
     */
    public function put( WP_REST_Request $request ) {
        global $coauthors_plus;

        $coauthor_id = (int) sanitize_text_field( $request['id'] );

        $coauthor = $coauthors_plus->get_coauthor_by( 'ID', $coauthor_id );
        if ( !$coauthor ) {
            return new WP_Error( 'rest_guest_not_found', __( 'Guest not found.', 'co-authors-plus' ),
                array( 'status' => 400 ) );
        }

        if ( $this->does_coauthor_exists( $request['user_email'], $request['user_login'] ) ) {
            return new WP_Error( 'rest_guest_invalid_username', __( 'Invalid username or already exists.', 'co-authors-plus' ),
                array( 'status' => 400 ) );
        }

        if ( $coauthors_plus->guest_authors->post_type === $coauthor->type ) {
            clean_post_cache( $coauthor->ID );

            $params = $this->prepare_params_for_database( $request->get_params() );
            foreach ( $params as $param => $value ) {
                update_post_meta( $coauthor->ID, 'cap-' . $param, $value );
            }

            $coauthors_plus->guest_authors->delete_guest_author_cache( $coauthor->ID );

            return $this->send_response( array( $coauthors_plus->get_coauthor_by( 'ID', $coauthor_id ) ) );
        }

        return new WP_Error( 'rest_guest_not_valid', __( 'You are trying to updante an non-valid guest.', 'co-authors-plus' ),
            array( 'status' => 400 ) );
    }

    /**
     * Checks if a coauthor was already added with the same user_email or login.
     *
     * @param $email
     * @param $user_login
     *
     * @return bool
     */
    private function does_coauthor_exists( $email, $user_login ) {
        global $coauthors_plus;

        // Don't allow empty usernames
        if ( ! $user_login ) {
            return false;
        }

        $guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'user_email',
            sanitize_email( $email) , true );

        if ( true == $guest_author  ) {
            return true;
        }

        // Guest authors can't be created with the same user_login as a user
        $user = get_user_by( 'slug', $user_login );
        if ( $user && is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
            return true;
        }

        if ( $coauthors_plus->guest_authors->get_guest_author_by( 'user_login', sanitize_text_field( $user_login ), true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array only with the supported fields from the class args are added to the
     * create or update data.
     *
     * @param $params
     * @param $ignore_user_login
     * @return array
     */
    private function prepare_params_for_database( $params, $ignore_user_login = true) {

        $args = $this->get_args();
        $data = array();

        foreach ( $params as $param => $value ) {
            if ( isset( $args[$param] ) ) {
                if ( ! $ignore_user_login && 'user_login' === $args[$param] ) {
                    continue;
                }
                $data[ $param ] = $value;
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function authorization( WP_REST_Request $request ) {
        global $coauthors_plus;
        return current_user_can( $coauthors_plus->guest_authors->list_guest_authors_cap );
    }
}