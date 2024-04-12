<?php
/**
 * Co-Author Description Block
 * 
 * @package Automattic\CoAuthorsPlus
 * @since 3.6.0
 */

namespace CoAuthors\Blocks; 

use WP_Block;

/**
 * Block CoAuthor Description
 *
 * @package CoAuthors
 */
class Block_CoAuthor_Description {
	/**
	 * Register Block
	 * 
	 * @since 3.6.0
	 */
	public static function register_block(): void {
		register_block_type(
			dirname( COAUTHORS_PLUS_FILE ) . '/build/blocks/block-coauthor-description',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Render Block
	 *
	 * @since 3.6.0
	 * @param array    $attributes
	 * @param string   $content
	 * @param WP_Block $block
	 * @return string
	 */
	public static function render_block( array $attributes, string $content, WP_Block $block ): string {

		$author = $block->context['co-authors-plus/author'] ?? array();

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
	 * @since 3.6.0
	 * @param array $attributes
	 * @return array
	 */
	public static function get_custom_block_wrapper_attributes( array $attributes ): array {

		$default = array(
			'class' => 'is-layout-flow',
		);

		$text_align = $attributes['textAlign'] ?? '';

		if ( empty( $text_align ) ) {
			return $default;
		}

		return array(
			'class' => $default['class'] . ' ' . sanitize_html_class( "has-text-align-{$text_align}" ),
		);
	}
}
