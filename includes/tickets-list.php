<?php
/* Events list
*/

include_once 'ticket.php';

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BFT_Tickets_List extends WP_List_Table {
	
	/** Class constructor */
	public function __construct($_filter_status) {
		parent::__construct( [
			'singular' => __( 'Ticket', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Tickets', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
	}
	
	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'ticket'    => 'Ticket',
			'status'    => 'Status',
			'user' => 'User',
			'orderStatus' => 'Order status',
			'email' => 'Email',
			'phone' => 'Phone',
			'notes' => 'Notes',
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
			'<input type="checkbox" name="bulk-ticket-update[]" value="%s" />', $item['id']
		);
	}
	
	/**
	 * Render the ticket column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_ticket( $item ) {
		$title = '<strong>' . $item['ticket'] . '</strong>';

		$actions = ['ID' => sprintf( 'ID: %d', absint($item['id']) )];

		return $title . $this->row_actions( $actions );
	}
	
	/**
	 * Render the status column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_status( $item ) {
		$check_nonce = wp_create_nonce( 'bft_ticket_check' );
		$uncheck_nonce = wp_create_nonce( 'bft_ticket_uncheck' );

		if($item['status'] == 1) {
			$title = 'New';
			$actions = [
				'check' => sprintf( '<a href="?page=%s&action=%s&ticket=%s&_wpnonce=%s">Check</a>', esc_attr( $_REQUEST['page'] ), 'ticket-check', absint( $item['id'] ), $check_nonce )
			];
		}
		else {
			$title = 'Checked';
			$actions = [
				'uncheck' => sprintf( '<a href="?page=%s&action=%s&ticket=%s&_wpnonce=%s">Uncheck</a>', esc_attr( $_REQUEST['page'] ), 'ticket-uncheck', absint( $item['id'] ), $uncheck_nonce )
			];
		}

		// return $title . $this->row_actions( $actions );
		return $title;
	}
	
	/**
	 * Render the order status column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_orderStatus( $item ) {
		$title = $item['orderStatus'];

		$order = new WC_Order($item['orderId']);
		$actions = ['order' => sprintf( '<a href="%s">Edit order</a>', $order->get_edit_order_url())];

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
		return $item[ $column_name ];
		/*
		switch( $column_name ) { 
			case 'Timestamp':
			case 'Status':
			  return $item[ $column_name ];
			default:
			  return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
		*/
	}
	
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	protected function record_count() {
		// Get tickets including all filters
		$ticktes = $this->get_tickets(PHP_INT_MAX, 1);
		
		// Return tickets count
		return count($ticktes);
	}
	
	/**
	 * Retrieve tickets data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	protected function get_tickets( $per_page = 5, $page_numer = 1 ) {
		// Query WooCommerce for all order ID's
		$query = new WC_Order_Query( array(
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'return' => 'ids',
		) );
		$orderIDs = $query->get_orders();
		
		$ticketsDefs = array();
		$productsDefs = array();
		
		$bftTickets = array();
		// Loop orders IDs
		foreach($orderIDs as $orderId)
		{
			// Get by order id
			$order = BFT_Order::GetByID($orderId);
			
			foreach($order->Tickets as $ticket)
			{
				if(!array_key_exists($ticket->TicketID, $ticketsDefs))
				{
					$ticketsDefs[$ticket->TicketID] = BFT_Ticket::GetByID($ticket->TicketID);
				}
				
				$dbTicket = $ticketsDefs[$ticket->TicketID];
				
				if(!array_key_exists($dbTicket->ProductID, $productsDefs))
				{
					$productsDefs[$dbTicket->ProductID] = wc_get_product($dbTicket->ProductID);
				}
				
				$dbProduct = $productsDefs[$dbTicket->ProductID];
				
				$ticketArray = array
				(
					'id'		=> $ticket->ID,
					'ticketId'    => $dbProduct->get_id(),
					'ticket'    => $dbProduct->get_name(),
					'status'    => $ticket->Status,
					'user' => $order->OrderBillingName,
					'orderId' => $order->OrderId,
					'orderStatus' => $order->Status,
					'orderKey' => $order->OrderKey,
					'email' => $order->OrderBillingEmail,
					'phone' => $order->OrderBillingPhone,
					'notes' => $order->OrderCustomerNote
				);
				array_push($bftTickets, $ticketArray);
			}
		}
		
		$ticket_status_filter = $_POST['tickets-filter-status'];
		$products_filter = $_POST['ticket-name'];
		$order_status_filter = $_POST['order-filter-status'];
		
		$tickets = array_filter($bftTickets, function($ticket) use ($ticket_status_filter, $products_filter, $order_status_filter)
		{
			return
				(
					empty($products_filter)
					|| in_array($ticket['ticketId'], $products_filter)
				)
				&&
				(
					empty($ticket_status_filter) 
					|| $ticket_status_filter == -1
					|| $ticket_status_filter == $ticket['status']
				)
				&&
				(
					empty($order_status_filter) 
					|| $order_status_filter == -1
					|| $order_status_filter == 'wc-' . $ticket['orderStatus']
				);
		});
		
		$offset = ($page_numer - 1) * $per_page;
		return array_slice($tickets, $offset, $per_page);
	}
	
	function extra_tablenav( $which ) {
		if($which == 'top'){
			?>
			<div class="alignleft actions">
				<select name="tickets-filter-status">
					<option value="-1" <?php echo (isset($_POST["tickets-filter-status"]) && $_POST["tickets-filter-status"] == -1) ? "selected" : "";?> >Ticket status</option>
					<option value="0" <?php echo (isset($_POST["tickets-filter-status"]) && $_POST["tickets-filter-status"] == 0) ? "selected" : "";?> >All</option>
					<option value="1"<?php echo (isset($_POST["tickets-filter-status"]) && $_POST["tickets-filter-status"] == 1) ? "selected" : "";?> >New</option>
					<option value="2"<?php echo (isset($_POST["tickets-filter-status"]) && $_POST["tickets-filter-status"] == 2) ? "selected" : "";?> >Checked</option>
				</select>
			</div>
			<div class="alignleft actions">
				<select name="order-filter-status">
					<option value="-1" <?php echo (isset($_POST["order-filter-status"]) && $_POST["order-filter-status"] == -1) ? "selected" : "";?> >Order status</option>
				<?php
					$wc_order_statuses = wc_get_order_statuses();
					foreach($wc_order_statuses as $status_key => $order_status)
					{?>
						<option value="<?php echo $status_key; ?>" <?php echo (isset($_POST["order-filter-status"]) && $_POST["order-filter-status"] == $status_key) ? "selected" : "";?> ><?php echo $order_status; ?></option>
					 <?php
					}
				?>
				</select>
				<input type="submit" name="tickets-filter-action" class="button" value="Filter"/>
			</div>
			<?php
		}
	}
	
	function get_bulk_actions() {
		$actions = array(
			'bulk-ticket-check' => 'Check',
			'bulk-ticket-uncheck' => 'Uncheck'
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

		$per_page     = $this->get_items_per_page( 'tickets_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
		
		$this->items = $this->get_tickets($per_page, $current_page);
	}
}