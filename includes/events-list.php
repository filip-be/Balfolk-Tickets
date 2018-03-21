<?php
/* Events list
*/


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BFT_Events_List extends WP_List_Table {
	protected $Filter_Status = 1;
	
	/** Class constructor */
	public function __construct($_filter_status) {
		parent::__construct( [
			'singular' => __( 'Ticket', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Tickets', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
		
		$this->Filter_Status = $_filter_status;
	}
	
	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'Name'    => 'Name',
			'Timestamp' => 'Created',
			'Status' => 'Status'
		];

		return $columns;
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
			'<input type="checkbox" name="bulk-event-archive[]" value="%s" />', $item['PK_ID']
		);
	}
	
	/**
	 * Render the Name column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_Name( $item ) {
		$edit_nonce = wp_create_nonce( 'bft_edit_event' );
		$archive_nonce = wp_create_nonce( 'bft_archive_event' );
		$restore_nonce = wp_create_nonce( 'bft_restore_event' );

		$title = '<strong>' . $item['Name'] . '</strong>';

		if($item['Status'] == 1) {
			$actions = [
				'ID' => sprintf( 'ID: %d', absint($item['PK_ID']) ),
				'edit' => sprintf( '<a href="?page=%s&action=%s&event=%s&_wpnonce=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), 'event-edit', absint( $item['PK_ID'] ), $edit_nonce ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&event=%s&_wpnonce=%s">Archive</a>', esc_attr( $_REQUEST['page'] ), 'event-archive', absint( $item['PK_ID'] ), $archive_nonce )
			];
		}
		else {
			$actions = [
				'ID' => sprintf( 'ID: %d', absint($item['PK_ID']) ),
				'restore' => sprintf( '<a href="?page=%s&action=%s&event=%s&_wpnonce=%s">Restore</a>', esc_attr( $_REQUEST['page'] ), 'event-restore', absint( $item['PK_ID'] ), $restore_nonce )
			];
		}

		return $title . $this->row_actions( $actions );
	}
	
	/**
	 * Render the column
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'Timestamp':
			case 'Status':
			  return $item[ $column_name ];
			default:
			  return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	public function get_event_query($count) {
		global $wpdb;
		$sql = "SELECT ".($count ? "COUNT(*)" : "e.*")." FROM {$wpdb->prefix}bft_event e ";
		if($this->Filter_Status > 0)
		{
			$sql .= sprintf("WHERE e.Status = %d ", $this->Filter_Status);
		}
		return $sql;
	}
	
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	protected function record_count() {
		global $wpdb;

		$sql = $this->get_event_query(true);

		return $wpdb->get_var( $sql );
	}
	
	/**
	 * Retrieve tickets data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	protected function get_events( $per_page = 5, $page_number = 1 ) {
		
		global $wpdb;

		$sql = $this->get_event_query(false);
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		return $result;
	}
	
	function extra_tablenav( $which ) {
		if($which == 'top'){
			?>
			<div class="alignleft actions">
				<select name="events-filter">
					<option value="1" <?php echo (isset($_POST["events-filter"]) && $_POST["events-filter"] == 1) ? "selected" : "";?> >Active</option>
					<option value="2"<?php echo (isset($_POST["events-filter"]) && $_POST["events-filter"] == 2) ? "selected" : "";?> >Archived</option>
					<option value="-1"<?php echo (isset($_POST["events-filter"]) && $_POST["events-filter"] == -1) ? "selected" : "";?> >All</option>
				</select>
				<input type="submit" name="events-filter-action" class="button" value="Filter"/>
			</div>
			<?php
		}
	}
	
	function get_bulk_actions() {
		$actions = array(
			'bulk-event-archive' => 'Archive',
			'bulk-event-restore' => 'Restore'
		);
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

		$per_page     = $this->get_items_per_page( 'events_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
		
		$this->items = $this->get_events($per_page, $current_page);
	}
}