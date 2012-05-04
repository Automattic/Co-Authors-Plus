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

	var $list_guest_authors_cap = 'list_users';

	/**
	 * Initialize our Guest Authors class and establish common hooks
	 */
	function __construct() {

		// Add the guest author management menu
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		// WP List Table for breaking out our Guest Authors
		require_once( dirname( __FILE__ ) . '/class-coauthors-wp-list-table.php' );

		// Any CSS or JS
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

		// Handle actions to create or delete guest author accounts
		add_action( 'admin_init', array( $this, 'handle_create_guest_author_action' ) );

		// Add metaboxes for our guest author management interface
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 10, 2 );
		add_action( 'wp_insert_post_data', array( $this, 'manage_guest_author_filter_post_data' ), 10, 2 );
		add_action( 'save_post', array( $this, 'manage_guest_author_save_meta_fields' ), 10, 2 );

		// Allow admins to create or edit guest author profiles from the Manage Users listing
		add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );

		// Add support for featured thumbnails that we can use for guest author avatars
		add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ) );
		add_filter( 'get_avatar', array( $this, 'filter_get_avatar' ),10 ,5 );

		// Register a post type to store our authors that aren't WP.com users
		$args = array(
				'label' => __( 'Guest Author', 'co-authors-plus' ),
				'labels' => array(
						'name' => __( 'Guest Authors', 'co-authors-plus' ),
						'singular_name' => __( 'Guest Author', 'co-authors-plus' ),
						'add_new' => _x( 'Add New', 'co-authors-plus' ),
						'all_items' => __( 'All Guest Authors', 'co-authors-plus' ),
						'add_new_item' => __( 'Add New Guest Author', 'co-authors-plus' ),
						'edit_item' => __( 'Edit Guest Author', 'co-authors-plus' ),
						'new_item' => __( 'New Guest Author', 'co-authors-plus' ),
						'view_item' => __( 'View Guest Author', 'co-authors-plus' ),
						'search_items' => __( 'Search Guest Authors', 'co-authors-plus' ),
						'not_found' => __( 'No guest author found', 'co-authors-plus' ),
						'not_found_in_trash' => __( 'No guest authors found in Trash', 'co-authors-plus' ),
					),
				'public' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'supports' => array(
						'thumbnail',
					),
				'rewrite' => false,
				'query_var' => false,
				'capability_type' => 'user',
				'map_meta_cap' => true,
			);
		register_post_type( $this->post_type, $args );

		// Hacky way to remove the title and the editor
		remove_post_type_support( $this->post_type, 'title' );
		remove_post_type_support( $this->post_type, 'editor' );

	}

	/**
	 * Handle the admin action to create a guest author based
	 * on an existing WordPress user
	 *
	 * @since 2.7
	 */
	function handle_create_guest_author_action() {

		if ( !isset( $_GET['action'], $_GET['nonce'], $_GET['user_id'] ) || $_GET['action'] != 'cap-create-guest-author' )
			return;

		if ( !wp_verify_nonce( $_GET['nonce'], 'create-guest-author' ) )
			wp_die( __( "Doin' something fishy, huh?", 'co-authors-plus' ) );

		// @todo permissions check

		// @todo Check to see if the user already has a guest profile
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
	 * Add the admin menus for seeing all co-authors
	 */
	function action_admin_menu() {

		add_submenu_page( 'users.php', __( 'Guest Authors', 'co-authors-plus' ), __( 'Guest Authors', 'co-authors-plus' ), $this->list_guest_authors_cap, 'view-guest-authors', array( $this, 'view_guest_authors_list' ) );

	}

	/**
	 * Enqueue any scripts or styles used for Guest Authors
	 */
	function action_admin_enqueue_scripts() {
		global $pagenow;
		// Enqueue our guest author CSS on the related pages
		if ( 'users.php' == $pagenow && isset( $_GET['page'] ) && $_GET['page'] == 'view-guest-authors' ) {
			wp_enqueue_style( 'guest-authors-css', COAUTHORS_PLUS_URL . 'css/guest-authors.css', false, COAUTHORS_PLUS_VERSION );
		}
	}

	/**
	 * Register the metaboxes used for Guest Authors
	 */
	function action_add_meta_boxes() {
		global $coauthors_plus;

		$post_type = $coauthors_plus->get_current_post_type();
		
		if ( $post_type == $this->post_type ) {
			// Remove the submitpost metabox because we have our own
			remove_meta_box( 'submitdiv', $post_type, 'side' );
			remove_meta_box( 'slugdiv', $post_type, 'normal' );
			add_meta_box( 'coauthors-manage-guest-author-save', __( 'Save', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_save' ), $post_type, 'side', 'default' );
			// Our metaboxes with co-author details
			add_meta_box( 'coauthors-manage-guest-author-name', __( 'Name', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_name' ), $post_type, 'normal', 'default' );
			add_meta_box( 'coauthors-manage-guest-author-contact-info', __( 'Contact Info', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_contact_info' ), $post_type, 'normal', 'default' );
			add_meta_box( 'coauthors-manage-guest-author-bio', __( 'About the guest author', 'co-authors-plus'), array( $this, 'metabox_manage_guest_author_bio' ), $post_type, 'normal', 'default' );
		}
	}

	/**
	 *
	 */
	function view_guest_authors_list() {

		echo '<div class="wrap">';
		echo '<div class="icon32" id="icon-users"><br/></div>';
		echo '<h2>' . __( 'Guest Authors', 'co-authors-plus' );
		// @todo caps check for creating a new user
		$add_new_link = admin_url( "post-new.php?post_type=$this->post_type" );
		echo '<a href="' . $add_new_link . '" class="add-new-h2">' . esc_html( 'Add New', 'co-authors-plus' ) . '</a>';
		echo '</h2>';
		$cap_list_table = new CoAuthors_WP_List_Table();
		$cap_list_table->prepare_items();
		$cap_list_table->display();
		echo '</div>';

	}

	/**
	 * Metabox for saving or updating a Guest Author
	 */
	function metabox_manage_guest_author_save() {
		global $post;

		if ( $post->post_status == 'publish' )
			$button_text = __( 'Update Guest Author', 'co-authors-plus' );
		else
			$button_text = __( 'Add New Guest Author', 'co-authors-plus' );
		submit_button( $button_text, 'primary', 'publish', false );

		// Secure all of our requests
		wp_nonce_field( 'guest-author-nonce', 'guest-author-nonce' );

	}

	/**
	 * Metabox to display all of the pertient names for a Guest Author without a user account
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
	 */
	function manage_guest_author_filter_post_data( $post_data, $original_args ) {

		if ( $post_data['post_type'] != $this->post_type )
			return $post_data;

		// @todo caps check
		if ( !isset( $_POST['guest-author-nonce'] ) || !wp_verify_nonce( $_POST['guest-author-nonce'], 'guest-author-nonce' ) )
			return $post_data;

		$post_data['post_title'] = sanitize_text_field( $_POST['cap-display_name'] );
		$post_data['post_name'] = $this->get_post_meta_key( sanitize_title( $_POST['cap-slug'] ) );
		return $post_data;

	}

	/**
	 * Save the various meta fields associated with our guest author model
	 */
	function manage_guest_author_save_meta_fields( $post_id, $post ) {
		global $coauthors_plus;

		if ( $post->post_type != $this->post_type )
			return;

		// @todo caps check
		if ( !isset( $_POST['guest-author-nonce'] ) || !wp_verify_nonce( $_POST['guest-author-nonce'], 'guest-author-nonce' ) )
			return;

		// Ensure there's a proper 'author' term for this coauthor
		// Add user as a term if they don't exist
		if( !term_exists( $post->post_name, $coauthors_plus->coauthor_taxonomy ) ) {
			$args = array( 'slug' => $post->post_name );
			wp_insert_term( $post->post_name, $coauthors_plus->coauthor_taxonomy, $args );
		}
		// Add the author as a post term
		wp_set_post_terms( $post_id, array( $post->post_name ), $coauthors_plus->coauthor_taxonomy, false );

		// Save our data to post meta
		$author_fields = $this->get_guest_author_fields();
		foreach( $author_fields as $author_field ) {
			$key = $this->get_post_meta_key( $author_field['key'] );
			if ( !isset( $_POST[$key] ) )
				continue;
			if ( isset( $author_field['sanitize_function'] ) && function_exists( $author_field['sanitize_function'] ) )
				$value = $author_field['sanitize_function']( $_POST[$key] );
			else
				$value = sanitize_text_field( $_POST[$key] );
			update_post_meta( $post_id, $key, $value );
		}

		// @todo save some amount of data to the term description field so
		// it can be searchable

	}

	/**
	 * Return a simulated WP_User object based on the post ID
	 * of a guest author
	 *
	 */
	function get_guest_author_by( $key, $value ) {

		if ( 'id' == $key ) {
			$post = get_post( $value );
		} else if ( 'login' == $key || 'post_name' == $key ) {
			global $wpdb;
			// @todo look for a more performant way of gathering this data
			$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name=%s", $value );
			$result = $wpdb->get_results( $query );
			if ( empty( $result ) )
				return false;
			$post = get_post( $result[0]->ID );
		}

		if ( !$post )
			return false;

		$guest_author = array(
			'ID' => $post->ID,
			// Hack to model the WP_User object
			'user_login' => $post->post_name,
			'user_nicename' => $post->post_name,
		);
		// Load the rest 
		$fields = $this->get_guest_author_fields( 'all' );
		foreach( $fields as $field ) {
			$key = $field['key'];
			$pm_key = $this->get_post_meta_key( $field['key'] );
			$guest_author[$key] = get_post_meta( $post->ID, $pm_key, true );
		}
		$guest_author['type'] = 'guest-author';
		return (object)$guest_author;
	}

	/**
	 * Get all of the meta fields that can be associated with a guest author
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
						'key'      => 'display_name',
						'label'    => __( 'Display Name', 'co-authors-plus'),
						'group'    => 'name',
					),
				array(
						'key'      => 'slug',
						'label'    => __( 'Slug', 'co-authors-plus'),
						'group'    => 'name',
					),
				// Contact info
				array(
						'key'      => 'user_email',
						'label'    => __( 'E-mail', 'co-authors-plus' ),
						'group'    => 'contact-info',
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
	 * @since 0.7
	 */
	function get_post_meta_key( $key ) {

		if ( false === stripos( $key, 'cap-' ) )
			$key = 'cap-' . $key;

		return $key;
	}

	/**
	 * Create a guest author from an existing WordPress user
	 */
	function create_guest_author_from_user_id( $user_id ) {

		$user = get_user_by( 'id', $user_id );
		if ( !$user )
			return new WP_Error( 'invalid-user', __( 'No user exists with that ID', 'co-authors-plus' ) );

		// Create the user as a new guest 
		$new_post = array(
				'post_title' => $user->display_name,
				'post_name' => $user->user_login,
				'post_type' => $this->post_type,
			);
		$post_id = wp_insert_post( $new_post, true );
		if ( is_wp_error( $post_id ) )
			return $post_id;

		$fields = $this->get_guest_author_fields();
		foreach( $fields as $field ) {
			$key = $field['key'];
			$pm_key = $this->get_post_meta_key( $field['key'] );
			update_post_meta( $post_id, $pm_key, $user->$key );
		}
		return $post_id;
	}

	/**
	 * On the User Management view, add action links to create or edit
	 * guest author profiles
	 *
	 * @todo The text of these links is definitely up in the air
	 *
	 * @param array $actions The existing actions to perform on a user
	 * @param object $user_object A WP_User object
	 * @return array $actions Modified actions
	 */
	function filter_user_row_actions( $actions, $user_object ) {

		$new_actions = array();
		if ( $guest_author = $this->get_guest_author_by( 'login', $user_object->user_login ) ) {
			$edit_guest_author_link = get_edit_post_link( $guest_author->ID );
			$new_actions['edit-guest-author'] = '<a href="' . esc_url( $edit_guest_author_link ) . '">' . __( 'Edit CA+ Profile', 'co-authors-plus' ) . '</a>';
		} else {
			$query_args = array(
					'action' => 'cap-create-guest-author',
					'user_id' => $user_object->ID,
					'nonce' => wp_create_nonce( 'create-guest-author' ),
				);
			$create_guest_author_link = add_query_arg( $query_args, admin_url( 'users.php' ) );
			$new_actions['create-guest-author'] = '<a href="' . esc_url( $create_guest_author_link ) . '">' . __( 'Create CA+ Profile', 'co-authors-plus' ) . '</a>';
		}

		return $new_actions + $actions;
	}

		/**
	 * Anything to do after the theme has been set up
	 */
	function action_after_setup_theme() {
		add_theme_support( 'post-thumbnails', array( $this->post_type ) );

		// @todo identify a few of the common image sizes used by get_avatar()
		add_image_size( 'guest-author-32', 32, 32, true );
	}

	/**
	 * Filter 'get_avatar' to replace with our own avatar if one exists
	 *
	 * @todo support for multiple avatar sizes
	 */
	function filter_get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

		if ( !is_email( $id_or_email ) )
			return $avatar;

		// @todo we need a better way of looking to see whether this email exists in our system to override
		// it probably should be cached too. maybe produce a URL that's a HTTP request against the site, and then serve
		// the image from that?
		global $wpdb;
		$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='cap-user_email' AND meta_value=%s", $id_or_email );
		$results = $wpdb->get_results( $query );
		if ( empty( $results ) )
			return $avatar;

		$post_id = $results[0]->post_id;
		if ( !has_post_thumbnail( $post_id ) )
			return $avatar;

		$args = array(
				'class' => 'avatar avatar-32 photo',
				'alt' => $alt,
			);
		$avatar = get_the_post_thumbnail( $post_id, 'guest-author-32', $args );

		return $avatar;
	}

}