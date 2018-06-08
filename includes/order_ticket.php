<?php

/* Order ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_OrderTicket extends BFT_Table {
	
	public $ID;
	public $TicketID;
	public $OrderID;
	public $OrderItemID;
	public $Hash;
	public $Timestamp;
	public $Status;
		
	protected static $tab_name = "bft_order_ticket";
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = self::$tab_name;
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "FK_TicketID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_OrderID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_OrderItemID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "Hash", "Type" => "varchar(45)", "Options" => "NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
			,"KEY FK_TicketID (FK_TicketID)"
			,"KEY FK_OrderID (FK_OrderID)"
			,"KEY FK_OrderItemID (FK_OrderItemID)"
			,"KEY FK_Hash (Hash)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
	
	// Get order tickets by FK's
	public static function GetOrderTickets($order_id, $event_id, $product_id, $order_item_id, $quantity)
	{
		global $wpdb;
		
		$ticket = BFT_Ticket::GetByEventIDProductID($event_id, $product_id);
		
		if(is_null($ticket))
		{
			BFT_Log::Warn(__CLASS__, "Could not find ticket with OrderID: {$order_id} EventID: {$event_id} OrderItemID: {$order_item_id} ProductID: {$product_id}");
			return null;
		}
		
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE FK_TicketID = %d AND FK_OrderID = %d AND FK_OrderItemID = %d", 
								$ticket->ID,
								$order_id,
								$order_item_id);
		$results = $wpdb->get_results($query);
		
		// Initialize array
		$orderTickets = array();
		
		// Numer of order tickets found
		$order_tickets_found = $wpdb->num_rows;
		
		// Iterate results
		foreach($results as $row)
		{
			$orderTicket = self::FromDBRow($row);
			if(is_null($orderTicket))
			{
				BFT_Log::Warn(__CLASS__, "Could not parse order ticket: " . print_r($row, true));
			}
			else
			{
				$orderTickets[] = $orderTicket;
			}
		}
		
		// Add missing order tickets
		while($quantity > 0
				&& $quantity > $order_tickets_found)
		{
			$orderTickets[] = self::SCreate($order_id, $ticket->ID, $order_item_id);
			$quantity--;
		}
		
		return $orderTickets;
	}
	
	// Get order ticket by ID
	public static function GetByID($order_ticket_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE PK_ID = %d", $order_ticket_id);
		$row = $wpdb->get_row($query);
		$orderTicket = self::FromDBRow($row);
		if(is_null($orderTicket))
		{
			BFT_Log::Warn(__CLASS__, 'Could not find order ticket with id: ' . $order_ticket_id);
		}
		return $orderTicket;
	}
	
	// Get order ticket by ID
	public static function GetByHash($order_ticket_hash)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE Hash = %s", $order_ticket_hash);
		$row = $wpdb->get_row($query);
		$orderTicket = self::FromDBRow($row);
		if(is_null($orderTicket))
		{
			BFT_Log::Warn(__CLASS__, 'Could not find order ticket with hash: ' . $order_ticket_hash);
		}
		return $orderTicket;
	}
	
	// Initialize from DB row
	protected static function FromDBRow($row)
	{
		if(is_null($row))
		{
			return null;
		}
		$orderTicket = new self();
		$orderTicket->ID = $row->PK_ID;
		$orderTicket->TicketID = $row->FK_TicketID;
		$orderTicket->OrderID = $row->FK_OrderID;
		$orderTicket->OrderItemID = $row->FK_OrderItemID;
		$orderTicket->Hash = $row->Hash;
		$orderTicket->Timestamp = $row->Timestamp;
		$orderTicket->Status = $row->Status;
		return $orderTicket;
	}
	
	// Create order ticket
	protected static function SCreate($order_id, $ticket_id, $order_item_id)
	{
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::$tab_name
			,array(
				'FK_TicketID' => $ticket_id,
				'FK_OrderID' => $order_id,
				'FK_OrderItemID' => $order_item_id,
				'Hash' => uniqid(),
				'Status' => 1
			)
			,array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%d'
			)
		);
		$orderTicket = self::GetByID($wpdb->insert_id);
		BFT_Log::Info(__CLASS__, sprintf('New order ticket created. ID: %d, OrderID: %d, OrderItemID: %d, TicketID: %d, Hash: %s, User: %s', 
				$orderTicket->ID, 
				$orderTicket->OrderID, 
				$orderTicket->OrderItemID, 
				$orderTicket->TicketID, 
				$orderTicket->Hash, 
				wp_get_current_user()->user_login));
		return $orderTicket;
	}
	
	
	
	public function UpdateStatus($status)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		
		if(!$wpdb->update(
			$table_name,
			array('Status' => $status),
			array('PK_ID' => $this->ID),
			array('%d'),
			array('%d'))) {
			BFT_Log::Warn(__CLASS__, "Could not update order ticket status: {$status}, TicketID: {$this->ID}");
			return false;
		}
		BFT_Log::Info(__CLASS__, "Order ticket status updated: {$status}, TicketID: {$this->ID}, User: ".wp_get_current_user()->user_login);
		return true;
	}
}