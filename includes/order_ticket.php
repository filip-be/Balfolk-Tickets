<?php

/* Order ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_OrderTicket extends BFT_Table {
	
	public $ID;
	public $EventID;
	public $TicketID;
	public $OrderID;
	public $Timestamp;
	public $Status;
	
	protected static $tab_name = "bft_order_ticket";
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = self::$tab_name;
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "FK_EventID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_TicketID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_OrderID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
			,"KEY FK_EventID (FK_EventID)"
			,"KEY FK_TicketID (FK_TicketID)"
			,"KEY FK_OrderID (FK_OrderID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
	
	// Get order ticket by FK's
	public static function GetOrderTicket($order_id, $event_id, $ticket_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE FK_EventID = %d AND FK_TicketID = %d AND FK_OrderID = %d", 
								$event_id,
								$ticket_id,
								$order_id);
		$row = $wpdb->get_row($query);
		$orderTicket = self::FromDBRow($row);
		if(is_null($orderTicket))
		{
			BFT_Log::Warn(__CLASS__, "Could not find order ticket with OrderID: {$order_id} EventID: {$event_id} TicketID: {$ticket_id}");
			$orderTicket = self::SCreate($order_id, $event_id, $ticket_id);
		}
		return $orderTicket;
	}
	
	// Get order ticket by ID
	protected static function GetByID($order_ticket_id)
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
	
	// Initialize from DB row
	protected static function FromDBRow($row)
	{
		if(is_null($row))
		{
			return null;
		}
		$orderTicket = new self();
		$orderTicket->ID = $row->PK_ID;
		$orderTicket->EventID = $row->FK_EventID;
		$orderTicket->TicketID = $row->FK_TicketID;
		$orderTicket->OrderID = $row->FK_OrderID;
		$orderTicket->Timestamp = $row->Timestamp;
		$orderTicket->Status = $row->Status;
		return $orderTicket;
	}
	
	// Create order ticket
	protected static function SCreate($order_id, $event_id, $ticket_id)
	{
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::$tab_name
			,array(
				'FK_EventID' => $event_id, 
				'FK_TicketID' => $ticket_id,
				'FK_OrderID' => $order_id,
				'Status' => 1
			)
			,array(
				'%d',
				'%d',
				'%d',
				'%d'
			)
		);
		$orderTicket = self::GetByID($wpdb->insert_id);
		BFT_Log::Info(__CLASS__, sprintf('New order ticket created. ID: %d, OrderID: %d, EventID: %d, TicketID: %d, User: %s', 
				$orderTicket->ID, 
				$orderTicket->OrderID, 
				$orderTicket->EventID, 
				$orderTicket->TicketID, 
				wp_get_current_user()->user_login));
		return $orderTicket;
	}
}