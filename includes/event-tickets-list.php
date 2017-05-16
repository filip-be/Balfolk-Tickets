<?php

/* Woocommerce products - "event tickets" class
*/


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BFT_Event_Tickets_List extends WP_List_Table {
	protected $EventID = -1;
	protected $EventTicketsOnly = false;

	/** Class constructor 
	 *
	 * @param int $event_id connected event id
	 * @param bool $event_tickets_only include tickets for this event only. If false exclude tickets connected to this event.
	 *
	 */
	public function __construct($_event_id, $_event_tickets_only) {
		parent::__construct( [
			'singular' => __( 'Ticket', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Tickets', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
		
		$this->EventID = $_event_id;
		$this->EventTicketsOnly = $_event_tickets_only;
	}

	protected function get_tickets_query($count) {
		global $wpdb;
		$sql = "SELECT ".($count ? "COUNT(*)" : "p.*")." FROM {$wpdb->prefix}posts p ";
		$sql .= "WHERE p.post_type = 'product' ";
		$sql .= "AND ";
		if(!$this->EventTicketsOnly) {
			$sql .= "NOT ";
		}
		$sql .= " EXISTS(SELECT 1 FROM {$wpdb->prefix}bft_ticket t WHERE t.FK_ProductID = p.ID AND t.FK_EventID = {$this->EventID} AND t.Status = 1)";
		return $sql;
	}

	/**
	 * Retrieve tickets data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	protected function get_tickets($per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$sql = $this->get_tickets_query(false);

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		return $result;
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	protected function record_count() {
		global $wpdb;

		$sql = $this->get_tickets_query(true);

		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No tickets avaliable.', 'sp' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			//case 'post_title':
			//	return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the post_title item
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_post_title( $item ) {
		$add_nonce = wp_create_nonce( 'bft_add_product' );
		$remove_nonce = wp_create_nonce( 'bft_remove_product' );
		
		$title = sprintf('<a class="row-title" href="post.php?post=%d&amp;action=edit">%s</a>', absint($item['ID']), esc_attr($item['post_title']));
		
		if($this->EventTicketsOnly) {
			$actions = [
				'ID' => sprintf( 'ID: %d', absint($item['ID']) ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&event=%s&_wpnonce=%s&eventAction=%s&ticket=%s">Remove</a>', esc_attr( $_REQUEST['page'] ), 'event-edit', absint( $this->EventID ), $remove_nonce, 'event-ticket-remove', absint($item['ID']) )
			];
		}
		else {
			$actions = [
				'ID' => sprintf( 'ID: %d', absint($item['ID']) ),
				'add' => sprintf( '<a href="?page=%s&action=%s&event=%s&_wpnonce=%s&eventAction=%s&ticket=%s">Add</a>', esc_attr( $_REQUEST['page'] ), 'event-edit', absint( $this->EventID ), $add_nonce, 'event-ticket-add', absint($item['ID']) )
			];
		}
		
		return $title . $this->row_actions( $actions );
	}
	
	
	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['ID']
		);
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'post_title'    => 'Product ticket'
		];

		return $columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = null;
		if($this->EventTicketsOnly) {
			$actions = [
				'bulk-ticket-remove' => 'Remove all',
			];
		}
		else {
			$actions = [
				'bulk-ticket-add' => 'Add all'
			];
		}
			
		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 * 
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		
		$this->_column_headers = array(
			$columns,
			$hidden,
			$sortable
		);

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'tickets_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = $this->get_tickets( $per_page, $current_page );
	}

}