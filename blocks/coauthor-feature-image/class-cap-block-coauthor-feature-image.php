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
		add_action( 'render_block_context', array( __CLASS__, 'provide_author_archive_context' ), 10, 3 );
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

		$featured_image = wp_get_attachment_image( $featured_media_id, $size_slug, false, $attr );

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
