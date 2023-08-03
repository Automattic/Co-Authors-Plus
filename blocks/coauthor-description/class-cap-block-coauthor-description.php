<?php
/**
 * Co-Author Description Block 
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthor Description
 */
class CAP_Block_CoAuthor_Description {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'render_block_context', array( __CLASS__, 'provide_author_archive_context' ), 10, 3 );
	}
	/**
	 * Register Block
	 */
	public static function register_block() : void {
		register_block_type(
			__DIR__ . '/build',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}
	/**
	 * Render Block
	 * 
	 * @param array $attributes
	 * @param string $content
	 * @param WP_Block $block
	 * @return string
	 */
	public static function render_block( array $attributes, string $content, WP_Block $block ) : string {

		$author = $block->context['cap/author'] ?? array();

		if ( empty( $author ) ) {
			return '';
		}

		$description = $author['description'] ?? '';

		if ( '' === $description ) {
			return '';
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(
				array(
					'class' => 'is-layout-flow'
				)
			),
			wp_kses_post( wpautop( wptexturize( $description ) ) )
		);
	}
	/**
	 * Provide Author Archive Context
	 *
	 * @param array         $context, 
	 * @param array         $parsed_block
	 * @param null|WP_Block $parent_block
	 * @return array
	 */
	public static function provide_author_archive_context( array $context, array $parsed_block, ?WP_Block $parent_block ) : array {
		if ( ! is_author() ) {
			return $context;
		}

		if ( null === $parsed_block['blockName'] ) {
			return $context;
		}

		// author if you do an individual piece of a coauthor outside of a coauthor template.
		if ( 'cap/coauthor-' === substr( $parsed_block['blockName'], 0, 13  ) && ( ! array_key_exists( 'cap/author', $context ) || empty( $context['cap/author'] ) ) ) {
			return array(
				'cap/author' => rest_get_server()->dispatch(
					WP_REST_Request::from_url(
						home_url(
							sprintf(
								'/wp-json/coauthor-blocks/v1/coauthor/%s',
								get_query_var( 'author_name' )
							)
						)
					)
				)->get_data()
			);
		}

		return $context;
	}
}
