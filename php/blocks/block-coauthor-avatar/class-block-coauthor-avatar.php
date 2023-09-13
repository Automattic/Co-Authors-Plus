<?php
/**
 * Co-Author Avatar
 *
 * @package CoAuthors
 * @since 3.6.0
 */

namespace CoAuthors\Blocks; 

use WP_Block;
/**
 * Block CoAuthor Avatar
 *
 * @package CoAuthors
 */
class Block_CoAuthor_Avatar {

	/**
	 * Register Block
	 *
	 * @since 3.6.0
	 */
	public static function register_block() : void {
		register_block_type(
			realpath( __DIR__ . '/../../..' ) . '/build/blocks/block-coauthor-avatar',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}
	/**
	 * Render Block
	 *
	 * @since 3.6.0
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
		
		$display_name = $author['display_name'] ?? '';
		$link    = $author['link'] ?? '';
		$is_link = '' !== $link && $attributes['isLink'] ?? false;
		$rel = $attributes['rel'] ?? '';

		$size    = $attributes['size'] ?? 24;
		$srcset  = array_map(
			function( $size, $url ) {
				return "{$url} {$size}w";
			},
			array_keys( $avatar_urls ),
			array_values( $avatar_urls )
		);

		$image_attributes = Templating::render_attributes(
			array_merge(
				get_block_core_post_featured_image_border_attributes( $attributes ),
				array(
					'src'    => $avatar_urls[$size],
					'width'  => $size,
					'height' => $size,
					'sizes'  => "{$size}px",
					'srcset' => implode( ', ', $srcset),
				)
			)
		);

		$image = Templating::render_self_closing_element(
			'img',
			$image_attributes
		);

		if ( $is_link ) {
			$link_attributes = Templating::render_attributes(
				array(
					'href'  => $link,
					'rel'   => $rel,
					'title' => sprintf( __( 'Posts by %s', 'co-authors-plus' ), $display_name ),
				)
			);
			$inner_content = Templating::render_element('a', $link_attributes, $image);
		} else {
			$inner_content = $image;
		}

		return Templating::render_element(
			'figure',
			get_block_wrapper_attributes(),
			$inner_content
		);
	}
}
