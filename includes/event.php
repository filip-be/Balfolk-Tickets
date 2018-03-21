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
	
	protected static $tab_name = "bft_event";
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = self::$tab_name;
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
		);
	}
	
	public static function Create($_Name)
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
	
	public static function Archive($_ID)
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
}