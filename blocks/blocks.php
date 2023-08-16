<?php

require_once dirname( __FILE__ ) . '/templating/class-templating.php';
require_once dirname( __FILE__ ) . '/coauthors/class-cap-block-coauthors.php';
require_once dirname( __FILE__ ) . '/coauthor-avatar/class-cap-block-coauthor-avatar.php';
require_once dirname( __FILE__ ) . '/coauthor-description/class-cap-block-coauthor-description.php';
require_once dirname( __FILE__ ) . '/coauthor-display-name/class-cap-block-coauthor-display-name.php';
require_once dirname( __FILE__ ) . '/coauthor-feature-image/class-cap-block-coauthor-feature-image.php';

new CAP_Block_CoAuthors();
new CAP_Block_CoAuthor_Avatar();
new CAP_Block_CoAuthor_Description();
new CAP_Block_CoAuthor_Display_Name();
new CAP_Block_CoAuthor_Feature_Image();

/**
 * Provide Author Archive Context
 *
 * @param array         $context, 
 * @param array         $parsed_block
 * @param null|WP_Block $parent_block
 * @return array
 */
function coauthors_blocks_provide_author_archive_context( array $context, array $parsed_block, ?WP_Block $parent_block ) : array {
	if ( ! is_author() ) {
		return $context;
	}

	if ( null === $parsed_block['blockName'] ) {
		return $context;
	}

	$uses_author_context = apply_filters(
		'coauthor_blocks_block_uses_author_context',
		'cap/coauthor-' === substr( $parsed_block['blockName'], 0, 13  ),
		$parsed_block['blockName']
	);
	
	$has_author_context = array_key_exists( 'cap/author', $context ) && is_array( $context['cap/author'] );

	if ( ! $uses_author_context || $has_author_context ) {
		return $context;
	}

	$author = rest_get_server()->dispatch(
		WP_REST_Request::from_url(
			home_url(
				sprintf(
					'/wp-json/coauthor-blocks/v1/coauthor/%s',
					get_query_var( 'author_name' )
				)
			)
		)
	)->get_data();

	if ( ! is_array( $author ) || ! array_key_exists( 'id', $author ) ) {
		return $context;
	}

	return array(
		'cap/author' => $author
	);
}
add_action( 'render_block_context', 'coauthors_blocks_provide_author_archive_context', 10, 3 );

/**
 * Enqueue Store
 */
function coauthors_blocks_enqueue_store() : void {
	$asset = require_once dirname( __FILE__ ) . '/store/build/index.asset.php';

	wp_enqueue_script(
		'coauthors-blocks-store',
		plugins_url( '/store/build/index.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version']
	);

	$data = apply_filters(
		'coauthor_blocks_store_data',
		array(
			'authorPlaceholder' => array(
				'id'             => 0,
				'display_name'   => 'FirstName LastName',
				'description'    => array(
					'raw'      => 'Placeholder description from Co-Authors block.',
					'rendered' => '<p>Placeholder description from Co-Authors block.</p>'
				),
				'link'           => '#',
				'featured_media' => 0,
				'avatar_urls'    => array_map( '__return_empty_string', array_flip( rest_get_avatar_sizes() ) )
			)
		)
	);

	wp_localize_script(
		'coauthors-blocks-store',
		'coAuthorsBlocks',
		$data
	);
}
add_action( 'enqueue_block_editor_assets', 'coauthors_blocks_enqueue_store' );
