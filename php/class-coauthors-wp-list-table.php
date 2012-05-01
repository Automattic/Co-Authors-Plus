<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there

require_once( ABSPATH . 'wp-admin/includes/screen.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * List all of the available Co-Authors within the system
 */
class CoAuthors_WP_List_Table extends WP_List_Table {

	function __construct() {
		parent::__construct( array(
				'plural' => __( 'Co-Authors', 'co-authors-plus' ),
				'singular' => __( 'Co-Author', 'co-authors-plus' ),
			) );
	}

	/**
	 *
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
				'paged' => $paged,
				'per_page' => $per_page,
			);
		$this->items = $coauthors_plus->get_coauthors( $args );

		// $users_per_page = $this->get_items_per_page( $per_page );

		$this->set_pagination_args( array(
			'total_items' => 20,
			'per_page' => $per_page,
			) );
	}

	function no_items() {
		_e( 'No matching co-authors were found.', 'co-authors-plus' );
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
				'type'           => __( 'Type', 'co-authors-plus' ),
			);
		return $columns;
	}

	/**
	 *
	 */
	function single_row( $item ) {
		static $alternate_class = '';
		$alternate_class = ( $alternate_class == '' ? ' alternate' : '' );
		$row_class = ' class="coauthor-static' . $alternate_class . '"';

		echo '<tr id="coauthor-' . $item->ID . '"' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 *
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

	function column_display_name( $item ) {
		$output = esc_html( $item->display_name );

		return $output;
	}

	/**
	 * Display the type of user object this is
	 */
	function column_type( $item ) {
		
		switch( $item->type ) {
			case 'coauthor':
				$output = __( 'Co-Author', 'co-authors-plus' );
				break;
			case 'wpuser':
				$output = __( 'WordPress User', 'co-authors-plus' );
				break;
			case 'both':
				$output = __( 'Co-Author & User', 'co-authors-plus' );
				break;
			default:
				$output = '';
				break;
		}
		return $output;
	}

}