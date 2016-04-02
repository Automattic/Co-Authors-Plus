<?php

/**
 * The base class for all API Controllers.
 *
 * Class CoAuthors_API_Controller
 */
class CoAuthors_API_Controller {

	/**
	 * HTTP return codes
	 */
	const OK = 200;
	const CREATED = 201;
	const BAD_REQUEST = 400;
	const NOT_FOUND = 404;

	/**
	 * @var string
	 */
	protected $route = null;

	/**
	 * Use this method to define the routes using WP register_rest_route() function.
	 *
	 * @throws Exception
	 * @return array
	 */
	public function create_routes() {
		throw new Exception( 'Child class needs to implement this method.' );
	}

	/**
	 * Handles route authorization if requested.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function authorization( WP_REST_Request $request ) {
		return true;
	}

	/**
	 * GET HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * POST HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function post( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * PUT HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
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
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * GET item HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function get_item( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * POST item HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function post_item( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * PUT item HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function put_item( WP_REST_Request $request ) {
		throw new Exception( 'Not implemented.' );
	}

	/**
	 * DELETE item HTTP method
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	public function delete_item( WP_REST_Request $request ) {
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
	public function sanitize_array( $param, WP_REST_Request $request, $key ) {
		return array_map( 'esc_attr', (array) $param );
	}

	/**
	 * Returns an array with the register_rest_route args definition.
	 *
	 * @param string $context
	 *
	 * @return array
	 */
	protected function get_args( $context = null ) {
		return array();
	}

	/**
	 * Receives an array of field args from get_args() and
	 * return an array to be usuable with register_rest_route args definition.
	 *
	 * @param $context
	 * @param $args
	 *
	 * @return array
	 */
	protected function filter_args( $context, $args ) {

		$items = array();

		foreach( $args as $key => $value) {
			if ( in_array( $context, $value['contexts'] ) ) {
				$items[$key] = $value['common'];
				if ( isset( $value[$context] ) ) {
					$items[$key] = array_merge($items[$key], $value[$context]);
				}
			}
		}
		return $items;
	}

	/**
	 * Wraps an array into the WP_REST_Response.
	 * Currently very limited, it should allow header and code to be setted.
	 *
	 * @param array $data
	 * @param integer $status_code
	 *
	 * @throws Exception
	 * @return WP_REST_Response
	 */
	protected function send_response( array $data, $status_code = 200 ) {
		$response = new WP_REST_Response( $data );
		$response->set_status( $status_code );

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