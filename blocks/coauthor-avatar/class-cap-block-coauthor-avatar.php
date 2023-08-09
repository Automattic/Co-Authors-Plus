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

		$author = $block->context['cap/author'] ?? array();

		if ( empty( $author ) ) {
			return '';
		}

		$avatar_urls = $author['avatar_urls'] ?? array();

		if ( empty( $avatar_urls ) ) {
			return '';
		}

		$link    = $author['link'] ?? '';
		$is_link = '' !== $link && $attributes['isLink'] ?? false;
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

		$rel = $attributes['rel'] ?? '';

		$inner_content = $is_link ? self::add_link( $link, $image, $rel ) : $image;

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
	 * @param null|string $rel
	 * @return string
	 */
	public static function add_link( string $link, string $content, ?string $rel = '' ) : string {
		return sprintf(
			'<a href="%s" rel="%s">%s</a>',
			$link,
			$rel,
			$content
		);
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
