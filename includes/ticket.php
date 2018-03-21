<?php

/* Ticket class
 */
require_once 'table.php';
require_once 'log.php';

class BFT_Ticket extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bft_ticket";
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "FK_EventID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "FK_ProductID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL"]
			,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
			,"KEY FK_EventID (FK_EventID)"
			,"KEY FK_ProductID (FK_ProductID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
}