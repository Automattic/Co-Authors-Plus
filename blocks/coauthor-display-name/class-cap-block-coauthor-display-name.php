<?php
/**
 * Co-Author Display Name Block 
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthor Display Name
 */
class CAP_Block_CoAuthor_Display_Name {
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

		$display_name = $block->context['display_name'] ?? '';

		if ( '' === $display_name ) {
			return '';
		}

		$link    = $block->context['link'] ?? '';
		$is_link = $attributes['isLink'] ?? false;
		$rel     = $attributes['rel'] ?? '';

		if ( $is_link && '' !== $link ) {
			$inner_content = sprintf(
				'<a rel="%s" href="%s" title="%s">%s</a>',
				$rel,
				$link,
				sprintf( __( 'Posts by %s', 'co-authors-plus' ), $display_name ),
				$display_name
			);
		} else {
			$inner_content = $display_name;
		}

		return sprintf( '<p %s>%s</p>', get_block_wrapper_attributes(), $inner_content );
	}
}
