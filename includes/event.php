<?php

/* Event class
 */
require_once 'table.php';
require_once 'ticket.php';
require_once 'status.php';

class BFT_Event extends BFT_Table {
	public $ID;
	public $Name;
	public $Timestamp;
	public $Status;
	public $SaleStartDate;
	
	protected static $tab_name = "bft_event";
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = self::$tab_name;
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "SaleStartDate", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
		);
	}
	
	public static function SCreate($_Name)
	{
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::$tab_name
			,array('Name' => $_Name)
			,array('%s')
		);
		$event = self::GetByID($wpdb->insert_id);
		BFT_Log::Info(__CLASS__, sprintf('New event created. ID: %d, Name: %s, User: %s', $event->ID, $event->Name, wp_get_current_user()->user_login));
		return $event;
	}
	
	public static function SArchive($_ID)
	{
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . self::$tab_name
			,array('Status' => BFT_Status::$Disabled)
			,array('PK_ID' => $_ID)
			,array('%d')
			,array('%d')
		);
		$event = self::GetByID($_ID);
		BFT_Log::Info(__CLASS__, sprintf('Event archived. ID: %d, Name: %s, User: %s', $event->ID, $event->Name, wp_get_current_user()->user_login));
	}
	
	public static function SRestore($_ID)
	{
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . self::$tab_name
			,array('Status' => BFT_Status::$Enabled)
			,array('PK_ID' => $_ID)
			,array('%d')
			,array('%d')
		);
		$event = self::GetByID($_ID);
		BFT_Log::Info(__CLASS__, sprintf('Event restored. ID: %d, Name: %s, User: %s', $event->ID, $event->Name, wp_get_current_user()->user_login));
	}
	
	public static function SEditNameDate($_ID, $_Name, $_SaleDate)
	{
		global $wpdb;
		$oldEvent = self::GetByID($_ID);
		
		$wpdb->update(
			$wpdb->prefix . self::$tab_name
			,array
			(
				'Name' => $_Name,
				'SaleStartDate' => date("Y-m-d H:i:s", strtotime($_SaleDate))
			)
			,array('PK_ID' => $_ID)
			,array
			(
				'%s',
				'%s'
			)
			,array('%d')
		);
		BFT_Log::Info(__CLASS__, sprintf('Event name & date changed. ID: %d, Name-Old: %s, Name-New: %s, SaleStartDate-Old: %s, SaleStartDate-New: %s, User: %s', 
			$_ID, $oldEvent->Name, $_Name, $oldEvent->SaleStartDate, $_SaleDate, wp_get_current_user()->user_login));
	}
	
	public static function GetByID($event_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE PK_ID = %d", $event_id);
		$row = $wpdb->get_row($query);
		return self::FromDBRow($row);
	}
	
	protected static function FromDBRow($row)
	{
		$event = new self();
		$event->ID = $row->PK_ID;
		$event->Name = $row->Name;
		$event->Timestamp = $row->Timestamp;
		$event->Status = $row->Status;
		$event->SaleStartDate = $row->SaleStartDate;
		return $event;
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
	
	public static function GetEvents($GetDisabled = false)
	{
		$events = array();
		
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tab_name;
		if(!$GetDisabled)
		{
			$query = $wpdb->prepare("SELECT * FROM $table_name WHERE Status = %d", BFT_Status::$Enabled);
		}
		else
		{
			$query = "SELECT * FROM $table_name";
		}
		foreach($wpdb->get_results($query) as $event)
		{
			array_push($events, self::FromDBRow($event));
		}
		
		return $events;
	}

	public function Archive() {
		self::Archive($this->ID);
	}
	
	public function Restore() {
		self::Restore($this->ID);
	}
	
	public function EditName($_Name) {
		self::EditName($this->ID, $_Name);
	}
	
	public function AddProduct($ProductID) {
		BFT_Ticket::Replace($this->ID, $ProductID, BFT_Status::$Enabled);
	}
	
	public function RemoveProduct($ProductID) {
		BFT_Ticket::Replace($this->ID, $ProductID, BFT_Status::$Disabled);
	}
	
	public function GetProducts() {
		global $wpdb;
		$sql = "SELECT p.* FROM {$wpdb->prefix}posts p ";
		$sql .= "WHERE p.post_type = 'product' ";
		$sql .= "AND  EXISTS(SELECT 1 FROM {$wpdb->prefix}bft_ticket t WHERE t.FK_ProductID = p.ID AND t.FK_EventID = {$this->ID} AND t.Status = 1) ";
		$sql .= "ORDER BY p.post_date ASC";
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		$products = array();
		foreach($result as $prod) {
			array_push($products, wc_get_product(pll_get_post($prod['ID'])));
		}
		
		return $products;
	}
}