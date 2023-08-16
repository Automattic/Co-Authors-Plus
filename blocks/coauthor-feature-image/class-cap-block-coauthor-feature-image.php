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

		$style_attribute_map = array(
			array(
				'prop' => 'width',
				'attr' => 'width'
			),
			array(
				'prop' => 'height',
				'attr' => 'height'
			),
			array(
				'prop' => 'object-fit',
				'attr' => 'scale'
			),
			array(
				'prop' => 'aspect-ratio',
				'attr' => 'aspectRatio'
			)
		);

		$styles = array_map(
			function( $style ) use ( $attributes ) : string {
				if ( empty( $attributes[$style['attr']] ) ) {
					return '';
				}
				return sprintf(
					"%s;",
					safecss_filter_attr(
						"{$style['prop']}:{$attributes[$style['attr']]}"
					)
				);
			},
			$style_attribute_map
		);

		$image_attributes = array_merge(
			array(
				'class' => '',
				'style' => ''
			),
			get_block_core_post_featured_image_border_attributes( $attributes )
		);

		$image_attributes['style'] .= implode( '', $styles );
		$wrapper_attributes = get_block_wrapper_attributes();
		$feature_image = wp_get_attachment_image( $featured_media_id, $attributes['sizeSlug'], false, $image_attributes );

		if ( true === $attributes['isLink'] ) {
			$inner_content = self::add_link( $author['link'], $feature_image, $attributes['rel'] );
		} else {
			$inner_content = $feature_image;
		}

		return "<figure {$wrapper_attributes}>{$inner_content}</figure>";
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
}
