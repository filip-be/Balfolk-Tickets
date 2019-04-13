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
	
	protected function get_wp_products($per_page = 0, $page_number = 1) {
		global $wpdb;
		
		// Get product IDs for this event
		$eventTicketsQuery = "SELECT t.FK_ProductID FROM {$wpdb->prefix}bft_ticket t WHERE t.FK_EventID = {$this->EventID} AND t.Status = 1";
		
		$eventTickets = $wpdb->get_results( $eventTicketsQuery, 'ARRAY_A' );
		
		// Post query filters
		$filters = array(
			'post_type' => 'product',
			'lang' => pll_default_language('slug')
		);
		
		if($per_page != 0 ) {
			$filters['offset'] = ( $page_number - 1 ) * $per_page;
			$filters['posts_per_page'] = $per_page;
		}
		
		// Order by query
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$filters['orderby'] = esc_sql( $_REQUEST['orderby'] );
			$filters['order'] = ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		// Get posts (products)
		$posts = get_posts( $filters );
		
		// Filter products matching this event
		$products = array();
		foreach($posts as $post) {
			// XNOR operation
			if(!(in_array(array('FK_ProductID' => $post->ID), $eventTickets) 
					xor $this->EventTicketsOnly)) {
				array_push($products, $post->to_array());
			}
		}
		return $products;
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
		return $this->get_wp_products($per_page, $page_number);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	protected function record_count() {
		$products = $this->get_wp_products();

		return sizeof($products);
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

		$per_page     = $this->get_items_per_page( 'tickets_per_page', 100 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = $this->get_tickets( $per_page, $current_page );
	}

}