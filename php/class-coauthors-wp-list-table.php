<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there

require_once( ABSPATH . 'wp-admin/includes/screen.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * List all of the available Co-Authors within the system
 */
class CoAuthors_WP_List_Table extends WP_List_Table {

	var $is_search = false;

	function __construct() {
		if( !empty( $_GET['s'] ) )
			$this->is_search = true;

		parent::__construct( array(
				'plural' => __( 'Co-Authors', 'co-authors-plus' ),
				'singular' => __( 'Co-Author', 'co-authors-plus' ),
			) );
	}

	/**
	 * Perform Co-Authors Query
	 */
	function prepare_items() {
		global $coauthors_plus;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable ) ;

		$paged = ( isset( $_REQUEST['paged'] ) ) ? intval( $_REQUEST['paged'] ) : 1;
		$per_page = 20;

		$args = array(
				'paged'          => $paged,
				'posts_per_page' => $per_page,
				'post_type'      => $coauthors_plus->guest_authors->post_type,
				'post_status'    => 'any',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
			);

		if ( $_GET['view'] == 'with-linked-account' ) {
			$args['meta_key'] = $coauthors_plus->guest_authors->get_post_meta_key( 'linked_account' );
			$args['meta_compare'] = '!=';
			$args['meta_value'] = '';
		}
		elseif ( $_GET['view'] == 'without-linked-account' ) {
			add_filter( 'posts_where', array( $this, 'filter_query_without_linked_account' ) );
		}

		if( $this->is_search )
			add_filter( 'posts_where', array( $this, 'filter_query_for_search' ) );

		$author_posts = new WP_Query( $args );
		$items = array();
		foreach( $author_posts->get_posts() as $author_post ) {
			$items[] = $coauthors_plus->guest_authors->get_guest_author_by( 'id', $author_post->ID );
		}

		if( $this->is_search )
			remove_filter( 'posts_where', array( $this, 'filter_query_for_search' ) );

		if ( $_GET['view'] == 'without-linked-acount' )
			remove_filter( 'posts_where', array( $this, 'filter_query_without_linked_account' ) );

		$this->items = $items;

		$this->set_pagination_args( array(
			'total_items' => $author_posts->found_posts,
			'per_page' => $per_page,
			) );
	}

	function filter_query_for_search( $where ) {
		global $wpdb;
		$var = '%' . sanitize_text_field( $_GET['s'] ) . '%';
		$where .= $wpdb->prepare( ' AND (post_title LIKE %s OR post_name LIKE %s )', $var, $var);
		return $where;
	}

	function filter_query_without_linked_account( $where ) {
		global $wpdb, $coauthors_plus;
		$where .= $wpdb->prepare( " AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s)", $coauthors_plus->guest_authors->get_post_meta_key( 'linked_account' ) );
		return $where;
	}

	/**
	 * Either there are no guest authors, or the search doesn't match any
	 */
	function no_items() {
		_e( 'No matching guest authors were found.', 'co-authors-plus' );
	}

	/**
	 * Generate the columns of information to be displayed on our list table
	 *
	 * @todo display the post count
	 */
	function get_columns() {
		$columns = array(
				'display_name'   => __( 'Display Name', 'co-authors-plus' ),
				'first_name'     => __( 'First Name', 'co-authors-plus' ),
				'last_name'      => __( 'Last Name', 'co-authors-plus' ),
				'user_email'     => __( 'E-mail', 'co-authors-plus' ),
				'linked_account' => __( 'Linked Account', 'co-authors-plus' ),
			);
		return $columns;
	}

	/**
	 * Render a single row
	 */
	function single_row( $item ) {
		static $alternate_class = '';
		$alternate_class = ( $alternate_class == '' ? ' alternate' : '' );
		$row_class = ' class="guest-author-static' . $alternate_class . '"';

		echo '<tr id="guest-author-' . $item->ID . '"' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Render columns, some are overridden below
	 */
	function column_default( $item, $column_name ) {

		switch( $column_name ) {
			case 'first_name':
			case 'last_name':
				return $item->$column_name;
			case 'user_email':
				return '<a href="' . esc_attr( 'mailto:' . $item->user_email ) . '">' . esc_html( $item->user_email ) . '</a>';
		}
	}

	/**
	 * Render display name, e.g. author name
	 */
	function column_display_name( $item ) {

		$item_edit_link = get_edit_post_link( $item->ID );

		$output = get_avatar( $item->user_email, 32 );
		// @todo caps check to see whether the user can edit. Otherwise, just show the name
		$output .= '<a href="' . esc_url( $item_edit_link ) . '">' . esc_html( $item->display_name ) . '</a>';

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $item_edit_link ) . '">' . __( 'Edit', 'co-authors-plus' ) . '</a>';
		$actions['delete'] = '<a href="#">' . __( 'Delete', 'co-authors-plus' ) . '</a>';
		$actions = apply_filters( 'coauthors_guest_author_row_actions', $actions, $item );
		$output .= $this->row_actions( $actions, false );

		return $output;
	}

	/**
	 * Render linked account
	 */
	function column_linked_account( $item ) {
		global $coauthors_plus;
		$username = get_post_meta( $item->ID, $coauthors_plus->guest_authors->get_post_meta_key( 'linked_account' ), true );
		if ( $username ) {
			$account = get_user_by( 'login', $username );
			if ( $account ) {
				if ( current_user_can( 'edit_users' ) ) {
					return '<a href="' . admin_url( 'user-edit.php?user_id=' . $account->ID ) . '">' . esc_html( $username ) . '</a>';
				}
				return $username;
			}
		}
		return '';
	}

	/**
	 * Show filters (views) on top
	 */
	function get_views() {
		global $wpdb, $coauthors_plus;

		$views = array();
		$all = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT count(ID) FROM $wpdb->posts WHERE post_type=%s", $coauthors_plus->guest_authors->post_type
		) );

		$query = $wpdb->prepare(
			"SELECT count(ID) FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key=%s
			WHERE post_type=%s",
			$coauthors_plus->guest_authors->get_post_meta_key( 'linked_account' ),
			$coauthors_plus->guest_authors->post_type
		);
		$with_linked_account = (int) $wpdb->get_var( $query );

		$without_linked_account = $all - $with_linked_account;

		$views = array(
			'all' => array( 'count' => $all, 'view' => NULL, 'label' => __( 'All' ) ),
			'with-linked-account' => array( 'count' => $with_linked_account, 'view' => 'with-linked-account', 'label' => __( 'With Linked Account' ) ),
			'without-linked-account' => array( 'count' => $without_linked_account, 'view' => 'without-linked-account', 'label' => __( 'Without Linked Account' ) ),
		);

		foreach ( $views as $key => $view ) {
			$url = admin_url( 'users.php?page=view-guest-authors' );
			if ( !empty( $view['view'] ) ) $url .= '&view=' . $view['view'];
			$class = '';
			if ( $_GET['view'] == $view['view'] || ( empty( $_GET['view'] ) && empty( $view['view'] ) ) ) $class = ' class="current"';
			$views[$key] = sprintf( '<a href="%s" %s >%s <span class="count">(%d)</span></a>', $url, $class, $view['label'], $view['count'] );
		}

		return $views;
	}

	function display() {
		global $coauthors_plus;
		echo '<form>';
		$this->views();
		echo '<input type="hidden" name="page" value="view-guest-authors" />';
		$this->search_box( $coauthors_plus->guest_authors->labels['search_items'], 'guest-authors' );
		echo '</form>';
		parent::display();
	}

}