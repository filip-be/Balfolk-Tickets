<?php

/* Order class
 */
require_once 'event.php';
require_once 'ticket.php';
require_once 'order_ticket.php';
require_once 'log.php';

class BFT_Order {
	
	protected static $defaultLanguageSlug = pll_default_language('slug');
	
	// WooCommerce order object
	private $Worder;
	public $Tickets;
	public $Status;
	public $OrderId;
	public $Type;
	
	public $OrderBillingName;
	public $OrderBillingEmail;
	public $OrderBillingPhone;
	public $OrderCustomerNote;
	public $OrderKey;
	public $OrderNotes;
	
	public function __construct( )
	{
	}
	
	// Get order data
	public function LoadOrderData()
	{
		$this->OrderBillingName = $this->Worder->get_formatted_billing_full_name();
		$this->OrderBillingEmail = $this->Worder->get_billing_email();
		$this->OrderBillingPhone = $this->Worder->get_billing_phone();
		$this->OrderCustomerNote = $this->Worder->get_customer_note();
		$this->OrderKey = $this->Worder->get_order_key();
	}
	
	// Get order by ID
	public static function GetByID($order_id)
	{
		$Order = new self();
		$Order->Worder = wc_get_order($order_id);
		if(is_null($Order->Worder) || $Order->Worder === false)
		{
			BFT_Log::Warn(__CLASS__, 'Could not find order with id: ' . $order_id);
			return null;
		}
		$Order->Tickets = $Order->get_tickets();
		$Order->Status = $Order->get_status();
		$Order->OrderId = $Order->Worder->get_id();
		$Order->LoadOrderData();
		$Order->Type = 'Full';
		return $Order;
	}
	
	// Get order by order_key
	public static function GetByKey($order_key)
	{
		$order_id = wc_get_order_id_by_order_key($order_key);
		if($order_id == 0)
		{
			BFT_Log::Warn(__CLASS__, 'Could not find order with key: ' . $order_key);
			return null;
		}
		$Order = self::GetByID($order_id);
		
		return $Order;
	}
	
	// Get partial order by order_ticket Hash value
	public static function GetByTicketHash($order_ticket_hash)
	{
		$OrderTicket = BFT_OrderTicket::GetByHash($order_ticket_hash);
		if(is_null($OrderTicket))
		{
			return null;
		}
		$Order = self::GetById($OrderTicket->OrderID);
		if(is_null($Order))
		{
			BFT_Log::Warn(__CLASS__, 'Could not find parent order for order ticket ' . $OrderTicket->ID);
			return null;
		}
		$Order->LoadOrderData();
		$Order->Tickets = array($OrderTicket);
		$Order->Type = 'Partial';
		
		return $Order;
	}
	
	// Load order notes
	public function load_notes()
	{
		$this->OrderNotes = wc_get_order_notes(array( 'order_id' => $this->OrderId ));
	}
	
	// Get order status
	public function get_status()
	{
		if(is_null($this->Worder))
		{
			BFT_Log::Warn(__CLASS__, 'Order is not initialized');
			return null;
		}
		return $this->Worder->get_status();
	}
	
	// Get tickets
	public function get_tickets() {
		if(is_null($this->Worder))
		{
			BFT_Log::Warn(__CLASS__, 'Order is not initialized');
			return null;
		}
		
		// Order ID
		$order_id = $this->Worder->get_id();
		
		// Initialize tickets array
		$tickets = array();
		
		// Loop through order items
		foreach($this->Worder->get_items() as $item) {
			$order_item_id = $item->get_id();
			$ticket_id = pll_get_post($item->get_product_id(), self::$defaultLanguageSlug);
			$event_id = $item->get_meta('_bft-event-id');
			$quantity = $item->get_quantity();
			if(is_null($event_id))
			{
				BFT_Log::Warn(__CLASS__, "Event ID not saved for the product item! OrderID: {$order_id} ItemID: {$order_item_id}");
			}
			$orderTickets = BFT_OrderTicket::GetOrderTickets($order_id, $event_id, $ticket_id, $order_item_id, $quantity);
			if($orderTickets != null)
			{
				$tickets = array_merge($tickets, $orderTickets);
			}
		}
		
		return $tickets;
	}
}