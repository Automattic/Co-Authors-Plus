<?php
/*
Plugin Name: Co-Authors Plus
Plugin URI: http://wordpress.org/extend/plugins/co-authors-plus/
Description: Allows multiple authors to be assigned to a post. This plugin is an extended version of the Co-Authors plugin developed by Weston Ruter.
Version: 3.0.3-working
Author: Mohammad Jangda, Daniel Bachhuber, Automattic
Copyright: 2008-2012 Shared and distributed between Mohammad Jangda, Daniel Bachhuber, Weston Ruter

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

define( 'COAUTHORS_PLUS_VERSION', '3.0.3-working' );

define( 'COAUTHORS_PLUS_PATH', dirname( __FILE__ ) );
define( 'COAUTHORS_PLUS_URL', plugin_dir_url( __FILE__ ) );

require_once( dirname( __FILE__ ) . '/template-tags.php' );

require_once( dirname( __FILE__ ) . '/php/class-coauthors-template-filters.php' );

if ( defined('WP_CLI') && WP_CLI )
	require_once( dirname( __FILE__ ) . '/php/class-wp-cli.php' );

class coauthors_plus {

	// Name for the taxonomy we're using to store relationships
	// and the post type we're using to store co-authors
	var $coauthor_taxonomy = 'author';

	var $coreauthors_meta_box_name = 'authordiv';
	var $coauthors_meta_box_name = 'coauthorsdiv';
	var $force_guest_authors = false;

	var $gravatar_size = 25;

	var $_pages_whitelist = array( 'post.php', 'post-new.php' );

	var $supported_post_types = array();

	var $ajax_search_fields = array( 'display_name', 'first_name', 'last_name', 'user_login', 'ID', 'user_email' );

	var $having_terms = '';

	/**
	 * __construct()
	 */
	function __construct() {

		// Register our models
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'init', array( $this, 'action_init_late' ), 100 );

		// Load admin_init function
		add_action( 'admin_init', array( $this,'admin_init' ) );

		// Modify SQL queries to include coauthors
		add_filter( 'posts_where', array( $this, 'posts_where_filter' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'posts_join_filter' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby_filter' ), 10, 2 );

		// Action to set users when a post is saved
		add_action( 'save_post', array( $this, 'coauthors_update_post' ), 10, 2 );
		// Filter to set the post_author field when wp_insert_post is called
		add_filter( 'wp_insert_post_data', array( $this, 'coauthors_set_post_author_field' ), 10, 2 );

		// Action to reassign posts when a user is deleted
		add_action( 'delete_user',  array( $this, 'delete_user_action' ) );

		add_filter( 'get_usernumposts', array( $this, 'filter_count_user_posts' ), 10, 2 );

		// Action to set up author auto-suggest
		add_action( 'wp_ajax_coauthors_ajax_suggest', array( $this, 'ajax_suggest' ) );

		// Filter to allow coauthors to edit posts
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 3 );

		// Handle the custom author meta box
		add_action( 'add_meta_boxes', array( $this, 'add_coauthors_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'remove_authors_box' ) );

		// Removes the author dropdown from the post quick edit
		add_action( 'admin_head', array( $this, 'remove_quick_edit_authors_box' ) );

		// Restricts WordPress from blowing away term order on bulk edit
		add_filter( 'wp_get_object_terms', array( $this, 'filter_wp_get_object_terms' ), 10, 4 );

		// Fix for author info not properly displaying on author pages
		add_action( 'template_redirect', array( $this, 'fix_author_page' ) );
		add_action( 'the_post', array( $this, 'fix_author_page' ) );

		// Support for Edit Flow's calendar and story budget
		add_filter( 'ef_calendar_item_information_fields', array( $this, 'filter_ef_calendar_item_information_fields' ), 10, 2 );
		add_filter( 'ef_story_budget_term_column_value', array( $this, 'filter_ef_story_budget_term_column_value' ), 10, 3 );

	}

	function coauthors_plus() {
		$this->__construct();
	}

	/**
	 * Register the taxonomy used to managing relationships,
	 * and the custom post type to store our author data
	 */
	function action_init() {

		// Allow Co-Authors Plus to be easily translated
		load_plugin_textdomain( 'co-authors-plus', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Load the Guest Authors functionality if needed
		if ( $this->is_guest_authors_enabled() ) {
			require_once( dirname( __FILE__ ) . '/php/class-coauthors-guest-authors.php' );
			$this->guest_authors = new CoAuthors_Guest_Authors;
			if ( apply_filters( 'coauthors_guest_authors_force', false ) ) {
				$this->force_guest_authors = true;
			}
		}

		// Maybe automatically apply our template tags
		if ( apply_filters( 'coauthors_auto_apply_template_tags', false ) ) {
			new CoAuthors_Template_Filters;
		}

	}

	/**
	 * Register the 'author' taxonomy and add post type support
	 */
	function action_init_late() {

		// Register new taxonomy so that we can store all of the relationships
		$args = array(
			'hierarchical' => false,
			'update_count_callback' => array( $this, '_update_users_posts_count' ),
			'label' => false,
			'query_var' => false,
			'rewrite' => false,
			'public' => false,
			'sort' => true,
			'args' => array( 'orderby' => 'term_order' ),
			'show_ui' => false
		);
		$post_types_with_authors = array_values( get_post_types() );
		foreach( $post_types_with_authors as $key => $name ) {
			if ( ! post_type_supports( $name, 'author' ) || 'revision' == $name )
				unset( $post_types_with_authors[$key] );
		}
		$this->supported_post_types = apply_filters( 'coauthors_supported_post_types', $post_types_with_authors );
		register_taxonomy( $this->coauthor_taxonomy, $this->supported_post_types, $args );
	}

	/**
	 * Initialize the plugin for the admin
	 */
	function admin_init() {
		global $pagenow;

		// Add the main JS script and CSS file
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Add necessary JS variables
		add_action( 'admin_head', array( $this, 'js_vars' ) );

		// Hooks to add additional coauthors to author column to Edit page
		add_filter( 'manage_posts_columns', array( $this, '_filter_manage_posts_columns' ) );
		add_filter( 'manage_pages_columns', array( $this, '_filter_manage_posts_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, '_filter_manage_posts_custom_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, '_filter_manage_posts_custom_column' ) );

		// Hooks to modify the published post number count on the Users WP List Table
		add_filter( 'manage_users_columns', array( $this, '_filter_manage_users_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, '_filter_manage_users_custom_column' ), 10, 3 );

		// Apply some targeted filters
		add_action( 'load-edit.php', array( $this, 'load_edit' ) );

	}

	/**
	 * Check whether the guest authors functionality is enabled or not
	 * Guest authors can be disabled entirely with:
	 *     add_filter( 'coauthors_guest_authors_enabled', '__return_false' )
	 *
	 * @since 3.0
	 */
	function is_guest_authors_enabled() {
		return apply_filters( 'coauthors_guest_authors_enabled', true );
	}

	/**
	 * Get a co-author object by a specific type of key
	 *
	 * @param string $key Key to search by (slug,email)
	 * @param string $value Value to search for
	 * @param object|false $coauthor The co-author on success, false on failure
	 */
	function get_coauthor_by( $key, $value, $force = false ) {

		// If Guest Authors are enabled, prioritize those profiles
		if ( $this->is_guest_authors_enabled() ) {
			$guest_author = $this->guest_authors->get_guest_author_by( $key, $value, $force );
			if ( is_object( $guest_author ) ) {
				return $guest_author;
			}
		}

		switch( $key ) {
			case 'id':
			case 'login':
			case 'user_login':
			case 'email':
			case 'user_nicename':
			case 'user_email':
				if ( 'user_login' == $key )
					$key = 'login';
				if ( 'user_email' == $key )
					$key = 'email';
				if ( 'user_nicename' == $key )
					$key = 'slug';
				// Ensure we aren't doing the lookup by the prefixed value
				if ( 'login' == $key || 'slug' == $key )
					$value = preg_replace( '#^cap\-#', '', $value );
				$user = get_user_by( $key, $value );
				if ( !$user || !is_user_member_of_blog( $user->ID ) )
					return false;
				$user->type = 'wpuser';
				// However, if guest authors are enabled and there's a guest author linked to this
				// user account, we want to use that instead
				if ( $this->is_guest_authors_enabled() ) {
					$guest_author = $this->guest_authors->get_guest_author_by( 'linked_account', $user->user_login );
					if ( is_object( $guest_author ) )
						$user = $guest_author;
				}
				return $user;
				break;
		}
		return false;

	}

	/**
	 * Whether or not Co-Authors Plus is enabled for this post type
	 * Must be called after init
	 *
	 * @since 3.0
	 *
	 * @param string $post_type The name of the post type we're considering
	 * @return bool Whether or not it's enabled
	 */
	function is_post_type_enabled( $post_type = null ) {

		if ( ! $post_type )
			$post_type = get_post_type();

		return (bool) in_array( $post_type, $this->supported_post_types );
	}

	/**
	 * Removes the standard WordPress Author box.
	 * We don't need it because the Co-Authors one is way cooler.
	 */
	function remove_authors_box() {

		if ( $this->is_post_type_enabled() )
			remove_meta_box( $this->coreauthors_meta_box_name, get_post_type(), 'normal' );
	}

	/**
	 * Adds a custom Authors box
	 */
	function add_coauthors_box() {

		if( $this->is_post_type_enabled() && $this->current_user_can_set_authors() )
			add_meta_box( $this->coauthors_meta_box_name, __('Authors', 'co-authors-plus'), array( $this, 'coauthors_meta_box' ), get_post_type(), apply_filters( 'coauthors_meta_box_context', 'normal'), apply_filters( 'coauthors_meta_box_priority', 'high'));
	}

	/**
	 * Callback for adding the custom author box
	 */
	function coauthors_meta_box( $post ) {
		global $post, $coauthors_plus, $current_screen;

		$post_id = $post->ID;

		// @daniel, $post_id and $post->post_author are always set when a new post is created due to auto draft,
		// and the else case below was always able to properly assign users based on wp_posts.post_author,
		// but that's not possible with force_guest_authors = true.
		if( !$post_id || $post_id == 0 || ( !$post->post_author && !$coauthors_plus->force_guest_authors ) || ( $current_screen->base == 'post' && $current_screen->action == 'add' ) ) {
			$coauthors = array();
			// If guest authors is enabled, try to find a guest author attached to this user ID
			if ( $this->is_guest_authors_enabled() ) {
				$coauthor = $coauthors_plus->guest_authors->get_guest_author_by( 'linked_account', wp_get_current_user()->user_login );
				if ( $coauthor ) {
					$coauthors[] = $coauthor;
				}
			}
			// If the above block was skipped, or if it failed to find a guest author, use the current
			// logged in user, so long as force_guest_authors is false. If force_guest_authors = true, we are
			// OK with having an empty authoring box.
			if ( !$coauthors_plus->force_guest_authors && empty( $coauthors ) ) {
				$coauthors[] = wp_get_current_user();
			}
		} else {
			$coauthors = get_coauthors();
		}

		$count = 0;
		if( !empty( $coauthors ) ) :
			?>
			<div id="coauthors-readonly" class="hide-if-js1">
				<ul>
				<?php
				foreach( $coauthors as $coauthor ) :
					$count++;
					?>
					<li>
						<?php echo get_avatar( $coauthor->user_email, $this->gravatar_size ); ?>
						<span id="coauthor-readonly-<?php echo $count; ?>" class="coauthor-tag">
							<input type="text" name="coauthorsinput[]" readonly="readonly" value="<?php echo esc_attr( $coauthor->display_name ); ?>" />
							<input type="text" name="coauthors[]" value="<?php echo esc_attr( $coauthor->user_login ); ?>" />
							<input type="text" name="coauthorsemails[]" value="<?php echo esc_attr( $coauthor->user_email ); ?>" />
						</span>
					</li>
					<?php
				endforeach;
				?>
				</ul>
				<div class="clear"></div>
				<p><?php _e( '<strong>Note:</strong> To edit post authors, please enable javascript or use a javascript-capable browser', 'co-authors-plus' ); ?></p>
			</div>
			<?php
		endif;
		?>

		<div id="coauthors-edit" class="hide-if-no-js">
			<p><?php _e( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'co-authors-plus' ); ?></p>
		</div>

		<?php wp_nonce_field( 'coauthors-edit', 'coauthors-nonce' ); ?>

		<?php
	}

	/**
	 * Removes the author dropdown from the post quick edit
	 * It's a bit hacky, but the only way I can figure out :(
	 */
	function remove_quick_edit_authors_box() {
		global $pagenow;

		if ( 'edit.php' == $pagenow && $this->is_post_type_enabled() )
			remove_post_type_support( get_post_type(), 'author' );
	}

	/**
	 * Add coauthors to author column on edit pages
	 *
	 * @param array $post_columns
	 */
	function _filter_manage_posts_columns( $posts_columns ) {

		$new_columns = array();
		if ( ! $this->is_post_type_enabled() )
			return $posts_columns;

		foreach ($posts_columns as $key => $value) {
			$new_columns[$key] = $value;
			if( $key == 'title' )
				$new_columns['coauthors'] = __( 'Authors', 'co-authors-plus' );

			if ( $key == 'author' )
				unset($new_columns[$key]);
		}
		return $new_columns;
	}

	/**
	 * Insert coauthors into post rows on Edit Page
	 *
	 * @param string $column_name
	 */
	function _filter_manage_posts_custom_column( $column_name ) {
		if ($column_name == 'coauthors') {
			global $post;
			$authors = get_coauthors( $post->ID );

			$count = 1;
			foreach( $authors as $author ) :
				$args = array(
						'author_name' => $author->user_nicename,
					);
				if ( 'post' != $post->post_type )
					$args['post_type'] = $post->post_type;
				$author_filter_url = add_query_arg( $args, admin_url( 'edit.php' ) );
				?>
				<a href="<?php echo esc_url( $author_filter_url ); ?>"><?php echo esc_html( $author->display_name ); ?></a><?php echo ( $count < count( $authors ) ) ? ',' : ''; ?>
				<?php
				$count++;
			endforeach;
		}
	}

	/**
	 * Unset the post count column because it's going to be inaccurate and provide our own
	 */
	function _filter_manage_users_columns( $columns ) {

		$new_columns = array();
		// Unset and add our column while retaining the order of the columns
		foreach( $columns as $column_name => $column_title ) {
			if ( 'posts' == $column_name )
				$new_columns['coauthors_post_count'] = __( 'Posts', 'co-authors-plus' );
			else
				$new_columns[$column_name] = $column_title;
		}
		return $new_columns;
	}

	/**
	 * Provide an accurate count when looking up the number of published posts for a user
	 */
	function _filter_manage_users_custom_column( $value, $column_name, $user_id ) {
		if ( 'coauthors_post_count' != $column_name )
			return $value;
		// We filter count_user_posts() so it provides an accurate number
		$numposts = count_user_posts( $user_id );
		$user = get_user_by( 'id', $user_id );
		if ( $numposts > 0 ) {
			$value .= "<a href='edit.php?author_name=$user->user_nicename' title='" . esc_attr__( 'View posts by this author', 'co-authors-plus' ) . "' class='edit'>";
			$value .= $numposts;
			$value .= '</a>';
		} else {
			$value .= 0;
		}
		return $value;
	}

	/**
	 * When we update the terms at all, we should update the published post count for each author
	 */
	function _update_users_posts_count( $tt_ids, $taxonomy ) {
		global $wpdb;

		$tt_ids = implode( ', ', array_map( 'intval', $tt_ids ) );
		$term_ids = $wpdb->get_results( "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ($tt_ids)" );

		foreach( (array)$term_ids as $term_id_result ) {
			$term = get_term_by( 'id', $term_id_result->term_id, $this->coauthor_taxonomy );
			$this->update_author_term_post_count( $term );
		}
		$tt_ids = explode( ', ', $tt_ids );
		clean_term_cache( $tt_ids, '', false );

	}

	/**
	 * Update the post count associated with an author term
	 *
	 * @since 3.0
	 *
	 * @param object $term The co-author term
	 */
	public function update_author_term_post_count( $term ) {
		global $wpdb;

		$coauthor = $this->get_coauthor_by( 'user_nicename', $term->slug );
		if ( ! $coauthor )
			return new WP_Error( 'missing-coauthor', __( 'No co-author exists for that term', 'co-authors-plus' ) );

		$query = "SELECT COUNT({$wpdb->posts}.ID) FROM {$wpdb->posts}";

		$query .= " LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
		$query .= " LEFT JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

		$having_terms_and_authors = $having_terms = $wpdb->prepare( "{$wpdb->term_taxonomy}.term_id = %d", $term->term_id );
		if ( 'wpuser' == $coauthor->type )
			$having_terms_and_authors .= $wpdb->prepare( " OR {$wpdb->posts}.post_author = %d", $coauthor->ID );

		$query .= " WHERE ({$having_terms_and_authors}) AND {$wpdb->posts}.post_type = 'post' AND {$wpdb->posts}.post_status = 'publish'";

		$query .= $wpdb->prepare( " GROUP BY {$wpdb->posts}.ID HAVING MAX( IF( {$wpdb->term_taxonomy}.taxonomy = '%s', IF( {$having_terms},2,1 ),0 ) ) <> 1 ", $this->coauthor_taxonomy );

		$count = $wpdb->query( $query );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );

		wp_cache_delete( 'author-term-' . $coauthor->user_nicename, 'co-authors-plus' );
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_join_filter( $join, $query ){
		global $wpdb;

		if( $query->is_author() ) {

			if ( !empty( $query->query_vars['post_type'] ) && !is_object_in_taxonomy( $query->query_vars['post_type'], $this->coauthor_taxonomy ) )
				return $join;

			// Check to see that JOIN hasn't already been added. Props michaelingp and nbaxley
			$term_relationship_join = " INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
			$term_taxonomy_join = " INNER JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

			if( strpos( $join, trim( $term_relationship_join ) ) === false ) {
				$join .= str_replace( "INNER JOIN", "LEFT JOIN", $term_relationship_join );
			}
			if( strpos( $join, trim( $term_taxonomy_join ) ) === false ) {
				$join .= str_replace( "INNER JOIN", "LEFT JOIN", $term_taxonomy_join );
			}
		}

		return $join;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_where_filter( $where, $query ){
		global $wpdb;

		if ( $query->is_author() ) {

			if ( !empty( $query->query_vars['post_type'] ) && !is_object_in_taxonomy( $query->query_vars['post_type'], $this->coauthor_taxonomy ) )
				return $where;

			if ( $query->get( 'author_name' ) )
				$author_name = sanitize_title( $query->get( 'author_name' ) );
			else
				$author_name = get_userdata( $query->get( 'author' ) )->user_nicename;

			$terms = array();
			$coauthor = $this->get_coauthor_by( 'user_nicename', $author_name );
			if ( $author_term = $this->get_author_term( $coauthor ) )
				$terms[] = $author_term;
			// If this coauthor has a linked account, we also need to get posts with those terms
			if ( ! empty( $coauthor->linked_account ) ) {
				$linked_account = get_user_by( 'login', $coauthor->linked_account );
				if ( $guest_author_term = $this->get_author_term( $linked_account ) )
					$terms[] = $guest_author_term;
			}

			// Whether or not to include the original 'post_author' value in the query
			if ( $this->force_guest_authors )
				$maybe_both = false;
			else
				$maybe_both = apply_filters( 'coauthors_plus_should_query_post_author', true );

			$maybe_both_query = $maybe_both ? '$1 OR' : '';

			if ( !empty( $terms ) ) {
				$terms_implode = '';
				$this->having_terms = '';
				foreach( $terms as $term ) {
					$terms_implode .= '(' . $wpdb->term_taxonomy . '.taxonomy = \''. $this->coauthor_taxonomy.'\' AND '. $wpdb->term_taxonomy .'.term_id = \''. $term->term_id .'\') OR ';
					$this->having_terms .= ' ' . $wpdb->term_taxonomy .'.term_id = \''. $term->term_id .'\' OR ';
				}
				$terms_implode = rtrim( $terms_implode, ' OR' );
				$this->having_terms = rtrim( $this->having_terms, ' OR' );
				$where = preg_replace( '/(\b(?:' . $wpdb->posts . '\.)?post_author\s*=\s*(\d+))/', '(' . $maybe_both_query . ' ' . $terms_implode . ')', $where, 1 ); #' . $wpdb->postmeta . '.meta_id IS NOT NULL AND
			}

		}
		return $where;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_groupby_filter( $groupby, $query ) {
		global $wpdb;

		if( $query->is_author() ) {

			if ( !empty( $query->query_vars['post_type'] ) && !is_object_in_taxonomy( $query->query_vars['post_type'], $this->coauthor_taxonomy ) )
				return $groupby;

			if ( $this->having_terms ) {
				$having = 'MAX( IF( ' . $wpdb->term_taxonomy . '.taxonomy = \''. $this->coauthor_taxonomy.'\', IF( ' . $this->having_terms . ',2,1 ),0 ) ) <> 1 ';
				$groupby = $wpdb->posts . '.ID HAVING ' . $having;
			}
		}
		return $groupby;
	}

	/**
	 * Filters post data before saving to db to set post_author
	 */
	function coauthors_set_post_author_field( $data, $postarr ) {

		// Bail on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && !DOING_AUTOSAVE )
			return $data;

		// Bail on revisions
		if ( ! $this->is_post_type_enabled( $data['post_type'] ) )
			return $data;

		// This action happens when a post is saved while editing a post
		if( isset( $_REQUEST['coauthors-nonce'] ) && isset( $_POST['coauthors'] ) && is_array( $_POST['coauthors'] ) ) {
			$author = sanitize_text_field( $_POST['coauthors'][0] );
			if ( $author ) {
				$author_data = $this->get_coauthor_by( 'user_nicename', $author );
				// If it's a guest author and has a linked account, store that information in post_author
				// because it'll be the valid user ID
				if ( 'guest-author' == $author_data->type && ! empty( $author_data->linked_account ) ) {
					$data['post_author'] = get_user_by( 'login', $author_data->linked_account )->ID;
				} else if ( $author_data->type == 'wpuser' )
					$data['post_author'] = $author_data->ID;
			}
		}

		// Restore the co-author when quick editing because we don't
		// allow changing the co-author on quick edit. In wp_insert_post(),
		// 'post_author' is set to current user if the $_REQUEST value doesn't exist
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'inline-save' ) {
			$coauthors = get_coauthors( $postarr['ID'] );
			if ( is_array( $coauthors ) ) {
				$coauthor = $this->get_coauthor_by( 'user_nicename', $coauthors[0]->user_nicename );
				if ( 'guest-author' == $coauthor->type && ! empty( $coauthor->linked_account ) ) {
					$data['post_author'] = get_user_by( 'user_login', $coauthor->linked_account )->ID;
				} else if ( $coauthor->type == 'wpuser' )
					$data['post_author'] = $coauthor->ID;
				// Refresh their post publish count too
				if ( 'publish' == $postarr['post_status'] || 'publish' == get_post_status( $postarr['ID'] ) ) {
					foreach( $coauthors as $coauthor ) {
						if ( $author_term = $this->get_author_term( $coauthor ) )
							$this->update_author_term_post_count( $author_term );
					}
				}
			}
		}

		// If for some reason we don't have the coauthors fields set
		if( ! isset( $data['post_author'] ) ) {
			$user = wp_get_current_user();
			$data['post_author'] = $user->ID;
		}

		// Allow the 'post_author' to be forced to generic user if it doesn't match any users on the post
		$data['post_author'] = apply_filters( 'coauthors_post_author_value', $data['post_author'], $postarr['ID'] );

		return $data;
	}

	/**
	 * Update a post's co-authors on the 'save_post' hook
	 *
	 * @param $post_ID
	 */
	function coauthors_update_post( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && !DOING_AUTOSAVE )
			return;

		if ( ! $this->is_post_type_enabled( $post->post_type ) )
			return;

		if( isset( $_POST['coauthors-nonce'] ) && isset( $_POST['coauthors'] ) ) {
			check_admin_referer( 'coauthors-edit', 'coauthors-nonce' );

			if( $this->current_user_can_set_authors() ){
				$coauthors = (array) $_POST['coauthors'];
				$coauthors = array_map( 'sanitize_text_field', $coauthors );
				return $this->add_coauthors( $post_id, $coauthors );
			}
		}
	}

	/**
	 * Add one or more co-authors as bylines for a post
	 */
	function add_coauthors( $post_id, $coauthors, $append = false ) {
		global $current_user;

		$post_id = (int) $post_id;
		$insert = false;

		// if an array isn't returned, create one and populate with default author
		if ( !is_array( $coauthors ) || 0 == count( $coauthors ) || empty( $coauthors ) ) {
			$coauthors = array( $current_user->user_login );
		}

		// Add each co-author to the post meta
		foreach( array_unique( $coauthors ) as $key => $author_name ){

			$author = $this->get_coauthor_by( 'user_login', $author_name );
			$term = $this->update_author_term( $author );
			$coauthors[$key] = $term->slug;
		}
		wp_set_post_terms( $post_id, $coauthors, $this->coauthor_taxonomy, $append );
	}

	/**
	 * Action taken when user is deleted.
	 * - User term is removed from all associated posts
	 * - Option to specify alternate user in place for each post
	 * @param delete_id
	 */
	function delete_user_action($delete_id){
		global $wpdb;

		$reassign_id = absint( $_POST['reassign_user'] );

		// If reassign posts, do that -- use coauthors_update_post
		if($reassign_id) {
			// Get posts belonging to deleted author
			$reassign_user = get_user_by( 'id', $reassign_id );
			// Set to new author
			if( is_object( $reassign_user ) ) {
				$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $delete_id ) );

				if ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						$this->add_coauthors( $post_id, array( $reassign_user->user_login ), true );
					}
				}
			}
		}

		$delete_user = get_user_by( 'id', $delete_id );
		if ( is_object( $delete_user ) ) {
			// Delete term
			wp_delete_term( $delete_user->user_login, $this->coauthor_taxonomy );
		}
	}

	/**
	 * Restrict WordPress from blowing away author order when bulk editing terms
	 *
	 * @since 2.6
	 * @props kingkool68, http://wordpress.org/support/topic/plugin-co-authors-plus-making-authors-sortable
	 */
	function filter_wp_get_object_terms( $terms, $object_ids, $taxonomies, $args ) {

		if ( !isset( $_REQUEST['bulk_edit'] ) || $taxonomies != "'author'" )
			return $terms;

		global $wpdb;
		$orderby = 'ORDER BY tr.term_order';
		$order = 'ASC';
		$object_ids = (int)$object_ids;
		$query = $wpdb->prepare( "SELECT t.slug, t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN (%s) AND tr.object_id IN (%s) $orderby $order", $taxonomies, $object_ids );
		$raw_coauthors = $wpdb->get_results( $query );
		$terms = array();
		foreach( $raw_coauthors as $author ) {
			$terms[] = $author->slug;
		}

		return $terms;

	}

	/**
	 * Filter the count_users_posts() core function to include our correct count
	 */
	function filter_count_user_posts( $count, $user_id ) {
		$user = get_userdata( $user_id );

		$term = $this->get_author_term( $user );
		// Only modify the count if the author already exists as a term
		if( $term && !is_wp_error( $term ) ) {
			$count = $term->count;
		}

		return $count;
	}

	/**
	 * Checks to see if the current user can set authors or not
	 */
	function current_user_can_set_authors( ) {
		global $post, $typenow;

		$post_type = get_post_type();
		// TODO: need to fix this; shouldn't just say no if don't have post_type
		if( ! $post_type ) return false;

		$post_type_object = get_post_type_object( $post_type );
		$current_user = wp_get_current_user();
		if ( ! $current_user )
			return false;
		// Super admins can do anything
		if ( function_exists( 'is_super_admin' ) && is_super_admin() )
			return true;

		$can_set_authors = isset( $current_user->allcaps['edit_others_posts'] ) ? $current_user->allcaps['edit_others_posts'] : false;

		return apply_filters( 'coauthors_plus_edit_authors', $can_set_authors );
	}

	/**
	 * Fix for author info not properly displaying on author pages
	 *
	 * On an author archive, if the first story has coauthors and
	 * the first author is NOT the same as the author for the archive,
	 * the query_var is changed.
	 *
	 * Also, we have to do some hacky WP_Query modification for guest authors
	 */
	function fix_author_page() {

		if ( !is_author() )
			return;

		global $wp_query, $authordata;

		if ( $author_name = sanitize_title( get_query_var( 'author_name' ) ) ) {
			$authordata = $this->get_coauthor_by( 'user_nicename', $author_name );
			if ( is_object( $authordata ) ) {
				$wp_query->queried_object = $authordata;
				$wp_query->queried_object_id = $authordata->ID;
			}
		}
	}

	/**
	 * Main function that handles search-as-you-type for adding authors
	 */
	function ajax_suggest() {

		if( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'coauthors-search' ) )
			die();

		if( empty( $_REQUEST['q'] ) )
			die();

		$search = sanitize_text_field( strtolower( $_REQUEST['q'] ) );
		$ignore = array_map( 'sanitize_text_field', explode( ',', $_REQUEST['existing_authors'] ) );

		$authors = $this->search_authors( $search, $ignore );

		foreach( $authors as $author ) {
			echo $author->ID ." | ". $author->user_login ." | ". $author->display_name ." | ". $author->user_email ."\n";
		}

		die();

	}

	/**
	 * Get matching authors based on a search value
	 */
	function search_authors( $search = '', $ignored_authors = array() ) {

		// Since 2.7, we're searching against the term description for the fields
		// instead of the user details. If the term is missing, we probably need to
		// backfill with user details. Let's do this first... easier than running
		// an upgrade script that could break on a lot of users
		$args = array(
				'count_total' => false,
				'search' => sprintf( '*%s*', $search ),
				'search_fields' => array(
					'ID',
					'display_name',
					'user_email',
					'user_login',
				),
				'fields' => 'all_with_meta',
			);
		add_filter( 'pre_user_query', array( $this, 'filter_pre_user_query' ) );
		$found_users = get_users( $args );
		remove_filter( 'pre_user_query', array( $this, 'filter_pre_user_query' ) );

		foreach( $found_users as $found_user ) {
			$term = $this->get_author_term( $found_user );
			if ( empty( $term ) || empty( $term->description ) ) {
				$this->update_author_term( $found_user );
			}
		}

		$args = array(
				'search' => $search,
				'get' => 'all',
				'number' => 10,
			);
		add_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );
		$found_terms = get_terms( $this->coauthor_taxonomy, $args );
		remove_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );
		if ( empty( $found_terms ) )
			return array();

		// Get the co-author objects
		$found_users = array();
		foreach( $found_terms as $found_term ) {
			$found_user = $this->get_coauthor_by( 'user_nicename', $found_term->slug );
			if ( !empty( $found_user ) )
				$found_users[$found_user->user_login] = $found_user;
		}

		// Allow users to always filter out certain users if needed (e.g. administrators)
		$ignored_authors = apply_filters( 'coauthors_edit_ignored_authors', $ignored_authors );
		foreach( $found_users as $key => $found_user ) {
			// Make sure the user is contributor and above (or a custom cap)
			if ( in_array( $found_user->user_login, $ignored_authors ) )
				unset( $found_users[$key] );
			else if ( $found_user->type == 'wpuser' && false === $found_user->has_cap( apply_filters( 'coauthors_edit_author_cap', 'edit_posts' ) ) )
				unset( $found_users[$key] );
		}
		return (array) $found_users;
	}

	/**
	 * Modify get_users() to search display_name instead of user_nicename
	 */
	function filter_pre_user_query( &$user_query ) {

		if ( is_object( $user_query ) )
			$user_query->query_where = str_replace( "user_nicename LIKE", "display_name LIKE", $user_query->query_where );
		return $user_query;
	}

	/**
	 * Modify get_terms() to LIKE against the term description instead of the term name
	 *
	 * @since 3.0
	 */
	function filter_terms_clauses( $pieces ) {

		$pieces['where'] = str_replace( 't.name LIKE', 'tt.description LIKE', $pieces['where'] );
		return $pieces;
	}

	/**
	 * Functions to add scripts and css
	 */
	function enqueue_scripts($hook_suffix) {
		global $pagenow, $post;

		if ( !$this->is_valid_page() || ! $this->is_post_type_enabled() || !$this->current_user_can_set_authors() )
			return;

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'co-authors-plus-css', COAUTHORS_PLUS_URL . 'css/co-authors-plus.css', false, COAUTHORS_PLUS_VERSION, 'all' );
		wp_enqueue_script( 'co-authors-plus-js', COAUTHORS_PLUS_URL . 'js/co-authors-plus.js', array('jquery', 'suggest'), COAUTHORS_PLUS_VERSION, true);

		$js_strings = array(
			'edit_label' => __( 'Edit', 'co-authors-plus' ),
			'delete_label' => __( 'Remove', 'co-authors-plus' ),
			'confirm_delete' => __( 'Are you sure you want to remove this author?', 'co-authors-plus' ),
			'input_box_title' => __( 'Click to change this author, or drag to change their position', 'co-authors-plus' ),
			'search_box_text' => __( 'Search for an author', 'co-authors-plus' ),
			'help_text' => __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'co-authors-plus' ),
		);
		wp_localize_script( 'co-authors-plus-js', 'coAuthorsPlusStrings', $js_strings );

	}

	/**
	 * load-edit.php is when the screen has been set up
	 */
	function load_edit() {

		$screen = get_current_screen();
		if ( in_array( $screen->post_type, $this->supported_post_types ) )
			add_filter( 'views_' . $screen->id, array( $this, 'filter_views' ) );
	}

	/**
	 * Filter the view links that appear at the top of the Manage Posts view
	 *
	 * @since 3.0
	 */
	function filter_views( $views ) {

		if ( array_key_exists( 'mine', $views ) )
			return $views;

		$views = array_reverse( $views );
		$all_view = array_pop( $views );
		$mine_args = array(
				'author_name'           => wp_get_current_user()->user_nicename,
			);
		if ( 'post' != get_post_type() )
			$mine_args['post_type'] = get_post_type();
		if ( ! empty( $_REQUEST['author_name'] ) && wp_get_current_user()->user_nicename == $_REQUEST['author_name'] )
			$class = ' class="current"';
		else
			$class = '';
		$views['mine'] = $view_mine = '<a' . $class . ' href="' . add_query_arg( $mine_args, admin_url( 'edit.php' ) ) . '">' . __( 'Mine', 'co-authors-plus' ) . '</a>';

		$views['all'] = str_replace( $class, '', $all_view );
		$views = array_reverse( $views );

		return $views;
	}

	/**
	 * Adds necessary javascript variables to admin pages
	 */
	function js_vars() {

		if ( ! $this->is_valid_page() || ! $this->is_post_type_enabled() || ! $this-> current_user_can_set_authors() )
			return;
		?>
			<script type="text/javascript">
				// AJAX link used for the autosuggest
				var coAuthorsPlus_ajax_suggest_link = '<?php echo add_query_arg(
					array(
						'action' => 'coauthors_ajax_suggest',
						'post_type' => get_post_type(),
					),
					wp_nonce_url( 'admin-ajax.php', 'coauthors-search' )
				); ?>';
			</script>
		<?php
	}

	/**
	 * Helper to only add javascript to necessary pages. Avoids bloat in admin.
	 */
	function is_valid_page() {
		global $pagenow;

		return (bool)in_array( $pagenow, $this->_pages_whitelist );
	}

	function get_post_id() {
		global $post;
		$post_id = 0;

		if ( is_object( $post ) ) {
			$post_id = $post->ID;
		}

		if( ! $post_id ) {
			if ( isset( $_GET['post'] ) )
				$post_id = (int) $_GET['post'];
			elseif ( isset( $_POST['post_ID'] ) )
				$post_id = (int) $_POST['post_ID'];
		}

		return $post_id;
	}

	/**
	 * Allows coauthors to edit the post they're coauthors of
	 */
	function filter_user_has_cap( $allcaps, $caps, $args ) {

		$cap = $args[0];
		$user_id = isset( $args[1] ) ? $args[1] : 0;
		$post_id = isset( $args[2] ) ? $args[2] : 0;

		$obj = get_post_type_object( get_post_type( $post_id ) );
		if ( ! $obj )
			return $allcaps;

		$caps_to_modify = array(
				$obj->cap->edit_post,
				'edit_post', // Need to filter this too, unfortunately: http://core.trac.wordpress.org/ticket/22415
				$obj->cap->edit_others_posts, // This as well: http://core.trac.wordpress.org/ticket/22417
			);
		if ( ! in_array( $cap, $caps_to_modify ) )
			return $allcaps;

		// We won't be doing any modification if they aren't already a co-author on the post
		if( ! is_user_logged_in() || ! is_coauthor_for_post( $user_id, $post_id ) )
			return $allcaps;

		$current_user = wp_get_current_user();
		if ( 'publish' == get_post_status( $post_id ) && ! empty( $current_user->allcaps[$obj->cap->edit_published_posts] ) )
			$allcaps[$obj->cap->edit_published_posts] = true;
		elseif ( 'private' == get_post_status( $post_id ) && ! empty( $current_user->allcaps[$obj->cap->edit_private_posts] ) )
			$allcaps[$obj->cap->edit_private_posts] = true;

		$allcaps[$obj->cap->edit_others_posts] = true;

		return $allcaps;
	}

	/**
	 * Get the author term for a given co-author
	 *
	 * @since 3.0
	 *
	 * @param object $coauthor The co-author object
	 * @return object|false $author_term The author term on success
	 */
	public function get_author_term( $coauthor ) {

		if ( ! is_object( $coauthor ) )
			return;

		$cache_key = 'author-term-' . $coauthor->user_nicename;
		if ( false !== ( $term = wp_cache_get( $cache_key, 'co-authors-plus' ) ) )
			return $term;

		// See if the prefixed term is available, otherwise default to just the nicename
		$term = get_term_by( 'slug', 'cap-' . $coauthor->user_nicename, $this->coauthor_taxonomy );
		if ( ! $term ) {
			$term = get_term_by( 'slug', $coauthor->user_nicename, $this->coauthor_taxonomy );
		}
		wp_cache_set( $cache_key, $term, 'co-authors-plus' );
		return $term;
	}

	/**
	 * Update the author term for a given co-author
	 *
	 * @since 3.0
	 *
	 * @param object $coauthor The co-author object (user or guest author)
	 * @return object|false $success Term object if successful, false if not
	 */
	public function update_author_term( $coauthor ) {

		if ( ! is_object( $coauthor ) )
			return false;

		// Update the taxonomy term to include details about the user for searching
		$search_values = array();
		foreach( $this->ajax_search_fields as $search_field ) {
			$search_values[] = $coauthor->$search_field;
		}
		$term_description = implode( ' ', $search_values );

		if ( $term = $this->get_author_term( $coauthor ) ) {
			wp_update_term( $term->term_id, $this->coauthor_taxonomy, array( 'description' => $term_description ) );
		} else {
			$coauthor_slug = 'cap-' . $coauthor->user_nicename;
			$args = array(
				'slug'          => $coauthor_slug,
				'description'   => $term_description,
			);
			$new_term = wp_insert_term( $coauthor->user_login, $this->coauthor_taxonomy, $args );
		}
		wp_cache_delete( 'author-term-' . $coauthor->user_nicename, 'co-authors-plus' );
		return $this->get_author_term( $coauthor );
	}

	/**
	 * Filter Edit Flow's 'ef_calendar_item_information_fields' to add co-authors
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/2
	 */
	function filter_ef_calendar_item_information_fields( $information_fields, $post_id ) {

		// Don't add the author row again if another plugin has removed
		if ( !array_key_exists( 'author', $information_fields ) )
			return $information_fields;

		$co_authors = get_coauthors( $post_id );
		if ( count( $co_authors ) > 1 )
			$information_fields['author']['label'] = __( 'Authors', 'co-authors-plus' );
		$co_authors_names = '';
		foreach( $co_authors as $co_author ) {
			$co_authors_names .= $co_author->display_name . ', ';
		}
		$information_fields['author']['value'] = rtrim( $co_authors_names, ', ' );
		return $information_fields;
	}

	/**
	 * Filter Edit Flow's 'ef_story_budget_term_column_value' to add co-authors to the story budget
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/2
	 */
	function filter_ef_story_budget_term_column_value( $column_name, $post, $parent_term ) {

		// We only want to modify the 'author' column
		if ( 'author' != $column_name )
			return $column_name;

		$co_authors = get_coauthors( $post->ID );
		$co_authors_names = '';
		foreach( $co_authors as $co_author ) {
			$co_authors_names .= $co_author->display_name . ', ';
		}
		return rtrim( $co_authors_names, ', ' );
	}

}

global $coauthors_plus;
$coauthors_plus = new coauthors_plus();

if ( ! function_exists('wp_notify_postauthor') ) :
/**
 * Notify a co-author of a comment/trackback/pingback to one of their posts.
 * This is a modified version of the core function in wp-includes/pluggable.php that
 * supports notifs to multiple co-authors. Unfortunately, this is the best way to do it :(
 *
 * @since 2.6.2
 *
 * @param int $comment_id Comment ID
 * @param string $comment_type Optional. The comment type either 'comment' (default), 'trackback', or 'pingback'
 * @return bool False if user email does not exist. True on completion.
 */
function wp_notify_postauthor( $comment_id, $comment_type = '' ) {
	$comment = get_comment( $comment_id );
	$post    = get_post( $comment->comment_post_ID );
	$coauthors = get_coauthors( $post->ID );
	foreach( $coauthors as $author ) {

		// The comment was left by the co-author
		if ( $comment->user_id == $author->ID )
			return false;

		// The co-author moderated a comment on his own post
		if ( $author->ID == get_current_user_id() )
			return false;

		// If there's no email to send the comment to
		if ( '' == $author->user_email )
			return false;

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		if ( empty( $comment_type ) ) $comment_type = 'comment';

		if ('comment' == $comment_type) {
			$notify_message  = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
			$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
		} elseif ('trackback' == $comment_type) {
			$notify_message  = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: website name, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$notify_message .= __('You can see all trackbacks on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
		} elseif ('pingback' == $comment_type) {
			$notify_message  = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Excerpt: ') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
			$notify_message .= __('You can see all pingbacks on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
		}
		$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
		$notify_message .= sprintf( __('Permalink: %s'), get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id ) . "\r\n";
		if ( EMPTY_TRASH_DAYS )
			$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		else
			$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

		$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

		if ( '' == $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: $comment->comment_author_email";
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}

		$message_headers = "$from\n"
			. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

		if ( isset($reply_to) )
			$message_headers .= $reply_to . "\n";

		$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment_id );
		$subject = apply_filters( 'comment_notification_subject', $subject, $comment_id );
		$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment_id );

		@wp_mail( $author->user_email, $subject, $notify_message, $message_headers );
	}

	return true;
}
endif;

if ( !function_exists('wp_notify_moderator') ) :
/**
 * Notifies the moderator of the blog about a new comment that is awaiting approval.
 * This is a modified version of the core function in wp-includes/pluggable.php that
 * supports notifs to multiple co-authors. Unfortunately, this is the best way to do it :(
 *
 * @since 2.6.2
 *
 * @param int $comment_id Comment ID
 * @return bool Always returns true
 */
function wp_notify_moderator( $comment_id ) {
	global $wpdb;

	if ( 0 == get_option( 'moderation_notify' ) )
		return true;

	$comment = get_comment($comment_id);
	$post = get_post($comment->comment_post_ID);
	$coauthors = get_coauthors( $post->ID );
	// Send to the administration and to the co-authors if the co-author can modify the comment.
	$email_to = array( get_option('admin_email') );
	foreach( $coauthors as $user ) {
		if ( user_can($user->ID, 'edit_comment', $comment_id) && !empty($user->user_email) && ( get_option('admin_email') != $user->user_email) )
			$email_to[] = $user->user_email;
	}

	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
	$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	switch ($comment->comment_type)
	{
		case 'trackback':
			$notify_message  = sprintf( __('A new trackback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			break;
		case 'pingback':
			$notify_message  = sprintf( __('A new pingback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			break;
		default: //Comments
			$notify_message  = sprintf( __('A new comment on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
			$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			break;
	}

	$notify_message .= sprintf( __('Approve it: %s'),  admin_url("comment.php?action=approve&c=$comment_id") ) . "\r\n";
	if ( EMPTY_TRASH_DAYS )
		$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
	else
		$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
	$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

	$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
 		'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
	$notify_message .= admin_url("edit-comments.php?comment_status=moderated") . "\r\n";

	$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
	$message_headers = '';

	$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
	$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
	$message_headers = apply_filters('comment_moderation_headers', $message_headers);

	foreach ( $email_to as $email )
		@wp_mail($email, $subject, $notify_message, $message_headers);

	return true;
}
endif;