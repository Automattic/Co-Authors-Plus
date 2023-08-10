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

		$size_slug = isset( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'thumbnail';
		$attr      = get_block_core_post_featured_image_border_attributes( $attributes );

		$extra_styles = '';

		// Aspect ratio with a height set needs to override the default width/height.
		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$extra_styles .= 'width:100%;height:100%;';
		} elseif ( ! empty( $attributes['height'] ) ) {
			$extra_styles .= "height:{$attributes['height']};";
		}

		if ( ! empty( $attributes['scale'] ) ) {
			$extra_styles .= "object-fit:{$attributes['scale']};";
		}

		if ( ! empty( $extra_styles ) ) {
			$attr['style'] = empty( $attr['style'] ) ? $extra_styles : $attr['style'] . $extra_styles;
		}

		$link    = $author['link'] ?? '';
		$is_link = '' !== $link && $attributes['isLink'] ?? false;
		$rel = $attributes['rel'] ?? '';

		$featured_image = wp_get_attachment_image( $featured_media_id, $size_slug, false, $attr );

		$featured_image = $is_link ? self::add_link( $link, $featured_image, $rel ) : $featured_image;

		$aspect_ratio = ! empty( $attributes['aspectRatio'] )
			? esc_attr( safecss_filter_attr( 'aspect-ratio:' . $attributes['aspectRatio'] ) ) . ';'
			: '';
		$width        = ! empty( $attributes['width'] )
			? esc_attr( safecss_filter_attr( 'width:' . $attributes['width'] ) ) . ';'
			: '';
		$height       = ! empty( $attributes['height'] )
			? esc_attr( safecss_filter_attr( 'height:' . $attributes['height'] ) ) . ';'
			: '';

		if ( ! $height && ! $width && ! $aspect_ratio ) {
			$wrapper_attributes = get_block_wrapper_attributes();
		} else {
			$wrapper_attributes = get_block_wrapper_attributes( array( 'style' => $aspect_ratio . $width . $height ) );
		}

		return "<figure {$wrapper_attributes}>{$featured_image}</figure>";
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
