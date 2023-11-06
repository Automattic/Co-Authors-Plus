<?php
/**
 * CoAuthors Controller
 *
 * @package Automattic\CoAuthorsPlus
 * @since 3.6.0
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
 * CoAuthors Controller
 *
 * @package CoAuthors
 */
class CoAuthors_Controller extends WP_REST_Controller {

	/**
	 * Instance of CoAuthors_Plus class
	 *
	 * @since 3.6.0
	 * @var CoAuthors_Plus $coauthors_plus
	 */
	public $coauthors_plus;

	/**
	 * Construct
	 *
	 * @since 3.6.0
	 * @param CoAuthors_Plus $coauthors_plus
	 */
	public function __construct( CoAuthors_Plus $coauthors_plus ) {
		$this->coauthors_plus = $coauthors_plus;
	}

	/**
	 * Register Rest Routes
	 *
	 * @since 3.6.0
	 */
	public function register_routes(): void {
		$this->register_coauthors_route();
		$this->register_coauthor_route();
	}

	/**
	 * Register Co-Authors Route
	 *
	 * Provide a post ID as an integer to retrieve an array of associated co-authors.
	 *
	 * Example: `/wp-json/coauthors/v1/coauthors?post_id=11111`
	 *
	 * @since 3.6.0
	 */
	public function register_coauthors_route(): void {
		register_rest_route(
			'coauthors/v1',
			'/coauthors',
			array(
				'args' => array(
					'post_id' => array(
						'description'       => __( 'Unique identifier for a post.', 'co-authors-plus' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => function( $post_id ): bool {
							return 0 !== absint( $post_id );
						},
						'sanitize_callback' => function( $post_id ): int {
							return absint( $post_id );
						},
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true'
				),
			)
		);
	}

	/**
	 * Register Co-Author Route
	 *
	 * Provide a user nicename as a hyphen-separated string to retrieve a single co-author.
	 *
	 * Example: `/wp-json/coauthors/v1/coauthors/user-nicename`
	 *
	 * @since 3.6.0
	 */
	public function register_coauthor_route(): void {
		register_rest_route(
			'coauthors/v1',
			'/coauthors/(?P<user_nicename>[\w-]+)',
			array(
				'args' => array(
					'user_nicename' => array(
						'description'       => __( 'Nicename / slug for co-author.', 'co-authors-plus' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function( $slug ): bool {
							return is_string( $slug );
						},
						'sanitize_callback' => function( $slug ) {
							return sanitize_title( $slug );
						},
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Get Item
	 *
	 * @since 3.6.0
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {

		$coauthor = $this->coauthors_plus->get_coauthor_by(
			'user_nicename',
			$request->get_param( 'user_nicename' )
		);

		if ( ! is_object( $coauthor ) ) {
			return new WP_Error(
				'rest_not_found',
				__( 'Sorry, we could not find that co-author.', 'co-authors-plus' ),
				array( 'status' => 404 )
			);
		}

		if ( ! self::is_coauthor( $coauthor ) ) {
			return new WP_Error(
				'rest_unusable_data',
				__( 'Sorry, an unusable response was produced.', 'co-authors-plus' ),
				array( 'status' => 404 )
			);
		}

		return self::prepare_item_for_response( $coauthor, $request );
	}

	/**
	 * Is Valid CoAuthor
	 *
	 * @since 3.6.0
	 * @param WP_User|stdClass $coauthor
	 */
	public static function is_coauthor( $coauthor ): bool {
		return is_a( $coauthor, 'WP_User' ) || self::is_guest_author( $coauthor );
	}

	/**
	 * Is Guest Author
	 *
	 * @since 3.6.0
	 * @param WP_User|stdClass $coauthor
	 */
	public static function is_guest_author( $coauthor ): bool {
		return property_exists( $coauthor, 'type' ) && 'guest-author' === $coauthor->type;
	}

	/**
	 * Get Items
	 *
	 * @since 3.6.0
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

		$coauthors = get_coauthors( $request->get_param( 'post_id' ) );

		if ( ! is_array( $coauthors ) ) {
			return new WP_Error(
				'rest_unusable_data',
				__( 'Sorry, an unusable response was produced.', 'co-authors-plus' ),
				array( 'status' => 406 )
			);
		}

		return rest_ensure_response(
			array_map(
				function( $author ) use ( $request ) : array {
					return $this->prepare_response_for_collection(
						$this->prepare_item_for_response( $author, $request )
					);
				},
				$coauthors
			)
		);
	}

	/**
	 * Retrieves the CoAuthor schema, conforming to JSON Schema.
	 *
	 * @since 3.6.0
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {

		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'coauthor',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Either user ID or guest author ID.', 'co-authors-plus' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'display_name'   => array(
					'description' => __( 'Author name for display.', 'co-authors-plus' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description'    => array(
					'description' => __( 'Author description.', 'co-authors-plus' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Author description as stored in database.', 'co-authors-plus' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'rendered' => array(
							'description' => __( 'Author description as rendered in HTML content.', 'co-authors-plus' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'user_nicename'  => array(
					'description' => __( 'Unique author slug.', 'co-authors-plus' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'link'           => array(
					'description' => __( 'URL of author archive.', 'co-authors-plus' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'featured_media' => array(
					'description' => __( 'ID of guest author featured image.', 'co-authors-plus' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		if ( get_option( 'show_avatars' ) ) {
			$schema['properties']['avatar_urls'] = array(
				'description' => __( 'URL for author avatar.', 'co-authors-plus' ),
				'type'        => 'object',
				'context'     => array( 'view' ),
				'readonly'    => true,
			);
		}

		// Take a snapshot of which fields are in the schema pre-filtering.
		$schema_fields = array_keys( $schema['properties'] );

		$schema = apply_filters( 'rest_coauthors_item_schema', $schema );

		// Emit a _doing_it_wrong warning if user tries to add new properties using this filter.
		$new_fields = array_diff( array_keys( $schema['properties'] ), $schema_fields );
		if ( count( $new_fields ) > 0 ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: register_rest_field */
					esc_html__( 'Please use %s to add new schema properties.', 'co-authors-plus' ),
					'register_rest_field'
				),
				'5.4.0'
			);
		}

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Prepare Item For Response
	 *
	 * @since 3.6.0
	 * @param stdClass|WP_User $author
	 * @param WP_REST_Request  $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_item_for_response( $author, $request ) {
	
		$fields = $this->get_fields_for_response( $request );

		if ( is_a( $author, 'WP_User' ) ) {
			$author              = $author->data;
			$author->description = get_user_meta( $author->ID, 'description', true );
		}

		$data = array();

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = (int) $author->ID;
		}

		if ( rest_is_field_included( 'avatar_urls', $fields ) ) {
			$data['avatar_urls'] = rest_get_avatar_urls( $author->ID );
		}

		if ( rest_is_field_included( 'description', $fields ) ) {
			$data['description'] = array();
		}

		if ( rest_is_field_included( 'description.raw', $fields ) ) {
			$data['description']['raw'] = (string) $author->description;
		}

		if ( rest_is_field_included( 'description.rendered', $fields ) ) {
			$data['description']['rendered'] = wp_kses_post( wpautop( wptexturize( (string) $author->description ) ) );
		}

		if ( rest_is_field_included( 'display_name', $fields ) ) {
			$data['display_name'] = (string) $author->display_name;
		}

		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = (string) get_author_posts_url( $author->ID, $author->user_nicename );
		}

		if ( rest_is_field_included( 'featured_media', $fields ) ) {
			if ( self::is_guest_author( $author ) ) {
				$data['featured_media'] = (int) get_post_thumbnail_id( $author->ID );
			} else {
				$data['featured_media'] = 0;
			}
		}

		if ( rest_is_field_included( 'user_nicename', $fields ) ) {
			$data['user_nicename'] = (string) $author->user_nicename;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		/**
		 * Filters the post data for a REST API response.
		 *
		 * @since 3.6.0
		 * @param WP_REST_Response $response The response object.
		 * @param stdClass|WP_User $author
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'rest_prepare_coauthor', $response, $author, $request );
	}
}
