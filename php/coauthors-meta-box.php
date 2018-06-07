<?php

/**
 * Functions that handle the coauthors metabox.
 */ 

/**
 * Removes the standard WordPress 'Author' box.
 * We don't need it because the Co-Authors Plus one is way cooler.
 */
function cap_remove_authors_box() {
	global $coauthors_plus;
	
	if ( $coauthors_plus->is_post_type_enabled() ) {
		remove_meta_box( $coauthors_plus->coreauthors_meta_box_name, get_post_type(), 'normal' );
	}
}

/**
 * Adds a custom 'Authors' box.
 */
function cap_add_coauthors_box() {
	global $coauthors_plus;
	
	if ( $coauthors_plus->is_post_type_enabled() && $coauthors_plus->current_user_can_set_authors() ) {
		add_meta_box( $coauthors_plus->coauthors_meta_box_name, apply_filters( 'coauthors_meta_box_title', __( 'Authors', 'co-authors-plus' ) ), 'cap_coauthors_meta_box', get_post_type(), apply_filters( 'coauthors_meta_box_context', 'normal' ), apply_filters( 'coauthors_meta_box_priority', 'high' ) );
	}
}

/**
 * Callback for adding the custom 'Authors' box.
 *
 * @param object $post
 */
function cap_coauthors_meta_box( $post ) {
	global $post, $coauthors_plus, $current_screen;

	$post_id = $post->ID;

	$default_user = apply_filters( 'coauthors_default_author', wp_get_current_user() );

	// @daniel, $post_id and $post->post_author are always set when a new post is created due to auto draft,
	// and the else case below was always able to properly assign users based on wp_posts.post_author,
	// but that's not possible with force_guest_authors = true.
	if ( ! $post_id || 0 === $post_id || ( ! $post->post_author && ! $coauthors_plus->force_guest_authors ) || ( 'post' === $current_screen->base && 'add' === $current_screen->action ) ) {
		$coauthors = array();
		// If guest authors is enabled, try to find a guest author attached to this user ID
		if ( $coauthors_plus->is_guest_authors_enabled() ) {
			$coauthor = $coauthors_plus->guest_authors->get_guest_author_by( 'linked_account', $default_user->user_login );
			if ( $coauthor ) {
				$coauthors[] = $coauthor;
			}
		}
		// If the above block was skipped, or if it failed to find a guest author, use the current
		// logged in user, so long as force_guest_authors is false. If force_guest_authors = true, we are
		// OK with having an empty authoring box.
		if ( ! $coauthors_plus->force_guest_authors && empty( $coauthors ) ) {
			if ( is_array( $default_user ) ) {
				$coauthors = $default_user;
			} else {
				$coauthors[] = $default_user;
			}
		}
	} else {
		$coauthors = get_coauthors();
	}

	$count = 0;
	if ( ! empty( $coauthors ) ) :
		?>
		<div id="coauthors-readonly" class="hide-if-js">
			<ul>
			<?php
			foreach ( $coauthors as $coauthor ) :
				$count++;
				?>
				<li>
					<?php echo get_avatar( $coauthor->user_email, $coauthors_plus->gravatar_size ); ?>
					<span id="<?php echo esc_attr( 'coauthor-readonly-' . $count ); ?>" class="coauthor-tag">
						<input type="text" name="coauthorsinput[]" readonly="readonly" value="<?php echo esc_attr( $coauthor->display_name ); ?>" />
						<input type="text" name="coauthors[]" value="<?php echo esc_attr( $coauthor->user_login ); ?>" />
						<input type="text" name="coauthorsemails[]" value="<?php echo esc_attr( $coauthor->user_email ); ?>" />
						<input type="text" name="coauthorsnicenames[]" value="<?php echo esc_attr( $coauthor->user_nicename ); ?>" />
					</span>
				</li>
				<?php
			endforeach;
			?>
			</ul>
			<div class="clear"></div>
			<p><?php echo wp_kses( __( '<strong>Note:</strong> To edit post authors, please enable javascript or use a javascript-capable browser', 'co-authors-plus' ), array( 'strong' => array() ) ); ?></p>
		</div>
		<?php
	endif;
	?>

	<div id="coauthors-edit" class="hide-if-no-js">
		<p><?php echo wp_kses( __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'co-authors-plus' ), array( 'strong' => array() ) ); ?></p>
	</div>

	<?php wp_nonce_field( 'coauthors-edit', 'coauthors-nonce' );
}