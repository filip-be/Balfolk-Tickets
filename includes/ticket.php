<?php

/* Ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_Ticket extends BFT_Table {
	protected static $tab_name = "bft_ticket";
	
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
		$res = $wpdb->replace(
			$wpdb->prefix . self::$tab_name
			,array(
				'FK_EventID' => $EventID, 
				'FK_ProductID' => $ProductID,
				'Status' => $Status
			)
			,array(
				'%d',
				'%d',
				'%d'
			)
		);
		$operation = "UNKNOWN OPERATION FOR";
		if($Status == 1) {
			$operation = "added to";
		}
		else if($Status == 2){
			$operation = "removed from";
		}
		BFT_Log::Info(__CLASS__, sprintf("Product {$operation} the event. EventID: %d, ProductID: %d, User: %s", $EventID, $ProductID, wp_get_current_user()->user_login));
		return res;
	}
}