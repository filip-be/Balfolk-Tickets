<?php

/* Log class
 */
require_once 'table.php';

class BFT_Log extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bfp_log";
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "FK_EventID", "Type" => "bigint(20)", "Options" => "UNSIGNED NULL"]
			,["Name" => "FK_TicketID", "Type" => "bigint(20)", "Options" => "UNSIGNED NULL"]
			,["Name" => "FK_OrderID", "Type" => "bigint(20)", "Options" => "UNSIGNED NULL"]
			,["Name" => "FK_OrderTicketID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Level", "Type" => "smallint(4)", "Options" => "NULL"]
			,["Name" => "Source", "Type" => "varchar(200)", "Options" => "NULL"]
			,["Name" => "Message", "Type" => "longtext", "Options" => "NULL"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
			,"KEY FK_EventID (FK_EventID)"
			,"KEY FK_TicketID (FK_TicketID)"
			,"KEY FK_OrderID (FK_OrderID)"
			,"KEY FK_OrderTicketID (FK_OrderTicketID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
}