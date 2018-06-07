<?php

/**
 * Functions that change the behavior of the Edit screen.
 */

/**
 * Removes the default 'author' dropdown from quick edit.
 */
function cap_remove_quick_edit_authors_box() {
	global $pagenow, $coauthors_plus;

	if ( 'edit.php' == $pagenow && $coauthors_plus->is_post_type_enabled() ) {
		remove_post_type_support( get_post_type(), $coauthors_plus->coauthor_taxonomy );
	}
}

/**
 * Add co-authors to 'authors' column on edit pages.
 *
 * @param array $post_columns
 * @return array
 */
function cap_filter_manage_posts_columns( $posts_columns ) {
	global $coauthors_plus;

	$new_columns = array();
	if ( ! $coauthors_plus->is_post_type_enabled() ) {
		return $posts_columns;
	}

	foreach ( $posts_columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'title' === $key ) {
			$new_columns['coauthors'] = __( 'Authors', 'co-authors-plus' );
		}

		if ( $coauthors_plus->coauthor_taxonomy === $key ) {
			unset( $new_columns[ $key ] );
		}
	}
	return $new_columns;
}

/**
 * Insert co-authors into post rows on Edit Page.
 *
 * @param string $column_name
 */
function cap_filter_manage_posts_custom_column( $column_name ) {
	if ( 'coauthors' === $column_name ) {
		global $post;
		$authors = get_coauthors( $post->ID );

		$count = 1;
		foreach ( $authors as $author ) :
			$args = array(
					'author_name' => $author->user_nicename,
				);
			if ( 'post' != $post->post_type ) {
				$args['post_type'] = $post->post_type;
			}
			$author_filter_url = add_query_arg( array_map( 'rawurlencode', $args ), admin_url( 'edit.php' ) );
			?>
			<a href="<?php echo esc_url( $author_filter_url ); ?>"
			data-user_nicename="<?php echo esc_attr( $author->user_nicename ) ?>"
			data-user_email="<?php echo esc_attr( $author->user_email ) ?>"
			data-display_name="<?php echo esc_attr( $author->display_name ) ?>"
			data-user_login="<?php echo esc_attr( $author->user_login ) ?>"
			><?php echo esc_html( $author->display_name ); ?></a><?php echo ( $count < count( $authors ) ) ? ',' : ''; ?>
			<?php
			$count++;
		endforeach;
	}
}

/**
 * Quick Edit co-authors box.
 */
function cap_action_quick_edit_custom_box( $column_name, $post_type ) {
	global $coauthors_plus;
	
	if ( 'coauthors' != $column_name || ! $coauthors_plus->is_post_type_enabled( $post_type ) || ! $coauthors_plus->current_user_can_set_authors() ) {
		return;
	}
	?>
	<label class="inline-edit-group inline-edit-coauthors">
		<span class="title"><?php esc_html_e( 'Authors', 'co-authors-plus' ) ?></span>
		<div id="coauthors-edit" class="hide-if-no-js">
			<p><?php echo wp_kses( __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'co-authors-plus' ), array( 'strong' => array() ) ); ?></p>
		</div>
		<?php wp_nonce_field( 'coauthors-edit', 'coauthors-nonce' ); ?>
	</label>
	<?php
}

/**
 * load-edit.php is when the screen has been set up.
 */
function cap_load_edit() {
	global $coauthors_plus;
	
	$screen = get_current_screen();
	if ( in_array( $screen->post_type, $coauthors_plus->supported_post_types ) ) {
		add_filter( 'views_' . $screen->id, 'cap_filter_views' );
	}
}

/**
 * Filter the view links that appear at the top of the Manage Posts view
 *
 * @param array $views
 * @return array
 *
 * @since 3.0
 */
function cap_filter_views( $views ) {

	if ( array_key_exists( 'mine', $views ) ) {
		return $views;
	}

	$views = array_reverse( $views );
	$all_view = array_pop( $views );
	$mine_args = array(
			'author_name'           => wp_get_current_user()->user_nicename,
		);
	if ( 'post' != get_post_type() ) {
		$mine_args['post_type'] = get_post_type();
	}
	if ( ! empty( $_REQUEST['author_name'] ) && wp_get_current_user()->user_nicename == $_REQUEST['author_name'] ) {
		$class = ' class="current"';
	} else {
		$class = '';
	}
	$views['mine'] = $view_mine = '<a' . $class . ' href="' . esc_url( add_query_arg( array_map( 'rawurlencode', $mine_args ), admin_url( 'edit.php' ) ) ) . '">' . __( 'Mine', 'co-authors-plus' ) . '</a>';

	$views['all'] = str_replace( $class, '', $all_view );
	$views = array_reverse( $views );

	return $views;
}