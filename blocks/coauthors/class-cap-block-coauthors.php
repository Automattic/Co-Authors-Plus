<?php
/**
 * Co-Authors Block
 * 
 * @package Co-Authors Plus
 */

/**
 * CAP Block CoAuthors
 */
class CAP_Block_CoAuthors {
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

		$post_id = array_key_exists( 'postId', $block->context ) ? absint( $block->context['postId'] ) : 0;
	
		if ( 0 === $post_id ) {
			return '';
		}

		$authors = array_map(
			function( stdClass|WP_User $author ) : array {
				return rest_get_server()->dispatch(
					WP_REST_Request::from_url(
						home_url(
							sprintf(
								'/wp-json/coauthor-blocks/v1/coauthor/%s',
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

		$blocks = self::render_coauthor_blocks_with_template(
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

		$inner_content = implode( $blocks_with_separators );

		if ( 'block' === $attributes['layout']['type'] && $attributes['style']['spacing']['blockGap'] ?? false ) {
			$gap_value =  self::get_gap_css_value( self::get_normalized_gap_value( $attributes['style']['spacing']['blockGap']));
			$style = "gap: {$gap_value};";
		} else {
			$style = null;
		}

		return self::get_block_wrapper_function(
			'div',
			get_block_wrapper_attributes(
				array(
					'class' => "is-layout-cap-{$attributes['layout']['type']}",
					'style' => $style
				)
			)
		)( $inner_content );
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
				fn( $v, callable $f ) => $f($v),
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
		return self::get_block_wrapper_function(
			'span',
			'class="wp-block-cap-coauthor__prefix"'
		)( $prefix );
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
		return self::get_block_wrapper_function(
			'span',
			'class="wp-block-cap-coauthor__suffix"'
		)( $suffix );
	}

	/**
	 * Render CoAuthor Blocks with Template
	 * 
	 * @param array $template
	 * @param array $authors
	 * @return array
	 */
	public static function render_coauthor_blocks_with_template( array $template, array $authors ) : array {
		return array_map(
			self::get_composed_map_function(
				self::get_template_render_function( $template ),
				// To match JSX from edition, remove line-breaks between blocks.
				fn( $content ) => str_replace("\n", '', $content ),
				// To match JSX from edition, trim whitespace around blocks.
				'trim',
				self::get_block_wrapper_function('div', 'class="wp-block-cap-coauthor"')
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
			fn(...$args) : string => implode($args),
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

		return "<span class=\"wp-block-cap-coauthor__separator\">{$separator}</span>";
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

		return "<span class=\"wp-block-cap-coauthor__separator\">{$last_separator}</span>";
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
				'blockName' => 'core/null'
			)
		);
	}

	public static function has_gap_attribute( array $attributes ) : bool {
		return ($attributes['style']['spacing']['blockGap'] ?? false) ? true : false;
	}

	public static function get_gap_css_value( array $gaps ) : string {
		return $gaps['row'] === $gaps['column'] ? $gaps['row'] : "{$gaps['row']} {$gaps['column']}";
	}

	public static function get_normalized_gap_value( string|array $gap ) : array {

		if ( is_array( $gap ) ) {
			$gap = array_merge(
				array(
					'top'  => '0',
					'left' => '0'
				),
				$gap
			);
		}

		return array(
			'row'    => self::get_preset_css_value( is_string( $gap ) ? $gap : $gap['top'] ),
			'column' => self::get_preset_css_value( is_string( $gap ) ? $gap : $gap['left'] ),
		);
	}

	


	public static function get_preset_css_value( string $value ) : string {
		if ( 'var:' !== substr( $value, 0, 4 ) ) {
			return $value;
		}
		return sprintf(
			'var(--wp--%s)',
			str_replace( '|', '--', substr( $value, 4 ) )
		);
	}

	/**
	 * Get Block Gap from Block Style Attribute
	 * Reduce block gap settings into custom proprties we can use in our CSS.
	 *
	 * @param array style
	 * @return string
	 */
	public static function get_block_gap_from_block_style_attribute( array $style ) : string {

		if ( ! array_key_exists( 'spacing', $style ) ) {
			return '';
		}

		$block_gap = $style['spacing']['blockGap'] ?? '';

		if ( empty( $block_gap ) ) {
			return '';
		}

		if ( ! is_array( $block_gap ) ) {
			$block_gap = array(
				'top'  => $block_gap,
				'left' => $block_gap
			);
		}

		$directions = array(
			'top'  => 'row',
			'left' => 'column'
		);

		$declarations = array_map(
			function( string $gap, $key ) use ( $directions ) : string {

				if ( 'var:preset|spacing|' === substr( $gap, 0, 19 ) ) {
					$slug  = str_replace( '|', '-', substr( $gap, 19 ) );
					$value = "var(--wp--preset--spacing--{$slug})";
				} else {
					$value = $gap;
				}

				return "{$directions[$key]}-gap:{$value}";
			},
			$block_gap,
			array_keys( $block_gap )
		);

		return implode( ';', $declarations );
	}

}
