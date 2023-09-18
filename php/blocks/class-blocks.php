<?php
/**
 * Blocks
 * 
 * @package Automattic\CoAuthorsPlus
 * @since 3.6.0
 */

namespace CoAuthors;

use WP_REST_Request;

/**
 * Blocks
 * 
 * @package CoAuthors
 */
class Blocks {
	/**
	 * Construct
	 *
	 * @since 3.6.0
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'initialize_blocks' ) );
	}

	/**
	 * Initialize Blocks
	 *
	 * @since 3.6.0
	 */
	public static function initialize_blocks(): void {

		if ( ! apply_filters( 'coauthors_plus_support_blocks', true ) ) {
			return;
		}

		add_filter( 'render_block_context', array( __CLASS__, 'provide_author_archive_context' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_store' ) );

		/**
		 * Templating functions used by many block render functions.
		 */
		require_once __DIR__ . '/templating/class-templating.php';

		/**
		 * Individual blocks.
		 */
		require_once __DIR__ . '/block-coauthors/class-block-coauthors.php';
		Blocks\Block_CoAuthors::register_block();

		require_once __DIR__ . '/block-coauthor-avatar/class-block-coauthor-avatar.php';
		Blocks\Block_CoAuthor_Avatar::register_block();

		require_once __DIR__ . '/block-coauthor-description/class-block-coauthor-description.php';
		Blocks\Block_CoAuthor_Description::register_block();

		require_once __DIR__ . '/block-coauthor-name/class-block-coauthor-name.php';
		Blocks\Block_CoAuthor_Name::register_block();

		require_once __DIR__ . '/block-coauthor-featured-image/class-block-coauthor-featured-image.php';
		Blocks\Block_CoAuthor_Featured_Image::register_block();
	}

	/**
	 * Provide Author Archive Context
	 *
	 * @since 3.6.0
	 * @param array $context
	 * @param array $parsed_block
	 * @return array
	 */
	public static function provide_author_archive_context( array $context, array $parsed_block ): array {
		if ( ! is_author() ) {
			return $context;
		}

		if ( null === $parsed_block['blockName'] ) {
			return $context;
		}

		/**
		 * Block Uses Author Context
		 * 
		 * @since 3.6.0
		 * @param bool
		 * @param string
		 */
		$uses_author_context = apply_filters(
			'coauthors_blocks_block_uses_author_context',
			'cap/coauthor-' === substr( $parsed_block['blockName'], 0, 13 ),
			$parsed_block['blockName']
		);
		
		$has_author_context = array_key_exists( 'cap/author', $context ) && is_array( $context['cap/author'] );

		if ( ! $uses_author_context || $has_author_context ) {
			return $context;
		}

		$author = self::get_author_with_api_schema( get_queried_object() );

		if ( ! is_array( $author ) ) {
			return $context;
		}

		return array(
			'cap/author' => $author,
		);
	}

	/**
	 * Enqueue Store
	 *
	 * @since 3.6.0
	 */
	public static function enqueue_store(): void {
		$asset = require dirname( COAUTHORS_PLUS_FILE ) . '/build/blocks-store/index.asset.php';

		wp_enqueue_script(
			'coauthors-blocks-store',
			plugins_url( '/co-authors-plus/build/blocks-store/index.js' ),
			$asset['dependencies'],
			$asset['version']
		);

		$data = apply_filters(
			'coauthors_blocks_store_data',
			array(
				'authorPlaceholder' => array(
					'id'             => 0,
					'display_name'   => 'FirstName LastName',
					'description'    => array(
						'raw'      => 'Placeholder description from Co-Authors block.',
						'rendered' => '<p>Placeholder description from Co-Authors block.</p>',
					),
					'link'           => '#',
					'featured_media' => 0,
					'avatar_urls'    => array_map( '__return_empty_string', array_flip( rest_get_avatar_sizes() ) ),
				),
			)
		);

		wp_localize_script(
			'coauthors-blocks-store',
			'coAuthorsBlocks',
			$data
		);
	}

	/**
	 * Get CoAuthor with API Schema
	 *
	 * Use the global WP_REST_Server to fetch author data,
	 * so that it matches what a user would see in the editor.
	 *
	 * @since 3.6.0
	 * @param false|WP_User|stdClass $author An author object from CoAuthors Plus.
	 * @return null|array Either an array of data about an author, or null.
	 */
	public static function get_author_with_api_schema( $author ): ?array {
		if ( ! ( is_a( $author, 'stdClass' ) || is_a( $author, 'WP_User' ) ) ) {
			return null;
		}

		$data = rest_get_server()->dispatch(
			WP_REST_Request::from_url(
				home_url(
					sprintf(
						'/wp-json/coauthors-blocks/v1/coauthor/%s',
						$author->user_nicename
					)
				)
			)
		)->get_data();

		if ( ! is_array( $data ) ) {
			return null;
		}

		// Lack of an `id` indicates an author was not found.
		if ( ! array_key_exists( 'id', $data ) ) {
			return null;
		}

		// The presence of `code` indicates this is an error response.
		if ( array_key_exists( 'code', $data ) ) {
			return null;
		}

		return $data;
	}
}
