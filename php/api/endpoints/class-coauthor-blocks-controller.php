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
	}

	/**
	 * Register Rest Routes
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
						'validate_callback' => fn( $post_id ) => is_int( $post_id )
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permission_check' ),
				)
			)
		);

		register_rest_route(
			'coauthor-blocks/v1',
			'/coauthor/(?P<user_nicename>[\d\w-]+)',
			array(
				'args' => array(
					'user_nicename' => array(
						'description'       => __( 'Nicename / slug for coauthor.' ),
						'type'              => 'string',
						'validate_callback' => fn( $slug ) => is_string( $slug ),
						'sanitize_callback' => fn( $slug ) => sanitize_title( $slug )
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permission_check' ),
				)
			)
		);
	}

	/**
	 * Get Item
	 * 
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) : WP_REST_Response|WP_Error {

		$coauthor = $this->coauthors_plus->get_coauthor_by(
			'user_nicename',
			$request->get_param( 'user_nicename' )
		);

		if ( ! is_object( $coauthor ) ) {
			return new WP_Error(
				'rest_not_found',
				__('Sorry, we could not find that coauthor.'),
				array( 'status' => 404 )
			);
		}

		if ( ! self::is_coauthor( $coauthor ) ) {
			return new WP_Error(
				'rest_unusable_data',
				__('Sorry, an unusable response was produced.'),
				array( 'status' => 406 )
			);
		}

		return self::prepare_item_for_response( $coauthor, $request );
	}

	/**
	 * Is Valid CoAuthor
	 *
	 * @param WP_User|stdClass $coauthor
	 */
	public static function is_coauthor( WP_User|stdClass $coauthor ) : bool {
		return is_a( $coauthor, 'WP_User' ) || ( property_exists( $coauthor, 'type' ) && 'guest-author' === $coauthor->type );
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
	 * Get Item Permission Check
	 * 
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function get_item_permission_check( WP_REST_Request $request ) : bool|WP_Error {

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to view coauthors.' ),
			array( 'status' => rest_authorization_required_code() )
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
	
		if ( is_a( $author, 'WP_User' ) ) {
			$author = $author->data;
		}

		$disallowed = array(
			'ID',
			'linked_account',
			'user_email',
			'user_login',
			'user_pass',
			'user_registered',
            'user_activation_key',
			'user_url',
			'user_status',
			'type'
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
