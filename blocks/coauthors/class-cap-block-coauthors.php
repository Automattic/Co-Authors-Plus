<?php
/**
 * Co-Authors Block
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthors
 */
class CAP_Block_CoAuthors {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
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
		$post_id = array_key_exists( 'postId', $block->context ) ? absint( $block->context['postId'] ) : 0;
	
		if ( 0 === $post_id ) {
			return '';
		}

		$authors = get_coauthors( $post_id );

		if ( ! is_array( $authors ) || empty( $authors ) ) {
			return '';
		}

		$author_blocks = array_map(
			self::get_block_wrapper_function('div', 'class="wp-block-cap-coauthor"'),
			array_map(
				self::get_template_render_function(
					self::get_block_as_template( $block )
				),
				$authors
			)
		);

		return self::get_block_wrapper_function('div', get_block_wrapper_attributes())(implode("\n", $author_blocks));
	}

	/**
	 * Get Block Wrapper Function
	 * 
	 * @param string $element_name
	 * @param string $attributes
	 * @return callable
	 */
	private static function get_block_wrapper_function( string $element_name, string $attributes ) : callable {
		return function( string $content ) use ( $element_name, $attributes ) : string {
			return "<{$element_name} {$attributes}>{$content}</{$element_name}>";
		};
	}

	/**
	 * Get Template Render Function
	 * 
	 * @param array $block_template
	 * @return callable
	 */
	private static function get_template_render_function( array $block_template ) : callable {
		return function( stdClass|WP_User $author ) use ( $block_template ) : string {
			return (
				new WP_Block(
					$block_template,
					array(
						'displayName' => $author->display_name,
					)
				)
			)->render( array( 'dynamic' => false ) );
		};
	}

	/**
	 * Get Block as Template
	 * 
	 * @param WP_Block $block
	 * @return array
	 */
	private static function get_block_as_template( WP_Block $block ) : array {
		return array_merge(
			$block->parsed_block,
			array(
				'blockName' => 'core/null'
			)
		);
	}
}
