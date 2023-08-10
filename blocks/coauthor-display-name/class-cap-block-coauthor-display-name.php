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

		$author = $block->context['cap/author'] ?? array();

		if ( empty( $author ) ) {
			return '';
		}

		$display_name = $author['display_name'] ?? '';

		if ( '' === $display_name ) {
			return '';
		}

		$link    = $author['link'] ?? '';
		$is_link = '' !== $link && $attributes['isLink'] ?? false;
		$rel     = $attributes['rel'] ?? '';

		if ( $is_link ) {
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

		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(
				self::get_custom_block_wrapper_attributes( $attributes )
			),
			$inner_content
		);
	}

	/**
	 * Get Custom Block Wrapper Attributes
	 * 
	 * @param array $attributes
	 * @return array
	 */
	public static function get_custom_block_wrapper_attributes( array $attributes ) : array {

		$text_align = $attributes['textAlign'] ?? '';

		if ( empty( $text_align ) ) {
			return array();
		}
		return array(
			'class' => esc_attr( "has-text-align-{$text_align}" )
		);
	}
}
