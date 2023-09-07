<?php
/**
 * Co-Authors Block
 * 
 * @package Co-Authors Plus
 */

namespace CoAuthors\Blocks;

use WP_Block;
use WP_REST_Request;

/**
 * Block CoAuthors
 */
class Block_CoAuthors {
	/**
	 * Register Block
	 */
	public static function register_block() : void {
		register_block_type(
			realpath( __DIR__ . '/../../..' ) . '/build/blocks/block-coauthors',
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

		$authors = array_map(
			function( $author ) : array {
				return rest_get_server()->dispatch(
					WP_REST_Request::from_url(
						home_url(
							sprintf(
								'/wp-json/coauthors-blocks/v1/coauthor/%s',
								$author->user_nicename
							)
						)
					)
				)->get_data();
			},
			get_coauthors( $post_id )
		);

		if ( ! is_array( $authors ) || empty( $authors ) ) {
			return '';
		}

		$blocks = self::render_coauthors_blocks_with_template(
			self::get_block_as_template( $block ),
			$authors
		);

		$separators = self::get_separators(
			count( $blocks ),
			$attributes
		);

		$blocks_with_separators = self::merge_blocks_with_separators(
			$blocks,
			$separators
		);

		if ( 'inline' === $attributes['layout']['type'] ) {
			array_unshift(
				$blocks_with_separators,
				self::render_prefix( $attributes['prefix'] ?? '' )
			);
			array_push(
				$blocks_with_separators,
				self::render_suffix( $attributes['suffix'] ?? '' )
			);
		}

		return current( $block->parsed_block['innerContent'] ) . implode( $blocks_with_separators ) . end( $block->parsed_block['innerContent'] );
	}

	/**
	 * Get Composed Map Function
	 * Use array reduce so an unknown array of functions can be used as single array_map callback
	 * 
	 * @param array $fns
	 * @return callable
	 */
	public static function get_composed_map_function( ...$fns ) : callable {
		return function ( $value ) use ( $fns ) {
			return array_reduce(
				$fns,
				function( $v, callable $f ) {
					return $f($v);
				},
				$value
			);
		};
	}

	/**
	 * Render Prefix
	 * 
	 * @param string $prefix
	 * @return string
	 */
	public static function render_prefix( string $prefix ) : string {
		if ( empty( $prefix ) ) {
			return $prefix;
		}
		return Templating::render_element('span', 'class="wp-block-cap-coauthors__prefix"', $prefix);
	}

	/**
	 * Render Suffix
	 * 
	 * @param string $suffix
	 * @return string
	 */
	public static function render_suffix( string $suffix ) : string {
		if ( empty( $suffix ) ) {
			return $suffix;
		}
		return Templating::render_element('span', 'class="wp-block-cap-coauthors__suffix"', $suffix);
	}

	/**
	 * Render Co-Authors Blocks with Template
	 * 
	 * @param array $template
	 * @param array $authors
	 * @return array
	 */
	public static function render_coauthors_blocks_with_template( array $template, array $authors ) : array {
		return array_map(
			self::get_composed_map_function(
				self::get_template_render_function( $template ),
				// To match JSX from editor, remove line-breaks between blocks.
				function( $content ) {
					return str_replace("\n", '', $content );
				},
				// To match JSX from editor, trim whitespace around blocks.
				'trim',
				Templating::get_render_element_function('div', 'class="wp-block-cap-coauthor"')
			),
			$authors
		);
	}

	/**
	 * Merge Blocks with Separators
	 * 
	 * @param array $blocks
	 * @param array $separators
	 * @return array
	 */
	private static function merge_blocks_with_separators( array $blocks, array $separators ) : array {
		return array_map(
			function(...$args) : string {
				return implode($args);
			},
			$blocks,
			$separators
		);
	}

	/**
	 * Get Separators
	 * 
	 * @param int $count
	 * @param array $attributes
	 */
	private static function get_separators( int $count, array $attributes ) : array {
		if ( 1 === $count ) {
			return array();
		}

		if ( 'inline' !== $attributes['layout']['type'] ) {
			return array();
		}

		$separator      = self::get_separator( $attributes );
		$last_separator = self::get_last_separator( $attributes, $separator );
		$separators     = array_fill( 0, $count - 1, $separator );

		if ( ! empty( $separators ) ) {
			array_splice( $separators, -1, 1, $last_separator );
		}

		return $separators;
	}

	/**
	 * Get Separator
	 * 
	 * @param array $attributes
	 * @return string $separator
	 */
	private static function get_separator( array $attributes ) : string {
		$separator = esc_html(
			$attributes['separator'] ?? ''
		);

		if ( '' === $separator ) {
			return $separator;
		}

		return Templating::render_element(
			'span',
			'class="wp-block-cap-coauthors__separator"',
			$separator
		);
	}

	/**
	 * Get Last Separator
	 * 
	 * @param array $attributes
	 * @param string $default
	 */
	private static function get_last_separator( array $attributes, string $default ) : string {
		$last_separator = esc_html(
			$attributes['lastSeparator'] ?? ''
		);

		if ( '' === $last_separator ) {
			return $default;
		}

		return Templating::render_element(
			'span',
			'class="wp-block-cap-coauthors__separator"',
			$last_separator
		);
	}

	/**
	 * Get Template Render Function
	 * 
	 * @param array $block_template
	 * @return callable
	 */
	private static function get_template_render_function( array $block_template ) : callable {
		return function( array $author ) use ( $block_template ) : string {
			return (
				new WP_Block(
					$block_template,
					array(
						'cap/author' => $author,
					)
				)
			)->render(
				array(
					'dynamic' => false,
				)
			);
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
				'innerContent' => array_slice( $block->parsed_block['innerContent'], 1, -1 ),
				'blockName'    => 'core/null'
			)
		);
	}
}
