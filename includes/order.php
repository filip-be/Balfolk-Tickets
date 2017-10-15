<?php

/* Order class
 */
require_once 'event.php';
require_once 'ticket.php';
require_once 'order_ticket.php';
require_once 'log.php';

class BFT_Order {
	
	protected static $defaultLanguageSlug = 'pl';
	
	// WooCommerce order object
	public $Worder;
	
	public function __construct( )
	{
	}
	
	// Get order by ID
	public static function GetByID($order_id)
	{
		$Order = new self();
		$Order->Worder = wc_get_order($order_id);
		if(is_null($Order->Worder))
		{
			BFT_Log::Warn(__CLASS__, 'Could not find order with id: ' . $order_id);
			return null;
		}
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
		$Order = BFT_Order::GetByID($order_id);
		return $Order;
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
	
	// Check if order is completed
	public function is_completed()
	{
		return $this->get_status() == 'completed';
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
			array_push($tickets, BFT_OrderTicket::GetOrderTickets($order_id, $event_id, $ticket_id, $order_item_id, $quantity));
		}
		
		return $tickets;
	}
}