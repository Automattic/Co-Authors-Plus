<?php
/**
 * Co-Author Display Feature Image
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthor Feature Image
 */
class CAP_Block_CoAuthor_Feature_Image {
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

		$featured_media_id = absint( $author['featured_media'] ?? 0 );
		$display_name      = $author['display_name'] ?? '';
		$link              = $author['link'] ?? '';

		if ( 0 === $featured_media_id ) {
			return '';
		}

		$attributes = array_merge(
			array(
				'width'       => '',
				'height'      => '',
				'sizeSlug'    => 'thumbnail',
				'scale'       => '',
				'aspectRatio' => '',
				'isLink'      => false,
				'rel'         => ''
			),
			$attributes
		);

		if ( empty( $attributes['width'] ) && ! empty( $attributes['height'] ) ) {
			$attributes['width'] = 'auto';
		}

		$style_attribute_key_map = array(
			'width'       => 'width',
			'height'      => 'height',
			'scale'       => 'object-fit',
			'aspectRatio' => 'aspect-ratio',
		);

		$styles = array_map(
			function( string $key, string $style ) use ( $attributes ) : string {
				if ( empty( $attributes[$key] ) ) {
					return '';
				}
				return sprintf(
					"%s;",
					safecss_filter_attr(
						"{$style}:{$attributes[$key]}"
					)
				);
			},
			array_keys( $style_attribute_key_map ),
			array_values( $style_attribute_key_map )
		);

		$image_attributes = array_merge(
			array(
				'class' => '',
				'style' => ''
			),
			get_block_core_post_featured_image_border_attributes( $attributes )
		);

		$image_attributes['style'] .= implode( '', $styles );

		$feature_image = wp_get_attachment_image( $featured_media_id, $attributes['sizeSlug'], false, $image_attributes );

		if ( '' !== $link && true === $attributes['isLink'] ) {
			$link_attributes = Templating::render_attributes(
				array(
					'href'  => $author['link'],
					'rel'   => $attributes['rel'],
					'title' => sprintf( __( 'Posts by %s', 'co-authors-plus' ), $display_name ),
				)
			);
			$inner_content = Templating::render_element(  'a', $link_attributes, $feature_image );
		} else {
			$inner_content = $feature_image;
		}

		return Templating::render_element( 'figure', get_block_wrapper_attributes(), $inner_content );
	}
}
