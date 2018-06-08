<?php

/* Ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_Ticket extends BFT_Table {
	protected static $tab_name = "bft_ticket";
	
	public $ID;
	public $EventID;
	public $ProductID;
	public $Timestamp;
	public $Status;
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = self::$tab_name;
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "FK_EventID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_ProductID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (FK_EventID, FK_ProductID)"
			,"KEY FK_EventID (PK_ID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
	
	public static function Replace($EventID, $ProductID, $Status)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		
		$query = $wpdb->prepare(
					"INSERT INTO $table_name (FK_EventID, FK_ProductID, Status) VALUES (%d, %d, %d) ON DUPLICATE KEY UPDATE Status = %d",
					$EventID,
					$ProductID,
					$Status,
					$Status);
		$res = $wpdb->query($query);
		
		$operation = "UNKNOWN OPERATION FOR";
		if($Status == 1) {
			$operation = "added to";
		}
		else if($Status == 2){
			$operation = "removed from";
		}
		BFT_Log::Info(__CLASS__, sprintf("Product {$operation} the event. EventID: %d, ProductID: %d, User: %s", $EventID, $ProductID, wp_get_current_user()->user_login));
		return $res;
	}
	
	// Get ticket by ID
	public static function GetByID($ticket_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE PK_ID = %d", $ticket_id);
		$row = $wpdb->get_row($query);
		$ticket = self::FromDBRow($row);
		if(is_null($ticket))
		{
			BFT_Log::Warn(__CLASS__, 'Could not find ticket with id: ' . $ticket_id);
		}
		return $ticket;
	}
	
	public static function GetAvailableProducts()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = "SELECT DISTINCT FK_ProductID FROM $table_name WHERE Status = 1";
		$rows = $wpdb->get_results($query);
		
		$products = array();
		
		foreach($rows as $product)
		{
			$wcProduct = wc_get_product($product->FK_ProductID);
			if($wcProduct != null && $wcProduct != false)
			{
				array_push($products, $wcProduct);
			}
		}
		
		return $products;
	}
	
	// Get ticket by ID
	public static function GetByEventIDProductID($event_id, $product_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE FK_EventID = %d AND FK_ProductID = %d", 
							$event_id,
							$product_id);
		$row = $wpdb->get_row($query);
		$ticket = self::FromDBRow($row);
		if(is_null($ticket))
		{
			BFT_Log::Warn(__CLASS__, "Could not find ticket with EventID: {$event_id}, ProductID: {$product_id}");
		}
		return $ticket;
	}
	
	// Initialize from DB row
	protected static function FromDBRow($row)
	{
		if(is_null($row))
		{
			return null;
		}
		$ticket = new self();
		$ticket->ID = $row->PK_ID;
		$ticket->EventID = $row->FK_EventID;
		$ticket->ProductID = $row->FK_ProductID;
		$ticket->Timestamp = $row->Timestamp;
		$ticket->Status = $row->Status;
		return $ticket;
	}
}