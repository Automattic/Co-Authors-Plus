<?php
/**
 * CoAuthor Blocks
 * 
 * @package CoAutors_Plus\API
 */

namespace CoAuthors\API\Endpoints;

use CoAuthors_Plus;
use stdClass;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

/**
 * CoAuthor Blocks
 */
class CoAuthor_Blocks_Controller extends WP_REST_Controller {

	/**
	 * Instance of CoAuthors_Plus class
	 *
	 * @var CoAuthors_Plus $coauthors_plus
	 */
	public CoAuthors_Plus $coauthors_plus;

	/**
	 * Construct
	 * 
	 * @param CoAuthors_Plus $coauthors_plus
	 */
	public function __construct( CoAuthors_Plus $coauthors_plus ) {
		$this->coauthors_plus = $coauthors_plus;

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register Rest Route
	 */
	public function register_routes() : void {
		register_rest_route(
			'coauthor-blocks/v1',
			'/coauthors/(?P<post_id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Unique identifier for a post.' ),
						'type'              => 'integer',
						'validate_callback' => 'is_int'
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permission_check' ),
				)
			)
		);
	}

	/**
	 * Get Items
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) : WP_REST_Response|WP_Error {

		$coauthors = get_coauthors( $request->get_param( 'post_id' ) );

		if ( ! is_array( $coauthors ) ) {
			return new WP_Error(
				'rest_unusable_data',
				__('Sorry, an unusable response was produced.'),
				array( 'status' => 406 )
			);
		}

		return rest_ensure_response(
			array_map(
				function( stdClass|WP_User $author ) use ( $request ) : array {
					return $this->prepare_response_for_collection(
						$this->prepare_item_for_response( $author, $request )
					);
				},
				$coauthors
			)
		);
	}

	/**
	 * Get Items Permission Check
	 * 
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function get_items_permission_check( WP_REST_Request $request ) : bool|WP_Error {

		$post_id = $request->get_param( 'post_id' );

		if ( current_user_can( 'edit_post', $post_id ) ) {
			return true;
		}

		if ( is_coauthor_for_post( get_current_user(), $post_id ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to view coauthors of this post.' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Prepare Item For Response
	 * 
	 * @param stdClass|WP_User $author
	 * @param WP_REST_Request  $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_item_for_response( $author, $request ) : WP_REST_Response|WP_Error {
	
		$disallowed = array(
			'ID',
			'linked_account',
			'user_email',
			'user_login'
		);

		$data = array_diff_key(
			(array) $author,
			array_flip( $disallowed )
		);

		$data['id']   = $author->ID;
		$data['link'] = get_author_posts_url( $author->ID, $author->user_nicename );

		$response = rest_ensure_response( $data );

		/**
		 * Filters the post data for a REST API response.
		 *
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param stdClass|WP_User $author
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "rest_prepare_coauthor_block", $response, $author, $request );
	}
}
