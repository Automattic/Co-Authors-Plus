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

		$description = $author['description']['raw'] ?? '';

		if ( '' === $description ) {
			return '';
		}

		return Templating::render_element(
			'div',
			get_block_wrapper_attributes(
				self::get_custom_block_wrapper_attributes( $attributes )
			),
			wp_kses_post( wpautop( wptexturize( $description ) ) )
		);
	}
	/**
	 * Get Custom Block Wrapper Attributes
	 * 
	 * @param array $attributes
	 * @return array
	 */
	public static function get_custom_block_wrapper_attributes( array $attributes ) : array {

		$default = array(
			'class' => 'is-layout-flow',
		);

		$text_align = $attributes['textAlign'] ?? '';

		if ( empty( $text_align ) ) {
			return $default;
		}

		return array(
			'class' => $default['class'] . ' ' . esc_attr( "has-text-align-{$text_align}" )
		);
	}
}
