<?php
/**
 * Co-Author Avatar
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthor Avatar
 */
class CAP_Block_CoAuthor_Avatar {
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
		register_block_style(
			'cap/coauthor-avatar',
			array(
				'name'         => 'rounded',
				'label'        => __('Rounded'),
				'inline_style' => '.wp-block-cap-coauthor-avatar.is-style-rounded img { border-radius: 50% }',
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

		$avatar_urls = $block->context['avatar_urls'] ?? array();

		if ( empty( $avatar_urls ) ) {
			return '';
		}

		$link    = $block->context['link'] ?? '';
		$is_link = $attributes['isLink'] ?? false;
		$size    = $attributes['size'] ?? 24;
		$srcset  = array_map(
			fn( $url, $size ) => "{$url} {$size}w",
			array_values( $avatar_urls ),
			array_keys( $avatar_urls )
		);

		$image = sprintf(
			'<img src="%s" width="%s" height="%s" sizes="%s" srcset="%s">',
			$avatar_urls[$size],
			$size,
			$size,
			"{$size}px",
			implode( ', ', $srcset)
		);

		$inner_content = $is_link ? self::add_link( $link, $image ) : $image;

		return self::get_block_wrapper_function(
			'div',
			get_block_wrapper_attributes()
		)( $inner_content );
	}

	/**
	 * Add Link
	 * 
	 * @param string $link
	 * @param string $content
	 * @return string
	 */
	public static function add_link( string $link, string $content ) : string {
		return sprintf('<a href="%s">%s</a>', $link, $content);
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
}
