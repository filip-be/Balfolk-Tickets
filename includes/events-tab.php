<?php

/* Include / remove events tab
 */
require_once 'event.php';
require_once 'ticket.php';
require_once 'events-list.php';
require_once 'tickets-list.php';
require_once 'event-tickets-list.php';
 
class BFT_EventTab {
	 /**
	 * Singleton instance
	 */
	static $instance = false;
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		if(is_admin()) {
			add_action('admin_menu', array(__CLASS__, '_create_events_menu'));
			add_filter( 'set-screen-option', array( __CLASS__, '_tickets_save_options' ), 10, 3);
		}
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BF_Tickets
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static function _create_events_menu() {
		// make the main menu item
		add_menu_page(
			'Balfolk events',
			'Balfolk events',
			'view_woocommerce_reports',
			'balfolk-events',
			array( __CLASS__, '_events_page' ),
			'dashicons-format-audio',
			21
		);
		
		add_submenu_page(
			'balfolk-events'
			,'Edit events'
			,'Edit events'
			,'view_woocommerce_reports'
			,'balfolk-events'
			,array( __CLASS__, '_events_page')
		);
		
		
		$ticketsHook = add_submenu_page(
			'balfolk-events'
			,'View tickets'
			,'View tickets'
			,'view_woocommerce_reports'
			,'balfolk-events-export'
			,array( __CLASS__, '_tickets_page')
		);
		
		
		add_action( "load-$ticketsHook", array( __CLASS__, '_tickets_add_options') );
		
		// add_filter( 'set-screen-option', array( __CLASS__, '_tickets_save_options'), 10, 3 );
	}
	
	protected static function _process_events_actions($field_event_name) {
		if( isset($_POST[$field_event_name]) && strlen($_POST[$field_event_name]) > 0) {
			if(isset($_GET['action'])
				&& $_GET['action'] == 'event-edit'
				&& isset($_GET['event']))
			{
				// Create new event
				BFT_Event::SEditNameDate($_GET['event'], $_POST[$field_event_name], $_POST[$field_event_name.'_date']);
				
				// Put a "event added" message on the screen
				?><div class="updated"><p><strong><?php _e('Event editted.', 'bft_event' ); ?></strong></p></div><?php
			}
			else
			{
				// Create new event
				BFT_Event::SCreate($_POST[$field_event_name]);
				
				// Put a "event added" message on the screen
				?><div class="updated"><p><strong><?php _e('Event added.', 'bft_event' ); ?></strong></p></div><?php
			}
		}
		
		// Single event archived
		if(isset($_GET['action'])
			&& $_GET['action'] == 'event-archive'
			&& isset($_GET['event'])
			&& isset($_GET['_wpnonce'])
			&& wp_verify_nonce(esc_attr($_GET['_wpnonce']), 'bft_archive_event')) {
			BFT_Event::SArchive($_GET['event']);
			?><div class="updated"><p><strong><?php _e('Event archived.', 'bft_event' ); ?></strong></p></div><?php
		}
		// Single event restored
		if(isset($_GET['action'])
			&& $_GET['action'] == 'event-restore'
			&& isset($_GET['event'])
			&& isset($_GET['_wpnonce'])
			&& wp_verify_nonce(esc_attr($_GET['_wpnonce']), 'bft_restore_event')) {
			BFT_Event::SRestore($_GET['event']);
			?><div class="updated"><p><strong><?php _e('Event restored.', 'bft_event' ); ?></strong></p></div><?php
		}
		// Bulk event archive
		if(isset($_POST['action'])
			&& $_POST['action'] == 'bulk-event-archive'
			&& isset($_POST['bulk-event-archive'])) {
			foreach($_POST['bulk-event-archive'] as $eventID) {
				BFT_Event::SArchive($eventID);
			}
			?><div class="updated"><p><strong><?php _e('Events archived.', 'bft_event' ); ?></strong></p></div><?php
		}
		// Bulk event restore
		if(isset($_POST['action'])
			&& $_POST['action'] == 'bulk-event-restore'
			&& isset($_POST['bulk-event-archive'])) {
			foreach($_POST['bulk-event-archive'] as $eventID) {
				BFT_Event::SRestore($eventID);
			}
			?><div class="updated"><p><strong><?php _e('Events restored.', 'bft_event' ); ?></strong></p></div><?php
		}
	}
	
	protected static function _process_event_actions($field_event_name)
	{
		if(isset($_GET['action'])
			&& $_GET['action'] == 'event-edit'
			&& isset($_GET['event']))
		{
			$event = BFT_Event::GetByID($_GET['event']);
			// Single ticket add
			if(isset($_GET['eventAction'])
				&& $_GET['eventAction'] == 'event-ticket-add'
				&& isset($_GET['ticket'])
				&& isset($_GET['_wpnonce'])
				&& wp_verify_nonce(esc_attr($_GET['_wpnonce']), 'bft_add_product'))
			{
				$event->AddProduct($_GET['ticket']);
				?><div class="updated"><p><strong><?php _e('Ticket added.', 'bft_event' ); ?></strong></p></div><?php
			}
			
			// Single ticket remove
			if(isset($_GET['eventAction'])
				&& $_GET['eventAction'] == 'event-ticket-remove'
				&& isset($_GET['ticket'])
				&& isset($_GET['_wpnonce'])
				&& wp_verify_nonce(esc_attr($_GET['_wpnonce']), 'bft_remove_product'))
			{
				$event->RemoveProduct($_GET['ticket']);
				?><div class="updated"><p><strong><?php _e('Ticket removed.', 'bft_event' ); ?></strong></p></div><?php
			}
			
			// Multiple ticket add
			if(isset($_POST['action'])
				&& $_POST['action'] == 'bulk-ticket-add'
				&& isset($_POST['bulk-action'])
				&& isset($_POST['_wpnonce']))
			{
				foreach($_POST['bulk-action'] as $productID) {
					$event->AddProduct($productID);
				}
				?><div class="updated"><p><strong><?php _e('Tickets added.', 'bft_event' ); ?></strong></p></div><?php
			}
			
			// Multiple ticket add
			if(isset($_POST['action'])
				&& $_POST['action'] == 'bulk-ticket-remove'
				&& isset($_POST['bulk-action'])
				&& isset($_POST['_wpnonce']))
			{
				foreach($_POST['bulk-action'] as $productID) {
					$event->RemoveProduct($productID);
				}
				?><div class="updated"><p><strong><?php _e('Tickets removed.', 'bft_event' ); ?></strong></p></div><?php
			}
		}
	}
	
	public static function _events_page()
	{
		// Check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		// Process POST/GET actions
		$field_event_name = 'bft_event_name';
		self::_process_events_actions($field_event_name);
		self::_process_event_actions($field_event_name);
		
		if(isset($_GET['action'])
			&& $_GET['action'] == 'event-edit'
			&& isset($_GET['event']))
		{
			self::print_event_page($_GET['event'], $field_event_name);
		}
		else
		{
			self::print_events_page($field_event_name);
		}
	}
	
	public static function print_events_page($field_event_name) {
		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<form name="New_Event" method="POST" action="">
				<p>
					<?php _e("Event name:", 'bft_events' ); ?> 
					<input type="text" name="<?= $field_event_name; ?>" value="" size="20">
					<input type="submit" name="Submit" class="button-primary" value="Add new event" />
				</p>
			</form>
			<hr />
			<form name="events-edit" method="POST" action="">
			<?php
				$FilterStatus = 1;
				if(isset($_POST["events-filter-action"])
					&& $_POST["events-filter-action"] == 'Filter'
					&& isset($_POST["events-filter"])) {
					$FilterStatus = $_POST["events-filter"];
				}
				
				$events_list = new BFT_Events_List($FilterStatus);
				$events_list->prepare_items();
				$events_list->display();
			?>
			</form>
		</div>
		<?php
	}
	
	public static function print_event_page($eventID, $field_event_name) {
		$event = BFT_Event::GetByID($eventID);
		?>
		<div class="wrap">
			<h1><?= esc_html($event->Name) ?> - edit event</h1>
			<form name="Edit_Event" method="POST" action="">
				<p>
					<?php _e("Event name:", 'bft_events' ); ?> 
					<input type="text" name="<?= $field_event_name; ?>" value="<?= esc_html($event->Name) ?>" size="20"/><br/>
					<?php _e("Sale start time:", 'bft_events' ); ?> 
					<input type="datetime-local" name="<?= $field_event_name; ?>_date" value="<?= date("Y-m-d\TH:i:s", strtotime($event->SaleStartDate)) ?>"/> (UTC time)<br/>
					<br/>
					<input type="submit" name="Submit" class="button-primary" value="Edit" />
				</p>
			</form>
			<hr />
			<h2>Connected products</h2>
			<form name="event-tickets-edit" method="POST" action="">
			<?php
				$tickets_list = new BFT_Event_Tickets_List($event->ID, true);
				$tickets_list->prepare_items();
				$tickets_list->display();
			?>
			</form>
			<hr/>
			<h2>Add products</h2>
			<form name="event-tickets-add" method="POST" action="">
			<?php
				$tickets_list = new BFT_Event_Tickets_List($event->ID, false);
				$tickets_list->prepare_items();
				$tickets_list->display();
			?>
			</form>
		</div>
		<?php
	}
	
	protected static function _process_tickets_actions() {
		if(!empty($_POST['action'])
			&& $_POST['action'] != -1
			&& !empty($_POST['bulk-ticket-update']))
		{
			$newStatus = $_POST['action'] == 'bulk-ticket-check' ? 2 : 1;
			
			foreach($_POST['bulk-ticket-update'] as $ticketID)
			{
				$ticket = BFT_OrderTicket::GetByID($ticketID);
				$ticket->UpdateStatus($newStatus);
			}
		}
		else if(!empty($_GET['action'])
			&& $_GET['action'] != -1
			&& !empty($_GET['ticket']))
		{
			$newStatus = $_GET['action'] == 'ticket-check' ? 2 : 1;
			
			$ticket = BFT_OrderTicket::GetByID($_GET['ticket']);
			$ticket->UpdateStatus($newStatus);
		}
	}
	
	protected static function IsChecked($postData, $value)
	{
		if(!empty($postData))
		{
			foreach($postData as $data)
			{
				if($data == $value)
				{
					return 'checked';
				}
			}
			return '';
		}
		else
		{	
			return 'checked';
		}
	}
	
	public static function print_tickets_page() {
		// Get available tickets
		$products = BFT_Ticket::GetAvailableProducts();
		?>
		<h1><?= esc_html(get_admin_page_title()); ?></h1>
		<div class="wrap">
			<form name="events-edit" method="POST" action="">
				<p>
				<?php
					foreach($products as $product)
					{
						echo '<input type="checkbox" id="ticket-name'.$product->get_id().'" name="ticket-name[]" value="'.$product->get_id().'" '.self::IsChecked($_POST['ticket-name'], $product->get_id()).' />';
						echo '<label for="ticket-name'.$product->get_id().'">'.$product->get_name().'</label>';
						echo "<br/>";
					}
				?>
					<br/>
					<input type="submit" name="tickets-filter-action" class="button" value="Filter" />
				</p>
				<hr />
			<?php
				$FilterStatus = 1;
				if(isset($_POST["events-filter-action"])
					&& $_POST["events-filter-action"] == 'Filter'
					&& isset($_POST["events-filter"])) {
					$FilterStatus = $_POST["events-filter"];
				}
				
				
				
				$events_list = new BFT_Tickets_List($FilterStatus);
				$events_list->prepare_items();
				$events_list->display();
			?>
			</form>
		</div>
		<?php
	}
	
	public static function _tickets_page()
	{
		// Check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		// Process POST/GET actions
		self::_process_tickets_actions();
		
		self::print_tickets_page();
	}
	
	public static function _tickets_add_options()
	{
		$per_page_args = array (
			'label'		=> __('Tickets per page', 'bft-event'),
			'default'	=> 20,
			'option'	=> 'tickets_per_page'
		);
		add_screen_option( 'per_page', $per_page_args );
	}
	
	public static function _tickets_save_options($status, $option, $value)
	{
		if( $option == 'tickets_per_page' )
		{
			return $value;
		}
		
		return $status;
	}
 }
 
 // Instantiate Balfolk tickets events tab class
$BFT_EventTab = BFT_EventTab::getInstance();