<?php

class CoAuthors_API_Controller {

    /**
     * @var string
     */
    protected $route = null;

    public function create_routes() {
        throw new Exception( 'Child class needs to implement this method.' );
    }

    /**
     * Handles route authorization if requested.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function authorization( WP_REST_Request $request ) {
        return true;
    }

    /**
     * DELETE HTTP method
     *
     * @param WP_REST_Request $request
     * @throws Exception
     * @return WP_REST_Response
     */
    public function get( WP_REST_Request $request ) {
        throw new Exception( 'Not implemented.' );
    }

    /**
     * DELETE HTTP method
     *
     * @param WP_REST_Request $request
     * @throws Exception
     * @return WP_REST_Response
     */
    public function post( WP_REST_Request $request ) {
        throw new Exception( 'Not implemented.' );
    }

    /**
     * DELETE HTTP method
     *
     * @param WP_REST_Request $request
     * @throws Exception
     * @return WP_REST_Response
     */
    public function put( WP_REST_Request $request ) {
        throw new Exception( 'Not implemented.' );
    }

    /**
     * DELETE HTTP method
     *
     * @param WP_REST_Request $request
     * @throws Exception
     * @return WP_REST_Response
     */
    public function delete( WP_REST_Request $request ) {
        throw new Exception( 'Not implemented.' );
    }

    /**
     * Returns a clean array
     *
     * @param $param
     * @param WP_REST_Request $request
     * @param $key
     *
     * @return array
     */
    public function sanitize_array($param, WP_REST_Request $request, $key) {
        return array_map( 'esc_attr', (array) $param );
    }

    /**
     * Returns an array with the register_rest_route args definition.
     *
     * @return array
     */
    protected function get_args() {
       return array();
    }

    /**
     * Wraps an array into the WP_REST_Response.
     * Currently very limited, it should allow header and code to be setted.
     *
     * @param array $data
     * @throws Exception
     * @return WP_REST_Response
     */
    protected function send_response( array $data ) {
        $response = new WP_REST_Response( $data );

        return $response;
    }

    /**
     * @return string
     */
    protected function get_route() {
        return $this->route;
    }

    /**
     * @return string
     */
    protected function get_namespace() {
        return COAUTHORS_PLUS_API_NAMESPACE . COAUTHORS_PLUS_API_VERSION;
    }
}