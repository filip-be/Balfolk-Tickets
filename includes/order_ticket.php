<?php

/* Order ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_OrderTicket extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bft_order_ticket";
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
}