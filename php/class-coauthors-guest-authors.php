<?php
/**
 * Co-Authors Guest Authors
 *
 * Key idea: Create guest authors to assign as bylines on a post without having
 * to give them access to the dashboard through a WP_User account
 */

class CoAuthors_Guest_Authors
{

	var $post_type = 'guest-author';
	var $parent_page = 'users.php';
	var $list_guest_authors_cap = 'list_users';

	public static $cache_group = 'coauthors-plus-guest-authors';

	/**
	 * Initialize our Guest Authors class and establish common hooks
	 */
	function __construct() {
		global $coauthors_plus;

		// Add the guest author management menu
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		// WP List Table for breaking out our Guest Authors
		require_once( dirname( __FILE__ ) . '/class-coauthors-wp-list-table.php' );

		// Get a co-author based on a query
		add_action( 'wp_ajax_search_coauthors_to_assign', array( $this, 'handle_ajax_search_coauthors_to_assign' ) );

		// Any CSS or JS
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

		// Extra notices
		add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );

		// Handle actions to create or delete guest author accounts
		add_action( 'admin_init', array( $this, 'handle_create_guest_author_action' ) );
		add_action( 'admin_init', array( $this, 'handle_delete_guest_author_action' ) );

		// Redirect if the user is mapped to a guest author
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );

		// Filter author links and such
		add_filter( 'author_link', array( $this, 'filter_author_link' ), 10, 3 );

		// Validate new guest authors
		add_filter( 'wp_insert_post_empty_content', array( $this, 'filter_wp_insert_post_empty_content' ), 10, 2 );

		// Add metaboxes for our guest author management interface
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 10, 2 );
		add_action( 'wp_insert_post_data', array( $this, 'manage_guest_author_filter_post_data' ), 10, 2 );
		add_action( 'save_post', array( $this, 'manage_guest_author_save_meta_fields' ), 10, 2 );

		// Empty associated caches when the guest author profile is updated
		add_filter( 'update_post_metadata', array( $this, 'filter_update_post_metadata' ), 10, 5 );

		// Modify the messages that appear when saving or creating
		add_filter( 'post_updated_messages', array( $this, 'filter_post_updated_messages' ) );

		// Allow admins to create or edit guest author profiles from the Manage Users listing
		add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );

		// Add support for featured thumbnails that we can use for guest author avatars
		add_filter( 'get_avatar', array( $this, 'filter_get_avatar' ),10 ,5 );

		// Allow users to change where this is placed in the WordPress admin
		$this->parent_page = apply_filters( 'coauthors_guest_author_parent_page', $this->parent_page );

		// Allow users to change the required cap for modifying guest authors
		$this->list_guest_authors_cap = apply_filters( 'coauthors_guest_author_manage_cap', $this->list_guest_authors_cap );

		// Set up default labels, but allow themes to modify
		$this->labels = apply_filters( 'coauthors_guest_author_labels', array(
			'singular' => __( 'Guest Author', 'co-authors-plus' ),
			'plural' => __( 'Guest Authors', 'co-authors-plus' ),
			'all_items' => __( 'All Guest Authors', 'co-authors-plus' ),
			'add_new_item' => __( 'Add New Guest Author', 'co-authors-plus' ),
			'edit_item' => __( 'Edit Guest Author', 'co-authors-plus' ),
			'new_item' => __( 'New Guest Author', 'co-authors-plus' ),
			'view_item' => __( 'View Guest Author', 'co-authors-plus' ),
			'search_items' => __( 'Search Guest Authors', 'co-authors-plus' ),
			'not_found' => __( 'No guest authors found', 'co-authors-plus' ),
			'not_found_in_trash' => __( 'No guest authors found in Trash', 'co-authors-plus' ),
			'update_item' => __( 'Update Guest Author', 'co-authors-plus' ),
			'metabox_about' => __( 'About the guest author', 'co-authors-plus' ),
		) );

		// Register a post type to store our authors that aren't WP.com users
		$args = array(
				'label' => $this->labels['singular'],
				'labels' => array(
						'name' => $this->labels['plural'],
						'singular_name' => $this->labels['singular'],
						'add_new' => _x( 'Add New', 'co-authors-plus' ),
						'all_items' => $this->labels['all_items'],
						'add_new_item' => $this->labels['add_new_item'],
						'edit_item' => $this->labels['edit_item'],
						'new_item' => $this->labels['new_item'],
						'view_item' => $this->labels['view_item'],
						'search_items' => $this->labels['search_items'],
						'not_found' => $this->labels['not_found'],
						'not_found_in_trash' => $this->labels['not_found_in_trash'],
					),
				'public' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'supports' => array(
						'thumbnail',
					),
				'taxonomies' => array(
						$coauthors_plus->coauthor_taxonomy,
					),
				'rewrite' => false,
				'query_var' => false,
			);
		register_post_type( $this->post_type, $args );

		// Some of the common sizes used by get_avatar
		$this->avatar_sizes = array(
				32,
				64,
				96,
				128
			);
		$this->avatar_sizes = apply_filters( 'coauthors_guest_author_avatar_sizes', $this->avatar_sizes );
		foreach( $this->avatar_sizes as $size ) {
			add_image_size( 'guest-author-' . $size, $size, $size, true );
		}

		// Hacky way to remove the title and the editor
		remove_post_type_support( $this->post_type, 'title' );
		remove_post_type_support( $this->post_type, 'editor' );

	}

	/**
	 * Filter the messages that appear when saving or updating a guest author
	 *
	 * @since 3.0
	 */
	function filter_post_updated_messages( $messages ) {
		global $post;

		$guest_author = $this->get_guest_author_by( 'ID', $post->ID );
		$guest_author_link = $this->filter_author_link( '', $guest_author->ID, $guest_author->user_nicename );

		$messages[$this->post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Guest author updated. <a href="%s">View profile</a>', 'co-authors-plus' ), esc_url( $guest_author_link ) ),
			2 => __( 'Custom field updated.', 'co-authors-plus' ),
			3 => __( 'Custom field deleted.', 'co-authors-plus' ),
			4 => __( 'Guest author updated.', 'co-authors-plus' ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __( 'Guest author restored to revision from %s', 'co-authors-plus' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Guest author updated. <a href="%s">View profile</a>', 'co-authors-plus' ), esc_url( $guest_author_link ) ),
			7 => __( 'Guest author saved.', 'co-authors-plus' ),
			8 => sprintf( __( 'Guest author submitted. <a target="_blank" href="%s">Preview profile</a>', 'co-authors-plus' ), esc_url( add_query_arg( 'preview', 'true', $guest_author_link ) ) ),
			9 => sprintf( __( 'Guest author scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview profile</a>', 'co-authors-plus' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $guest_author_link ) ),
			10 => sprintf( __('Guest author updated. <a target="_blank" href="%s">Preview profile</a>', 'co-authors-plus' ), esc_url( add_query_arg( 'preview', 'true', $guest_author_link ) ) ),
		);
		return $messages;
	}

	/**
	 * Handle the admin action to create a guest author based
	 * on an existing WordPress user
	 *
	 * @since 3.0
	 */
	function handle_create_guest_author_action() {

		if ( !isset( $_GET['action'], $_GET['nonce'], $_GET['user_id'] ) || $_GET['action'] != 'cap-create-guest-author' )
			return;

		if ( !wp_verify_nonce( $_GET['nonce'], 'create-guest-author' ) )
			wp_die( __( "Doin' something fishy, huh?", 'co-authors-plus' ) );

		if ( ! current_user_can( $this->list_guest_authors_cap ) )
			wp_die( __( "You don't have permission to perform this action.", 'co-authors-plus' ) );

		$user_id = intval( $_GET['user_id'] );

		// Create the guest author
		$post_id = $this->create_guest_author_from_user_id( $user_id );
		if ( is_wp_error( $post_id ) )
			wp_die( $post_id->get_error_message() );

		// Redirect to the edit Guest Author screen
		$edit_link = get_edit_post_link( $post_id, 'redirect' );
		$redirect_to = add_query_arg( 'message', 'guest-author-created', $edit_link );
		wp_safe_redirect( $redirect_to );
		exit;

	}

	/**
	 * Handle the admin action to delete a guest author and possibly reassign their posts
	 *
	 * @since 3.0
	 */
	function handle_delete_guest_author_action() {
		global $coauthors_plus;

		if ( !isset( $_POST['action'], $_POST['reassign'], $_POST['_wpnonce'], $_POST['id'] ) || 'delete-guest-author' != $_POST['action'] )
			return;

		// Verify the user is who they say they are
		if ( !wp_verify_nonce( $_POST['_wpnonce'], 'delete-guest-author' ) )
			wp_die( __( "Doin' something fishy, huh?", 'co-authors-plus' ) );

		// Make sure they can perform the action
		if ( ! current_user_can( $this->list_guest_authors_cap ) )
			wp_die( __( "You don't have permission to perform this action.", 'co-authors-plus' ) );

		// Make sure the guest author actually exists
		$guest_author = $this->get_guest_author_by( 'ID', (int)$_POST['id'] );
		if ( ! $guest_author )
			wp_die( __( "Guest author can't be deleted because it doesn't exist.", 'co-authors-plus' ) );

		// Perform the reassignment if needed
		$guest_author_term = $coauthors_plus->get_author_term( $guest_author );
		switch( $_POST['reassign'] ) {
			// Leave assigned to the current linked account
			case 'leave-assigned':
				$reassign_to = $guest_author->linked_account;
				break;
			// Reassign to a different user
			case 'reassign-another':
				$user_nicename = sanitize_title( $_POST['leave-assigned-to'] );
				$reassign_to = $coauthors_plus->get_coauthor_by( 'user_nicename', $user_nicename );
				if ( ! $reassign_to )
					wp_die( __( 'Co-author does not exists. Try again?', 'co-authors-plus' ) );
				$reassign_to = $reassign_to->user_login;
				break;
			// Remove the byline, but don't delete the post
			case 'remove-byline':
				$reassign_to = false;
				break;
			default:
				wp_die( __( "Please make sure to pick an option.", 'co-authors-plus' ) );
				break;
		}

		$retval = $this->delete( $guest_author->ID, $reassign_to );

		$args = array(
				'page'        => 'view-guest-authors',
			);
		if ( is_wp_error( $retval ) )
			$args['message'] = 'delete-error';
		else
			$args['message'] = 'guest-author-deleted';

		// Redirect to safety
		$redirect_to = add_query_arg( $args, admin_url( $this->parent_page ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Given a search query, suggest some co-authors that might match it
	 *
	 * @since 3.0
	 */
	function handle_ajax_search_coauthors_to_assign() {
		global $coauthors_plus;

		if ( ! current_user_can( $this->list_guest_authors_cap ) )
			die();

		$search = sanitize_text_field( $_GET['q'] );
		if ( ! empty( $_GET['guest_author'] ) )
			$ignore = array( $this->get_guest_author_by( 'ID', (int)$_GET['guest_author'] )->user_login );
		else
			$ignore = array();

		$results = wp_list_pluck( $coauthors_plus->search_authors( $search, $ignore ), 'user_login' );
		$retval = array();
		foreach( $results as $user_login ) {
			$coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $user_login );
			$retval[] = (object)array(
					'display_name'       => $coauthor->display_name,
					'user_login'         => $coauthor->user_login,
					'id'                 => $coauthor->user_nicename,
				);
		}
		echo json_encode( $retval );
		die();
	}


	/**
	 * Some redirection we need to do for linked accounts
	 *
	 * @todo support author ID query vars
	 */
	function action_parse_request( $query ) {

		if ( !isset( $query->query_vars['author_name'] ) )
			return $query;

		$coauthor = $this->get_guest_author_by( 'linked_account', sanitize_title( $query->query_vars['author_name'] ) );
		if ( is_object( $coauthor ) && $query->query_vars['author_name'] != $coauthor->linked_account ) {
			global $wp_rewrite;
			$link = $wp_rewrite->get_author_permastruct();

			if ( empty($link) ) {
				$file = home_url( '/' );
				$link = $file . '?author_name=' . $coauthor->user_login;
			} else {
				$link = str_replace('%author%', $coauthor->user_login, $link);
				$link = home_url( user_trailingslashit( $link ) );
			}
			wp_safe_redirect( $link );
			exit;
		}

		return $query;
	}

	/**
	 * Add the admin menus for seeing all co-authors
	 *
	 * @since 3.0
	 */
	function action_admin_menu() {

		add_submenu_page( $this->parent_page, $this->labels['plural'], $this->labels['plural'], $this->list_guest_authors_cap, 'view-guest-authors', array( $this, 'view_guest_authors_list' ) );

	}

	/**
	 * Enqueue any scripts or styles used for Guest Authors
	 *
	 * @since 3.0
	 */
	function action_admin_enqueue_scripts() {
		global $pagenow;
		// Enqueue our guest author CSS on the related pages
		if ( $this->parent_page == $pagenow && isset( $_GET['page'] ) && $_GET['page'] == 'view-guest-authors' ) {
			wp_enqueue_script( 'jquery-select2', COAUTHORS_PLUS_URL . 'lib/select2/select2.min.js', array( 'jquery' ), COAUTHORS_PLUS_VERSION );
			wp_enqueue_style( 'cap-jquery-select2-css', COAUTHORS_PLUS_URL . 'lib/select2/select2.css', false, COAUTHORS_PLUS_VERSION );

			wp_enqueue_style( 'guest-authors-css', COAUTHORS_PLUS_URL . 'css/guest-authors.css', false, COAUTHORS_PLUS_VERSION );
			wp_enqueue_script( 'guest-authors-js', COAUTHORS_PLUS_URL . 'js/guest-authors.js', false, COAUTHORS_PLUS_VERSION );
		} else if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) && $this->post_type == get_post_type() ) {
			add_action( 'admin_head', array( $this, 'change_title_icon' ) );
		}
	}

	/**
	 * Change the icon appearing next to the title
	 * Core doesn't allow us to filter screen_icon(), so changing the ID is the next best thing
	 *
	 * @since 3.0.1
	 */
	function change_title_icon() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#icon-edit').attr('id', 'icon-users');
			});
		</script>
		<?php
	}

	/**
	 * Show some extra notices to the user
	 *
	 * @since 3.0
	 */
	function action_admin_notices() {
		global $pagenow;

		if ( $this->parent_page != $pagenow || ! isset( $_REQUEST['message'] ) )
			return;

		switch( $_REQUEST['message'] ) {
			case 'guest-author-deleted':
				$message = __( 'Guest author deleted.', 'co-authors-plus' );
				break;
			default:
				$message = false;
				break;
		}

		if ( $message )
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';

	}

	/**
	 * Register the metaboxes used for Guest Authors
	 *
	 * @since 3.0
	 */
	function action_add_meta_boxes() {
		global $coauthors_plus;

		if ( get_post_type() == $this->post_type ) {
			// Remove the submitpost metabox because we have our own
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
			remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
			add_meta_box( 'coauthors-manage-guest-author-save', __( 'Save', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_save' ), $this->post_type, 'side', 'default' );
			add_meta_box( 'coauthors-manage-guest-author-slug', __( 'Unique Slug', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_slug' ), $this->post_type, 'side', 'default' );
			// Our metaboxes with co-author details
			add_meta_box( 'coauthors-manage-guest-author-name', __( 'Name', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_name' ), $this->post_type, 'normal', 'default' );
			add_meta_box( 'coauthors-manage-guest-author-contact-info', __( 'Contact Info', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_contact_info' ), $this->post_type, 'normal', 'default' );
			add_meta_box( 'coauthors-manage-guest-author-bio', $this->labels['metabox_about'], array( $this, 'metabox_manage_guest_author_bio' ), $this->post_type, 'normal', 'default' );
		}
	}

	/**
	 * View a list table of all guest authors
	 *
	 * @since 3.0
	 */
	function view_guest_authors_list() {

		// Allow guest authors to be deleted
		if ( isset( $_GET['action'], $_GET['id'], $_GET['_wpnonce'] ) && 'delete' == $_GET['action'] ) {
			// Make sure the user is who they say they are
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'guest-author-delete' ) )
				wp_die( __( "Doin' something fishy, huh?", 'co-authors-plus' ) );

			// Make sure the guest author actually exists
			$guest_author = $this->get_guest_author_by( 'ID', (int)$_GET['id'] );
			if ( ! $guest_author )
				wp_die( __( "Guest author can't be deleted because it doesn't exist.", 'co-authors-plus' ) );

			echo '<div class="wrap">';
			echo '<div class="icon32" id="icon-users"><br/></div>';
			echo '<h2>' . sprintf( __( 'Delete %s', 'co-authors-plus ' ), $this->labels['plural'] ) . '</h2>';
			echo '<p>' . __( 'You have specified this guest author for deletion:', 'co-authors-plus' ) . '</p>';
			echo '<p>#' . $guest_author->ID . ': ' . esc_html( $guest_author->display_name ) . '</p>';
			echo '<p>' . __( "What should be done with posts assigned to this guest author?", 'co-authors-plus' ) . '</p>';
			echo '<p class="description">' . __( "Note: If you'd like to delete the guest author and all of their posts, you should delete their posts first and then come back to delete the guest author.", 'co-authors-plus' ) . '</p>';
			echo '<form method="POST" action="' . esc_url( add_query_arg( 'page', 'view-guest-authors', admin_url( $this->parent_page ) ) ) . '">';
			// Hidden stuffs
			echo '<input type="hidden" name="action" value="delete-guest-author" />';
			wp_nonce_field( 'delete-guest-author' );
			echo '<input type="hidden" id="id" name="id" value="' . esc_attr( (int)$_GET['id'] ) . '" />';
			echo '<fieldset><ul style="list-style-type:none;">';
			// Reassign to another user
			echo '<li class="hide-if-no-js"><label for="reassign-another">';
			echo '<input type="radio" id="reassign-another" name="reassign" class="reassign-option" value="reassign-another" />&nbsp;&nbsp;' . __( 'Reassign to another co-author:', 'co-authors-plus' ) . '&nbsp;&nbsp;</label>';
			echo '<input type="hidden" id="leave-assigned-to" name="leave-assigned-to" style="width:200px;" />';
			echo '</li>';
			// Leave mapped to a linked account
			if ( get_user_by( 'login', $guest_author->linked_account ) ) {
				echo '<li><label for="leave-assigned">';
				echo '<input type="radio" id="leave-assigned" class="reassign-option" name="reassign" value="leave-assigned" />&nbsp;&nbsp;' . sprintf( __( 'Leave posts assigned to the mapped user, %s.', 'co-authors-plus' ), $guest_author->linked_account );
				echo '</label></li>';
			}
			// Remove bylines from the posts
			echo '<li><label for="remove-byline">';
			echo '<input type="radio" id="remove-byline" class="reassign-option" name="reassign" value="remove-byline" />&nbsp;&nbsp;' . __( 'Remove byline from posts (but leave each post in its current status).', 'co-authors-plus' );
			echo '</label></li>';
			echo '</ul></fieldset>';
			submit_button( __( 'Confirm Deletion', 'co-authors-plus' ), 'secondary', 'submit', true, array( 'disabled' => 'disabled' ) );
			echo '</form>';
			echo '</div>';
		} else {
			echo '<div class="wrap">';
			echo '<div class="icon32" id="icon-users"><br/></div>';
			echo '<h2>' . $this->labels['plural'];
			// @todo caps check for creating a new user
			$add_new_link = admin_url( "post-new.php?post_type=$this->post_type" );
			echo '<a href="' . $add_new_link . '" class="add-new-h2">' . esc_html( __( 'Add New', 'co-authors-plus' ) ) . '</a>';
			echo '</h2>';
			$cap_list_table = new CoAuthors_WP_List_Table();
			$cap_list_table->prepare_items();
			echo '<form id="guest-authors-filter" action="" method="GET">';
			echo '<input type="hidden" name="page" value="view-guest-authors" />';
			$cap_list_table->display();
			echo '</form>';
			echo '</div>';
		}

	}

	/**
	 * Metabox for saving or updating a Guest Author
	 *
	 * @since 3.0
	 */
	function metabox_manage_guest_author_save() {
		global $post, $coauthors_plus;

		if ( in_array( $post->post_status, array( 'pending', 'publish', 'draft' ) ) )
			$button_text = $this->labels['update_item'];
		else
			$button_text = $this->labels['add_new_item'];
		submit_button( $button_text, 'primary', 'publish', false );

		// Secure all of our requests
		wp_nonce_field( 'guest-author-nonce', 'guest-author-nonce' );

	}

	/**
	 * Metabox for editing this guest author's slug or changing the linked account
	 *
	 * @since 3.0
	 */
	function metabox_manage_guest_author_slug() {
		global $post;

		$pm_key = $this->get_post_meta_key( 'user_login' );
		$existing_slug = get_post_meta( $post->ID, $pm_key, true );

		echo '<input type="text" disabled="disabled" name="' . esc_attr( $pm_key ) . '" value="' . esc_attr( urldecode( $existing_slug ) ) . '" />';

		// Taken from grist_authors.
		$linked_account_key = $this->get_post_meta_key( 'linked_account' );
		$linked_account = get_post_meta( $post->ID, $linked_account_key, true );
		if ( $user = get_user_by( 'login', $linked_account ) )
			$linked_account_id = $user->ID;
		else
			$linked_account_id = -1;

		// If user_login is the same as linked account, don't let the association be removed
		if ( $linked_account == $existing_slug )
			add_filter( 'wp_dropdown_users', array( $this, 'filter_wp_dropdown_users_to_disable' ) );

		$linked_account_user_ids = wp_list_pluck( $this->get_all_linked_accounts(), 'ID' );
		if ( false !== ( $key = array_search( $linked_account_id, $linked_account_user_ids ) ) )
			unset( $linked_account_user_ids[$key] );

		echo '<p><label>' . __( 'WordPress User Mapping', 'co-authors-plus' ) . '</label> ';
		wp_dropdown_users( array(
			'show_option_none' => __( '-- Not mapped --', 'co-authors-plus' ),
			'name' => esc_attr( $this->get_post_meta_key( 'linked_account' ) ),
			// If we're adding an author or if there is no post author (0), then use -1 (which is show_option_none).
			// We then take -1 on save and convert it back to 0. (#blamenacin)
			'selected' => $linked_account_id,
			// Don't let user accounts to be linked to more than one guest author
			'exclude'  => $linked_account_user_ids,
		) );
		echo '</p>';

		remove_filter( 'wp_dropdown_users', array( $this, 'filter_wp_dropdown_users_to_disable' ) );
	}

	/**
	 * Make a wp_dropdown_users disabled
	 * Only applied if the user_login value for the guest author matches its linked account
	 *
	 * @since 3.0
	 */
	public function filter_wp_dropdown_users_to_disable( $output ) {
		return str_replace( '<select ', '<select disabled="disabled" ', $output );
	}

	/**
	 * Metabox to display all of the pertient names for a Guest Author without a user account
	 *
	 * @since 3.0
	 */
	function metabox_manage_guest_author_name() {
		global $post;

		$fields = $this->get_guest_author_fields( 'name' );
		echo '<table class="form-table"><tbody>';
		foreach( $fields as $field ) {
			$pm_key = $this->get_post_meta_key( $field['key'] );
			$value = get_post_meta( $post->ID, $pm_key, true );
			echo '<tr><th>';
			echo '<label for="' . esc_attr( $pm_key ) . '">' . $field['label'] . '</label>';
			echo '</th><td>';
			echo '<input type="text" name="' . esc_attr( $pm_key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			echo '</td></tr>';
		}
		echo '</tbody></table>';

	}

	/**
	 * Metabox to display all of the pertient contact details for a Guest Author without a user account
	 *
	 * @since 3.0
	 */
	function metabox_manage_guest_author_contact_info() {
		global $post;

		$fields = $this->get_guest_author_fields( 'contact-info' );
		echo '<table class="form-table"><tbody>';
		foreach( $fields as $field ) {
			$pm_key = $this->get_post_meta_key( $field['key'] );
			$value = get_post_meta( $post->ID, $pm_key, true );
			echo '<tr><th>';
			echo '<label for="' . esc_attr( $pm_key ) . '">' . $field['label'] . '</label>';
			echo '</th><td>';
			echo '<input type="text" name="' . esc_attr( $pm_key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			echo '</td></tr>';
		}
		echo '</tbody></table>';

	}

	/**
	 * Metabox to edit the bio and other biographical details of the Guest Author
	 *
	 * @since 3.0
	 */
	function metabox_manage_guest_author_bio() {
		global $post;

		$fields = $this->get_guest_author_fields( 'about' );
		echo '<table class="form-table"><tbody>';
		foreach( $fields as $field ) {
			$pm_key = $this->get_post_meta_key( $field['key'] );
			$value = get_post_meta( $post->ID, $pm_key, true );
			echo '<tr><th>';
			echo '<label for="' . esc_attr( $pm_key ) . '">' . $field['label'] . '</label>';
			echo '</th><td>';
			echo '<textarea style="width:300px;margin-bottom:6px;" name="' . esc_attr( $pm_key ) . '">' . esc_textarea( $value ) . '</textarea>';
			echo '</td></tr>';
		}
		echo '</tbody></table>';

	}

	/**
	 * When a guest author is created or updated, we need to properly create
	 * the post_name based on some data provided by the user
	 *
	 * @since 3.0
	 */
	function manage_guest_author_filter_post_data( $post_data, $original_args ) {

		if ( $post_data['post_type'] != $this->post_type )
			return $post_data;

		// @todo caps check
		if ( !isset( $_POST['guest-author-nonce'] ) || !wp_verify_nonce( $_POST['guest-author-nonce'], 'guest-author-nonce' ) )
			return $post_data;

		$post_data['post_title'] = sanitize_text_field( $_POST['cap-display_name'] );
		$slug = sanitize_title( get_post_meta( $original_args['ID'], $this->get_post_meta_key( 'user_login' ), true ) );
		if ( ! $slug )
			$slug = sanitize_title( $_POST['cap-display_name'] );
		// Uh oh, no guest authors without slugs
		if ( ! $slug )
			wp_die( __( 'Guest authors cannot be created without display names.', 'co-authors-plus' ) );
		$post_data['post_name'] = $this->get_post_meta_key( $slug );

		// Guest authors can't be created with the same user_login as a user
		$user_nicename = str_replace( 'cap-', '', $slug );
		$user = get_user_by( 'slug', $user_nicename );
		if ( $user 
			&& is_user_member_of_blog( $user->ID, get_current_blog_id() )
			&& $user->user_login != get_post_meta( $original_args['ID'], $this->get_post_meta_key( 'linked_account' ), true ) )
			wp_die( __( 'Guest authors cannot be created with the same user_login value as a user. Try creating a profile from the user on the Manage Users listing instead.', 'co-authors-plus' ) );

		// Guest authors can't have the same post_name value
		$guest_author = $this->get_guest_author_by( 'post_name', $post_data['post_name'] );
		if ( $guest_author && $guest_author->ID != $original_args['ID'] )
			wp_die( __( 'Display name conflicts with another guest author display name.', 'co-authors-plus' ) );

		return $post_data;
	}

	/**
	 * Save the various meta fields associated with our guest author model
	 *
	 * @since 3.0
	 */
	function manage_guest_author_save_meta_fields( $post_id, $post ) {
		global $coauthors_plus;

		if ( $post->post_type != $this->post_type )
			return;

		// @todo caps check
		if ( !isset( $_POST['guest-author-nonce'] ) || !wp_verify_nonce( $_POST['guest-author-nonce'], 'guest-author-nonce' ) )
			return;

		// Save our data to post meta
		$author_fields = $this->get_guest_author_fields();
		foreach( $author_fields as $author_field ) {

			$key = $this->get_post_meta_key( $author_field['key'] );
			// 'user_login' should only be saved on post update if it doesn't exist
			if ( 'user_login' == $author_field['key'] && !get_post_meta( $post_id, $key, true ) ) {
				$display_name_key = $this->get_post_meta_key( 'display_name' );
				$temp_slug = sanitize_title( $_POST[$display_name_key] );
				update_post_meta( $post_id, $key, $temp_slug );
				continue;
			}
			if ( 'linked_account' == $author_field['key'] ) {
				$linked_account_key = $this->get_post_meta_key( 'linked_account' );
				if ( ! empty( $_POST[$linked_account_key] ) )
					$user_id = intval( $_POST[$linked_account_key] );
				else
					continue;
				$user = get_user_by( 'id', $user_id );
				if ( $user_id > 0 && is_object( $user ) )
					$user_login = $user->user_login;
				else
					$user_login = '';
				update_post_meta( $post_id, $key, $user_login );
				continue;
			}
			if ( !isset( $_POST[$key] ) )
				continue;
			if ( isset( $author_field['sanitize_function'] ) && function_exists( $author_field['sanitize_function'] ) )
				$value = $author_field['sanitize_function']( $_POST[$key] );
			else
				$value = sanitize_text_field( $_POST[$key] );
			update_post_meta( $post_id, $key, $value );
		}

		$author = $this->get_guest_author_by( 'ID', $post_id );
		$author_term = $coauthors_plus->update_author_term( $author );
		// Add the author as a post term
		wp_set_post_terms( $post_id, array( $author_term->slug ), $coauthors_plus->coauthor_taxonomy, false );
	}

	/**
	 * Return a simulated WP_User object based on the post ID
	 * of a guest author
	 *
	 * @since 3.0
	 *
	 * @param string $key Key to search by (login,email)
	 * @param string $value Value to search for
	 * @param object|false $coauthor The guest author on success, false on failure
	 */
	function get_guest_author_by( $key, $value, $force = false ) {
		global $wpdb;

		$cache_key = md5( 'guest-author-' . $key . '-' . $value );
		if ( false == $force && false !== ( $retval = wp_cache_get( $cache_key, self::$cache_group ) ) )
			return $retval;

		switch( $key ) {
			case 'ID':
			case 'id':
				$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID=%d", $value );
				$post_id = $wpdb->get_var( $query );
				if ( empty( $post_id ) )
					return false;
				break;
			case 'user_nicename':
			case 'post_name':
				$value = $this->get_post_meta_key( $value );
				$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name=%s AND post_type = %s", $value, $this->post_type );
				$post_id = $wpdb->get_var( $query );
				if ( empty( $post_id ) )
					return false;
				break;
			case 'login':
			case 'user_login':
			case 'linked_account':
			case 'user_email':
				if ( 'login' == $key )
					$key = 'user_login';
				// Ensure we aren't doing the lookup by the prefixed value
				if ( 'user_login' == $key )
					$value = preg_replace( '#^cap\-#', '', $value );
				$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s;", $this->get_post_meta_key( $key ), $value );
				$post_id = $wpdb->get_var( $query );
				if ( empty( $post_id ) ) {
					if ( 'user_login' == $key )
						return $this->get_guest_author_by( 'post_name', $value ); // fallback to post_name in case the guest author isn't a linked account
					return false;
				}
				break;
			default:
				$post_id = false;
				break;
		}

		if ( !$post_id )
			return false;

		$guest_author = array(
			'ID' => $post_id,
		);

		// Load the guest author fields
		$fields = $this->get_guest_author_fields();
		foreach( $fields as $field ) {
			$key = $field['key'];
			$pm_key = $this->get_post_meta_key( $field['key'] );
			$guest_author[$key] = get_post_meta( $post_id, $pm_key, true );
		}
		// Support for non-Latin characters. They're stored as urlencoded slugs
		$guest_author['user_login'] = urldecode( $guest_author['user_login'] );

		// Hack to model the WP_User object
		$guest_author['user_nicename'] = sanitize_title( $guest_author['user_login'] );
		$guest_author['type'] = 'guest-author';

		wp_cache_set( $cache_key, (object)$guest_author, self::$cache_group );

		return (object)$guest_author;
	}

	/**
	 * Get all of the meta fields that can be associated with a guest author
	 *
	 * @since 3.0
	 */
	function get_guest_author_fields( $groups = 'all' ) {

		$groups = (array)$groups;
		$global_fields = array(
				// Hidden (included in object, no UI elements)
				array(
						'key'      => 'ID',
						'label'    => __( 'ID', 'co-authors-plus' ),
						'group'    => 'hidden',
					),
				// Name
				array(
						'key'      => 'display_name',
						'label'    => __( 'Display Name', 'co-authors-plus'),
						'group'    => 'name',
						'required' => true,
					),
				array(
						'key'      => 'first_name',
						'label'    => __( 'First Name', 'co-authors-plus'),
						'group'    => 'name',
					),
				array(
						'key'      => 'last_name',
						'label'    => __( 'Last Name', 'co-authors-plus'),
						'group'    => 'name',
					),
				array(
						'key'      => 'user_login',
						'label'    => __( 'Slug', 'co-authors-plus'),
						'group'    => 'slug',
						'required' => true,
					),
				// Contact info
				array(
						'key'      => 'user_email',
						'label'    => __( 'E-mail', 'co-authors-plus' ),
						'group'    => 'contact-info',
					),
				array(
						'key'      => 'linked_account',
						'label'    => __( 'Linked Account', 'co-authors-plus' ),
						'group'    => 'slug',
					),
				array(
						'key'      => 'website',
						'label'    => __( 'Website', 'co-authors-plus' ),
						'group'    => 'contact-info',
					),
				array(
						'key'      => 'aim',
						'label'    => __( 'AIM', 'co-authors-plus' ),
						'group'    => 'contact-info',
					),
				array(
						'key'      => 'yahooim',
						'label'    => __( 'Yahoo IM', 'co-authors-plus' ),
						'group'    => 'contact-info',
					),
				array(
						'key'      => 'jabber',
						'label'    => __( 'Jabber / Google Talk', 'co-authors-plus' ),
						'group'    => 'contact-info',
					),
				array(
						'key'      => 'description',
						'label'    => __( 'Biographical Info', 'co-authors-plus' ),
						'group'    => 'about',
						'sanitize_function' => 'wp_filter_post_kses',
					),
			);
		$fields_to_return = array();
		foreach( $global_fields as $single_field ) {
			if ( in_array( $single_field['group'], $groups ) || $groups[0] == 'all' && $single_field['group'] != 'hidden' )
				$fields_to_return[] = $single_field;
		}

		return apply_filters( 'coauthors_guest_author_fields', $fields_to_return, $groups );

	}

	/**
	 * Gets a postmeta key by prefixing it with 'cap-'
	 * if not yet prefixed
	 *
	 * @since 3.0
	 */
	function get_post_meta_key( $key ) {

		if ( 0 !== stripos( $key, 'cap-' ) )
			$key = 'cap-' . $key;

		return $key;
	}

	/**
	 * Get all of the user accounts that have been linked
	 *
	 * @since 3.0
	 */
	function get_all_linked_accounts( $force = false ) {
		global $wpdb;

		$cache_key = 'all-linked-accounts';
		$retval = wp_cache_get( $cache_key, self::$cache_group );

		if ( true === $force || false === $retval ) {
			$user_logins = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value !=''", $this->get_post_meta_key( 'linked_account' ) ) );
			$users = array();
			foreach( $user_logins as $user_login ) {
				$user = get_user_by( 'login', $user_login );
				if ( ! $user )
					continue;
				$users[] = array(
						'ID'              => $user->ID,
						'user_login'      => $user->user_login,
					);
			}
			$retval = $users;
			wp_cache_set( $cache_key, $retval, self::$cache_group );
		}
		return ( $retval ) ? $retval : array();
	}

	/**
	 * Filter update post metadata
	 * Clean caches when any of the values have been changed
	 *
	 * @since 3.0
	 */
	function filter_update_post_metadata( $retnull, $object_id, $meta_key, $meta_value, $prev_value ) {

		if ( $this->post_type != get_post_type( $object_id ) )
			return null;

		// If the linked_account is changing, invalidate the cache of all linked accounts
		// Don't regenerate though, as we haven't saved the new value
		$linked_account_key = $this->get_post_meta_key( 'linked_account' );
		if ( $linked_account_key == $meta_key && $meta_value != get_post_meta( $object_id, $linked_account_key, true ) ) {
			$this->delete_guest_author_cache( $object_id );
		}

		// If one of the guest author meta values has changed, we'll need to invalidate all keys
		if ( false !== strpos( $meta_key, 'cap-' ) && $meta_value != get_post_meta( $object_id, $meta_key, true ) ) {
			$this->delete_guest_author_cache( $object_id );
		}

		return null;
	}

	/**
	 * Delete all of the cache values associated with a guest author
	 *
	 * @since 3.0
	 *
	 * @param int|object $guest_author The guest author ID or object
	 */
	public function delete_guest_author_cache( $id_or_object ) {

		if ( is_object( $id_or_object ) )
			$guest_author = $id_or_object;
		else
			$guest_author = $this->get_guest_author_by( 'ID', $id_or_object );

		// Delete the lookup cache associated with each old co-author value
		$keys = wp_list_pluck( $this->get_guest_author_fields(), 'key' );
		$keys = array_merge( $keys, array( 'login', 'post_name', 'user_nicename', 'ID' ) );
		foreach( $keys as $key ) {
			if ( 'post_name' == $key )
				$key = 'user_nicename';
			else if ( 'login' == $key )
				$key = 'user_login';
			$cache_key = md5( 'guest-author-' . $key . '-' . $guest_author->$key );
			wp_cache_delete( $cache_key, self::$cache_group );
		}

		// Delete the 'all-linked-accounts' cache
		wp_cache_delete( 'all-linked-accounts', self::$cache_group );

	} 


	/**
	 * Create a guest author
	 *
	 * @since 3.0
	 */
	function create( $args ) {
		global $coauthors_plus;

		// Validate the arguments that have been passed
		$fields = $this->get_guest_author_fields();
		foreach( $fields as $field ) {

			// Make sure required fields are there
			if ( isset( $field['required'] ) && $field['required'] && empty( $args[$field['key']] ) ) {
				return new WP_Error( 'field-required', sprintf( __( '%s is a required field', 'co-authors-plus' ), $field['key'] ) );
			}

			// The user login field shouldn't collide with any existing users
			if ( 'user_login' == $field['key'] && $existing_coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $args['user_login'], true ) ) {
				if ( 'guest-author' == $existing_coauthor->type )
					return new WP_Error( 'duplicate-field', __( 'user_login cannot duplicate existing guest author or mapped user', 'co-authors-plus' ) );
			}

		}

		// Create the primary post object
		$new_post = array(
				'post_title'      => $args['display_name'],
				'post_name'       => sanitize_title( $this->get_post_meta_key( $args['user_login'] ) ),
				'post_type'       => $this->post_type,
			);
		$post_id = wp_insert_post( $new_post, true );
		if ( is_wp_error( $post_id ) )
			return $post_id;

		// Add all of the fields for the new guest author
		foreach( $fields as $field ) {
			$key = $field['key'];
			if ( empty( $args[$key] ) )
				continue;
			$pm_key = $this->get_post_meta_key( $key );
			update_post_meta( $post_id, $pm_key, $args[$key] );
		}

		// Make sure the author term exists and that we're assigning it to this post type
		$author_term = $coauthors_plus->update_author_term( $this->get_guest_author_by( 'ID', $post_id ) );
		wp_set_post_terms( $post_id, array( $author_term->slug ), $coauthors_plus->coauthor_taxonomy, false );

		return $post_id;
	}

	/**
	 * Delete a guest author
	 *
	 * @since 3.0
	 *
	 * @param int $post_id The ID for the guest author profile
	 * @param string $reassign_to User login value for the co-author to reassign posts to
	 * @return bool|WP_Error $success True on success, WP_Error on a failure
	 */
	public function delete( $id, $reassign_to = false ) {
		global $coauthors_plus;

		$guest_author = $this->get_guest_author_by( 'ID', $id );
		if ( ! $guest_author )
			return new WP_Error( 'guest-author-missing', __( 'Guest author does not exist', 'co-authors-plus' ) );

		$guest_author_term = $coauthors_plus->get_author_term( $guest_author );

		if ( $reassign_to ) {

			// We're reassigning the guest author's posts user to its linked account
			if ( $guest_author->linked_account == $reassign_to )
				$reassign_to_author = get_user_by( 'login', $reassign_to );
			else 
				$reassign_to_author = $coauthors_plus->get_coauthor_by( 'user_login', $reassign_to );
			if ( ! $reassign_to_author )
				return new WP_Error( 'reassign-to-missing', __( 'Reassignment co-author does not exist', 'co-authors-plus' ) );

			$reassign_to_term = $coauthors_plus->get_author_term( $reassign_to_author );
			// In the case where the guest author and its linked account shared the same term, we don't want to reassign
			if ( $guest_author_term->term_id != $reassign_to_term->term_id )
				wp_delete_term( $guest_author_term->term_id, $coauthors_plus->coauthor_taxonomy, array( 'default' => $reassign_to_term->term_id, 'force_default' => true ) );

		} else {
			wp_delete_term( $guest_author_term->term_id, $coauthors_plus->coauthor_taxonomy );
		}

		// Delete the guest author profile
		wp_delete_post( $guest_author->ID, true );

		// Make sure all of the caches are reset
		$this->delete_guest_author_cache( $guest_author );
		return true;
	}


	/**
	 * Create a guest author from an existing WordPress user
	 *
	 * @since 3.0
	 *
	 * @param int $user_id ID for a WordPress user
	 * @return int|WP_Error $retval ID for the new guest author on success, WP_Error on failure
	 */
	function create_guest_author_from_user_id( $user_id ) {

		$user = get_user_by( 'id', $user_id );
		if ( ! $user )
			return new WP_Error( 'invalid-user', __( 'No user exists with that ID', 'co-authors-plus' ) );

		$guest_author = array();
		foreach( $this->get_guest_author_fields() as $field ) {
			$key = $field['key'];
			if ( ! empty( $user->$key ) )
				$guest_author[$key] = $user->$key;
			else
				$guest_author[$key] = '';
		}
		// Don't need the old user ID
		unset( $guest_author['ID'] );
		// Retain the user mapping and try to produce an unique user_login based on the name.
		$guest_author['linked_account'] = $guest_author['user_login'];
		if ( ! empty( $guest_author['display_name'] ) && $guest_author['display_name'] != $guest_author['user_login'] )
			$guest_author['user_login'] = sanitize_title( $guest_author['display_name'] );
		else if ( ! empty( $guest_author['first_name'] ) && ! empty( $guest_author['last_name'] ) )
			$guest_author['user_login'] = sanitize_title( $guest_author['first_name'] . ' ' . $guest_author['last_name'] );

		$retval = $this->create( $guest_author );
		return $retval;
	}

	/**
	 * Guest authors must have Display Names
	 *
	 * @since 3.0
	 */
	function filter_wp_insert_post_empty_content( $maybe_empty, $postarr ) {

		if ( $this->post_type != $postarr['post_type'] )
			return $maybe_empty;

		if ( empty( $postarr['post_title'] ) )
			return true;

		return $maybe_empty;
	}

	/**
	 * On the User Management view, add action links to create or edit
	 * guest author profiles
	 *
	 * @since 3.0
	 *
	 * @param array $actions The existing actions to perform on a user
	 * @param object $user_object A WP_User object
	 * @return array $actions Modified actions
	 */
	function filter_user_row_actions( $actions, $user_object ) {

		if ( ! current_user_can( $this->list_guest_authors_cap ) )
			return $actions;

		$new_actions = array();
		if ( $guest_author = $this->get_guest_author_by( 'linked_account', $user_object->user_login ) ) {
			$edit_guest_author_link = get_edit_post_link( $guest_author->ID );
			$new_actions['edit-guest-author'] = '<a href="' . esc_url( $edit_guest_author_link ) . '">' . __( 'Edit Profile', 'co-authors-plus' ) . '</a>';
		} else {
			$query_args = array(
					'action' => 'cap-create-guest-author',
					'user_id' => $user_object->ID,
					'nonce' => wp_create_nonce( 'create-guest-author' ),
				);
			$create_guest_author_link = add_query_arg( $query_args, admin_url( $this->parent_page ) );
			$new_actions['create-guest-author'] = '<a href="' . esc_url( $create_guest_author_link ) . '">' . __( 'Create Profile', 'co-authors-plus' ) . '</a>';
		}

		return $new_actions + $actions;
	}

	/**
	 * Filter 'get_avatar' to replace with our own avatar if one exists
	 *
	 * @since 3.0
	 */
	function filter_get_avatar( $avatar, $id_or_email, $size, $default ) {

		if ( is_object( $id_or_email ) || !is_email( $id_or_email ) )
			return $avatar;

		// See if this matches a guest author
		$guest_author = $this->get_guest_author_by( 'user_email', $id_or_email );
		if ( ! $guest_author )
			return $avatar;

		// See if the guest author as an avatar
		if ( ! has_post_thumbnail( $guest_author->ID ) )
			return $avatar;

		$args = array(
				'class' => "avatar avatar-{$size} photo",
			);
		if ( in_array( $size, $this->avatar_sizes ) )
			$size = 'guest-author-' . $size;
		else
			$size = array( $size, $size );
		$avatar = get_the_post_thumbnail( $guest_author->ID, $size, $args );

		return $avatar;
	}

	/**
	 * Filter the URL used in functions like the_author_posts_link()
	 *
	 * @since 3.0
	 */
	function filter_author_link( $link, $author_id, $author_nicename ) {

		// If we're using this at the top of the loop on author.php,
		// our queried object should be set correctly
		if ( !$author_nicename && is_author() && get_queried_object() )
			$author_nicename = get_queried_object()->user_nicename;

		if ( empty($link) ) {
			$link = add_query_arg( 'author_name', $author_nicename, home_url() );
		} else {
			global $wp_rewrite;
			$link = $wp_rewrite->get_author_permastruct();
			if ( $link ) {
				$link = str_replace('%author%', $author_nicename, $link);
				$link = home_url( user_trailingslashit( $link ) );
			} else {
				$link = add_query_arg( 'author_name', $author_nicename, home_url() );
			}
		}
		return $link;

	}

}